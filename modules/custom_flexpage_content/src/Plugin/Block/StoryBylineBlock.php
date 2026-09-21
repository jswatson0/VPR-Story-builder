<?php

namespace Drupal\custom_flexpage_content\Plugin\Block;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\FormStateInterface;
use Drupal\media\MediaInterface;
use Drupal\taxonomy\TermInterface;

/**
 * Provides a story byline block with author(s) and publication date.
 *
 * @Block(
 *   id = "custom_flexpage_story_byline_block",
 *   admin_label = @Translation("Byline"),
 *   category = @Translation("Visual Storytelling")
 * )
 */
class StoryBylineBlock extends VisualStoryBlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'author_term_id' => 0,
      'second_author_term_id' => 0,
      'publication_date' => '',
      'show_image' => TRUE,
      'alignment' => 'left',
      'color_theme' => 'light',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state): array {
    $form = parent::blockForm($form, $form_state);

    $author_term_id = (int) ($this->configuration['author_term_id'] ?? 0);
    $author_term = $author_term_id > 0 ? $this->loadAuthorTerm($author_term_id) : NULL;

    $form['author_term_id'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Author'),
      '#description' => $this->t('Select an author from the News authors taxonomy.'),
      '#target_type' => 'taxonomy_term',
      '#selection_settings' => ['target_bundles' => ['utnews_authors' => 'utnews_authors']],
      '#default_value' => $author_term,
      '#tags' => FALSE,
      '#required' => TRUE,
    ];

    $second_author_term_id = (int) ($this->configuration['second_author_term_id'] ?? 0);
    $second_author_term = $second_author_term_id > 0 ? $this->loadAuthorTerm($second_author_term_id) : NULL;

    $form['second_author_term_id'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Second author'),
      '#description' => $this->t('Optional. Select a co-author from the News authors taxonomy.'),
      '#target_type' => 'taxonomy_term',
      '#selection_settings' => ['target_bundles' => ['utnews_authors' => 'utnews_authors']],
      '#default_value' => $second_author_term,
      '#tags' => FALSE,
    ];

    $form['show_image'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show author photo'),
      '#description' => $this->t('Displays each author’s image if one is set on their News authors term. Has no effect if a selected author has no image.'),
      '#default_value' => (bool) ($this->configuration['show_image'] ?? TRUE),
    ];

    $form['publication_date'] = [
      '#type' => 'date',
      '#title' => $this->t('Publication date'),
      '#default_value' => $this->configuration['publication_date'] ?? '',
    ];

    $form['alignment'] = [
      '#type' => 'select',
      '#title' => $this->t('Alignment'),
      '#default_value' => $this->configuration['alignment'] ?? 'left',
      '#options' => [
        'left' => $this->t('Left'),
        'center' => $this->t('Center'),
        'right' => $this->t('Right'),
      ],
    ];

    $form['color_theme'] = [
      '#type' => 'select',
      '#title' => $this->t('Color theme'),
      '#description' => $this->t('Light for dark backgrounds, dark for light backgrounds.'),
      '#default_value' => $this->configuration['color_theme'] ?? 'light',
      '#options' => [
        'light' => $this->t('Light copy (white text)'),
        'dark' => $this->t('Dark copy (dark text)'),
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state): void {
    parent::blockSubmit($form, $form_state);

    $this->configuration['author_term_id'] = $this->extractNodeTargetId($form_state->getValue('author_term_id'));
    $this->configuration['second_author_term_id'] = $this->extractNodeTargetId($form_state->getValue('second_author_term_id'));
    $this->configuration['show_image'] = (bool) $form_state->getValue('show_image', TRUE);
    $this->configuration['publication_date'] = (string) $form_state->getValue('publication_date', '');
    $this->configuration['alignment'] = (string) $form_state->getValue('alignment', 'left');
    $this->configuration['color_theme'] = (string) $form_state->getValue('color_theme', 'light');
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $cache_tags = [];
    $show_image = (bool) ($this->configuration['show_image'] ?? TRUE);

    $authors = [];
    foreach (['author_term_id', 'second_author_term_id'] as $config_key) {
      $term_id = (int) ($this->configuration[$config_key] ?? 0);
      if ($term_id <= 0) {
        continue;
      }

      $term = $this->loadAuthorTerm($term_id);
      if (!$term) {
        continue;
      }

      $cache_tags = Cache::mergeTags($cache_tags, $term->getCacheTags());
      $authors[] = [
        'name' => $term->label(),
        'url' => $term->toUrl()->toString(),
        'image_url' => $show_image ? $this->getAuthorImageUrl($term, $cache_tags) : '',
      ];
    }

    if (empty($authors)) {
      return [];
    }

    $raw_date = (string) ($this->configuration['publication_date'] ?? '');
    $date_object = $raw_date !== '' ? \DateTime::createFromFormat('Y-m-d', $raw_date) : FALSE;

    return [
      '#theme' => 'custom_flexpage_story_byline',
      '#authors' => $authors,
      '#publication_date' => $date_object ? $date_object->format('F j, Y') : '',
      '#publication_date_iso' => $date_object ? $date_object->format('Y-m-d') : '',
      '#alignment' => $this->configuration['alignment'] ?? 'left',
      '#color_theme' => $this->configuration['color_theme'] ?? 'light',
      '#attached' => [
        'library' => ['custom_flexpage_content/story_byline_block'],
      ],
      '#cache' => [
        'tags' => $cache_tags,
      ],
    ];
  }

  /**
   * Loads a News authors taxonomy term by ID.
   */
  protected function loadAuthorTerm(int $term_id): ?TermInterface {
    $term = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($term_id);
    if ($term instanceof TermInterface && $term->bundle() === 'utnews_authors') {
      return $term;
    }
    return NULL;
  }

  /**
   * Gets the author photo URL from a News authors term, if set.
   */
  protected function getAuthorImageUrl(TermInterface $term, array &$cache_tags): string {
    if (!$term->hasField('field_utnews_author_image') || $term->get('field_utnews_author_image')->isEmpty()) {
      return '';
    }

    $media_id = (int) $term->get('field_utnews_author_image')->target_id;
    if ($media_id <= 0) {
      return '';
    }

    $media = \Drupal::entityTypeManager()->getStorage('media')->load($media_id);
    if (!$media instanceof MediaInterface) {
      return '';
    }

    $cache_tags = Cache::mergeTags($cache_tags, $media->getCacheTags());
    $image = $this->extractImageFromMedia($media, $cache_tags);

    return $image['url'] ?? '';
  }

}
