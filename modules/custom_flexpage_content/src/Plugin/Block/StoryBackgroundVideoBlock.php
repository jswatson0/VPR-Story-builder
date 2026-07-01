<?php

namespace Drupal\custom_flexpage_content\Plugin\Block;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a full-bleed background video section block.
 *
 * @Block(
 *   id = "custom_flexpage_story_background_video_block",
 *   admin_label = @Translation("Background Video"),
 *   category = @Translation("Visual Storytelling")
 * )
 */
class StoryBackgroundVideoBlock extends VisualStoryBlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'video_media_id'   => 0,
      'video_url'        => '',
      'poster_media_id'  => 0,
      'poster_url'       => '',
      'poster_alt'       => '',
      'eyebrow'          => '',
      'headline'         => '',
      'dek'              => '',
      'cta_label'        => '',
      'cta_url'          => '',
      'height'           => 'screen',
      'text_position'    => 'bottom-left',
      'copy_theme'       => 'light',
      'overlay_strength' => 'medium',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state): array {
    $form = parent::blockForm($form, $form_state);

    $form['editor_help'] = [
      '#type'  => 'details',
      '#title' => $this->t('How This Block Works'),
      '#open'  => FALSE,
    ];
    $form['editor_help']['summary'] = [
      '#type'   => 'item',
      '#markup' => $this->t('<p>Full-width section with an autoplaying muted video background. Add a poster image as a fallback for browsers where autoplay is unavailable. Keep video files under 10 MB and use MP4/H.264 for the broadest device compatibility.</p><p><strong>Recommended flow:</strong> 1) upload video to Media Library, 2) set a poster image, 3) write concise overlay copy, 4) tune contrast settings.</p>'),
    ];

    $video_media_id = (int) ($this->configuration['video_media_id'] ?? 0);
    $form['video_media_id'] = [
      '#type'            => 'media_library',
      '#title'           => $this->t('Background video'),
      '#description'     => $this->t('Select a video from Media Library. MP4/H.264 recommended. Plays silently on loop.'),
      '#allowed_bundles' => $this->getVideoMediaTypeIds(),
      '#default_value'   => $video_media_id > 0 ? $video_media_id : NULL,
      '#cardinality'     => 1,
    ];

    $form['video_url'] = [
      '#type'               => 'url',
      '#title'              => $this->t('Or enter a direct video URL'),
      '#description'        => $this->t('Skip if you selected from Media Library above. MP4 recommended.'),
      '#default_value'      => $this->configuration['video_url'] ?? '',
      '#wrapper_attributes' => ['class' => ['vs-field-fallback']],
    ];

    $poster_media_id = (int) ($this->configuration['poster_media_id'] ?? 0);
    $form['poster_media_id'] = [
      '#type'            => 'media_library',
      '#title'           => $this->t('Poster / fallback image'),
      '#description'     => $this->t('Shown before the video loads and on devices that cannot autoplay. Strongly recommended.'),
      '#allowed_bundles' => $this->getImageMediaTypeIds(),
      '#default_value'   => $poster_media_id > 0 ? $poster_media_id : NULL,
      '#cardinality'     => 1,
    ];

    $form['poster_url'] = [
      '#type'               => 'textfield',
      '#title'              => $this->t('Or enter a direct poster image URL'),
      '#description'        => $this->t('Skip if you selected from Media Library above.'),
      '#default_value'      => $this->configuration['poster_url'] ?? '',
      '#maxlength'          => 2048,
      '#wrapper_attributes' => ['class' => ['vs-field-fallback']],
    ];

    $form['poster_alt'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Poster image alt text'),
      '#default_value' => $this->configuration['poster_alt'] ?? '',
      '#maxlength'     => 255,
    ];

    $form['eyebrow'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Eyebrow'),
      '#description'   => $this->t('Short contextual label above the headline.'),
      '#default_value' => $this->configuration['eyebrow'] ?? '',
      '#maxlength'     => 255,
    ];

    $form['headline'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Headline'),
      '#default_value' => $this->configuration['headline'] ?? '',
      '#maxlength'     => 255,
    ];

    $form['dek'] = [
      '#type'          => 'textarea',
      '#title'         => $this->t('Description'),
      '#description'   => $this->t('Supporting text beneath the headline. 1–2 short sentences is ideal.'),
      '#default_value' => $this->configuration['dek'] ?? '',
      '#rows'          => 3,
    ];

    $form['height'] = [
      '#type'          => 'select',
      '#title'         => $this->t('Section height'),
      '#default_value' => $this->configuration['height'] ?? 'screen',
      '#options'       => [
        'short'  => $this->t('Short (~40vh)'),
        'tall'   => $this->t('Tall (~70vh)'),
        'screen' => $this->t('Full screen (100vh)'),
      ],
    ];

    $form['text_position'] = [
      '#type'          => 'select',
      '#title'         => $this->t('Text position'),
      '#default_value' => $this->configuration['text_position'] ?? 'bottom-left',
      '#options'       => [
        'top-left'      => $this->t('Top left'),
        'top-center'    => $this->t('Top center'),
        'top-right'     => $this->t('Top right'),
        'center-left'   => $this->t('Center left'),
        'center'        => $this->t('Center'),
        'center-right'  => $this->t('Center right'),
        'bottom-left'   => $this->t('Bottom left'),
        'bottom-center' => $this->t('Bottom center'),
        'bottom-right'  => $this->t('Bottom right'),
      ],
    ];

    $form['copy_theme'] = [
      '#type'          => 'select',
      '#title'         => $this->t('Copy color theme'),
      '#description'   => $this->t('Use light copy on dark video, dark copy on light video.'),
      '#default_value' => $this->configuration['copy_theme'] ?? 'light',
      '#options'       => [
        'light' => $this->t('Light copy'),
        'dark'  => $this->t('Dark copy'),
      ],
    ];

    $form['overlay_strength'] = [
      '#type'          => 'select',
      '#title'         => $this->t('Overlay strength'),
      '#description'   => $this->t('Dark overlay behind text to ensure contrast.'),
      '#default_value' => $this->configuration['overlay_strength'] ?? 'medium',
      '#options'       => [
        'none'   => $this->t('None'),
        'light'  => $this->t('Light'),
        'medium' => $this->t('Medium'),
        'heavy'  => $this->t('Heavy'),
      ],
    ];

    $form['cta_label'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('CTA label'),
      '#description'   => $this->t('Optional button text, e.g. "Learn more".'),
      '#default_value' => $this->configuration['cta_label'] ?? '',
      '#maxlength'     => 255,
    ];

    $form['cta_url'] = [
      '#type'          => 'url',
      '#title'         => $this->t('CTA URL'),
      '#default_value' => $this->configuration['cta_url'] ?? '',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state): void {
    parent::blockSubmit($form, $form_state);

    $this->configuration['video_media_id']   = $this->extractMediaTargetId($form_state->getValue('video_media_id'));
    $this->configuration['video_url']        = trim((string) $form_state->getValue('video_url', ''));
    $this->configuration['poster_media_id']  = $this->extractMediaTargetId($form_state->getValue('poster_media_id'));
    $this->configuration['poster_url']       = trim((string) $form_state->getValue('poster_url', ''));
    $this->configuration['poster_alt']       = trim((string) $form_state->getValue('poster_alt', ''));
    $this->configuration['eyebrow']          = trim((string) $form_state->getValue('eyebrow', ''));
    $this->configuration['headline']         = trim((string) $form_state->getValue('headline', ''));
    $this->configuration['dek']             = trim((string) $form_state->getValue('dek', ''));
    $this->configuration['cta_label']        = trim((string) $form_state->getValue('cta_label', ''));
    $this->configuration['cta_url']          = trim((string) $form_state->getValue('cta_url', ''));
    $this->configuration['height']           = (string) $form_state->getValue('height', 'screen');
    $this->configuration['text_position']    = (string) $form_state->getValue('text_position', 'bottom-left');
    $this->configuration['copy_theme']       = (string) $form_state->getValue('copy_theme', 'light');
    $this->configuration['overlay_strength'] = (string) $form_state->getValue('overlay_strength', 'medium');
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $cache_tags = [];

    $video = $this->resolveVideoSource(
      (int) ($this->configuration['video_media_id'] ?? 0),
      (string) ($this->configuration['video_url'] ?? ''),
      $cache_tags
    );

    $poster = $this->resolveImageSource(
      (int) ($this->configuration['poster_media_id'] ?? 0),
      (string) ($this->configuration['poster_url'] ?? ''),
      (string) ($this->configuration['poster_alt'] ?? ''),
      $cache_tags
    );

    if (!$video && !$poster) {
      return [];
    }

    return [
      '#theme'           => 'custom_flexpage_story_background_video',
      '#video_url'       => $video['url'] ?? '',
      '#video_mime'      => $video['mime'] ?? 'video/mp4',
      '#poster_url'      => $poster['url'] ?? '',
      '#poster_alt'      => $poster['alt'] ?? '',
      '#eyebrow'         => $this->configuration['eyebrow'] ?? '',
      '#headline'        => $this->configuration['headline'] ?? '',
      '#dek'             => $this->configuration['dek'] ?? '',
      '#cta_label'       => $this->configuration['cta_label'] ?? '',
      '#cta_url'         => $this->configuration['cta_url'] ?? '',
      '#height'          => $this->configuration['height'] ?? 'screen',
      '#text_position'   => $this->configuration['text_position'] ?? 'bottom-left',
      '#copy_theme'      => $this->configuration['copy_theme'] ?? 'light',
      '#overlay_strength' => $this->configuration['overlay_strength'] ?? 'medium',
      '#attached'        => [
        'library' => ['custom_flexpage_content/story_background_video_block'],
      ],
      '#cache' => ['tags' => $cache_tags],
    ];
  }

}
