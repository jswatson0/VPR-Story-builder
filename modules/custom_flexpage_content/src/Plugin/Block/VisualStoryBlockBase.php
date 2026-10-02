<?php

namespace Drupal\custom_flexpage_content\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\media\MediaInterface;
use Drupal\media\MediaTypeInterface;
use Drupal\taxonomy\TermInterface;

/**
 * Shared helpers for visual storytelling block plugins.
 */
abstract class VisualStoryBlockBase extends BlockBase {

  /**
   * Gets the shared help text for an optional media alt-text field.
   */
  protected function mediaAltDescription(): TranslatableMarkup {
    return $this->t('Leave blank to use the alt text already set on the selected image in Media Library. Only fill this in to override it, or when using a direct image URL instead of Media Library.');
  }

  /**
   * Gets media type IDs that use the image source plugin.
   */
  protected function getImageMediaTypeIds(): array {
    $media_types = \Drupal::entityTypeManager()->getStorage('media_type')->loadMultiple();
    $image_type_ids = [];

    foreach ($media_types as $media_type) {
      if (!$media_type instanceof MediaTypeInterface) {
        continue;
      }

      if ($media_type->getSource()->getPluginId() === 'image') {
        $image_type_ids[] = $media_type->id();
      }
    }

    return !empty($image_type_ids) ? $image_type_ids : array_keys($media_types);
  }

  /**
   * Gets media type IDs that use image or video-file source plugins.
   */
  protected function getImageAndVideoMediaTypeIds(): array {
    $media_types = \Drupal::entityTypeManager()->getStorage('media_type')->loadMultiple();
    $allowed_ids = [];

    foreach ($media_types as $media_type) {
      if (!$media_type instanceof MediaTypeInterface) {
        continue;
      }

      $plugin_id = $media_type->getSource()->getPluginId();
      if (in_array($plugin_id, ['image', 'video_file'], TRUE)) {
        $allowed_ids[] = $media_type->id();
      }
    }

    return !empty($allowed_ids) ? $allowed_ids : array_keys($media_types);
  }

  /**
   * Extracts a target ID from media library form values.
   */
  protected function extractMediaTargetId($value): int {
    if (is_array($value) && isset($value['target_id'])) {
      return (int) $value['target_id'];
    }

    if (is_array($value) && isset($value['media_library_selection'])) {
      $value = $value['media_library_selection'];
    }

    if (is_string($value) && str_contains($value, ',')) {
      $value = explode(',', $value)[0] ?? '';
    }

    if (is_numeric($value)) {
      return (int) $value;
    }

    return 0;
  }

  /**
   * Resolves image URL and alt text from media or fallback URL.
   */
  protected function resolveImageSource(int $media_id, string $fallback_url, string $fallback_alt, array &$cache_tags): ?array {
    $image_url = trim($fallback_url);
    $alt = trim($fallback_alt);

    if ($media_id > 0) {
      $media = \Drupal::entityTypeManager()->getStorage('media')->load($media_id);
      if ($media instanceof MediaInterface) {
        $cache_tags = Cache::mergeTags($cache_tags, $media->getCacheTags());
        $media_image = $this->extractImageFromMedia($media, $cache_tags);
        if ($media_image) {
          $image_url = $media_image['url'];
          if ($alt === '' && $media_image['alt'] !== '') {
            $alt = $media_image['alt'];
          }
        }
      }
    }

    if ($image_url === '') {
      return NULL;
    }

    return [
      'url' => $image_url,
      'alt' => $alt,
    ];
  }

  /**
   * Gets image URL and alt text from the first image field on media.
   */
  protected function extractImageFromMedia(MediaInterface $media, array &$cache_tags): ?array {
    foreach ($media->getFieldDefinitions() as $field_name => $definition) {
      if ($definition->getType() !== 'image' || $media->get($field_name)->isEmpty()) {
        continue;
      }

      $image_item = $media->get($field_name)->first();
      if (!$image_item || !$image_item->entity) {
        continue;
      }

      $file = $image_item->entity;
      $cache_tags = Cache::mergeTags($cache_tags, $file->getCacheTags());

      return [
        'url' => \Drupal::service('file_url_generator')->generateString($file->getFileUri()),
        'alt' => trim((string) ($image_item->alt ?? '')),
      ];
    }

    return NULL;
  }

