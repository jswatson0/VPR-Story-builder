<?php

namespace Drupal\custom_flexpage_content\Plugin\Block;

use Drupal\Core\Form\FormStateInterface;
use Drupal\node\NodeInterface;

/**
 * Provides a configurable storytelling catalog grid block.
 *
 * @Block(
 *   id = "custom_flexpage_story_grid_catalog_block",
 *   admin_label = @Translation("Story Grid Catalog"),
 *   category = @Translation("Visual Storytelling")
 * )
 */
class StoryGridCatalogBlock extends VisualStoryBlockBase {

  /**
   * Maximum configurable catalog items.
   */
  protected const MAX_ITEMS = 12;

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    $config = [
      'eyebrow' => '',
      'headline' => '',
      'intro_value' => '',
      'intro_format' => 'basic_html',
      'theme' => 'light',
      'desktop_columns' => '3',
      'gutter_size' => 'regular',
      'section_width' => 'wide',
      'card_surface' => 'filled',
      'corner_radius' => 'none',
    ];

    for ($i = 1; $i <= self::MAX_ITEMS; $i++) {
      $key = "item_{$i}";
      $config["{$key}_title"] = '';
      $config["{$key}_body_value"] = '';
      $config["{$key}_body_format"] = 'basic_html';
      $config["{$key}_media_id"] = 0;
      $config["{$key}_media_url"] = '';
      $config["{$key}_media_alt"] = '';
      $config["{$key}_cta_label"] = '';
      $config["{$key}_cta_url"] = '';
      $config["{$key}_cta_node_id"] = 0;
      $config["{$key}_column_span"] = '1';
      $config["{$key}_layout"] = 'stacked';
      $config["{$key}_image_ratio"] = 'landscape';
    }

    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state): array {
    $form = parent::blockForm($form, $form_state);

    $form['editor_help'] = [
      '#type' => 'details',
      '#title' => $this->t('How This Block Works'),
      '#open' => TRUE,
    ];
    $form['editor_help']['summary'] = [
      '#type' => 'item',
      '#markup' => $this->t('<p>This block renders a Shorthand-style catalog grid with visible cards instead of a modal gallery. Each card can include media, headline, summary copy, and an optional CTA.</p><p><strong>Recommended flow:</strong> 1) set section heading and intro, 2) choose columns, gutter, and card surface, 3) fill in cards, 4) use a larger column span when you want a row to have fewer cards or a featured split layout.</p><p><strong>Important:</strong> split layouts only apply when a card spans the full desktop row. Example: in a 3-column grid, the card must have a span of 3 to render as a side-by-side split card.</p>'),
    ];

    $form['eyebrow'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Eyebrow'),
      '#default_value' => $this->configuration['eyebrow'] ?? '',
      '#maxlength' => 255,
    ];

    $form['headline'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Section headline'),
      '#default_value' => $this->configuration['headline'] ?? '',
      '#maxlength' => 255,
    ];

    $form['intro'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Intro copy'),
      '#description' => $this->t('Optional short copy shown above the grid.'),
      '#default_value' => $this->configuration['intro_value'] ?? '',
      '#format' => $this->configuration['intro_format'] ?? filter_default_format(),
      '#rows' => 4,
    ];

    $form['theme'] = [
      '#type' => 'select',
      '#title' => $this->t('Color theme'),
      '#default_value' => $this->configuration['theme'] ?? 'light',
      '#options' => [
        'light' => $this->t('Light'),
        'dark' => $this->t('Dark'),
      ],
    ];

    $form['desktop_columns'] = [
      '#type' => 'select',
      '#title' => $this->t('Desktop columns'),
      '#description' => $this->t('Sets the maximum number of columns on wide screens. Items can span multiple columns to create varied rows. Split card layouts only activate when an item span matches this column count.'),
      '#default_value' => (string) ($this->configuration['desktop_columns'] ?? '3'),
      '#options' => [
        '2' => $this->t('2'),
        '3' => $this->t('3'),
        '4' => $this->t('4'),
      ],
    ];

    $form['gutter_size'] = [
      '#type' => 'select',
      '#title' => $this->t('Gutter size'),
      '#default_value' => $this->configuration['gutter_size'] ?? 'regular',
      '#options' => [
        'compact' => $this->t('Compact'),
        'regular' => $this->t('Regular'),
        'relaxed' => $this->t('Relaxed'),
      ],
    ];

    $form['section_width'] = [
      '#type' => 'select',
      '#title' => $this->t('Grid width'),
      '#default_value' => $this->configuration['section_width'] ?? 'wide',
      '#options' => [
        'narrow' => $this->t('Narrow'),
        'standard' => $this->t('Standard'),
        'wide' => $this->t('Wide'),
      ],
    ];

    $form['card_surface'] = [
      '#type' => 'select',
      '#title' => $this->t('Card surface'),
      '#description' => $this->t('Controls the card background and border treatment.'),
      '#default_value' => $this->configuration['card_surface'] ?? 'filled',
      '#options' => [
        'plain' => $this->t('Plain'),
        'bordered' => $this->t('Bordered'),
        'filled' => $this->t('Filled'),
      ],
    ];

    $form['corner_radius'] = [
      '#type' => 'select',
      '#title' => $this->t('Corner radius'),
      '#description' => $this->t('Applies to the card border only. Images remain square inside the card.'),
      '#default_value' => $this->configuration['corner_radius'] ?? 'none',
      '#options' => [
        'none' => $this->t('None'),
        'small' => $this->t('Small'),
        'medium' => $this->t('Medium'),
        'large' => $this->t('Large'),
        'pill' => $this->t('Extra large'),
      ],
    ];

    for ($i = 1; $i <= self::MAX_ITEMS; $i++) {
      $key = "item_{$i}";
      $form[$key] = [
        '#type' => 'details',
        '#title' => $this->t('Catalog item @num', ['@num' => $i]),
        '#open' => $i <= 4,
      ];

      $form[$key]["{$key}_title"] = [
        '#type' => 'textfield',
        '#title' => $this->t('Title'),
        '#description' => $this->t('Leave title, copy, and media empty to hide this item.'),
        '#default_value' => $this->configuration["{$key}_title"] ?? '',
        '#maxlength' => 255,
      ];

      $media_id = (int) ($this->configuration["{$key}_media_id"] ?? 0);
      $form[$key]["{$key}_media_id"] = [
        '#type' => 'media_library',
        '#title' => $this->t('Card image (preferred)'),
        '#allowed_bundles' => $this->getImageMediaTypeIds(),
        '#default_value' => $media_id > 0 ? $media_id : NULL,
        '#cardinality' => 1,
      ];

      $form[$key]["{$key}_media_url"] = [
        '#type' => 'textfield',
        '#title' => $this->t('Card image URL (fallback)'),
        '#default_value' => $this->configuration["{$key}_media_url"] ?? '',
        '#maxlength' => 2048,
      ];

      $form[$key]["{$key}_media_alt"] = [
        '#type' => 'textfield',
        '#title' => $this->t('Card image alt text'),
        '#default_value' => $this->configuration["{$key}_media_alt"] ?? '',
        '#maxlength' => 255,
      ];

      $form[$key]["{$key}_body"] = [
        '#type' => 'text_format',
        '#title' => $this->t('Summary copy'),
        '#default_value' => $this->configuration["{$key}_body_value"] ?? '',
        '#format' => $this->configuration["{$key}_body_format"] ?? filter_default_format(),
        '#rows' => 4,
      ];

      $form[$key]["{$key}_cta_label"] = [
        '#type' => 'textfield',
        '#title' => $this->t('CTA label'),
        '#default_value' => $this->configuration["{$key}_cta_label"] ?? '',
        '#maxlength' => 255,
      ];

      $form[$key]["{$key}_cta_node_id"] = [
        '#type' => 'entity_autocomplete',
        '#title' => $this->t('CTA internal page'),
        '#target_type' => 'node',
        '#selection_settings' => [
          'target_bundles' => NULL,
        ],
        '#default_value' => $this->loadNodeFromConfig("{$key}_cta_node_id"),
        '#tags' => FALSE,
      ];

      $form[$key]["{$key}_cta_url"] = [
        '#type' => 'url',
        '#title' => $this->t('CTA external URL'),
        '#default_value' => $this->configuration["{$key}_cta_url"] ?? '',
      ];

      $form[$key]["{$key}_column_span"] = [
        '#type' => 'select',
        '#title' => $this->t('Desktop column span'),
        '#description' => $this->t('Use larger spans to create rows with fewer items or a featured card. To enable a split layout, set this to the full desktop column count for the section.'),
        '#default_value' => (string) ($this->configuration["{$key}_column_span"] ?? '1'),
        '#options' => [
          '1' => $this->t('1 column'),
          '2' => $this->t('2 columns'),
          '3' => $this->t('3 columns'),
          '4' => $this->t('4 columns'),
        ],
      ];

      $form[$key]["{$key}_layout"] = [
        '#type' => 'select',
        '#title' => $this->t('Card layout'),
        '#description' => $this->t('Stacked and overlay work at any span. Split layouts only render when this card spans the full desktop row. Example: use span 3 in a 3-column grid or span 4 in a 4-column grid.'),
        '#default_value' => $this->configuration["{$key}_layout"] ?? 'stacked',
        '#options' => [
          'stacked' => $this->t('Stacked'),
          'overlay' => $this->t('Overlay: full background image'),
          'split-media-left' => $this->t('Split: media left'),
          'split-media-right' => $this->t('Split: media right'),
        ],
      ];

      $form[$key]["{$key}_image_ratio"] = [
        '#type' => 'select',
        '#title' => $this->t('Media crop'),
        '#description' => $this->t('Controls how this card image is cropped. Natural preserves the original image ratio.'),
        '#default_value' => $this->configuration["{$key}_image_ratio"] ?? 'landscape',
        '#options' => [
          'landscape' => $this->t('Landscape'),
          'square' => $this->t('Square'),
          'portrait' => $this->t('Portrait'),
          'natural' => $this->t('Natural / uncropped'),
        ],
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state): void {
    parent::blockSubmit($form, $form_state);

    $intro = $form_state->getValue('intro', []);

    $this->configuration['eyebrow'] = trim((string) $form_state->getValue('eyebrow', ''));
    $this->configuration['headline'] = trim((string) $form_state->getValue('headline', ''));
    $this->configuration['intro_value'] = trim((string) ($intro['value'] ?? ''));
    $this->configuration['intro_format'] = (string) ($intro['format'] ?? filter_default_format());
    $this->configuration['theme'] = (string) $form_state->getValue('theme', 'light');
    $this->configuration['desktop_columns'] = (string) $form_state->getValue('desktop_columns', '3');
    $this->configuration['gutter_size'] = (string) $form_state->getValue('gutter_size', 'regular');
    $this->configuration['section_width'] = (string) $form_state->getValue('section_width', 'wide');
    $this->configuration['card_surface'] = (string) $form_state->getValue('card_surface', 'filled');
    $this->configuration['corner_radius'] = (string) $form_state->getValue('corner_radius', 'none');

    for ($i = 1; $i <= self::MAX_ITEMS; $i++) {
      $key = "item_{$i}";
      $item_values = $form_state->getValue($key, []);
      $body = $item_values["{$key}_body"] ?? [];

      $this->configuration["{$key}_title"] = trim((string) ($item_values["{$key}_title"] ?? ''));
      $this->configuration["{$key}_body_value"] = trim((string) ($body['value'] ?? ''));
      $this->configuration["{$key}_body_format"] = (string) ($body['format'] ?? filter_default_format());
      $this->configuration["{$key}_media_id"] = $this->extractMediaTargetId($item_values["{$key}_media_id"] ?? NULL);
      $this->configuration["{$key}_media_url"] = trim((string) ($item_values["{$key}_media_url"] ?? ''));
      $this->configuration["{$key}_media_alt"] = trim((string) ($item_values["{$key}_media_alt"] ?? ''));
      $this->configuration["{$key}_cta_label"] = trim((string) ($item_values["{$key}_cta_label"] ?? ''));
      $this->configuration["{$key}_cta_node_id"] = $this->extractNodeTargetId($item_values["{$key}_cta_node_id"] ?? NULL);
      $this->configuration["{$key}_cta_url"] = trim((string) ($item_values["{$key}_cta_url"] ?? ''));
      $this->configuration["{$key}_column_span"] = (string) $item_values["{$key}_column_span"] ?? '1';
      $this->configuration["{$key}_layout"] = (string) $item_values["{$key}_layout"] ?? 'stacked';
      $this->configuration["{$key}_image_ratio"] = (string) $item_values["{$key}_image_ratio"] ?? 'landscape';
    }
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $cache_tags = [];
    $desktop_columns = max(2, min(4, (int) ($this->configuration['desktop_columns'] ?? 3)));
    $items = [];

    for ($i = 1; $i <= self::MAX_ITEMS; $i++) {
      $key = "item_{$i}";
      $item = $this->buildItem($key, $desktop_columns, $cache_tags);
      if ($item !== NULL) {
        $items[] = $item;
      }
    }

    if (empty($items)) {
      return [];
    }

    return [
      '#theme' => 'custom_flexpage_story_grid_catalog',
      '#eyebrow' => $this->configuration['eyebrow'] ?? '',
      '#headline' => $this->configuration['headline'] ?? '',
      '#intro' => [
        '#type' => 'processed_text',
        '#text' => $this->configuration['intro_value'] ?? '',
        '#format' => $this->configuration['intro_format'] ?? filter_default_format(),
      ],
      '#items' => $items,
      '#desktop_columns' => $desktop_columns,
      '#gutter_size' => $this->configuration['gutter_size'] ?? 'regular',
      '#section_width' => $this->configuration['section_width'] ?? 'wide',
      '#card_surface' => $this->configuration['card_surface'] ?? 'filled',
      '#corner_radius' => $this->configuration['corner_radius'] ?? 'none',
      '#color_theme' => $this->configuration['theme'] ?? 'light',
      '#attached' => [
        'library' => ['custom_flexpage_content/story_grid_catalog_block'],
      ],
      '#cache' => [
        'tags' => array_values(array_unique($cache_tags)),
      ],
    ];
  }

  /**
   * Builds one catalog item.
   */
  protected function buildItem(string $key, int $desktop_columns, array &$cache_tags): ?array {
    $title = trim((string) ($this->configuration["{$key}_title"] ?? ''));
    $body_value = (string) ($this->configuration["{$key}_body_value"] ?? '');
    $body_format = (string) ($this->configuration["{$key}_body_format"] ?? filter_default_format());
    $image = $this->resolveImageSource(
      (int) ($this->configuration["{$key}_media_id"] ?? 0),
      (string) ($this->configuration["{$key}_media_url"] ?? ''),
      (string) ($this->configuration["{$key}_media_alt"] ?? ''),
      $cache_tags
    );

    if ($title === '' && trim(strip_tags($body_value)) === '' && !$image) {
      return NULL;
    }

    $cta_label = trim((string) ($this->configuration["{$key}_cta_label"] ?? ''));
    $cta_url = $this->resolveCtaUrl(
      (int) ($this->configuration["{$key}_cta_node_id"] ?? 0),
      (string) ($this->configuration["{$key}_cta_url"] ?? ''),
      $cache_tags
    );
    $column_span = max(1, min($desktop_columns, (int) ($this->configuration["{$key}_column_span"] ?? 1)));
    $layout = (string) ($this->configuration["{$key}_layout"] ?? 'stacked');
    $is_featured = $column_span >= $desktop_columns;

    if (!$is_featured && in_array($layout, ['split-media-left', 'split-media-right'], TRUE)) {
      $layout = 'stacked';
    }

    return [
      'id' => $key,
      'title' => $title,
      'body' => [
        '#type' => 'processed_text',
        '#text' => $body_value,
        '#format' => $body_format,
      ],
      'media_url' => $image['url'] ?? '',
      'media_alt' => $image['alt'] ?? '',
      'cta_label' => $cta_label,
      'cta_url' => $cta_url,
      'column_span' => $column_span,
      'layout' => $layout,
      'image_ratio' => (string) ($this->configuration["{$key}_image_ratio"] ?? 'landscape'),
      'is_featured' => $is_featured,
      'has_media' => !empty($image['url']),
    ];
  }

  /**
   * Resolves a CTA URL from internal node selection or external URL.
   */
  protected function resolveCtaUrl(int $node_id, string $fallback_url, array &$cache_tags): string {
    $cta_url = trim($fallback_url);
    if ($node_id <= 0) {
      return $cta_url;
    }

    $node = \Drupal::entityTypeManager()->getStorage('node')->load($node_id);
    if ($node instanceof NodeInterface && $node->access('view')) {
      $cache_tags = array_merge($cache_tags, $node->getCacheTags());
      return $node->toUrl()->toString();
    }

    return $cta_url;
  }

  /**
   * Loads a configured node entity for autocomplete defaults.
   */
  protected function loadNodeFromConfig(string $config_key): ?NodeInterface {
    $node_id = (int) ($this->configuration[$config_key] ?? 0);
    if ($node_id <= 0) {
      return NULL;
    }

    $node = \Drupal::entityTypeManager()->getStorage('node')->load($node_id);
    return $node instanceof NodeInterface ? $node : NULL;
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
