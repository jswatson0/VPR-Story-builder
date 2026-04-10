<?php

namespace Drupal\custom_flexpage_content\Plugin\Block;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a text-over-media storytelling block.
 *
 * @Block(
 *   id = "custom_flexpage_text_over_media_block",
 *   admin_label = @Translation("Story Text Over Media"),
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
      'body' => '',
      'media_id' => 0,
      'media_image_url' => '',
      'media_alt' => '',
      'layout' => 'media-right',
      'theme' => 'dark',
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

    $form['body'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Body copy'),
      '#default_value' => $this->configuration['body'] ?? '',
      '#required' => TRUE,
      '#rows' => 7,
    ];

    $media_id = (int) ($this->configuration['media_id'] ?? 0);
    $form['media_id'] = [
      '#type' => 'media_library',
      '#title' => $this->t('Media image (preferred)'),
      '#allowed_bundles' => $this->getImageMediaTypeIds(),
      '#default_value' => $media_id > 0 ? $media_id : NULL,
      '#cardinality' => 1,
    ];

    $form['media_image_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Media image URL (fallback)'),
      '#default_value' => $this->configuration['media_image_url'] ?? '',
      '#maxlength' => 2048,
    ];

    $form['media_alt'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Media image alt text'),
      '#default_value' => $this->configuration['media_alt'] ?? '',
      '#maxlength' => 255,
    ];

    $form['layout'] = [
      '#type' => 'select',
      '#title' => $this->t('Layout'),
      '#default_value' => $this->configuration['layout'] ?? 'media-right',
      '#options' => [
        'media-right' => $this->t('Text left, media right'),
        'media-left' => $this->t('Media left, text right'),
      ],
    ];

    $form['theme'] = [
      '#type' => 'select',
      '#title' => $this->t('Color theme'),
      '#default_value' => $this->configuration['theme'] ?? 'dark',
      '#options' => [
        'dark' => $this->t('Dark'),
        'light' => $this->t('Light'),
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state): void {
    parent::blockSubmit($form, $form_state);

    $this->configuration['headline'] = trim((string) $form_state->getValue('headline', ''));
    $this->configuration['body'] = trim((string) $form_state->getValue('body', ''));
    $this->configuration['media_id'] = $this->extractMediaTargetId($form_state->getValue('media_id'));
    $this->configuration['media_image_url'] = trim((string) $form_state->getValue('media_image_url', ''));
    $this->configuration['media_alt'] = trim((string) $form_state->getValue('media_alt', ''));
    $this->configuration['layout'] = (string) $form_state->getValue('layout', 'media-right');
    $this->configuration['theme'] = (string) $form_state->getValue('theme', 'dark');
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $cache_tags = [];
    $image = $this->resolveImageSource(
      (int) ($this->configuration['media_id'] ?? 0),
      (string) ($this->configuration['media_image_url'] ?? ''),
      (string) ($this->configuration['media_alt'] ?? ''),
      $cache_tags
    );

    if (!$image) {
      return [];
    }

    return [
      '#theme' => 'custom_flexpage_text_over_media',
      '#headline' => $this->configuration['headline'] ?? '',
      '#body' => $this->configuration['body'] ?? '',
      '#media_url' => $image['url'],
      '#media_alt' => $image['alt'],
      '#layout' => $this->configuration['layout'] ?? 'media-right',
      '#color_theme' => $this->configuration['theme'] ?? 'dark',
      '#attached' => [
        'library' => ['custom_flexpage_content/story_blocks'],
      ],
      '#cache' => [
        'tags' => $cache_tags,
      ],
    ];
  }

}
