<?php

namespace Drupal\custom_flexpage_content\Plugin\Block;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Provides a two-column scrolling section with locked media beside the text.
 *
 * @Block(
 *   id = "custom_flexpage_story_sidecar_block",
 *   admin_label = @Translation("Sidecar"),
 *   category = @Translation("Visual Storytelling")
 * )
 */
class StorySidecarBlock extends VisualStoryBlockBase {

  /**
   * Maximum configurable slides.
   */
  protected const MAX_ITEMS = 10;

  /**
   * Number of slide slots shown in the form before "Add another slide".
   */
  protected const INITIAL_ITEMS = 3;

  /**
   * Form state key holding the slide slot IDs shown in the form, in order.
   */
  protected const SLOTS_KEY = 'vs_sidecar_slots';

  /**
   * Form state key holding the next slot ID to hand out.
   */
  protected const NEXT_SLOT_KEY = 'vs_sidecar_next_slot';

  /**
   * Form state key holding the most recently added slot ID.
   */
  protected const ADDED_SLOT_KEY = 'vs_sidecar_added_slot';

  /**
   * HTML ID of the AJAX-replaced slide slots wrapper.
   */
  protected const SLIDES_WRAPPER_ID = 'vs-sidecar-slides';

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
      'text_side' => 'left',
      'panel_width' => 'medium',
      'transition' => 'crossfade',
      'media_display' => 'cover',
    ];

    for ($i = 1; $i <= self::MAX_ITEMS; $i++) {
      $key = "item_{$i}";
      $config["{$key}_title"] = '';
      $config["{$key}_body_value"] = '';
      $config["{$key}_body_format"] = 'basic_html';
      $config["{$key}_media_id"] = 0;
      $config["{$key}_media_url"] = '';
      $config["{$key}_media_alt"] = '';
      $config["{$key}_caption"] = '';
      $config["{$key}_focal_x"] = '50';
      $config["{$key}_focal_y"] = '50';
      $config["{$key}_media_display"] = 'section';
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
      '#markup' => $this->t('<p>This block builds a two-column scrolling section. One column holds a full-height image or video that locks in place while the narrative text in the other column scrolls past. Each slide pairs one piece of media with its text; when the reader scrolls into the next slide\'s text, the media changes.</p><p><strong>Crossfade</strong> keeps the media locked and fades to the next slide\'s media. <strong>Release</strong> lets each slide\'s media scroll away with the end of its text, and the next slide\'s media scrolls in and locks.</p><p><strong>Recommended flow:</strong> 1) choose the text side, panel width, and transition, 2) fill in the slides (drag the handle beside a slide to reorder it, use Add another slide for more, or Remove slide to delete one), 3) choose how media displays: Full bleed fills the column edge to edge, Whole image shows the entire image with margins, and Portrait frame crops to a 4:5 portrait with margins (each slide can override the section setting), 4) set a focal point on each cropped slide so the subject stays in frame.</p><p>On phones the columns stack: the media fills the screen behind the text, and the text scrolls over it. Empty slides are skipped.</p>'),
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
      '#description' => $this->t('Optional. Shown at the top of the text column, before the first slide.'),
      '#default_value' => $this->configuration['headline'] ?? '',
      '#maxlength' => 255,
    ];

    $form['intro'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Intro copy'),
      '#description' => $this->t('Optional short copy shown under the headline.'),
      '#default_value' => $this->configuration['intro_value'] ?? '',
      '#format' => $this->configuration['intro_format'] ?? filter_default_format(),
      '#rows' => 3,
    ];

    $form['theme'] = [
      '#type' => 'select',
      '#title' => $this->t('Section color theme'),
      '#description' => $this->t('Sets the text column background and text colors.'),
      '#default_value' => $this->configuration['theme'] ?? 'light',
      '#options' => [
        'light' => $this->t('Light'),
        'dark' => $this->t('Dark'),
      ],
    ];

    $form['text_side'] = [
      '#type' => 'select',
      '#title' => $this->t('Text side'),
      '#default_value' => $this->configuration['text_side'] ?? 'left',
      '#options' => [
        'left' => $this->t('Text left, media right'),
        'right' => $this->t('Media left, text right'),
      ],
    ];

    $form['panel_width'] = [
      '#type' => 'select',
      '#title' => $this->t('Text panel width'),
      '#description' => $this->t('Share of the section width given to the text column on larger screens. The media fills the rest.'),
      '#default_value' => $this->configuration['panel_width'] ?? 'medium',
      '#options' => [
        'narrow' => $this->t('Narrow (one third)'),
        'medium' => $this->t('Medium (40%)'),
        'wide' => $this->t('Wide (half)'),
      ],
    ];

    $form['transition'] = [
      '#type' => 'select',
      '#title' => $this->t('Media transition'),
      '#default_value' => $this->configuration['transition'] ?? 'crossfade',
      '#options' => [
        'crossfade' => $this->t('Crossfade: media stays locked and fades to the next'),
        'release' => $this->t('Release: media scrolls away with its text, next media locks'),
      ],
    ];

    $form['media_display'] = [
      '#type' => 'select',
      '#title' => $this->t('Media display'),
      '#description' => $this->t('How each slide\'s image or video fills the media column. Individual slides can override this.'),
      '#default_value' => $this->configuration['media_display'] ?? 'cover',
      '#options' => $this->mediaDisplayOptions(),
    ];

    // Each slide in the form has a stable slot ID, so unsaved input stays with
    // its slide when other slides are removed or reordered. Slots 1-MAX_ITEMS
    // load the saved item_N settings; added slots get IDs above MAX_ITEMS so
    // they start empty. blockSubmit() saves them back to item_1..item_N in
    // their dragged order.
    $slots = $form_state->get(self::SLOTS_KEY);
    if ($slots === NULL) {
      // Show INITIAL_ITEMS slots, or enough to cover every saved slide.
      $slide_count = self::INITIAL_ITEMS;
      for ($i = self::MAX_ITEMS; $i > self::INITIAL_ITEMS; $i--) {
        if ($this->itemHasContent("item_{$i}")) {
          $slide_count = $i;
          break;
        }
      }
      $slots = range(1, $slide_count);
      $form_state->set(self::SLOTS_KEY, $slots);
      $form_state->set(self::NEXT_SLOT_KEY, self::MAX_ITEMS + 1);
    }
    $added_slot = $form_state->get(self::ADDED_SLOT_KEY);

    // Reuses the Card Carousel editor library (row styling and renumbering
    // "Slide N" labels after a drag) through its class and data hooks.
    $form['slides'] = [
      '#type' => 'container',
      '#attributes' => ['id' => self::SLIDES_WRAPPER_ID],
      '#attached' => [
        'library' => ['custom_flexpage_content/story_card_carousel_editor'],
      ],
    ];

    $form['slides']['table'] = [
      '#type' => 'table',
      '#header' => [$this->t('Slides'), $this->t('Weight')],
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'vs-sidecar-weight',
        ],
      ],
      '#attributes' => [
        'class' => ['vs-card-carousel-cards'],
        'data-vs-card-carousel-cards' => '',
      ],
    ];

    foreach ($slots as $position => $slot) {
      $key = "item_{$slot}";
      $form['slides']['table'][$slot] = [
        '#attributes' => ['class' => ['draggable']],
      ];
      $form['slides']['table'][$slot]['slide'] = [
        '#type' => 'details',
        '#title' => $this->slideSummaryTitle($position + 1, trim((string) ($this->configuration["{$key}_title"] ?? ''))),
        // Open the first few, plus the slot that was just added.
        '#open' => $position < 2 || $slot === $added_slot,
      ];
      $slide = &$form['slides']['table'][$slot]['slide'];

      $media_id = (int) ($this->configuration["{$key}_media_id"] ?? 0);
      $slide["{$key}_media_id"] = [
        '#type' => 'media_library',
        '#title' => $this->t('Image or video (preferred)'),
        '#description' => $this->t('Videos play muted and loop while their slide is on screen. Leave media, heading, and text empty to hide this slide.'),
        '#allowed_bundles' => $this->getImageAndVideoMediaTypeIds(),
        '#default_value' => $media_id > 0 ? $media_id : NULL,
        '#cardinality' => 1,
      ];

      $slide["{$key}_media_url"] = [
        '#type' => 'textfield',
        '#title' => $this->t('Or enter a direct image or video URL'),
        '#description' => $this->t('Skip if you selected from Media Library above. URLs ending in .mp4, .webm, or .ogg are treated as video.'),
        '#default_value' => $this->configuration["{$key}_media_url"] ?? '',
        '#maxlength' => 2048,
        '#wrapper_attributes' => ['class' => ['vs-field-fallback']],
      ];

      $slide["{$key}_media_alt"] = [
        '#type' => 'textfield',
        '#title' => $this->t('Image alt text'),
        '#description' => $this->mediaAltDescription(),
        '#default_value' => $this->configuration["{$key}_media_alt"] ?? '',
        '#maxlength' => 255,
      ];

      $slide["{$key}_caption"] = [
        '#type' => 'textfield',
        '#title' => $this->t('Media caption or credit'),
        '#description' => $this->t('Optional. Shown in the corner of the media.'),
        '#default_value' => $this->configuration["{$key}_caption"] ?? '',
        '#maxlength' => 255,
      ];

      $slide["{$key}_media_display"] = [
        '#type' => 'select',
        '#title' => $this->t('Media display'),
        '#default_value' => $this->configuration["{$key}_media_display"] ?? 'section',
        '#options' => ['section' => $this->t('Same as section setting')] + $this->mediaDisplayOptions(),
      ];

      $slide["{$key}_focal_x"] = [
        '#type' => 'number',
        '#title' => $this->t('Media focal point X (%)'),
        '#description' => $this->t('0 = left edge, 100 = right edge. Full bleed and Portrait frame crop the media, so set this to keep the subject in frame. Has no effect on Whole image.'),
        '#default_value' => (float) ($this->configuration["{$key}_focal_x"] ?? 50),
        '#min' => 0,
        '#max' => 100,
        '#step' => 1,
      ];

      $slide["{$key}_focal_y"] = [
        '#type' => 'number',
        '#title' => $this->t('Media focal point Y (%)'),
        '#description' => $this->t('0 = top edge, 100 = bottom edge.'),
        '#default_value' => (float) ($this->configuration["{$key}_focal_y"] ?? 50),
        '#min' => 0,
        '#max' => 100,
        '#step' => 1,
      ];

      $slide["{$key}_title"] = [
        '#type' => 'textfield',
        '#title' => $this->t('Heading'),
        '#default_value' => $this->configuration["{$key}_title"] ?? '',
        '#maxlength' => 255,
      ];

      $slide["{$key}_body"] = [
        '#type' => 'text_format',
        '#title' => $this->t('Text'),
        '#description' => $this->t('The narrative that scrolls beside this slide\'s media. A few paragraphs works well; the media stays locked until this text scrolls by.'),
        '#default_value' => $this->configuration["{$key}_body_value"] ?? '',
        '#format' => $this->configuration["{$key}_body_format"] ?? filter_default_format(),
        '#rows' => 6,
      ];

      $slide['remove'] = [
        '#type' => 'submit',
        '#value' => $this->t('Remove slide'),
        '#name' => "vs_sidecar_remove_{$slot}",
        '#vs_sidecar_slot' => $slot,
        '#access' => count($slots) > 1,
        '#submit' => [[static::class, 'removeSlideSubmit']],
        '#ajax' => [
          'callback' => [static::class, 'slidesAjaxCallback'],
          'wrapper' => self::SLIDES_WRAPPER_ID,
        ],
        '#limit_validation_errors' => [],
        '#attributes' => ['class' => ['button--danger', 'button--small']],
      ];
      unset($slide);

      $form['slides']['table'][$slot]['weight'] = [
        '#type' => 'weight',
        '#title' => $this->t('Weight for slide @num', ['@num' => $position + 1]),
        '#title_display' => 'invisible',
        '#default_value' => $position,
        '#delta' => self::MAX_ITEMS,
        '#attributes' => ['class' => ['vs-sidecar-weight']],
      ];
    }

    $form['slides']['add_slide'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add another slide'),
      '#name' => 'vs_sidecar_add_slide',
      '#access' => count($slots) < self::MAX_ITEMS,
      '#submit' => [[static::class, 'addSlideSubmit']],
      '#ajax' => [
        'callback' => [static::class, 'slidesAjaxCallback'],
        'wrapper' => self::SLIDES_WRAPPER_ID,
      ],
      '#limit_validation_errors' => [],
      '#prefix' => '<div class="vs-card-carousel-add-card">',
      '#suffix' => '<div class="description">' . $this->t('Up to @max slides.', ['@max' => self::MAX_ITEMS]) . '</div></div>',
    ];

    return $form;
  }

  /**
   * Submit handler for "Add another slide": appends an empty slide slot.
   */
  public static function addSlideSubmit(array &$form, FormStateInterface $form_state): void {
    $slots = static::slotsInSubmittedOrder($form_state);
    if (count($slots) < self::MAX_ITEMS) {
      $slot = (int) $form_state->get(self::NEXT_SLOT_KEY);
      $slots[] = $slot;
      $form_state->set(self::NEXT_SLOT_KEY, $slot + 1);
      $form_state->set(self::ADDED_SLOT_KEY, $slot);
    }
    $form_state->set(self::SLOTS_KEY, $slots);
    $form_state->setRebuild();
  }

  /**
   * Submit handler for "Remove slide": drops that slide's slot.
   */
  public static function removeSlideSubmit(array &$form, FormStateInterface $form_state): void {
    $removed = $form_state->getTriggeringElement()['#vs_sidecar_slot'] ?? NULL;
    $slots = array_values(array_diff(static::slotsInSubmittedOrder($form_state), [$removed]));
    $form_state->set(self::SLOTS_KEY, $slots);
    $form_state->set(self::ADDED_SLOT_KEY, NULL);
    $form_state->setRebuild();
  }

  /**
   * AJAX callback for the add/remove buttons: returns the rebuilt slides.
   */
  public static function slidesAjaxCallback(array &$form, FormStateInterface $form_state): array {
    $parents = static::slidesParents($form_state->getTriggeringElement()['#array_parents']);
    return NestedArray::getValue($form, $parents);
  }

  /**
   * Gets the slot IDs sorted by the weights currently in the form.
   *
   * Keeps a drag made before Add/Remove from being lost in the rebuild. Reads
   * raw input because those buttons skip validation, so no values are set.
   */
  protected static function slotsInSubmittedOrder(FormStateInterface $form_state): array {
    $slots = $form_state->get(self::SLOTS_KEY) ?? [];
    $parents = static::slidesParents($form_state->getTriggeringElement()['#parents']);
    $rows = NestedArray::getValue($form_state->getUserInput(), [...$parents, 'table']) ?? [];

    $order = array_flip($slots);
    $weights = [];
    foreach ($slots as $position => $slot) {
      $weights[$slot] = isset($rows[$slot]['weight']) ? (int) $rows[$slot]['weight'] : $position;
    }
    usort($slots, fn($a, $b) => [$weights[$a], $order[$a]] <=> [$weights[$b], $order[$b]]);

    return $slots;
  }

  /**
   * Trims an element's parents down to the 'slides' container.
   */
  protected static function slidesParents(array $parents): array {
    return array_slice($parents, 0, array_search('slides', $parents, TRUE) + 1);
  }

  /**
   * Builds a slide fieldset title: "Slide N", plus the heading when set.
   */
  protected function slideSummaryTitle(int $number, string $heading): TranslatableMarkup {
    if ($heading === '') {
      return $this->t('Slide <span data-vs-card-num>@num</span>', ['@num' => $number]);
    }
    return $this->t('Slide <span data-vs-card-num>@num</span> <span class="vs-card-carousel-card-heading">· @heading</span>', [
      '@num' => $number,
      '@heading' => $heading,
    ]);
  }

  /**
   * Checks whether a saved slide has any content worth showing in the form.
   */
  protected function itemHasContent(string $key): bool {
    foreach (['title', 'body_value', 'media_url', 'caption'] as $field) {
      if (trim(strip_tags((string) ($this->configuration["{$key}_{$field}"] ?? ''))) !== '') {
        return TRUE;
      }
    }
    return (int) ($this->configuration["{$key}_media_id"] ?? 0) > 0;
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
    $this->configuration['text_side'] = (string) $form_state->getValue('text_side', 'left');
    $this->configuration['panel_width'] = (string) $form_state->getValue('panel_width', 'medium');
    $this->configuration['transition'] = (string) $form_state->getValue('transition', 'crossfade');
    $this->configuration['media_display'] = (string) $form_state->getValue('media_display', 'cover');

    // Save slides to item_1..item_N in their dragged order, then clear the
    // positions left over.
    $rows = $form_state->getValue(['slides', 'table']) ?: [];
    uasort($rows, fn($a, $b) => (int) ($a['weight'] ?? 0) <=> (int) ($b['weight'] ?? 0));
    $position = 0;
    foreach ($rows as $slot => $row) {
      $position++;
      $this->setItemConfiguration("item_{$position}", "item_{$slot}", $row['slide'] ?? []);
    }
    for ($i = $position + 1; $i <= self::MAX_ITEMS; $i++) {
      $this->setItemConfiguration("item_{$i}", "item_{$i}", []);
    }
  }

  /**
   * Saves one slide's submitted values into a slide position.
   *
   * @param string $target
   *   The item_N configuration prefix to write.
   * @param string $source
   *   The item_N prefix the slide's form elements were named with.
   * @param array $values
   *   The slide's submitted values; empty to reset the position.
   */
  protected function setItemConfiguration(string $target, string $source, array $values): void {
    $body = $values["{$source}_body"] ?? [];

    $this->configuration["{$target}_title"] = trim((string) ($values["{$source}_title"] ?? ''));
    $this->configuration["{$target}_body_value"] = trim((string) ($body['value'] ?? ''));
    $this->configuration["{$target}_body_format"] = (string) ($body['format'] ?? filter_default_format());
    $this->configuration["{$target}_media_id"] = $this->extractMediaTargetId($values["{$source}_media_id"] ?? NULL);
    $this->configuration["{$target}_media_url"] = trim((string) ($values["{$source}_media_url"] ?? ''));
    $this->configuration["{$target}_media_alt"] = trim((string) ($values["{$source}_media_alt"] ?? ''));
    $this->configuration["{$target}_caption"] = trim((string) ($values["{$source}_caption"] ?? ''));
    $this->configuration["{$target}_focal_x"] = (string) ($values["{$source}_focal_x"] ?? '50');
    $this->configuration["{$target}_focal_y"] = (string) ($values["{$source}_focal_y"] ?? '50');
    $this->configuration["{$target}_media_display"] = (string) ($values["{$source}_media_display"] ?? 'section');
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $cache_tags = [];
    $items = [];

    for ($i = 1; $i <= self::MAX_ITEMS; $i++) {
      $item = $this->buildItem("item_{$i}", $cache_tags);
      if ($item !== NULL) {
        $items[] = $item;
      }
    }

    if (empty($items)) {
      return [];
    }

    return [
      '#theme' => 'custom_flexpage_story_sidecar',
      '#eyebrow' => $this->configuration['eyebrow'] ?? '',
      '#headline' => $this->configuration['headline'] ?? '',
      '#intro' => trim(strip_tags($this->configuration['intro_value'] ?? '')) === '' ? '' : [
        '#type' => 'processed_text',
        '#text' => $this->configuration['intro_value'],
        '#format' => $this->configuration['intro_format'] ?? filter_default_format(),
      ],
      '#items' => $items,
      '#color_theme' => $this->configuration['theme'] ?? 'light',
      '#text_side' => $this->configuration['text_side'] ?? 'left',
      '#panel_width' => $this->configuration['panel_width'] ?? 'medium',
      '#transition' => $this->configuration['transition'] ?? 'crossfade',
      '#attached' => [
        'library' => ['custom_flexpage_content/story_sidecar_block'],
      ],
      '#cache' => [
        'tags' => array_values(array_unique($cache_tags)),
      ],
    ];
  }

  /**
   * Builds one slide, or NULL when it has no content.
   */
  protected function buildItem(string $key, array &$cache_tags): ?array {
    $title = trim((string) ($this->configuration["{$key}_title"] ?? ''));
    $body_value = (string) ($this->configuration["{$key}_body_value"] ?? '');
    $media = $this->resolveMediaAsset(
      (int) ($this->configuration["{$key}_media_id"] ?? 0),
      (string) ($this->configuration["{$key}_media_url"] ?? ''),
      (string) ($this->configuration["{$key}_media_alt"] ?? ''),
      $cache_tags
    );

    $has_body = trim(strip_tags($body_value)) !== '';
    if ($title === '' && !$has_body && !$media) {
      return NULL;
    }

    return [
      'title' => $title,
      'has_body' => $has_body,
      'body' => [
        '#type' => 'processed_text',
        '#text' => $body_value,
        '#format' => (string) ($this->configuration["{$key}_body_format"] ?? filter_default_format()),
      ],
      'media_kind' => $media['kind'] ?? '',
      'media_url' => $media['url'] ?? '',
      'media_mime' => $media['mime'] ?? '',
      'media_alt' => $media['alt'] ?? '',
      'caption' => trim((string) ($this->configuration["{$key}_caption"] ?? '')),
      'focal_x' => $this->clampPercent($this->configuration["{$key}_focal_x"] ?? 50),
      'focal_y' => $this->clampPercent($this->configuration["{$key}_focal_y"] ?? 50),
      'media_display' => $this->resolveMediaDisplay((string) ($this->configuration["{$key}_media_display"] ?? 'section')),
    ];
  }

  /**
   * Gets the media display choices shared by the section and slide selects.
   */
  protected function mediaDisplayOptions(): array {
    return [
      'cover' => $this->t('Full bleed: fills the column edge to edge (cropped)'),
      'contain' => $this->t('Whole image: shows the entire image, with margins'),
      'portrait' => $this->t('Portrait frame: cropped to 4:5, with margins'),
    ];
  }

  /**
   * Resolves a slide's media display, falling back to the section setting.
   */
  protected function resolveMediaDisplay(string $slide_display): string {
    $display = $slide_display === 'section' ? (string) ($this->configuration['media_display'] ?? 'cover') : $slide_display;
    return array_key_exists($display, $this->mediaDisplayOptions()) ? $display : 'cover';
  }

  /**
   * Clamps a focal point value to a 0-100 percentage string.
   */
  protected function clampPercent(mixed $value): string {
    return (string) max(0, min(100, (float) $value));
  }

}
