<?php

namespace Drupal\custom_flexpage_content\Plugin\Block;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides an image scrollpoints storytelling block.
 *
 * @Block(
 *   id = "custom_flexpage_scrollpoints_block",
 *   admin_label = @Translation("Story Scrollpoints"),
 *   category = @Translation("Visual Storytelling")
 * )
 */
class ScrollpointsBlock extends VisualStoryBlockBase {

  /**
   * Maximum number of points.
   */
  private const MAX_POINTS = 8;

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'headline' => '',
      'intro' => '',
      'image_media_id' => 0,
      'image_url' => '',
      'image_alt' => '',
      'points' => [
        [
          'title' => '',
          'body' => '',
          'x' => 50,
          'y' => 50,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state): array {
    $form = parent::blockForm($form, $form_state);

    $form['headline'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Headline'),
      '#default_value' => $this->configuration['headline'] ?? '',
      '#required' => TRUE,
      '#maxlength' => 255,
    ];

    $form['intro'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Intro text'),
      '#default_value' => $this->configuration['intro'] ?? '',
      '#rows' => 4,
    ];

    $media_id = (int) ($this->configuration['image_media_id'] ?? 0);
    $form['image_media_id'] = [
      '#type' => 'media_library',
      '#title' => $this->t('Annotated image (preferred)'),
      '#allowed_bundles' => $this->getImageMediaTypeIds(),
      '#default_value' => $media_id > 0 ? $media_id : NULL,
      '#cardinality' => 1,
    ];

    $form['image_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Annotated image URL (fallback)'),
      '#default_value' => $this->configuration['image_url'] ?? '',
      '#maxlength' => 2048,
    ];

    $form['image_alt'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Annotated image alt text'),
      '#default_value' => $this->configuration['image_alt'] ?? '',
      '#maxlength' => 255,
    ];

    $configured_points = $this->configuration['points'] ?? [];
    for ($index = 0; $index < self::MAX_POINTS; $index++) {
      $point = $configured_points[$index] ?? [];
      $point_number = $index + 1;

      $form['points'][$index] = [
        '#type' => 'details',
        '#title' => $this->t('Point @number', ['@number' => $point_number]),
        '#tree' => TRUE,
        '#open' => $index < 3,
      ];

      $form['points'][$index]['title'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Point title'),
        '#default_value' => $point['title'] ?? '',
        '#maxlength' => 255,
      ];

      $form['points'][$index]['body'] = [
        '#type' => 'textarea',
        '#title' => $this->t('Point copy'),
        '#default_value' => $point['body'] ?? '',
        '#rows' => 3,
      ];

      $form['points'][$index]['x'] = [
        '#type' => 'number',
        '#title' => $this->t('X position (%)'),
        '#default_value' => isset($point['x']) ? (float) $point['x'] : 50,
        '#min' => 0,
        '#max' => 100,
        '#step' => 0.1,
      ];

      $form['points'][$index]['y'] = [
        '#type' => 'number',
        '#title' => $this->t('Y position (%)'),
        '#default_value' => isset($point['y']) ? (float) $point['y'] : 50,
        '#min' => 0,
        '#max' => 100,
        '#step' => 0.1,
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state): void {
    parent::blockSubmit($form, $form_state);

    $this->configuration['headline'] = trim((string) $form_state->getValue('headline', ''));
    $this->configuration['intro'] = trim((string) $form_state->getValue('intro', ''));
    $this->configuration['image_media_id'] = $this->extractMediaTargetId($form_state->getValue('image_media_id'));
    $this->configuration['image_url'] = trim((string) $form_state->getValue('image_url', ''));
    $this->configuration['image_alt'] = trim((string) $form_state->getValue('image_alt', ''));

    $submitted_points = $form_state->getValue('points', []);
    $clean_points = [];

    foreach ($submitted_points as $point) {
      if (!is_array($point)) {
        continue;
      }

      $title = trim((string) ($point['title'] ?? ''));
      $body = trim((string) ($point['body'] ?? ''));
      if ($title === '' && $body === '') {
        continue;
      }

      $x = max(0, min(100, (float) ($point['x'] ?? 50)));
      $y = max(0, min(100, (float) ($point['y'] ?? 50)));

      $clean_points[] = [
        'title' => $title,
        'body' => $body,
        'x' => $x,
        'y' => $y,
      ];
    }

    $this->configuration['points'] = $clean_points;
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $cache_tags = [];
    $image = $this->resolveImageSource(
      (int) ($this->configuration['image_media_id'] ?? 0),
      (string) ($this->configuration['image_url'] ?? ''),
      (string) ($this->configuration['image_alt'] ?? ''),
      $cache_tags
    );

    $points = $this->configuration['points'] ?? [];
    if (!$image || empty($points)) {
      return [];
    }

    return [
      '#theme' => 'custom_flexpage_scrollpoints',
      '#headline' => $this->configuration['headline'] ?? '',
      '#intro' => $this->configuration['intro'] ?? '',
      '#image_url' => $image['url'],
      '#image_alt' => $image['alt'],
      '#points' => $points,
      '#attached' => [
        'library' => ['custom_flexpage_content/story_blocks'],
      ],
      '#cache' => [
        'tags' => $cache_tags,
      ],
    ];
  }

}

