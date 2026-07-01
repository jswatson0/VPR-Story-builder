<?php

namespace Drupal\custom_flexpage_content\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\FormStateInterface;
use Drupal\media\MediaInterface;
use Drupal\media\MediaTypeInterface;

/**
 * Provides a configurable image grid block.
 *
 * @Block(
 *   id = "research_image_grid_block",
 *   admin_label = @Translation("Photo Grid"),
 *   category = @Translation("Research")
 * )
 */
class ResearchImageGridBlock extends BlockBase {

  /**
   * Maximum configurable grid items.
   */
  private const MAX_ITEMS = 12;

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'modal_title_default' => 'Microscopy image',
      'images_per_row' => 3,
      'items' => [
        [
          'title' => '',
          'media_id' => 0,
          'image_url' => '',
          'alt_text' => '',
          'description' => '',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state): array {
    $form = parent::blockForm($form, $form_state);

    $configured_items = $this->configuration['items'] ?? [];

    $form['modal_title_default'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default modal title'),
      '#default_value' => $this->configuration['modal_title_default'] ?? 'Microscopy image',
      '#maxlength' => 255,
    ];

    $form['images_per_row'] = [
      '#type' => 'select',
      '#title' => $this->t('Images per row'),
      '#default_value' => (int) ($this->configuration['images_per_row'] ?? 3),
      '#options' => [
        1 => $this->t('1'),
        2 => $this->t('2'),
        3 => $this->t('3'),
        4 => $this->t('4'),
      ],
      '#description' => $this->t('Choose how many images are shown per row.'),
      '#required' => TRUE,
    ];

    $form['items_help'] = [
      '#type' => 'item',
      '#markup' => $this->t('Configure up to @count images. Choose Media where possible. Rows without a media selection or image URL are ignored.', ['@count' => self::MAX_ITEMS]),
    ];

    for ($index = 0; $index < self::MAX_ITEMS; $index++) {
      $item = $configured_items[$index] ?? [];
      $item_number = $index + 1;

      $form['items'][$index] = [
        '#type' => 'details',
        '#title' => $this->t('Grid item @number', ['@number' => $item_number]),
        '#open' => $index < 3,
        '#tree' => TRUE,
      ];

      $form['items'][$index]['title'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Title'),
        '#default_value' => $item['title'] ?? '',
        '#maxlength' => 255,
      ];

      $media_id = (int) ($item['media_id'] ?? 0);
      $form['items'][$index]['media_id'] = [
        '#type' => 'media_library',
        '#title' => $this->t('Media image (preferred)'),
        '#allowed_bundles' => $this->getImageMediaTypeIds(),
        '#default_value' => $media_id > 0 ? $media_id : NULL,
        '#description' => $this->t('Select an image from Media Library.'),
        '#cardinality' => 1,
      ];

      $form['items'][$index]['image_url'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Or enter a direct image URL'),
        '#description' => $this->t('Skip if you selected from Media Library above.'),
        '#default_value' => $item['image_url'] ?? '',
        '#maxlength' => 2048,
        '#wrapper_attributes' => ['class' => ['vs-field-fallback']],
      ];

      $form['items'][$index]['alt_text'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Image alt text'),
        '#description' => $this->t('If left blank, title is used.'),
        '#default_value' => $item['alt_text'] ?? '',
        '#maxlength' => 255,
      ];

      $form['items'][$index]['description'] = [
        '#type' => 'text_format',
        '#title' => $this->t('Modal description'),
        '#default_value' => $this->getDescriptionValue($item),
        '#format' => $this->getDescriptionFormat($item),
        '#rows' => 3,
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state): void {
    parent::blockSubmit($form, $form_state);

    $this->configuration['modal_title_default'] = trim((string) $form_state->getValue('modal_title_default', 'Microscopy image'));
    $images_per_row = (int) $form_state->getValue('images_per_row', 3);
    $this->configuration['images_per_row'] = min(4, max(1, $images_per_row));

    $submitted_items = $form_state->getValue('items', []);
    $clean_items = [];

    foreach ($submitted_items as $item) {
      if (!is_array($item)) {
        continue;
      }

      $media_id = $this->extractMediaTargetId($item['media_id'] ?? NULL);
      $image_url = trim((string) ($item['image_url'] ?? ''));
      if ($media_id === 0 && $image_url === '') {
        continue;
      }

      $clean_items[] = [
        'title' => trim((string) ($item['title'] ?? '')),
        'media_id' => $media_id,
        'image_url' => $image_url,
        'alt_text' => trim((string) ($item['alt_text'] ?? '')),
        'description' => [
          'value' => $this->getDescriptionValue($item),
          'format' => $this->getDescriptionFormat($item),
        ],
      ];
    }

    $this->configuration['items'] = $clean_items;
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $configured_items = $this->configuration['items'] ?? [];
    $items = [];
    $cache_tags = [];

    foreach ($configured_items as $item) {
      $resolved_item = $this->resolveItem($item, $cache_tags);
      if ($resolved_item) {
        $items[] = $resolved_item;
      }
    }

    if (empty($items)) {
      return [];
    }

    return [
      '#theme' => 'research_image_grid_block',
      '#items' => $items,
      '#modal_title_default' => $this->configuration['modal_title_default'] ?? 'Microscopy image',
      '#images_per_row' => min(4, max(1, (int) ($this->configuration['images_per_row'] ?? 3))),
      '#attached' => [
        'library' => ['custom_flexpage_content/research_image_grid_block'],
      ],
      '#cache' => [
        'tags' => $cache_tags,
      ],
    ];
  }

  /**
   * Resolves a configured item to renderable values.
   */
  private function resolveItem(array $item, array &$cache_tags): ?array {
    $title = trim((string) ($item['title'] ?? ''));
    $image_url = trim((string) ($item['image_url'] ?? ''));
    $alt_text = trim((string) ($item['alt_text'] ?? ''));
    $description_value = $this->getDescriptionValue($item);
    $description_format = $this->getDescriptionFormat($item);
    $media_id = (int) ($item['media_id'] ?? 0);

    if ($media_id > 0) {
      $media = \Drupal::entityTypeManager()->getStorage('media')->load($media_id);
      if ($media instanceof MediaInterface) {
        $cache_tags = Cache::mergeTags($cache_tags, $media->getCacheTags());
        $media_image = $this->extractImageFromMedia($media, $cache_tags);
        if ($media_image) {
          $image_url = $media_image['url'];
          if ($alt_text === '' && $media_image['alt'] !== '') {
            $alt_text = $media_image['alt'];
          }
          if ($title === '') {
            $title = $media->label();
          }
        }
      }
    }

    if ($image_url === '') {
      return NULL;
    }

    if ($alt_text === '') {
      $alt_text = $title;
    }

    return [
      'title' => $title,
      'image_url' => $image_url,
      'alt_text' => $alt_text,
      'description_markup' => [
        '#type' => 'processed_text',
        '#text' => $description_value,
        '#format' => $description_format,
      ],
    ];
  }

  /**
   * Gets image URL and alt text from the first image field on media.
   */
  private function extractImageFromMedia(MediaInterface $media, array &$cache_tags): ?array {
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
      $url = \Drupal::service('file_url_generator')->generateString($file->getFileUri());
      $alt = trim((string) ($image_item->alt ?? ''));

      return [
        'url' => $url,
        'alt' => $alt,
      ];
    }

    return NULL;
  }

  /**
   * Extracts target ID from entity autocomplete values.
   */
  private function extractMediaTargetId($value): int {
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
   * Gets media type IDs that use the image source plugin.
   */
  private function getImageMediaTypeIds(): array {
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

    if (!empty($image_type_ids)) {
      return $image_type_ids;
    }

    // Fallback: allow all media types if no image type was detected.
    return array_keys($media_types);
  }

  /**
   * Gets description text value with backward compatibility.
   */
  private function getDescriptionValue(array $item): string {
    if (isset($item['description']) && is_array($item['description'])) {
      return trim((string) ($item['description']['value'] ?? ''));
    }

    return trim((string) ($item['description'] ?? ''));
  }

  /**
   * Gets description text format with fallback.
   */
  private function getDescriptionFormat(array $item): string {
    if (isset($item['description']) && is_array($item['description'])) {
      $format = (string) ($item['description']['format'] ?? '');
      if ($format !== '') {
        return $format;
      }
    }

    $default_format = \Drupal::config('filter.settings')->get('fallback_format');
    return is_string($default_format) && $default_format !== '' ? $default_format : 'basic_html';
  }

}
