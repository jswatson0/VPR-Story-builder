<?php

namespace Drupal\custom_flexpage_content\Plugin\Block;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\NodeInterface;

/**
 * Provides a scroll-driven multi-panel image reveal block.
 *
 * @Block(
 *   id = "custom_flexpage_story_reveal_section_block",
 *   admin_label = @Translation("Panel Reveal"),
 *   category = @Translation("Visual Storytelling")
 * )
 */
class StoryRevealSectionBlock extends VisualStoryBlockBase {

  protected const MAX_PANELS = 6;

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    $config = [
      'panel_count' => 2,
      'scroll_duration' => 'medium',
      'page_turn' => FALSE,
    ];

    for ($n = 1; $n <= self::MAX_PANELS; $n++) {
      $p = "panel_{$n}";
      $config["{$p}_media_id"]       = 0;
      $config["{$p}_image_url"]      = '';
      $config["{$p}_alt"]            = '';
      $config["{$p}_focal_x"]        = '50';
      $config["{$p}_focal_y"]        = '40';
      $config["{$p}_slide_direction"]= 'from-right';
      $config["{$p}_eyebrow"]        = '';
      $config["{$p}_headline"]       = '';
      $config["{$p}_dek"]            = '';
      $config["{$p}_text_position"]  = 'bottom-left';
      $config["{$p}_copy_theme"]     = 'light';
      $config["{$p}_overlay"]        = 'medium';
      $config["{$p}_cta_label"]      = '';
      $config["{$p}_cta_url"]        = '';
      $config["{$p}_cta_node_id"]    = 0;
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
      '#markup' => $this->t('<p>This block pins a full-viewport canvas to the screen and swipes through multiple image panels as the reader scrolls. Each panel has its own background image, focal point, overlay, and optional text content.</p><p><strong>Recommended flow:</strong> 1) set panel count and swipe direction, 2) fill each panel\'s image and copy, 3) adjust overlay and text position per panel. Panels without an image are skipped.</p>'),
    ];

    $panel_count = (int) ($this->configuration['panel_count'] ?? 2);

    $form['panel_count'] = [
      '#type' => 'select',
      '#title' => $this->t('Number of panels'),
      '#description' => $this->t('Only panels with a background image will render. Extra panel fields below are always available.'),
      '#default_value' => $panel_count,
      '#options' => array_combine(range(2, self::MAX_PANELS), array_map(fn($n) => $this->t('@n panels', ['@n' => $n]), range(2, self::MAX_PANELS))),
    ];

    $form['scroll_duration'] = [
      '#type' => 'select',
      '#title' => $this->t('Scroll duration per transition'),
      '#description' => $this->t('How much scrolling is needed to complete each panel transition. Longer feels more cinematic.'),
      '#default_value' => $this->configuration['scroll_duration'] ?? 'medium',
      '#options' => [
        'short'  => $this->t('Short'),
        'medium' => $this->t('Medium'),
        'long'   => $this->t('Long'),
      ],
    ];

    $form['page_turn'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Page turn effect'),
      '#description'   => $this->t('Panels rotate in from the left edge like turning a book page. When enabled, per-panel slide direction settings are ignored.'),
      '#default_value' => (bool) ($this->configuration['page_turn'] ?? FALSE),
    ];

