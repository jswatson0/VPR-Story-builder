<?php

namespace Drupal\custom_flexpage_content\Plugin\Block;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\NodeInterface;

/**
 * Provides a text-over-media storytelling block.
 *
 * @Block(
 *   id = "custom_flexpage_text_over_media_block",
 *   admin_label = @Translation("Text + Image"),
 *   category = @Translation("Visual Storytelling")
 * )
 */
class TextOverMediaBlock extends VisualStoryBlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'headline' => '',
      'body_value' => '',
      'body_format' => 'basic_html',
      'cta_label' => '',
      'cta_url' => '',
      'cta_node_id' => 0,
      'media_id' => 0,
      'media_image_url' => '',
      'media_alt' => '',
      'media_caption' => '',
      'layout' => 'media-right',
      'mobile_stack' => 'text-first',
      'style_preset' => 'editorial',
      'theme' => 'dark',
      'column_ratio' => 'equal',
      'drop_cap' => FALSE,
      'media_focal_x' => '50',
      'media_focal_y' => '50',
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
      '#markup' => $this->t('<p>This block renders a side-by-side text and image section. Choose left/right layout, set column ratio, then write rich text with headings, lists, and links using CKEditor.</p><p><strong>Recommended flow:</strong> 1) choose image, 2) set focal point, 3) enter headline and rich copy, 4) choose layout/theme, 5) adjust ratio.</p>'),
    ];

    $media_id = (int) ($this->configuration['media_id'] ?? 0);
    $form['media_id'] = [
      '#type' => 'media_library',
      '#title' => $this->t('Media asset (preferred)'),
      '#description' => $this->t('Use Media Library when possible. Supports image and native video media types.'),
      '#allowed_bundles' => $this->getImageAndVideoMediaTypeIds(),
      '#default_value' => $media_id > 0 ? $media_id : NULL,
      '#cardinality' => 1,
    ];

    $form['media_image_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Or enter a direct media URL'),
      '#description' => $this->t('Skip if you selected from Media Library above. Accepts image or video URL (.mp4/.webm).'),
      '#default_value' => $this->configuration['media_image_url'] ?? '',
      '#maxlength' => 2048,
      '#wrapper_attributes' => ['class' => ['vs-field-fallback']],
    ];

    $form['media_alt'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Media image alt text'),
      '#description' => $this->mediaAltDescription(),
      '#default_value' => $this->configuration['media_alt'] ?? '',
      '#maxlength' => 255,
    ];

    $form['media_caption'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Media caption (optional)'),
      '#description' => $this->t('Optional short credit or context note for the media, e.g. a photo credit. Leave blank to hide.'),
      '#default_value' => $this->configuration['media_caption'] ?? '',
      '#maxlength' => 255,
    ];

    $form['media_focal_x'] = [
      '#type' => 'number',
      '#title' => $this->t('Image focal point X (%)'),
      '#description' => $this->t('0 = left edge, 100 = right edge.'),
      '#default_value' => (float) ($this->configuration['media_focal_x'] ?? 50),
      '#min' => 0,
      '#max' => 100,
      '#step' => 1,
    ];

    $form['media_focal_y'] = [
      '#type' => 'number',
      '#title' => $this->t('Image focal point Y (%)'),
      '#description' => $this->t('0 = top edge, 100 = bottom edge.'),
      '#default_value' => (float) ($this->configuration['media_focal_y'] ?? 50),
      '#min' => 0,
      '#max' => 100,
      '#step' => 1,
    ];

    $form['headline'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Headline'),
      '#description' => $this->t('Primary section heading. Keep concise for stronger visual hierarchy.'),
      '#default_value' => $this->configuration['headline'] ?? '',
      '#maxlength' => 255,
    ];

    $form['drop_cap'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable drop cap'),
      '#description' => $this->t('Styles the first letter of the first paragraph in body copy as a drop cap.'),
      '#default_value' => (bool) ($this->configuration['drop_cap'] ?? FALSE),
    ];

    $form['body'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Body copy'),
      '#description' => $this->t('Rich text body using CKEditor.'),
      '#default_value' => $this->configuration['body_value'] ?? ($this->configuration['body'] ?? ''),
      '#format' => $this->configuration['body_format'] ?? filter_default_format(),
      '#required' => TRUE,
      '#rows' => 7,
    ];

    $form['layout'] = [
      '#type' => 'select',
      '#title' => $this->t('Layout'),
      '#description' => $this->t('Choose whether the image sits left or right of text.'),
      '#default_value' => $this->configuration['layout'] ?? 'media-right',
      '#options' => [
        'media-right' => $this->t('Text left, media right'),
        'media-left' => $this->t('Media left, text right'),
      ],
    ];

    $form['mobile_stack'] = [
      '#type' => 'select',
      '#title' => $this->t('Mobile stack order'),
      '#description' => $this->t('Controls section order on small screens.'),
      '#default_value' => $this->configuration['mobile_stack'] ?? 'text-first',
      '#options' => [
        'text-first' => $this->t('Text first'),
        'media-first' => $this->t('Media first'),
      ],
    ];

    $form['column_ratio'] = [
      '#type' => 'select',
      '#title' => $this->t('Column ratio'),
      '#description' => $this->t('Controls width balance between text and image columns.'),
      '#default_value' => $this->configuration['column_ratio'] ?? 'equal',
      '#options' => [
        'equal' => $this->t('50 / 50'),
        'text-wide' => $this->t('60 / 40 (text wider)'),
        'media-wide' => $this->t('40 / 60 (media wider)'),
      ],
    ];

    $form['style_preset'] = [
      '#type' => 'select',
      '#title' => $this->t('Style preset'),
      '#description' => $this->t('Quick visual style presets for storytelling tone.'),
      '#default_value' => $this->configuration['style_preset'] ?? 'editorial',
      '#options' => [
        'editorial' => $this->t('Editorial'),
        'feature' => $this->t('Feature'),
        'data-story' => $this->t('Data Story'),
      ],
    ];

    $form['theme'] = [
      '#type' => 'select',
      '#title' => $this->t('Color theme'),
      '#description' => $this->t('Dark for richer contrast, light for brighter editorial style.'),
      '#default_value' => $this->configuration['theme'] ?? 'dark',
      '#options' => [
        'dark' => $this->t('Dark'),
        'light' => $this->t('Light'),
      ],
    ];

    $form['cta_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('CTA label'),
      '#description' => $this->t('Optional button text, e.g. "Explore data".'),
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
      '#title' => $this->t('Or enter an external URL'),
      '#description' => $this->t('For links outside this site only. Leave empty if you selected a page above.'),
      '#default_value' => $this->configuration['cta_url'] ?? '',
      '#wrapper_attributes' => ['class' => ['vs-field-fallback']],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state): void {
    parent::blockSubmit($form, $form_state);

    $this->configuration['headline'] = trim((string) $form_state->getValue('headline', ''));
    $body = $form_state->getValue('body', []);
    $this->configuration['body_value'] = trim((string) ($body['value'] ?? ''));
    $this->configuration['body_format'] = (string) ($body['format'] ?? filter_default_format());
    $this->configuration['cta_label'] = trim((string) $form_state->getValue('cta_label', ''));
    $this->configuration['cta_node_id'] = $this->extractNodeTargetId($form_state->getValue('cta_node_id'));
    $this->configuration['cta_url'] = trim((string) $form_state->getValue('cta_url', ''));
    $this->configuration['media_id'] = $this->extractMediaTargetId($form_state->getValue('media_id'));
    $this->configuration['media_image_url'] = trim((string) $form_state->getValue('media_image_url', ''));
    $this->configuration['media_alt'] = trim((string) $form_state->getValue('media_alt', ''));
    $this->configuration['media_caption'] = trim((string) $form_state->getValue('media_caption', ''));
    $this->configuration['media_focal_x'] = (string) max(0, min(100, (float) $form_state->getValue('media_focal_x', '50')));
    $this->configuration['media_focal_y'] = (string) max(0, min(100, (float) $form_state->getValue('media_focal_y', '50')));
    $this->configuration['layout'] = (string) $form_state->getValue('layout', 'media-right');
    $this->configuration['mobile_stack'] = (string) $form_state->getValue('mobile_stack', 'text-first');
    $this->configuration['column_ratio'] = (string) $form_state->getValue('column_ratio', 'equal');
    $this->configuration['style_preset'] = (string) $form_state->getValue('style_preset', 'editorial');
    $this->configuration['drop_cap'] = (bool) $form_state->getValue('drop_cap', FALSE);
    $this->configuration['theme'] = (string) $form_state->getValue('theme', 'dark');
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $cache_tags = [];
    $image = $this->resolveMediaAsset(
      (int) ($this->configuration['media_id'] ?? 0),
      (string) ($this->configuration['media_image_url'] ?? ''),
      (string) ($this->configuration['media_alt'] ?? ''),
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
        $cache_tags = Cache::mergeTags($cache_tags, $node->getCacheTags());
        $cta_url = $node->toUrl()->toString();
      }
    }

    $media_kind = $image['kind'] ?? 'image';
    $media_mime = $image['mime'] ?? '';

    return [
      '#theme' => 'custom_flexpage_text_over_media',
      '#headline' => $this->configuration['headline'] ?? '',
      '#body' => [
        '#type' => 'processed_text',
        '#text' => $this->configuration['body_value'] ?? ($this->configuration['body'] ?? ''),
        '#format' => $this->configuration['body_format'] ?? filter_default_format(),
      ],
      '#cta_label' => $this->configuration['cta_label'] ?? '',
      '#cta_url' => $cta_url,
      '#media_url' => $image['url'],
      '#media_kind' => $media_kind,
      '#media_mime' => $media_mime,
      '#media_alt' => $image['alt'],
      '#media_caption' => $this->configuration['media_caption'] ?? '',
      '#layout' => $this->configuration['layout'] ?? 'media-right',
      '#mobile_stack' => $this->configuration['mobile_stack'] ?? 'text-first',
      '#column_ratio' => $this->configuration['column_ratio'] ?? 'equal',
      '#style_preset' => $this->configuration['style_preset'] ?? 'editorial',
      '#drop_cap' => $this->configuration['drop_cap'] ?? FALSE,
      '#media_focal_x' => $this->configuration['media_focal_x'] ?? '50',
      '#media_focal_y' => $this->configuration['media_focal_y'] ?? '50',
      '#color_theme' => $this->configuration['theme'] ?? 'dark',
      '#attached' => [
        'library' => ['custom_flexpage_content/text_over_media_block'],
      ],
      '#cache' => [
        'tags' => $cache_tags,
      ],
    ];
  }

  /**
   * Resolves image/video media from selected media or fallback URL.
   */
  protected function resolveMediaAsset(int $media_id, string $fallback_url, string $fallback_alt, array &$cache_tags): ?array {
    $fallback_url = trim($fallback_url);
    $fallback_alt = trim($fallback_alt);

    if ($media_id > 0) {
      $media = \Drupal::entityTypeManager()->getStorage('media')->load($media_id);
      if ($media) {
        $cache_tags = Cache::mergeTags($cache_tags, $media->getCacheTags());
        $video = $this->extractVideoFromMedia($media, $cache_tags);
        if ($video) {
          return [
            'kind' => 'video',
            'url' => $video['url'],
            'mime' => $video['mime'],
            'alt' => $fallback_alt,
          ];
        }

        $image = $this->extractImageFromMedia($media, $cache_tags);
        if ($image) {
          return [
            'kind' => 'image',
            'url' => $image['url'],
            'mime' => '',
            'alt' => $fallback_alt !== '' ? $fallback_alt : $image['alt'],
          ];
        }
      }
    }

    if ($fallback_url === '') {
      return NULL;
    }

    $normalized = strtolower(preg_replace('/\?.*$/', '', $fallback_url) ?? $fallback_url);
    $is_video = str_ends_with($normalized, '.mp4') || str_ends_with($normalized, '.webm') || str_ends_with($normalized, '.ogg');

    if ($is_video) {
      $mime = str_ends_with($normalized, '.webm') ? 'video/webm' : (str_ends_with($normalized, '.ogg') ? 'video/ogg' : 'video/mp4');
      return [
        'kind' => 'video',
        'url' => $fallback_url,
        'mime' => $mime,
        'alt' => $fallback_alt,
      ];
    }

    return [
      'kind' => 'image',
      'url' => $fallback_url,
      'mime' => '',
      'alt' => $fallback_alt,
    ];
  }


}
