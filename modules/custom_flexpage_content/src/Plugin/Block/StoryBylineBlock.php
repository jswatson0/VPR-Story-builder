<?php

namespace Drupal\custom_flexpage_content\Plugin\Block;

use Drupal\Core\Form\FormStateInterface;

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
      'alignment' => 'right',
      'color_theme' => 'light',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state): array {
    $form = parent::blockForm($form, $form_state);
    $form += $this->bylineFormElements(TRUE);

    $form['alignment'] = [
      '#type' => 'select',
      '#title' => $this->t('Alignment'),
      '#default_value' => $this->configuration['alignment'] ?? 'right',
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

    $this->bylineFormSubmit($form_state);
    $this->configuration['alignment'] = (string) $form_state->getValue('alignment', 'right');
    $this->configuration['color_theme'] = (string) $form_state->getValue('color_theme', 'light');
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $cache_tags = [];
    $build = $this->buildByline(
      $this->configuration['alignment'] ?? 'right',
      $this->configuration['color_theme'] ?? 'light',
      FALSE,
      $cache_tags
    );

    if (!$build) {
      return [];
    }

    $build['#cache'] = ['tags' => $cache_tags];
    return $build;
  }

}
