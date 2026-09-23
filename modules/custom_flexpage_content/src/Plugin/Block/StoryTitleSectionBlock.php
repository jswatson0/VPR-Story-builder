<?php

namespace Drupal\custom_flexpage_content\Plugin\Block;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\FormStateInterface;
use Drupal\media\MediaInterface;

/**
 * Provides a Shorthand-style title section hero block.
 *
 * @Block(
 *   id = "custom_flexpage_story_title_section_block",
 *   admin_label = @Translation("Section Header"),
 *   category = @Translation("Visual Storytelling")
 * )
 */
class StoryTitleSectionBlock extends VisualStoryBlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'layout' => 'full',
      'title' => '',
      'subtitle' => '',
      'byline' => '',
      'byline_position' => 'left',
      'text_position' => 'bottom-left',
      'text_alignment' => 'left',
      'bg_media_id' => 0,
      'bg_image_url' => '',
      'bg_alt' => '',
      'focal_x' => '50',
      'focal_y' => '50',
      'overlay_opacity' => 50,
      'overlay_color' => 'dark',
      'overlay_style' => 'gradient-down',
      'height' => 'screen',
      'text_scroll_effect' => 'fade',
      'text_scroll_per_line' => FALSE,
      'bg_scroll_effect' => 'parallax',
      'padding_top' => 'medium',
      'padding_bottom' => 'medium',
      'half_image_side_desktop' => 'left',
      'half_image_side_mobile' => 'top',
      'copy_theme' => 'light',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state): array {
    $form = parent::blockForm($form, $form_state);

    $form['layout'] = [
      '#type' => 'select',
      '#title' => $this->t('Layout'),
      '#default_value' => $this->configuration['layout'],
      '#options' => [
        'full' => $this->t('Full width — text over background'),
        'half' => $this->t('Half width — media and text side by side'),
        'text-only' => $this->t('Text only — no background media'),
      ],
    ];

    $form['half_image_side_desktop'] = [
      '#type' => 'select',
      '#title' => $this->t('Image side (desktop)'),
      '#default_value' => $this->configuration['half_image_side_desktop'],
      '#options' => [
        'left' => $this->t('Image left, text right'),
        'right' => $this->t('Image right, text left'),
      ],
      '#states' => [
        'visible' => [':input[name$="[layout]"]' => ['value' => 'half']],
      ],
    ];

    $form['half_image_side_mobile'] = [
      '#type' => 'select',
      '#title' => $this->t('Image side (mobile / portrait)'),
      '#default_value' => $this->configuration['half_image_side_mobile'],
      '#options' => [
        'top' => $this->t('Image top, text bottom'),
        'bottom' => $this->t('Image bottom, text top'),
      ],
      '#states' => [
        'visible' => [':input[name$="[layout]"]' => ['value' => 'half']],
      ],
    ];

    // Background media group.
    $form['background_media'] = [
      '#type' => 'details',
      '#title' => $this->t('Background media'),
      '#open' => TRUE,
    ];

    $media_id = (int) ($this->configuration['bg_media_id'] ?? 0);
    $form['background_media']['bg_media_id'] = [
      '#type' => 'media_library',
      '#title' => $this->t('Background image or video'),
      '#description' => $this->t('Accepts image or video media. Recommended: 2560×1440px JPEG for images; 1920×1080px MP4 muted for video.'),
      '#allowed_bundles' => $this->getImageAndVideoMediaTypeIds(),
      '#default_value' => $media_id > 0 ? $media_id : NULL,
      '#cardinality' => 1,
    ];

    $form['background_media']['bg_image_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Or enter a direct image URL'),
      '#description' => $this->t('Skip if you selected from Media Library above.'),
      '#default_value' => $this->configuration['bg_image_url'] ?? '',
      '#maxlength' => 2048,
      '#wrapper_attributes' => ['class' => ['vs-field-fallback']],
    ];

    $form['background_media']['bg_alt'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Image alt text'),
      '#description' => $this->mediaAltDescription(),
      '#default_value' => $this->configuration['bg_alt'] ?? '',
      '#maxlength' => 255,
    ];

    $form['background_media']['focal_x'] = [
      '#type' => 'number',
      '#title' => $this->t('Focal point X (%)'),
      '#description' => $this->t('0 = left edge, 100 = right edge. Controls which part of the image stays visible when cropped.'),
      '#default_value' => (float) ($this->configuration['focal_x'] ?? 50),
      '#min' => 0,
      '#max' => 100,
      '#step' => 1,
    ];

    $form['background_media']['focal_y'] = [
      '#type' => 'number',
      '#title' => $this->t('Focal point Y (%)'),
      '#description' => $this->t('0 = top edge, 100 = bottom edge.'),
      '#default_value' => (float) ($this->configuration['focal_y'] ?? 50),
      '#min' => 0,
      '#max' => 100,
      '#step' => 1,
    ];

    // Text content group.
    $form['text_content'] = [
      '#type' => 'details',
      '#title' => $this->t('Text content'),
      '#open' => TRUE,
    ];

    $form['text_content']['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title (H1)'),
      '#description' => $this->t('Primary heading. Rendered as H1 for SEO and screen readers.'),
      '#required' => TRUE,
      '#default_value' => $this->configuration['title'] ?? '',
      '#maxlength' => 255,
    ];

    $form['text_content']['subtitle'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Subtitle'),
      '#description' => $this->t('Supporting text beneath the title. 1–2 sentences is ideal.'),
      '#default_value' => $this->configuration['subtitle'] ?? '',
      '#rows' => 3,
    ];

    $form['text_content']['byline'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Byline / author'),
      '#description' => $this->t('Optional. Displayed separately from the title block, e.g. "By Jane Smith".'),
      '#default_value' => $this->configuration['byline'] ?? '',
      '#maxlength' => 255,
    ];

    $form['text_content']['byline_position'] = [
      '#type' => 'select',
      '#title' => $this->t('Byline alignment'),
      '#default_value' => $this->configuration['byline_position'] ?? 'left',
      '#options' => [
        'left' => $this->t('Left'),
        'center' => $this->t('Center'),
        'right' => $this->t('Right'),
      ],
    ];

    // Position & dimensions group.
    $form['position'] = [
      '#type' => 'details',
      '#title' => $this->t('Position & dimensions'),
      '#open' => TRUE,
    ];

    $form['position']['text_position'] = [
      '#type' => 'select',
      '#title' => $this->t('Text block position'),
      '#description' => $this->t('Nine-position grid placing the title block within the section. Has no effect in half-width layout.'),
      '#default_value' => $this->configuration['text_position'] ?? 'bottom-left',
      '#options' => [
        'top-left' => $this->t('Top left'),
        'top-center' => $this->t('Top center'),
        'top-right' => $this->t('Top right'),
        'middle-left' => $this->t('Middle left'),
        'middle-center' => $this->t('Middle center'),
        'middle-right' => $this->t('Middle right'),
        'bottom-left' => $this->t('Bottom left'),
        'bottom-center' => $this->t('Bottom center'),
        'bottom-right' => $this->t('Bottom right'),
      ],
      '#states' => [
        'invisible' => [':input[name$="[layout]"]' => ['value' => 'half']],
      ],
    ];

    $form['position']['text_alignment'] = [
      '#type' => 'select',
      '#title' => $this->t('Text alignment'),
      '#description' => $this->t('Independent of block position — text can be right-aligned even when the block sits on the left.'),
      '#default_value' => $this->configuration['text_alignment'] ?? 'left',
      '#options' => [
        'left' => $this->t('Left'),
        'center' => $this->t('Center'),
        'right' => $this->t('Right'),
      ],
    ];

    $form['position']['height'] = [
      '#type' => 'select',
      '#title' => $this->t('Section height'),
      '#default_value' => $this->configuration['height'] ?? 'screen',
      '#options' => [
        'screen' => $this->t('Full screen (100vh)'),
        '150' => $this->t('150% of screen (150vh)'),
        'half' => $this->t('Half screen (50vh)'),
        'third' => $this->t('Third of screen (33vh)'),
      ],
    ];

    $form['position']['padding_top'] = [
      '#type' => 'select',
      '#title' => $this->t('Top padding'),
      '#default_value' => $this->configuration['padding_top'] ?? 'medium',
      '#options' => [
        'small' => $this->t('Small'),
        'medium' => $this->t('Medium'),
        'large' => $this->t('Large'),
      ],
    ];

    $form['position']['padding_bottom'] = [
      '#type' => 'select',
      '#title' => $this->t('Bottom padding'),
      '#default_value' => $this->configuration['padding_bottom'] ?? 'medium',
      '#options' => [
        'small' => $this->t('Small'),
        'medium' => $this->t('Medium'),
        'large' => $this->t('Large'),
      ],
    ];

    // Overlay & color group.
    $form['style'] = [
      '#type' => 'details',
      '#title' => $this->t('Overlay & color'),
      '#open' => FALSE,
    ];

    $form['style']['copy_theme'] = [
      '#type' => 'select',
      '#title' => $this->t('Copy color theme'),
      '#description' => $this->t('Use light copy on dark/busy images, dark copy on light or plain images.'),
      '#default_value' => $this->configuration['copy_theme'] ?? 'light',
      '#options' => [
        'light' => $this->t('Light copy (white text)'),
        'dark' => $this->t('Dark copy (dark text)'),
      ],
    ];

    $form['style']['overlay_color'] = [
      '#type' => 'select',
      '#title' => $this->t('Overlay color'),
      '#default_value' => $this->configuration['overlay_color'] ?? 'dark',
      '#options' => [
        'dark' => $this->t('Dark'),
        'light' => $this->t('Light'),
        'burnt-orange' => $this->t('Burnt orange'),
      ],
    ];

    $form['style']['overlay_style'] = [
      '#type' => 'select',
      '#title' => $this->t('Overlay style'),
      '#description' => $this->t('Controls how the overlay fades across the image to aid text legibility.'),
      '#default_value' => $this->configuration['overlay_style'] ?? 'gradient-down',
      '#options' => [
        'flat' => $this->t('Flat'),
        'gradient-down' => $this->t('Gradient — stronger at top'),
        'gradient-up' => $this->t('Gradient — stronger at bottom'),
        'radial' => $this->t('Radial vignette — darker at edges'),
      ],
    ];

    $form['style']['overlay_opacity'] = [
      '#type' => 'number',
      '#title' => $this->t('Overlay opacity (%)'),
      '#description' => $this->t('0 = no overlay, 100 = fully opaque. Use to balance text legibility against the image.'),
      '#default_value' => (int) ($this->configuration['overlay_opacity'] ?? 50),
      '#min' => 0,
      '#max' => 100,
      '#step' => 5,
    ];

    // Scroll effects group.
    $form['scroll'] = [
      '#type' => 'details',
      '#title' => $this->t('Scroll effects'),
      '#open' => FALSE,
    ];

    $form['scroll']['bg_scroll_effect'] = [
      '#type' => 'select',
      '#title' => $this->t('Background scroll effect'),
      '#default_value' => $this->configuration['bg_scroll_effect'] ?? 'parallax',
      '#options' => [
        'none' => $this->t('None — background is static'),
        'parallax' => $this->t('Parallax — background drifts slower than scroll'),
        'zoom-in' => $this->t('Zoom in — background scale increases on scroll'),
      ],
    ];

    $form['scroll']['text_scroll_effect'] = [
      '#type' => 'select',
      '#title' => $this->t('Text scroll-out effect'),
      '#description' => $this->t('Effect applied to the title copy as the reader scrolls down through the section.'),
      '#default_value' => $this->configuration['text_scroll_effect'] ?? 'fade',
      '#options' => [
        'none' => $this->t('None'),
        'fade' => $this->t('Fade out'),
        'blur' => $this->t('Blur and fade out'),
        'zoom' => $this->t('Zoom out and fade'),
      ],
    ];

    $form['scroll']['text_scroll_per_line'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Apply effect per line (title first, then subtitle in sequence)'),
      '#default_value' => (bool) ($this->configuration['text_scroll_per_line'] ?? FALSE),
      '#states' => [
        'invisible' => [':input[name$="[text_scroll_effect]"]' => ['value' => 'none']],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state): void {
    parent::blockSubmit($form, $form_state);

    $this->configuration['layout'] = (string) $form_state->getValue('layout', 'full');
    $this->configuration['half_image_side_desktop'] = (string) $form_state->getValue('half_image_side_desktop', 'left');
    $this->configuration['half_image_side_mobile'] = (string) $form_state->getValue('half_image_side_mobile', 'top');

    $bg = $form_state->getValue('background_media', []);
    $this->configuration['bg_media_id'] = $this->extractMediaTargetId($bg['bg_media_id'] ?? NULL);
    $this->configuration['bg_image_url'] = trim((string) ($bg['bg_image_url'] ?? ''));
    $this->configuration['bg_alt'] = trim((string) ($bg['bg_alt'] ?? ''));
    $this->configuration['focal_x'] = (string) max(0, min(100, (float) ($bg['focal_x'] ?? 50)));
    $this->configuration['focal_y'] = (string) max(0, min(100, (float) ($bg['focal_y'] ?? 50)));

    $text = $form_state->getValue('text_content', []);
    $this->configuration['title'] = trim((string) ($text['title'] ?? ''));
    $this->configuration['subtitle'] = trim((string) ($text['subtitle'] ?? ''));
    $this->configuration['byline'] = trim((string) ($text['byline'] ?? ''));
    $this->configuration['byline_position'] = (string) ($text['byline_position'] ?? 'left');

    $pos = $form_state->getValue('position', []);
    $this->configuration['text_position'] = (string) ($pos['text_position'] ?? 'bottom-left');
    $this->configuration['text_alignment'] = (string) ($pos['text_alignment'] ?? 'left');
    $this->configuration['height'] = (string) ($pos['height'] ?? 'screen');
    $this->configuration['padding_top'] = (string) ($pos['padding_top'] ?? 'medium');
    $this->configuration['padding_bottom'] = (string) ($pos['padding_bottom'] ?? 'medium');

    $style = $form_state->getValue('style', []);
    $this->configuration['copy_theme'] = (string) ($style['copy_theme'] ?? 'light');
    $this->configuration['overlay_color'] = (string) ($style['overlay_color'] ?? 'dark');
    $this->configuration['overlay_style'] = (string) ($style['overlay_style'] ?? 'gradient-down');
    $this->configuration['overlay_opacity'] = max(0, min(100, (int) ($style['overlay_opacity'] ?? 50)));

    $scroll = $form_state->getValue('scroll', []);
    $this->configuration['bg_scroll_effect'] = (string) ($scroll['bg_scroll_effect'] ?? 'parallax');
    $this->configuration['text_scroll_effect'] = (string) ($scroll['text_scroll_effect'] ?? 'fade');
    $this->configuration['text_scroll_per_line'] = (bool) ($scroll['text_scroll_per_line'] ?? FALSE);
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $cache_tags = [];
    $bg_kind = 'none';
    $bg_url = '';
    $bg_mime = '';
    $bg_alt = trim((string) ($this->configuration['bg_alt'] ?? ''));

    $bg_media_id = (int) ($this->configuration['bg_media_id'] ?? 0);
    if ($bg_media_id > 0) {
      $media = \Drupal::entityTypeManager()->getStorage('media')->load($bg_media_id);
      if ($media instanceof MediaInterface) {
        $cache_tags = Cache::mergeTags($cache_tags, $media->getCacheTags());
        $video = $this->extractVideoFromMedia($media, $cache_tags);
        if ($video) {
          $bg_kind = 'video';
          $bg_url = $video['url'];
          $bg_mime = $video['mime'];
        }
        else {
          $image = $this->extractImageFromMedia($media, $cache_tags);
          if ($image) {
            $bg_kind = 'image';
            $bg_url = $image['url'];
            if ($bg_alt === '') {
              $bg_alt = $image['alt'];
            }
          }
        }
      }
    }
    elseif (!empty($this->configuration['bg_image_url'])) {
      $bg_kind = 'image';
      $bg_url = trim((string) $this->configuration['bg_image_url']);
    }

    $layout = (string) ($this->configuration['layout'] ?? 'full');
    if ($layout !== 'text-only' && $bg_kind === 'none') {
      $layout = 'text-only';
    }

    // Convert 0–100 integer to 0.00–1.00 decimal for CSS.
    $overlay_opacity = number_format(
      (int) ($this->configuration['overlay_opacity'] ?? 50) / 100,
      2, '.', ''
    );

    return [
      '#theme' => 'custom_flexpage_story_title_section',
      '#layout' => $layout,
      '#title' => $this->configuration['title'] ?? '',
      '#subtitle' => $this->configuration['subtitle'] ?? '',
      '#byline' => $this->configuration['byline'] ?? '',
      '#byline_position' => $this->configuration['byline_position'] ?? 'left',
      '#text_position' => $this->configuration['text_position'] ?? 'bottom-left',
      '#text_alignment' => $this->configuration['text_alignment'] ?? 'left',
      '#bg_kind' => $bg_kind,
      '#bg_url' => $bg_url,
      '#bg_mime' => $bg_mime,
      '#bg_alt' => $bg_alt,
      '#focal_x' => $this->configuration['focal_x'] ?? '50',
      '#focal_y' => $this->configuration['focal_y'] ?? '50',
      '#overlay_color' => $this->configuration['overlay_color'] ?? 'dark',
      '#overlay_style' => $this->configuration['overlay_style'] ?? 'gradient-down',
      '#overlay_opacity' => $overlay_opacity,
      '#height' => $this->configuration['height'] ?? 'screen',
      '#padding_top' => $this->configuration['padding_top'] ?? 'medium',
      '#padding_bottom' => $this->configuration['padding_bottom'] ?? 'medium',
      '#bg_scroll_effect' => $this->configuration['bg_scroll_effect'] ?? 'parallax',
      '#text_scroll_effect' => $this->configuration['text_scroll_effect'] ?? 'fade',
      '#text_scroll_per_line' => (bool) ($this->configuration['text_scroll_per_line'] ?? FALSE),
      '#half_image_side_desktop' => $this->configuration['half_image_side_desktop'] ?? 'left',
      '#half_image_side_mobile' => $this->configuration['half_image_side_mobile'] ?? 'top',
      '#copy_theme' => $this->configuration['copy_theme'] ?? 'light',
      '#attached' => [
        'library' => ['custom_flexpage_content/story_title_section_block'],
      ],
      '#cache' => [
        'tags' => $cache_tags,
      ],
    ];
  }

}
