<?php

namespace Drupal\custom_flexpage_content\Plugin\Block;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a classic depth parallax visual storytelling hero block.
 *
 * @Block(
 *   id = "custom_flexpage_story_hero_classic_depth_parallax_block",
 *   admin_label = @Translation("Story Hero: Classic Depth Parallax"),
 *   category = @Translation("Visual Storytelling")
 * )
 */
class StoryHeroClassicDepthParallaxBlock extends VisualStoryBlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'eyebrow' => '',
      'headline' => '',
      'dek' => '',
      'cta_label' => '',
      'cta_url' => '',
      'background_media_id' => 0,
      'background_image_url' => '',
      'background_alt' => '',
      'height' => 'tall',
      'text_alignment' => 'left',
      'content_vertical_position' => 'bottom',
      'copy_theme' => 'light',
      'overlay_strength' => 'full',
      'midground_strength' => 'medium',
      'foreground_behavior' => 'pinned_then_release',
      'foreground_pin_vh' => '60',
      'text_zoom_effect' => 'out',
      'focal_x' => '50',
      'focal_y' => '32',
      'background_zoom' => '1.00',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state): array {
    $form = parent::blockForm($form, $form_state);

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

    $form['dek'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Description'),
      '#default_value' => $this->configuration['dek'] ?? '',
      '#rows' => 4,
    ];

    $media_id = (int) ($this->configuration['background_media_id'] ?? 0);
    $form['background_media_id'] = [
      '#type' => 'media_library',
      '#title' => $this->t('Background media image (preferred)'),
      '#allowed_bundles' => $this->getImageMediaTypeIds(),
      '#default_value' => $media_id > 0 ? $media_id : NULL,
      '#cardinality' => 1,
    ];

    $form['background_image_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Background image URL (fallback)'),
      '#default_value' => $this->configuration['background_image_url'] ?? '',
      '#maxlength' => 2048,
    ];

    $form['background_alt'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Background image alt text'),
      '#default_value' => $this->configuration['background_alt'] ?? '',
      '#maxlength' => 255,
    ];

    $form['focal_x'] = [
      '#type' => 'number',
      '#title' => $this->t('Image focal point X (%)'),
      '#description' => $this->t('0 = left edge, 100 = right edge.'),
      '#default_value' => (float) ($this->configuration['focal_x'] ?? 50),
      '#min' => 0,
      '#max' => 100,
      '#step' => 1,
    ];

    $form['focal_y'] = [
      '#type' => 'number',
      '#title' => $this->t('Image focal point Y (%)'),
      '#description' => $this->t('0 = top edge, 100 = bottom edge.'),
      '#default_value' => (float) ($this->configuration['focal_y'] ?? 32),
      '#min' => 0,
      '#max' => 100,
      '#step' => 1,
    ];

    $form['background_zoom'] = [
      '#type' => 'select',
      '#title' => $this->t('Background zoom'),
      '#description' => $this->t('Lower values show more of the image.'),
      '#default_value' => (string) ($this->configuration['background_zoom'] ?? '1.06'),
      '#options' => [
        '1.00' => $this->t('100% (no zoom)'),
        '1.03' => $this->t('103% (subtle)'),
        '1.06' => $this->t('106% (default)'),
        '1.10' => $this->t('110% (strong)'),
      ],
    ];

    $form['height'] = [
      '#type' => 'select',
      '#title' => $this->t('Section height'),
      '#default_value' => $this->configuration['height'] ?? 'tall',
      '#options' => [
        'short' => $this->t('Short'),
        'tall' => $this->t('Tall'),
        'screen' => $this->t('Full screen'),
      ],
    ];

    $form['text_alignment'] = [
      '#type' => 'select',
      '#title' => $this->t('Text alignment'),
      '#default_value' => $this->configuration['text_alignment'] ?? 'left',
      '#options' => [
        'left' => $this->t('Left'),
        'center' => $this->t('Center'),
        'right' => $this->t('Right'),
      ],
    ];

    $form['content_vertical_position'] = [
      '#type' => 'select',
      '#title' => $this->t('Text vertical position'),
      '#default_value' => $this->configuration['content_vertical_position'] ?? 'middle',
      '#options' => [
        'top' => $this->t('Top'),
        'middle' => $this->t('Middle'),
        'bottom' => $this->t('Bottom'),
      ],
    ];

    $form['copy_theme'] = [
      '#type' => 'select',
      '#title' => $this->t('Copy color theme'),
      '#description' => $this->t('Use light copy on darker images, dark copy on lighter images.'),
      '#default_value' => $this->configuration['copy_theme'] ?? 'light',
      '#options' => [
        'light' => $this->t('Light copy'),
        'dark' => $this->t('Dark copy'),
      ],
    ];

    $form['overlay_strength'] = [
      '#type' => 'select',
      '#title' => $this->t('Overlay strength'),
      '#default_value' => $this->configuration['overlay_strength'] ?? 'full',
      '#options' => [
        'light' => $this->t('Light'),
        'medium' => $this->t('Medium'),
        'heavy' => $this->t('Heavy'),
        'full' => $this->t('Full (100%)'),
      ],
    ];

    $form['text_zoom_effect'] = [
      '#type' => 'select',
      '#title' => $this->t('Text zoom on scroll'),
      '#description' => $this->t('Subtle zoom applied to foreground copy while scrolling.'),
      '#default_value' => $this->configuration['text_zoom_effect'] ?? 'out',
      '#options' => [
        'none' => $this->t('None'),
        'in' => $this->t('Zoom in'),
        'out' => $this->t('Zoom out'),
      ],
    ];

    $form['midground_strength'] = [
      '#type' => 'select',
      '#title' => $this->t('Midground depth strength'),
      '#default_value' => $this->configuration['midground_strength'] ?? 'medium',
      '#options' => [
        'subtle' => $this->t('Subtle'),
        'medium' => $this->t('Medium'),
        'strong' => $this->t('Strong'),
      ],
    ];

    $form['foreground_behavior'] = [
      '#type' => 'select',
      '#title' => $this->t('Foreground scroll behavior'),
      '#default_value' => $this->configuration['foreground_behavior'] ?? 'pinned_then_release',
      '#options' => [
        'pinned_then_release' => $this->t('Stable (no vertical movement)'),
        'faster_than_scroll' => $this->t('Move faster than scroll (1.1x)'),
      ],
    ];

    $form['foreground_pin_vh'] = [
      '#type' => 'select',
      '#title' => $this->t('Foreground travel distance'),
      '#description' => $this->t('Controls how far the copy moves when "Move faster than scroll" is selected.'),
      '#default_value' => (string) ($this->configuration['foreground_pin_vh'] ?? '60'),
      '#states' => [
        'visible' => [
          ':input[name="settings[foreground_behavior]"]' => ['value' => 'faster_than_scroll'],
        ],
      ],
      '#options' => [
        '40' => $this->t('40vh (subtle)'),
        '60' => $this->t('60vh'),
        '80' => $this->t('80vh (strong)'),
      ],
    ];

    $form['cta_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('CTA label'),
      '#default_value' => $this->configuration['cta_label'] ?? '',
      '#maxlength' => 255,
    ];

    $form['cta_url'] = [
      '#type' => 'url',
      '#title' => $this->t('CTA URL'),
      '#default_value' => $this->configuration['cta_url'] ?? '',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state): void {
    parent::blockSubmit($form, $form_state);

    $this->configuration['eyebrow'] = trim((string) $form_state->getValue('eyebrow', ''));
    $this->configuration['headline'] = trim((string) $form_state->getValue('headline', ''));
    $this->configuration['dek'] = trim((string) $form_state->getValue('dek', ''));
    $this->configuration['cta_label'] = trim((string) $form_state->getValue('cta_label', ''));
    $this->configuration['cta_url'] = trim((string) $form_state->getValue('cta_url', ''));
    $this->configuration['background_media_id'] = $this->extractMediaTargetId($form_state->getValue('background_media_id'));
    $this->configuration['background_image_url'] = trim((string) $form_state->getValue('background_image_url', ''));
    $this->configuration['background_alt'] = trim((string) $form_state->getValue('background_alt', ''));
    $this->configuration['height'] = (string) $form_state->getValue('height', 'tall');
    $this->configuration['text_alignment'] = (string) $form_state->getValue('text_alignment', 'left');
    $this->configuration['content_vertical_position'] = (string) $form_state->getValue('content_vertical_position', 'bottom');
    $this->configuration['copy_theme'] = (string) $form_state->getValue('copy_theme', 'light');
    $this->configuration['overlay_strength'] = (string) $form_state->getValue('overlay_strength', 'full');
    $this->configuration['midground_strength'] = (string) $form_state->getValue('midground_strength', 'medium');
    $this->configuration['foreground_behavior'] = (string) $form_state->getValue('foreground_behavior', 'pinned_then_release');
    $this->configuration['foreground_pin_vh'] = (string) $form_state->getValue('foreground_pin_vh', '60');
    $this->configuration['text_zoom_effect'] = (string) $form_state->getValue('text_zoom_effect', 'out');
    $this->configuration['focal_x'] = (string) max(0, min(100, (float) $form_state->getValue('focal_x', '50')));
    $this->configuration['focal_y'] = (string) max(0, min(100, (float) $form_state->getValue('focal_y', '32')));
    $zoom = (float) $form_state->getValue('background_zoom', '1.00');
    $this->configuration['background_zoom'] = number_format(max(1.00, min(1.15, $zoom)), 2, '.', '');
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $cache_tags = [];
    $image = $this->resolveImageSource(
      (int) ($this->configuration['background_media_id'] ?? 0),
      (string) ($this->configuration['background_image_url'] ?? ''),
      (string) ($this->configuration['background_alt'] ?? ''),
      $cache_tags
    );

    if (!$image) {
      return [];
    }

    return [
      '#theme' => 'custom_flexpage_story_hero_classic_depth_parallax',
      '#eyebrow' => $this->configuration['eyebrow'] ?? '',
      '#headline' => $this->configuration['headline'] ?? '',
      '#dek' => $this->configuration['dek'] ?? '',
      '#cta_label' => $this->configuration['cta_label'] ?? '',
      '#cta_url' => $this->configuration['cta_url'] ?? '',
      '#background_url' => $image['url'],
      '#background_alt' => $image['alt'],
      '#height' => $this->configuration['height'] ?? 'tall',
      '#text_alignment' => $this->configuration['text_alignment'] ?? 'left',
      '#content_vertical_position' => $this->configuration['content_vertical_position'] ?? 'bottom',
      '#copy_theme' => $this->configuration['copy_theme'] ?? 'light',
      '#overlay_strength' => $this->configuration['overlay_strength'] ?? 'full',
      '#midground_strength' => $this->configuration['midground_strength'] ?? 'medium',
      '#foreground_behavior' => $this->configuration['foreground_behavior'] ?? 'pinned_then_release',
      '#foreground_pin_vh' => $this->configuration['foreground_pin_vh'] ?? '60',
      '#text_zoom_effect' => $this->configuration['text_zoom_effect'] ?? 'out',
      '#focal_x' => $this->configuration['focal_x'] ?? '50',
      '#focal_y' => $this->configuration['focal_y'] ?? '32',
      '#background_zoom' => $this->configuration['background_zoom'] ?? '1.00',
      '#attached' => [
        'library' => ['custom_flexpage_content/story_blocks'],
      ],
      '#cache' => [
        'tags' => $cache_tags,
      ],
    ];
  }

}