  /**
   * Extracts node target ID from entity autocomplete form values.
   */
  protected function extractNodeTargetId(mixed $value): int {
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

  /**
   * Gets media type IDs that use the video_file source plugin.
   */
  protected function getVideoMediaTypeIds(): array {
    $media_types = \Drupal::entityTypeManager()->getStorage('media_type')->loadMultiple();
    $video_ids = [];

    foreach ($media_types as $media_type) {
      if (!$media_type instanceof MediaTypeInterface) {
        continue;
      }

      if ($media_type->getSource()->getPluginId() === 'video_file') {
        $video_ids[] = $media_type->id();
      }
    }

    return !empty($video_ids) ? $video_ids : array_keys($media_types);
  }

  /**
   * Resolves video URL and MIME type from media or fallback URL.
   */
  protected function resolveVideoSource(int $media_id, string $fallback_url, array &$cache_tags): ?array {
    if ($media_id > 0) {
      $media = \Drupal::entityTypeManager()->getStorage('media')->load($media_id);
      if ($media instanceof MediaInterface) {
        $cache_tags = Cache::mergeTags($cache_tags, $media->getCacheTags());
        $video = $this->extractVideoFromMedia($media, $cache_tags);
        if ($video) {
          return $video;
        }
      }
    }

    $url = trim($fallback_url);
    if ($url !== '') {
      return ['url' => $url, 'mime' => 'video/mp4'];
    }

    return NULL;
  }

  /**
   * Gets video URL and MIME type from the first video file field on media.
   */
  protected function extractVideoFromMedia(MediaInterface $media, array &$cache_tags): ?array {
    foreach ($media->getFieldDefinitions() as $field_name => $definition) {
      if ($definition->getType() !== 'file' || $media->get($field_name)->isEmpty()) {
        continue;
      }

      $file_item = $media->get($field_name)->first();
      if (!$file_item || !$file_item->entity) {
        continue;
      }

      $file = $file_item->entity;
      $mime_type = (string) $file->getMimeType();
      if ($mime_type === '' || !str_starts_with($mime_type, 'video/')) {
        continue;
      }

      $cache_tags = Cache::mergeTags($cache_tags, $file->getCacheTags());
      return [
        'url' => \Drupal::service('file_url_generator')->generateString($file->getFileUri()),
        'mime' => $mime_type,
      ];
    }

    return NULL;
  }

  /**
   * Builds the shared byline form fields (authors, photo toggle, date).
   *
   * Reads defaults from the author_term_id, second_author_term_id,
   * show_image and publication_date configuration keys.
   */
  protected function bylineFormElements(bool $require_author): array {
    $elements = [];

    $author_term_id = (int) ($this->configuration['author_term_id'] ?? 0);
    $elements['author_term_id'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Author'),
      '#description' => $this->t('Select an author from the News authors taxonomy.'),
      '#target_type' => 'taxonomy_term',
      '#selection_settings' => ['target_bundles' => ['utnews_authors' => 'utnews_authors']],
      '#default_value' => $author_term_id > 0 ? $this->loadAuthorTerm($author_term_id) : NULL,
      '#tags' => FALSE,
      '#required' => $require_author,
    ];

    $second_author_term_id = (int) ($this->configuration['second_author_term_id'] ?? 0);
    $elements['second_author_term_id'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Second author'),
      '#description' => $this->t('Optional. Select a co-author from the News authors taxonomy.'),
      '#target_type' => 'taxonomy_term',
      '#selection_settings' => ['target_bundles' => ['utnews_authors' => 'utnews_authors']],
      '#default_value' => $second_author_term_id > 0 ? $this->loadAuthorTerm($second_author_term_id) : NULL,
      '#tags' => FALSE,
    ];

    $elements['show_image'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show author photo'),
      '#description' => $this->t('Displays each author’s image if one is set on their News authors term. Has no effect if a selected author has no image.'),
      '#default_value' => (bool) ($this->configuration['show_image'] ?? TRUE),
    ];

    $elements['publication_date'] = [
      '#type' => 'date',
      '#title' => $this->t('Publication date'),
      '#default_value' => $this->configuration['publication_date'] ?? '',
    ];

    return $elements;
  }

  /**
   * Saves the shared byline form values into configuration.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The block form state.
   * @param array $parents
   *   Parent keys of the byline elements within the block form, if nested.
   */
  protected function bylineFormSubmit(FormStateInterface $form_state, array $parents = []): void {
    $this->configuration['author_term_id'] = $this->extractNodeTargetId($form_state->getValue([...$parents, 'author_term_id']));
    $this->configuration['second_author_term_id'] = $this->extractNodeTargetId($form_state->getValue([...$parents, 'second_author_term_id']));
    $this->configuration['show_image'] = (bool) ($form_state->getValue([...$parents, 'show_image']) ?? TRUE);
    $this->configuration['publication_date'] = (string) ($form_state->getValue([...$parents, 'publication_date']) ?? '');
  }

  /**
   * Builds a byline render array from the shared byline configuration keys.
   *
   * Returns an empty array when no valid author is configured.
   */
  protected function buildByline(string $alignment, string $color_theme, bool $embedded, array &$cache_tags): array {
    $show_image = (bool) ($this->configuration['show_image'] ?? TRUE);

    $authors = [];
    foreach (['author_term_id', 'second_author_term_id'] as $config_key) {
      $term_id = (int) ($this->configuration[$config_key] ?? 0);
      if ($term_id <= 0) {
        continue;
      }

      $term = $this->loadAuthorTerm($term_id);
      if (!$term) {
        continue;
      }

      $cache_tags = Cache::mergeTags($cache_tags, $term->getCacheTags());
      $authors[] = [
        'name' => $term->label(),
        'url' => $term->toUrl()->toString(),
        'image_url' => $show_image ? $this->getAuthorImageUrl($term, $cache_tags) : '',
      ];
    }

    if (empty($authors)) {
      return [];
    }

    $raw_date = (string) ($this->configuration['publication_date'] ?? '');
    $date_object = $raw_date !== '' ? \DateTime::createFromFormat('Y-m-d', $raw_date) : FALSE;

    return [
      '#theme' => 'custom_flexpage_story_byline',
      '#authors' => $authors,
      '#publication_date' => $date_object ? $date_object->format('F j, Y') : '',
      '#publication_date_iso' => $date_object ? $date_object->format('Y-m-d') : '',
      '#alignment' => $alignment,
      '#color_theme' => $color_theme,
      '#embedded' => $embedded,
      '#attached' => [
        'library' => ['custom_flexpage_content/story_byline_block'],
      ],
    ];
  }

  /**
   * Loads a News authors taxonomy term by ID.
   */
  protected function loadAuthorTerm(int $term_id): ?TermInterface {
    $term = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($term_id);
    if ($term instanceof TermInterface && $term->bundle() === 'utnews_authors') {
      return $term;
    }
    return NULL;
  }

  /**
   * Gets the author photo URL from a News authors term, if set.
   */
  protected function getAuthorImageUrl(TermInterface $term, array &$cache_tags): string {
    if (!$term->hasField('field_utnews_author_image') || $term->get('field_utnews_author_image')->isEmpty()) {
      return '';
    }

    $media_id = (int) $term->get('field_utnews_author_image')->target_id;
    if ($media_id <= 0) {
      return '';
    }

    $media = \Drupal::entityTypeManager()->getStorage('media')->load($media_id);
    if (!$media instanceof MediaInterface) {
      return '';
    }

    $cache_tags = Cache::mergeTags($cache_tags, $media->getCacheTags());
    $image = $this->extractImageFromMedia($media, $cache_tags);

    return $image['url'] ?? '';
  }

}
