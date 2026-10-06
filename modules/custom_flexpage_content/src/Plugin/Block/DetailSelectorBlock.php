<?php

namespace Drupal\custom_flexpage_content\Plugin\Block;

use Drupal\Core\Form\FormStateInterface;
use Drupal\node\NodeInterface;

/**
 * Provides a two-column detail selector storytelling block.
 *
 * @Block(
 *   id = "custom_flexpage_detail_selector_block",
 *   admin_label = @Translation("Interactive Panels"),
 *   category = @Translation("Visual Storytelling")
 * )
 */
class DetailSelectorBlock extends VisualStoryBlockBase {

  /**
   * Maximum configurable selector items.
   */
  protected const MAX_ITEMS = 6;

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    $config = [
      'eyebrow' => '',
      'headline' => '',
      'intro_value' => '',
      'intro_format' => 'basic_html',
      'default_panel_label' => 'Overview',
      'default_headline' => 'Research Centers',
      'default_body_value' => '<p>Select a center from the list to view its details, image, and call to action.</p>',
      'default_body_format' => 'basic_html',
      'default_media_id' => 0,
      'default_media_url' => '',
      'default_media_alt' => '',
      'default_cta_label' => '',
      'default_cta_url' => '',
      'default_cta_node_id' => 0,
      'heading_position' => 'left',
      'theme' => 'light',
    ];

    for ($i = 1; $i <= self::MAX_ITEMS; $i++) {
      $config["item_{$i}_heading"] = '';
      $config["item_{$i}_headline"] = '';
      $config["item_{$i}_body_value"] = '';
      $config["item_{$i}_body_format"] = 'basic_html';
      $config["item_{$i}_media_id"] = 0;
      $config["item_{$i}_media_url"] = '';
      $config["item_{$i}_media_alt"] = '';
      $config["item_{$i}_cta_label"] = '';
      $config["item_{$i}_cta_url"] = '';
      $config["item_{$i}_cta_node_id"] = 0;
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
      '#open' => FALSE,
    ];
    $form['editor_help']['summary'] = [
      '#type' => 'item',
      '#markup' => $this->t('<p>This block renders a two-column selector. The left column lists clickable headings. The right column starts with default content, then swaps in the matching detail panel when a heading is selected.</p><p><strong>Recommended flow:</strong> 1) write the default panel, 2) add each heading and detail panel, 3) attach optional image and CTA for the entries that need them.</p><p>Leave an item heading empty to hide that row entirely.</p>'),
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
      '#description' => $this->t('Optional short copy above the two-column selector.'),
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

    $form['heading_position'] = [
      '#type' => 'select',
      '#title' => $this->t('Heading column position'),
      '#description' => $this->t('Choose whether the selectable heading list appears on the left or right side of the detail panel.'),
      '#default_value' => $this->configuration['heading_position'] ?? 'left',
      '#options' => [
        'left' => $this->t('Left'),
        'right' => $this->t('Right'),
      ],
    ];

    $form['default_panel'] = [
      '#type' => 'details',
      '#title' => $this->t('Default detail panel'),
      '#open' => FALSE,
    ];

    $form['default_panel']['default_panel_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default panel label'),
      '#description' => $this->t('Internal label for the starting state. Used for accessibility and reset behavior.'),
      '#default_value' => $this->configuration['default_panel_label'] ?? 'Overview',
      '#maxlength' => 255,
    ];

    $form['default_panel']['default_headline'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default panel headline'),
      '#default_value' => $this->configuration['default_headline'] ?? '',
      '#maxlength' => 255,
    ];

    $form['default_panel']['default_body'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Default panel body'),
      '#default_value' => $this->configuration['default_body_value'] ?? '',
      '#format' => $this->configuration['default_body_format'] ?? filter_default_format(),
      '#rows' => 5,
    ];

    $default_media_id = (int) ($this->configuration['default_media_id'] ?? 0);
    $form['default_panel']['default_media_id'] = [
      '#type' => 'media_library',
      '#title' => $this->t('Default panel image (optional)'),
      '#allowed_bundles' => $this->getImageMediaTypeIds(),
      '#default_value' => $default_media_id > 0 ? $default_media_id : NULL,
      '#cardinality' => 1,
    ];

