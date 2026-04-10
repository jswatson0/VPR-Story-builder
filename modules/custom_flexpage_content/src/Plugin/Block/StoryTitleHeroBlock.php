<?php

namespace Drupal\custom_flexpage_content\Plugin\Block;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a visual storytelling hero/title block.
 *
 * @Block(
 *   id = "custom_flexpage_story_title_hero_block",
 *   admin_label = @Translation("Story Title Hero"),
 *   category = @Translation("Visual Storytelling")
 * )
 */
class StoryTitleHeroBlock extends VisualStoryBlockBase {

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
      'overlay_strength' => 'medium',
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

    $form['overlay_strength'] = [
      '#type' => 'select',
      '#title' => $this->t('Overlay strength'),
      '#default_value' => $this->configuration['overlay_strength'] ?? 'medium',
      '#options' => [
        'light' => $this->t('Light'),
        'medium' => $this->t('Medium'),
        'heavy' => $this->t('Heavy'),
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
    $this->configuration['overlay_strength'] = (string) $form_state->getValue('overlay_strength', 'medium');
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
      '#theme' => 'custom_flexpage_story_hero',
      '#eyebrow' => $this->configuration['eyebrow'] ?? '',
      '#headline' => $this->configuration['headline'] ?? '',
      '#dek' => $this->configuration['dek'] ?? '',
      '#cta_label' => $this->configuration['cta_label'] ?? '',
      '#cta_url' => $this->configuration['cta_url'] ?? '',
      '#background_url' => $image['url'],
      '#background_alt' => $image['alt'],
      '#height' => $this->configuration['height'] ?? 'tall',
      '#text_alignment' => $this->configuration['text_alignment'] ?? 'left',
      '#overlay_strength' => $this->configuration['overlay_strength'] ?? 'medium',
      '#attached' => [
        'library' => ['custom_flexpage_content/story_blocks'],
      ],
      '#cache' => [
        'tags' => $cache_tags,
      ],
    ];
  }

}

