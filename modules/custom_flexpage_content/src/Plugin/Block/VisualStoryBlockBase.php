<?php

namespace Drupal\custom_flexpage_content\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\media\MediaInterface;
use Drupal\media\MediaTypeInterface;

/**
 * Shared helpers for visual storytelling block plugins.
 */
abstract class VisualStoryBlockBase extends BlockBase {

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

}