    $form['default_panel']['default_media_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default panel image URL (fallback)'),
      '#default_value' => $this->configuration['default_media_url'] ?? '',
      '#maxlength' => 2048,
    ];

    $form['default_panel']['default_media_alt'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default panel image alt text'),
      '#description' => $this->mediaAltDescription(),
      '#default_value' => $this->configuration['default_media_alt'] ?? '',
      '#maxlength' => 255,
    ];

    $form['default_panel']['default_cta_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default panel CTA label'),
      '#default_value' => $this->configuration['default_cta_label'] ?? '',
      '#maxlength' => 255,
    ];

    $default_cta_node = $this->loadNodeFromConfig('default_cta_node_id');
    $form['default_panel']['default_cta_node_id'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Default panel CTA internal page'),
      '#target_type' => 'node',
      '#selection_settings' => [
        'target_bundles' => NULL,
      ],
      '#default_value' => $default_cta_node,
      '#tags' => FALSE,
    ];

    $form['default_panel']['default_cta_url'] = [
      '#type' => 'url',
      '#title' => $this->t('Or enter an external URL'),
      '#description' => $this->t('For links outside this site only. Leave empty if you selected a page above.'),
      '#default_value' => $this->configuration['default_cta_url'] ?? '',
      '#wrapper_attributes' => ['class' => ['vs-field-fallback']],
    ];

    for ($i = 1; $i <= self::MAX_ITEMS; $i++) {
      $key = "item_{$i}";
      $fieldset = [
        '#type' => 'details',
        '#title' => $this->t('Selector item @num', ['@num' => $i]),
        '#open' => $i <= 3,
      ];

      $fieldset["{$key}_heading"] = [
        '#type' => 'textfield',
        '#title' => $this->t('Heading label'),
        '#description' => $this->t('Shown in the left column. Leave blank to hide this item.'),
        '#default_value' => $this->configuration["{$key}_heading"] ?? '',
        '#maxlength' => 255,
      ];

      $fieldset["{$key}_headline"] = [
        '#type' => 'textfield',
        '#title' => $this->t('Detail panel headline'),
        '#description' => $this->t('Optional headline for the right column. Leave blank to show no headline.'),
        '#default_value' => $this->configuration["{$key}_headline"] ?? '',
        '#maxlength' => 255,
      ];

      $fieldset["{$key}_body"] = [
        '#type' => 'text_format',
        '#title' => $this->t('Detail panel body'),
        '#default_value' => $this->configuration["{$key}_body_value"] ?? '',
        '#format' => $this->configuration["{$key}_body_format"] ?? filter_default_format(),
        '#rows' => 5,
      ];

      $media_id = (int) ($this->configuration["{$key}_media_id"] ?? 0);
      $fieldset["{$key}_media_id"] = [
        '#type' => 'media_library',
        '#title' => $this->t('Detail panel image (optional)'),
        '#allowed_bundles' => $this->getImageMediaTypeIds(),
        '#default_value' => $media_id > 0 ? $media_id : NULL,
        '#cardinality' => 1,
      ];

      $fieldset["{$key}_media_url"] = [
        '#type' => 'textfield',
        '#title' => $this->t('Detail panel image URL (fallback)'),
        '#default_value' => $this->configuration["{$key}_media_url"] ?? '',
        '#maxlength' => 2048,
      ];

      $fieldset["{$key}_media_alt"] = [
        '#type' => 'textfield',
        '#title' => $this->t('Detail panel image alt text'),
        '#description' => $this->mediaAltDescription(),
        '#default_value' => $this->configuration["{$key}_media_alt"] ?? '',
        '#maxlength' => 255,
      ];

      $fieldset["{$key}_cta_label"] = [
        '#type' => 'textfield',
        '#title' => $this->t('Detail panel CTA label'),
        '#default_value' => $this->configuration["{$key}_cta_label"] ?? '',
        '#maxlength' => 255,
      ];

      $fieldset["{$key}_cta_node_id"] = [
        '#type' => 'entity_autocomplete',
        '#title' => $this->t('Detail panel CTA internal page'),
        '#target_type' => 'node',
        '#selection_settings' => [
          'target_bundles' => NULL,
        ],
        '#default_value' => $this->loadNodeFromConfig("{$key}_cta_node_id"),
        '#tags' => FALSE,
      ];

      $fieldset["{$key}_cta_url"] = [
        '#type' => 'url',
        '#title' => $this->t('Or enter an external URL'),
        '#description' => $this->t('For links outside this site only. Leave empty if you selected a page above.'),
        '#default_value' => $this->configuration["{$key}_cta_url"] ?? '',
        '#wrapper_attributes' => ['class' => ['vs-field-fallback']],
      ];

      $form[$key] = $fieldset;
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state): void {
    parent::blockSubmit($form, $form_state);

    $intro = $form_state->getValue('intro', []);
    $default_panel_values = $form_state->getValue('default_panel', []);
    $default_body = $default_panel_values['default_body'] ?? [];

    $this->configuration['eyebrow'] = trim((string) $form_state->getValue('eyebrow', ''));
    $this->configuration['headline'] = trim((string) $form_state->getValue('headline', ''));
    $this->configuration['intro_value'] = trim((string) ($intro['value'] ?? ''));
    $this->configuration['intro_format'] = (string) ($intro['format'] ?? filter_default_format());
    $this->configuration['heading_position'] = (string) $form_state->getValue('heading_position', 'left');
    $this->configuration['theme'] = (string) $form_state->getValue('theme', 'light');
    $this->configuration['default_panel_label'] = trim((string) ($default_panel_values['default_panel_label'] ?? 'Overview'));
    $this->configuration['default_headline'] = trim((string) ($default_panel_values['default_headline'] ?? ''));
    $this->configuration['default_body_value'] = trim((string) ($default_body['value'] ?? ''));
    $this->configuration['default_body_format'] = (string) ($default_body['format'] ?? filter_default_format());
    $this->configuration['default_media_id'] = $this->extractMediaTargetId($default_panel_values['default_media_id'] ?? NULL);
    $this->configuration['default_media_url'] = trim((string) ($default_panel_values['default_media_url'] ?? ''));
    $this->configuration['default_media_alt'] = trim((string) ($default_panel_values['default_media_alt'] ?? ''));
    $this->configuration['default_cta_label'] = trim((string) ($default_panel_values['default_cta_label'] ?? ''));
    $this->configuration['default_cta_node_id'] = $this->extractNodeTargetId($default_panel_values['default_cta_node_id'] ?? NULL);
    $this->configuration['default_cta_url'] = trim((string) ($default_panel_values['default_cta_url'] ?? ''));

    for ($i = 1; $i <= self::MAX_ITEMS; $i++) {
      $key = "item_{$i}";
      $item_values = $form_state->getValue($key, []);
      $body = $item_values["{$key}_body"] ?? [];

      $this->configuration["{$key}_heading"] = trim((string) ($item_values["{$key}_heading"] ?? ''));
      $this->configuration["{$key}_headline"] = trim((string) ($item_values["{$key}_headline"] ?? ''));
      $this->configuration["{$key}_body_value"] = trim((string) ($body['value'] ?? ''));
      $this->configuration["{$key}_body_format"] = (string) ($body['format'] ?? filter_default_format());
      $this->configuration["{$key}_media_id"] = $this->extractMediaTargetId($item_values["{$key}_media_id"] ?? NULL);
      $this->configuration["{$key}_media_url"] = trim((string) ($item_values["{$key}_media_url"] ?? ''));
      $this->configuration["{$key}_media_alt"] = trim((string) ($item_values["{$key}_media_alt"] ?? ''));
      $this->configuration["{$key}_cta_label"] = trim((string) ($item_values["{$key}_cta_label"] ?? ''));
      $this->configuration["{$key}_cta_node_id"] = $this->extractNodeTargetId($item_values["{$key}_cta_node_id"] ?? NULL);
      $this->configuration["{$key}_cta_url"] = trim((string) ($item_values["{$key}_cta_url"] ?? ''));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $cache_tags = [];
    $default_panel = $this->buildPanel(
      'default',
      (string) ($this->configuration['default_headline'] ?? ''),
      (string) ($this->configuration['default_body_value'] ?? ''),
      (string) ($this->configuration['default_body_format'] ?? filter_default_format()),
      (int) ($this->configuration['default_media_id'] ?? 0),
      (string) ($this->configuration['default_media_url'] ?? ''),
      (string) ($this->configuration['default_media_alt'] ?? ''),
      (string) ($this->configuration['default_cta_label'] ?? ''),
      (int) ($this->configuration['default_cta_node_id'] ?? 0),
      (string) ($this->configuration['default_cta_url'] ?? ''),
      $cache_tags
    );

    $items = [];
    for ($i = 1; $i <= self::MAX_ITEMS; $i++) {
      $key = "item_{$i}";
      $heading = trim((string) ($this->configuration["{$key}_heading"] ?? ''));
      if ($heading === '') {
        continue;
      }

      $panel = $this->buildPanel(
        $key,
        (string) ($this->configuration["{$key}_headline"] ?? ''),
        (string) ($this->configuration["{$key}_body_value"] ?? ''),
        (string) ($this->configuration["{$key}_body_format"] ?? filter_default_format()),
        (int) ($this->configuration["{$key}_media_id"] ?? 0),
        (string) ($this->configuration["{$key}_media_url"] ?? ''),
        (string) ($this->configuration["{$key}_media_alt"] ?? ''),
        (string) ($this->configuration["{$key}_cta_label"] ?? ''),
        (int) ($this->configuration["{$key}_cta_node_id"] ?? 0),
        (string) ($this->configuration["{$key}_cta_url"] ?? ''),
        $cache_tags
      );

      $items[] = [
        'id' => $key,
        'heading' => $heading,
        'panel' => $panel,
      ];
    }

    if (empty($items)) {
      return [];
    }

    return [
      '#theme' => 'custom_flexpage_detail_selector',
      '#eyebrow' => $this->configuration['eyebrow'] ?? '',
      '#headline' => $this->configuration['headline'] ?? '',
      '#intro' => [
        '#type' => 'processed_text',
        '#text' => $this->configuration['intro_value'] ?? '',
        '#format' => $this->configuration['intro_format'] ?? filter_default_format(),
      ],
      '#default_panel_label' => $this->configuration['default_panel_label'] ?? 'Overview',
      '#default_panel' => $default_panel,
      '#items' => $items,
      '#heading_position' => $this->configuration['heading_position'] ?? 'left',
      '#color_theme' => $this->configuration['theme'] ?? 'light',
      '#attached' => [
        'library' => ['custom_flexpage_content/detail_selector_block'],
      ],
      '#cache' => [
        'tags' => array_values(array_unique($cache_tags)),
      ],
    ];
  }

  /**
   * Builds one right-column panel payload.
   */
  protected function buildPanel(
    string $id,
    string $headline,
    string $body_value,
    string $body_format,
    int $media_id,
    string $media_url,
    string $media_alt,
    string $cta_label,
    int $cta_node_id,
    string $cta_url,
    array &$cache_tags
  ): array {
    $image = $this->resolveImageSource($media_id, $media_url, $media_alt, $cache_tags);
    $resolved_cta_url = $this->resolveCtaUrl($cta_node_id, $cta_url, $cache_tags);

    return [
      'id' => $id,
      'headline' => trim($headline),
      'body' => [
        '#type' => 'processed_text',
        '#text' => $body_value,
        '#format' => $body_format,
      ],
      'media_url' => $image['url'] ?? '',
      'media_alt' => $image['alt'] ?? '',
      'cta_label' => trim($cta_label),
      'cta_url' => $resolved_cta_url,
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
