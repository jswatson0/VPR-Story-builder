<?php

namespace Drupal\custom_flexpage_content\Plugin\Block;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a storytelling timeline block with milestone items.
 *
 * @Block(
 *   id = "custom_flexpage_story_timeline_milestones_block",
 *   admin_label = @Translation("Timeline"),
 *   category = @Translation("Visual Storytelling")
 * )
 */
class StoryTimelineMilestonesBlock extends VisualStoryBlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'eyebrow' => '',
      'headline' => '',
      'intro_value' => '',
      'intro_format' => 'basic_html',
      'media_id' => 0,
      'media_url' => '',
      'media_alt' => '',
      'media_focal_x' => '50',
      'media_focal_y' => '40',
      'theme' => 'light',
      'layout' => 'alternating',
      'milestones' => "2019|Program Launch|Initial community pilot launched with local partners.\n2022|Regional Scale|Program expanded to additional campuses and stakeholders.\n2026|National Spotlight|Work recognized in a national storytelling initiative.",
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state): array {
    $form = parent::blockForm($form, $form_state);

    $form['editor_help'] = [
      '#type' => 'details',
      '#title' => $this->t('How This Block Works'),
      '#open' => FALSE,
    ];
    $form['editor_help']['summary'] = [
      '#type' => 'item',
      '#markup' => $this->t('<p>This block renders a visual chronology with milestone cards connected by a central timeline rail. Use it to communicate progress, phases, or historical narrative.</p><p><strong>Milestone format:</strong> one line per row as <code>Year|Title|Description</code>.</p>'),
    ];

    $media_id = (int) ($this->configuration['media_id'] ?? 0);
    $form['media_id'] = [
      '#type' => 'media_library',
      '#title' => $this->t('Header media image (optional)'),
      '#description' => $this->t('Recommended first step. Adds a visual lead-in above the milestone rail.'),
      '#allowed_bundles' => $this->getImageMediaTypeIds(),
      '#default_value' => $media_id > 0 ? $media_id : NULL,
      '#cardinality' => 1,
    ];

    $form['media_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Header image URL (fallback)'),
      '#description' => $this->t('Use only if Media Library is not used.'),
      '#default_value' => $this->configuration['media_url'] ?? '',
      '#maxlength' => 2048,
    ];

    $form['media_alt'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Header image alt text'),
      '#default_value' => $this->configuration['media_alt'] ?? '',
      '#maxlength' => 255,
    ];

    $form['media_focal_x'] = [
      '#type' => 'number',
      '#title' => $this->t('Header image focal point X (%)'),
      '#default_value' => (float) ($this->configuration['media_focal_x'] ?? 50),
      '#min' => 0,
      '#max' => 100,
      '#step' => 1,
    ];

    $form['media_focal_y'] = [
      '#type' => 'number',
      '#title' => $this->t('Header image focal point Y (%)'),
      '#default_value' => (float) ($this->configuration['media_focal_y'] ?? 40),
      '#min' => 0,
      '#max' => 100,
      '#step' => 1,
    ];

    $form['eyebrow'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Eyebrow'),
      '#default_value' => $this->configuration['eyebrow'] ?? '',
      '#maxlength' => 255,
    ];

    $form['headline'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Headline'),
      '#default_value' => $this->configuration['headline'] ?? '',
      '#required' => TRUE,
      '#maxlength' => 255,
    ];

    $form['intro'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Intro copy'),
      '#description' => $this->t('Optional short lead paragraph above milestones.'),
      '#default_value' => $this->configuration['intro_value'] ?? '',
      '#format' => $this->configuration['intro_format'] ?? filter_default_format(),
      '#required' => FALSE,
      '#rows' => 5,
    ];

    $form['milestones'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Milestones'),
      '#description' => $this->t('Enter one milestone per line as Year|Title|Description.'),
      '#default_value' => $this->configuration['milestones'] ?? '',
      '#required' => TRUE,
      '#rows' => 8,
    ];

    $form['layout'] = [
      '#type' => 'select',
      '#title' => $this->t('Card layout'),
      '#default_value' => $this->configuration['layout'] ?? 'alternating',
      '#options' => [
        'alternating' => $this->t('Alternating (left / right)'),
        'single-side' => $this->t('Single side (all right)'),
      ],
    ];

    $form['theme'] = [
      '#type' => 'select',
      '#title' => $this->t('Color theme'),
      '#default_value' => $this->configuration['theme'] ?? 'light',
      '#options' => [
        'light' => $this->t('Light'),
        'dark' => $this->t('Dark'),
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state): void {
    parent::blockSubmit($form, $form_state);

    $intro = $form_state->getValue('intro', []);

    $this->configuration['media_id'] = $this->extractMediaTargetId($form_state->getValue('media_id'));
    $this->configuration['media_url'] = trim((string) $form_state->getValue('media_url', ''));
    $this->configuration['media_alt'] = trim((string) $form_state->getValue('media_alt', ''));
    $this->configuration['media_focal_x'] = (string) max(0, min(100, (float) $form_state->getValue('media_focal_x', '50')));
    $this->configuration['media_focal_y'] = (string) max(0, min(100, (float) $form_state->getValue('media_focal_y', '40')));
    $this->configuration['eyebrow'] = trim((string) $form_state->getValue('eyebrow', ''));
    $this->configuration['headline'] = trim((string) $form_state->getValue('headline', ''));
    $this->configuration['intro_value'] = trim((string) ($intro['value'] ?? ''));
    $this->configuration['intro_format'] = (string) ($intro['format'] ?? filter_default_format());
    $this->configuration['milestones'] = trim((string) $form_state->getValue('milestones', ''));
    $this->configuration['layout'] = (string) $form_state->getValue('layout', 'alternating');
    $this->configuration['theme'] = (string) $form_state->getValue('theme', 'light');
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $cache_tags = [];
    $image = $this->resolveImageSource(
      (int) ($this->configuration['media_id'] ?? 0),
      (string) ($this->configuration['media_url'] ?? ''),
      (string) ($this->configuration['media_alt'] ?? ''),
      $cache_tags
    );

    $items = $this->parseMilestones((string) ($this->configuration['milestones'] ?? ''));
    if (empty($items)) {
      return [];
    }

    return [
      '#theme' => 'custom_flexpage_story_timeline_milestones',
      '#eyebrow' => $this->configuration['eyebrow'] ?? '',
      '#headline' => $this->configuration['headline'] ?? '',
      '#intro' => [
        '#type' => 'processed_text',
        '#text' => $this->configuration['intro_value'] ?? '',
        '#format' => $this->configuration['intro_format'] ?? filter_default_format(),
      ],
      '#items' => $items,
      '#layout' => $this->configuration['layout'] ?? 'alternating',
      '#color_theme' => $this->configuration['theme'] ?? 'light',
      '#media_url' => $image['url'] ?? '',
      '#media_alt' => $image['alt'] ?? '',
      '#media_focal_x' => $this->configuration['media_focal_x'] ?? '50',
      '#media_focal_y' => $this->configuration['media_focal_y'] ?? '40',
      '#attached' => [
        'library' => ['custom_flexpage_content/story_timeline_milestones_block'],
      ],
      '#cache' => [
        'tags' => $cache_tags,
      ],
    ];
  }

  /**
   * Parses milestone editor input into structured items.
   */
  protected function parseMilestones(string $raw): array {
    $rows = preg_split('/\r\n|\r|\n/', $raw) ?: [];
    $items = [];

    foreach ($rows as $row) {
      $line = trim($row);
      if ($line === '') {
        continue;
      }

      $parts = array_map('trim', explode('|', $line, 3));
      $items[] = [
        'year' => (string) ($parts[0] ?? ''),
        'title' => (string) ($parts[1] ?? ''),
        'description' => (string) ($parts[2] ?? ''),
      ];
    }

    return array_values(array_filter($items, static function (array $item): bool {
      return $item['year'] !== '' || $item['title'] !== '' || $item['description'] !== '';
    }));
  }

}