    for ($n = 1; $n <= self::MAX_PANELS; $n++) {
      $p      = "panel_{$n}";
      $active = $n <= $panel_count;

      $form[$p] = [
        '#type'  => 'details',
        '#title' => $active
          ? $this->t('Panel @n', ['@n' => $n])
          : $this->t('Panel @n (inactive)', ['@n' => $n]),
        '#open'  => $n === 1,
      ];

      $media_id = (int) ($this->configuration["{$p}_media_id"] ?? 0);
      $form[$p]["{$p}_media_id"] = [
        '#type'            => 'media_library',
        '#title'           => $this->t('Background image'),
        '#description'     => $this->t('Required — panels without an image are skipped.'),
        '#allowed_bundles' => $this->getImageMediaTypeIds(),
        '#default_value'   => $media_id > 0 ? $media_id : NULL,
        '#cardinality'     => 1,
      ];

      $form[$p]["{$p}_image_url"] = [
        '#type'                => 'textfield',
        '#title'               => $this->t('Or enter a direct image URL'),
        '#description'         => $this->t('Skip if you selected from Media Library above.'),
        '#default_value'       => $this->configuration["{$p}_image_url"] ?? '',
        '#maxlength'           => 2048,
        '#wrapper_attributes'  => ['class' => ['vs-field-fallback']],
      ];

      $form[$p]["{$p}_alt"] = [
        '#type'          => 'textfield',
        '#title'         => $this->t('Alt text'),
        '#default_value' => $this->configuration["{$p}_alt"] ?? '',
        '#maxlength'     => 255,
      ];

      $form[$p]["{$p}_focal_x"] = [
        '#type'          => 'number',
        '#title'         => $this->t('Focal point X (%)'),
        '#default_value' => (float) ($this->configuration["{$p}_focal_x"] ?? 50),
        '#min' => 0, '#max' => 100, '#step' => 1,
      ];

      $form[$p]["{$p}_focal_y"] = [
        '#type'          => 'number',
        '#title'         => $this->t('Focal point Y (%)'),
        '#default_value' => (float) ($this->configuration["{$p}_focal_y"] ?? 40),
        '#min' => 0, '#max' => 100, '#step' => 1,
      ];

      $form[$p]["{$p}_slide_direction"] = [
        '#type'          => 'select',
        '#title'         => $this->t('Slide-in direction'),
        '#description'   => $this->t('Direction from which this panel enters. Not used for the first panel.'),
        '#default_value' => $this->configuration["{$p}_slide_direction"] ?? 'from-right',
        '#options'       => [
          'from-right'  => $this->t('From right'),
          'from-left'   => $this->t('From left'),
          'from-bottom' => $this->t('From bottom'),
          'from-top'    => $this->t('From top'),
        ],
      ];

      $form[$p]["{$p}_overlay"] = [
        '#type'          => 'select',
        '#title'         => $this->t('Overlay strength'),
        '#default_value' => $this->configuration["{$p}_overlay"] ?? 'medium',
        '#options'       => [
          'none'   => $this->t('None'),
          'light'  => $this->t('Light'),
          'medium' => $this->t('Medium'),
          'heavy'  => $this->t('Heavy'),
        ],
      ];

      $form[$p]["{$p}_eyebrow"] = [
        '#type'          => 'textfield',
        '#title'         => $this->t('Eyebrow'),
        '#default_value' => $this->configuration["{$p}_eyebrow"] ?? '',
        '#maxlength'     => 255,
      ];

      $form[$p]["{$p}_headline"] = [
        '#type'          => 'textfield',
        '#title'         => $this->t('Headline'),
        '#default_value' => $this->configuration["{$p}_headline"] ?? '',
        '#maxlength'     => 255,
      ];

      $form[$p]["{$p}_dek"] = [
        '#type'          => 'textarea',
        '#title'         => $this->t('Description'),
        '#description'   => $this->t('Brief supporting text. 1–2 sentences recommended.'),
        '#default_value' => $this->configuration["{$p}_dek"] ?? '',
        '#rows'          => 2,
      ];

      $form[$p]["{$p}_text_position"] = [
        '#type'          => 'select',
        '#title'         => $this->t('Text position'),
        '#default_value' => $this->configuration["{$p}_text_position"] ?? 'bottom-left',
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

      $form[$p]["{$p}_copy_theme"] = [
        '#type'          => 'select',
        '#title'         => $this->t('Copy theme'),
        '#default_value' => $this->configuration["{$p}_copy_theme"] ?? 'light',
        '#options'       => [
          'light' => $this->t('Light copy'),
          'dark'  => $this->t('Dark copy'),
        ],
      ];

      $form[$p]["{$p}_cta_label"] = [
        '#type'          => 'textfield',
        '#title'         => $this->t('CTA label'),
        '#default_value' => $this->configuration["{$p}_cta_label"] ?? '',
        '#maxlength'     => 255,
      ];

      $form[$p]["{$p}_cta_node_id"] = [
        '#type'               => 'entity_autocomplete',
        '#title'              => $this->t('CTA internal page'),
        '#description'        => $this->t('Select a node for internal links. Overrides external URL when set.'),
        '#target_type'        => 'node',
        '#selection_settings' => ['target_bundles' => NULL],
        '#default_value'      => $this->loadNodeFromConfig("{$p}_cta_node_id"),
        '#tags'               => FALSE,
      ];

      $form[$p]["{$p}_cta_url"] = [
        '#type'               => 'url',
        '#title'              => $this->t('Or enter an external URL'),
        '#description'        => $this->t('For links outside this site only. Leave empty if you selected a page above.'),
        '#default_value'      => $this->configuration["{$p}_cta_url"] ?? '',
        '#wrapper_attributes' => ['class' => ['vs-field-fallback']],
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state): void {
    parent::blockSubmit($form, $form_state);

    $this->configuration['panel_count']     = (int) $form_state->getValue('panel_count', 2);
    $this->configuration['scroll_duration'] = (string) $form_state->getValue('scroll_duration', 'medium');
    $this->configuration['page_turn']       = (bool) $form_state->getValue('page_turn', FALSE);

    for ($n = 1; $n <= self::MAX_PANELS; $n++) {
      $p  = "panel_{$n}";
      $pv = $form_state->getValue($p, []);

      $this->configuration["{$p}_media_id"]        = $this->extractMediaTargetId($pv["{$p}_media_id"] ?? NULL);
      $this->configuration["{$p}_image_url"]       = trim((string) ($pv["{$p}_image_url"] ?? ''));
      $this->configuration["{$p}_alt"]             = trim((string) ($pv["{$p}_alt"] ?? ''));
      $this->configuration["{$p}_focal_x"]         = (string) max(0, min(100, (float) ($pv["{$p}_focal_x"] ?? 50)));
      $this->configuration["{$p}_focal_y"]         = (string) max(0, min(100, (float) ($pv["{$p}_focal_y"] ?? 40)));
      $this->configuration["{$p}_slide_direction"] = (string) ($pv["{$p}_slide_direction"] ?? 'from-right');
      $this->configuration["{$p}_overlay"]         = (string) ($pv["{$p}_overlay"] ?? 'medium');
      $this->configuration["{$p}_eyebrow"]         = trim((string) ($pv["{$p}_eyebrow"] ?? ''));
      $this->configuration["{$p}_headline"]        = trim((string) ($pv["{$p}_headline"] ?? ''));
      $this->configuration["{$p}_dek"]             = trim((string) ($pv["{$p}_dek"] ?? ''));
      $this->configuration["{$p}_text_position"]   = (string) ($pv["{$p}_text_position"] ?? 'bottom-left');
      $this->configuration["{$p}_copy_theme"]      = (string) ($pv["{$p}_copy_theme"] ?? 'light');
      $this->configuration["{$p}_cta_label"]       = trim((string) ($pv["{$p}_cta_label"] ?? ''));
      $this->configuration["{$p}_cta_node_id"]     = $this->extractNodeTargetId($pv["{$p}_cta_node_id"] ?? NULL);
      $this->configuration["{$p}_cta_url"]         = trim((string) ($pv["{$p}_cta_url"] ?? ''));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $panel_count = (int) ($this->configuration['panel_count'] ?? 2);
    $panels      = [];
    $cache_tags  = [];

    for ($n = 1; $n <= $panel_count; $n++) {
      $p     = "panel_{$n}";
      $image = $this->resolveImageSource(
        (int) ($this->configuration["{$p}_media_id"] ?? 0),
        (string) ($this->configuration["{$p}_image_url"] ?? ''),
        (string) ($this->configuration["{$p}_alt"] ?? ''),
        $cache_tags
      );

      if (!$image) {
        continue;
      }

      $cta_url = $this->resolveCtaUrl(
        (int) ($this->configuration["{$p}_cta_node_id"] ?? 0),
        (string) ($this->configuration["{$p}_cta_url"] ?? ''),
        $cache_tags
      );

      $panels[] = [
        'image_url'       => $image['url'],
        'alt'             => $image['alt'],
        'focal_x'         => $this->configuration["{$p}_focal_x"] ?? '50',
        'focal_y'         => $this->configuration["{$p}_focal_y"] ?? '40',
        'slide_direction' => $this->configuration["{$p}_slide_direction"] ?? 'from-right',
        'overlay'         => $this->configuration["{$p}_overlay"] ?? 'medium',
        'eyebrow'         => $this->configuration["{$p}_eyebrow"] ?? '',
        'headline'        => $this->configuration["{$p}_headline"] ?? '',
        'dek'             => $this->configuration["{$p}_dek"] ?? '',
        'text_position'   => $this->configuration["{$p}_text_position"] ?? 'bottom-left',
        'copy_theme'      => $this->configuration["{$p}_copy_theme"] ?? 'light',
        'cta_label'       => $this->configuration["{$p}_cta_label"] ?? '',
        'cta_url'         => $cta_url,
      ];
    }

    if (empty($panels)) {
      return [];
    }

    return [
      '#theme'           => 'custom_flexpage_story_reveal_section',
      '#panels'          => $panels,
      '#scroll_duration' => $this->configuration['scroll_duration'] ?? 'medium',
      '#page_turn'       => (bool) ($this->configuration['page_turn'] ?? FALSE),
      '#attached'        => [
        'library' => ['custom_flexpage_content/story_reveal_section_block'],
      ],
      '#cache' => [
        'tags' => array_values(array_unique($cache_tags)),
      ],
    ];
  }

  /**
   * Resolves a CTA URL from an internal node or external fallback.
   */
  protected function resolveCtaUrl(int $node_id, string $fallback_url, array &$cache_tags): string {
    $cta_url = trim($fallback_url);
    if ($node_id <= 0) {
      return $cta_url;
    }

    $node = \Drupal::entityTypeManager()->getStorage('node')->load($node_id);
    if ($node instanceof NodeInterface && $node->access('view')) {
      $cache_tags = Cache::mergeTags($cache_tags, $node->getCacheTags());
      return $node->toUrl()->toString();
    }

    return $cta_url;
  }

  /**
   * Loads a configured node entity for entity_autocomplete default values.
   */
  protected function loadNodeFromConfig(string $config_key): ?NodeInterface {
    $node_id = (int) ($this->configuration[$config_key] ?? 0);
    if ($node_id <= 0) {
      return NULL;
    }

    $node = \Drupal::entityTypeManager()->getStorage('node')->load($node_id);
    return $node instanceof NodeInterface ? $node : NULL;
  }

}
