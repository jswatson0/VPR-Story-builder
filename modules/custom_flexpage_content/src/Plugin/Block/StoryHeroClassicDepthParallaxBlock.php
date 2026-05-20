<?php

namespace Drupal\custom_flexpage_content\Plugin\Block;

use Drupal\Core\Form\FormStateInterface;
use Drupal\node\NodeInterface;

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
      'cta_node_id' => 0,
      'background_media_id' => 0,
      'background_image_url' => '',
      'background_alt' => '',
      'height' => 'tall',
      'text_alignment' => 'left',
      'content_vertical_position' => 'bottom',
      'copy_theme' => 'light',
      'overlay_strength' => 'full',
      'background_scroll_behavior' => 'parallax',
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
    $form['#attributes']['data-vs-hero-block-form'] = '1';
    $form['#attached']['library'][] = 'custom_flexpage_content/block_editor';

    $form['editor_help'] = [
      '#type' => 'details',
      '#title' => $this->t('How This Block Works'),
      '#open' => TRUE,
    ];
    $form['editor_help']['summary'] = [
      '#type' => 'item',
      '#markup' => $this->t('<p>This hero renders a full-width background image with layered visual treatment and scroll-linked motion. Add short narrative copy (eyebrow, headline, description, CTA), choose alignment and contrast settings, then fine-tune motion.</p><p><strong>Recommended flow:</strong> 1) choose image, 2) set focal point, 3) write concise copy, 4) choose copy theme + overlay for contrast, 5) tune motion settings.</p>'),
    ];

    $media_id = (int) ($this->configuration['background_media_id'] ?? 0);
    $form['background_media_id'] = [
      '#type' => 'media_library',
      '#title' => $this->t('Background media image (preferred)'),
      '#description' => $this->t('Use Media Library when possible for better media management and reuse.'),
      '#allowed_bundles' => $this->getImageMediaTypeIds(),
      '#default_value' => $media_id > 0 ? $media_id : NULL,
      '#cardinality' => 1,
    ];

    $form['background_image_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Background image URL (fallback)'),
      '#description' => $this->t('Optional fallback only if Media Library is not used.'),
      '#default_value' => $this->configuration['background_image_url'] ?? '',
      '#maxlength' => 2048,
    ];

    $form['background_alt'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Background image alt text'),
      '#description' => $this->t('Accessibility text that describes the image context.'),
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

    $form['eyebrow'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Eyebrow'),
      '#description' => $this->t('Short contextual label above the headline. Best kept brief.'),
      '#default_value' => $this->configuration['eyebrow'] ?? '',
      '#maxlength' => 255,
    ];

    $form['headline'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Headline'),
      '#description' => $this->t('Primary message shown in the hero. Keep concise for best readability.'),
      '#default_value' => $this->configuration['headline'] ?? '',
      '#required' => TRUE,
      '#maxlength' => 255,
    ];

    $form['dek'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Description'),
      '#description' => $this->t('Supporting text beneath the headline. 1-2 short sentences is ideal.'),
      '#default_value' => $this->configuration['dek'] ?? '',
      '#rows' => 4,
    ];

    $form['height'] = [
      '#type' => 'select',
      '#title' => $this->t('Section height'),
      '#description' => $this->t('Controls hero prominence. Tall is a good editorial default.'),
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
      '#description' => $this->t('Horizontal placement of the copy container.'),
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
      '#description' => $this->t('Vertical placement of copy over the background image.'),
      '#default_value' => $this->configuration['content_vertical_position'] ?? 'bottom',
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
      '#description' => $this->t('Increase overlay strength when text contrast needs improvement.'),
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

    $form['background_scroll_behavior'] = [
      '#type' => 'select',
      '#title' => $this->t('Background scroll behavior'),
      '#description' => $this->t('Choose whether the background image moves on scroll or stays fixed in place.'),
      '#default_value' => $this->configuration['background_scroll_behavior'] ?? 'parallax',
      '#options' => [
        'parallax' => $this->t('Move on scroll'),
        'static' => $this->t('No movement on scroll'),
      ],
    ];

    $form['cta_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('CTA label'),
      '#description' => $this->t('Optional button text, e.g. "Learn more".'),
      '#default_value' => $this->configuration['cta_label'] ?? '',
      '#maxlength' => 255,
    ];

    $cta_node_id = (int) ($this->configuration['cta_node_id'] ?? 0);
    $cta_node = $cta_node_id > 0 ? \Drupal::entityTypeManager()->getStorage('node')->load($cta_node_id) : NULL;
    if (!$cta_node instanceof NodeInterface) {
      $cta_node = NULL;
    }

    $form['cta_node_id'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('CTA internal page'),
      '#description' => $this->t('Select a node for internal links. If selected, this is used instead of external URL.'),
      '#target_type' => 'node',
      '#selection_settings' => [
        'target_bundles' => NULL,
      ],
      '#default_value' => $cta_node,
      '#tags' => FALSE,
    ];

    $form['cta_url'] = [
      '#type' => 'url',
      '#title' => $this->t('CTA external URL'),
      '#description' => $this->t('Use for external destinations. Leave empty if using internal page selector above.'),
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
    $this->configuration['cta_node_id'] = $this->extractNodeTargetId($form_state->getValue('cta_node_id'));
    $this->configuration['cta_url'] = trim((string) $form_state->getValue('cta_url', ''));
    $this->configuration['background_media_id'] = $this->extractMediaTargetId($form_state->getValue('background_media_id'));
    $this->configuration['background_image_url'] = trim((string) $form_state->getValue('background_image_url', ''));
    $this->configuration['background_alt'] = trim((string) $form_state->getValue('background_alt', ''));
    $this->configuration['height'] = (string) $form_state->getValue('height', 'tall');
    $this->configuration['text_alignment'] = (string) $form_state->getValue('text_alignment', 'left');
    $this->configuration['content_vertical_position'] = (string) $form_state->getValue('content_vertical_position', 'bottom');
    $this->configuration['copy_theme'] = (string) $form_state->getValue('copy_theme', 'light');
    $this->configuration['overlay_strength'] = (string) $form_state->getValue('overlay_strength', 'full');
    $this->configuration['background_scroll_behavior'] = (string) $form_state->getValue('background_scroll_behavior', 'parallax');
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

    $cta_url = trim((string) ($this->configuration['cta_url'] ?? ''));
    $cta_node_id = (int) ($this->configuration['cta_node_id'] ?? 0);
    if ($cta_node_id > 0) {
      $node = \Drupal::entityTypeManager()->getStorage('node')->load($cta_node_id);
      if ($node instanceof NodeInterface && $node->access('view')) {
        $cache_tags = array_merge($cache_tags, $node->getCacheTags());
        $cta_url = $node->toUrl()->toString();
      }
    }

    return [
      '#theme' => 'custom_flexpage_story_hero_classic_depth_parallax',
      '#eyebrow' => $this->configuration['eyebrow'] ?? '',
      '#headline' => $this->configuration['headline'] ?? '',
      '#dek' => $this->configuration['dek'] ?? '',
      '#cta_label' => $this->configuration['cta_label'] ?? '',
      '#cta_url' => $cta_url,
      '#background_url' => $image['url'],
      '#background_alt' => $image['alt'],
      '#height' => $this->configuration['height'] ?? 'tall',
      '#text_alignment' => $this->configuration['text_alignment'] ?? 'left',
      '#content_vertical_position' => $this->configuration['content_vertical_position'] ?? 'bottom',
      '#copy_theme' => $this->configuration['copy_theme'] ?? 'light',
      '#overlay_strength' => $this->configuration['overlay_strength'] ?? 'full',
      '#background_scroll_behavior' => $this->configuration['background_scroll_behavior'] ?? 'parallax',
      '#text_zoom_effect' => $this->configuration['text_zoom_effect'] ?? 'out',
      '#focal_x' => $this->configuration['focal_x'] ?? '50',
      '#focal_y' => $this->configuration['focal_y'] ?? '32',
      '#background_zoom' => $this->configuration['background_zoom'] ?? '1.00',
      '#attached' => [
        'library' => ['custom_flexpage_content/story_hero_classic_depth_parallax_block'],
      ],
      '#cache' => [
        'tags' => $cache_tags,
      ],
    ];
  }

  /**
   * Extracts node target ID from entity autocomplete values.
   */
  protected function extractNodeTargetId($value): int {
    if (is_array($value) && isset($value[0]['target_id'])) {
      return (int) $value[0]['target_id'];
    }

    if (is_array($value) && isset($value['target_id'])) {
      return (int) $value['target_id'];
    }

    if (is_numeric($value)) {
      return (int) $value;
    }

    if (is_string($value) && preg_match('/\((\d+)\)\s*$/', $value, $matches)) {
      return (int) ($matches[1] ?? 0);
    }

    return 0;
  }

}
