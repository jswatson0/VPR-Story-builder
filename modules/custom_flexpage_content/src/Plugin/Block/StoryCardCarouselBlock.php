<?php

namespace Drupal\custom_flexpage_content\Plugin\Block;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\node\NodeInterface;

/**
 * Provides a swipeable carousel of portrait image cards.
 *
 * @Block(
 *   id = "custom_flexpage_story_card_carousel_block",
 *   admin_label = @Translation("Card Carousel"),
 *   category = @Translation("Visual Storytelling")
 * )
 */
class StoryCardCarouselBlock extends VisualStoryBlockBase {

  /**
   * Maximum configurable cards.
   */
  protected const MAX_ITEMS = 10;

  /**
   * Number of card slots shown in the form before "Add another card".
   */
  protected const INITIAL_ITEMS = 4;

  /**
   * Form state key holding the card slot IDs shown in the form, in order.
   */
  protected const SLOTS_KEY = 'vs_card_carousel_slots';

  /**
   * Form state key holding the next slot ID to hand out.
   */
  protected const NEXT_SLOT_KEY = 'vs_card_carousel_next_slot';

  /**
   * Form state key holding the most recently added slot ID.
   */
  protected const ADDED_SLOT_KEY = 'vs_card_carousel_added_slot';

  /**
   * HTML ID of the AJAX-replaced card slots wrapper.
   */
  protected const CARDS_WRAPPER_ID = 'vs-card-carousel-cards';

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
      'card_shape' => 'portrait',
      'card_size' => 'medium',
      'show_peek' => TRUE,
      'loop' => FALSE,
    ];

    for ($i = 1; $i <= self::MAX_ITEMS; $i++) {
      $key = "item_{$i}";
      $config["{$key}_title"] = '';
      $config["{$key}_body_value"] = '';
      $config["{$key}_body_format"] = 'basic_html';
      $config["{$key}_media_id"] = 0;
      $config["{$key}_media_url"] = '';
      $config["{$key}_media_alt"] = '';
      $config["{$key}_focal_x"] = '50';
      $config["{$key}_focal_y"] = '50';
      $config["{$key}_cta_label"] = '';
      $config["{$key}_cta_url"] = '';
      $config["{$key}_cta_node_id"] = 0;
      $config["{$key}_text_color"] = 'light';
      $config["{$key}_text_position"] = 'bottom';
      $config["{$key}_text_alignment"] = 'left';
      $config["{$key}_overlay_strength"] = 'medium';
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
      '#markup' => $this->t('<p>This block renders a swipeable, social-style carousel of portrait cards. Each card uses its image as a full background with the heading, copy, and CTA laid over it. Visitors swipe (or use the arrows and dots below the card) to move between cards.</p><p><strong>Recommended flow:</strong> 1) choose card shape and size, 2) fill in the cards (drag the handle beside a card to reorder it, use Add another card for more, or Remove card to delete one), 3) for each card set a focal point so the subject stays in frame when cropped to portrait, 4) pick text color, position, and overlay strength so the copy reads clearly over that image.</p><p>Empty cards are skipped. Keep copy short; long body text will be cut off on smaller cards.</p>'),
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
      '#description' => $this->t('Optional short copy shown above the carousel.'),
      '#default_value' => $this->configuration['intro_value'] ?? '',
      '#format' => $this->configuration['intro_format'] ?? filter_default_format(),
      '#rows' => 3,
    ];

    $form['theme'] = [
      '#type' => 'select',
      '#title' => $this->t('Section color theme'),
      '#description' => $this->t('Light has no background and sits on the page; Dark adds a dark section background. Also sets the heading and control colors. Card text colors are set per card below.'),
      '#default_value' => $this->configuration['theme'] ?? 'light',
      '#options' => [
        'light' => $this->t('Light'),
        'dark' => $this->t('Dark'),
      ],
    ];

    $form['card_shape'] = [
      '#type' => 'select',
      '#title' => $this->t('Card shape'),
      '#default_value' => $this->configuration['card_shape'] ?? 'portrait',
      '#options' => [
        'portrait' => $this->t('Portrait (4:5, feed post)'),
        'tall' => $this->t('Tall (2:3)'),
        'story' => $this->t('Story (9:16, full-screen phone)'),
      ],
    ];

    $form['card_size'] = [
      '#type' => 'select',
      '#title' => $this->t('Card size'),
      '#description' => $this->t('Maximum card width on larger screens. On phones, cards fill most of the screen width.'),
      '#default_value' => $this->configuration['card_size'] ?? 'medium',
      '#options' => [
        'small' => $this->t('Small'),
        'medium' => $this->t('Medium'),
        'large' => $this->t('Large'),
      ],
    ];

    $form['show_peek'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show neighboring cards'),
      '#description' => $this->t('Lets the edges of the previous and next cards peek in beside the current card, hinting that visitors can swipe.'),
      '#default_value' => (bool) ($this->configuration['show_peek'] ?? TRUE),
    ];

    $form['loop'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Loop arrows'),
      '#description' => $this->t('When checked, the next arrow on the last card jumps back to the first card (and vice versa). Otherwise the arrows are disabled at either end.'),
      '#default_value' => (bool) ($this->configuration['loop'] ?? FALSE),
    ];

    // Each card in the form has a stable slot ID, so unsaved input stays with
    // its card when other cards are removed or reordered. Slots 1-MAX_ITEMS
    // load the saved item_N settings; added slots get IDs above MAX_ITEMS so
    // they start empty. blockSubmit() saves them back to item_1..item_N in
    // their dragged order.
    $slots = $form_state->get(self::SLOTS_KEY);
    if ($slots === NULL) {
      // Show INITIAL_ITEMS slots, or enough to cover every saved card.
      $card_count = self::INITIAL_ITEMS;
      for ($i = self::MAX_ITEMS; $i > self::INITIAL_ITEMS; $i--) {
        if ($this->itemHasContent("item_{$i}")) {
          $card_count = $i;
          break;
        }
      }
      $slots = range(1, $card_count);
      $form_state->set(self::SLOTS_KEY, $slots);
      $form_state->set(self::NEXT_SLOT_KEY, self::MAX_ITEMS + 1);
    }
    $added_slot = $form_state->get(self::ADDED_SLOT_KEY);

    $form['cards'] = [
      '#type' => 'container',
      '#attributes' => ['id' => self::CARDS_WRAPPER_ID],
      '#attached' => [
        'library' => ['custom_flexpage_content/story_card_carousel_editor'],
      ],
    ];

    $form['cards']['table'] = [
      '#type' => 'table',
      '#header' => [$this->t('Cards'), $this->t('Weight')],
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'vs-card-carousel-weight',
        ],
      ],
      '#attributes' => [
        'class' => ['vs-card-carousel-cards'],
        'data-vs-card-carousel-cards' => '',
      ],
    ];

    foreach ($slots as $position => $slot) {
      $key = "item_{$slot}";
      $form['cards']['table'][$slot] = [
        '#attributes' => ['class' => ['draggable']],
      ];
      $form['cards']['table'][$slot]['card'] = [
        '#type' => 'details',
        // The editor JS renumbers data-vs-card-num after a drag. The saved
        // heading is shown too so collapsed cards are easy to tell apart.
        '#title' => $this->cardSummaryTitle($position + 1, trim((string) ($this->configuration["{$key}_title"] ?? ''))),
        // Open the first few, plus the slot that was just added.
        '#open' => $position < 3 || $slot === $added_slot,
      ];

      $media_id = (int) ($this->configuration["{$key}_media_id"] ?? 0);
      $form['cards']['table'][$slot]['card']["{$key}_media_id"] = [
        '#type' => 'media_library',
        '#title' => $this->t('Background image (preferred)'),
        '#description' => $this->t('Leave image, heading, and copy empty to hide this card.'),
        '#allowed_bundles' => $this->getImageMediaTypeIds(),
        '#default_value' => $media_id > 0 ? $media_id : NULL,
        '#cardinality' => 1,
      ];

      $form['cards']['table'][$slot]['card']["{$key}_media_url"] = [
        '#type' => 'textfield',
        '#title' => $this->t('Or enter a direct image URL'),
        '#description' => $this->t('Skip if you selected from Media Library above.'),
        '#default_value' => $this->configuration["{$key}_media_url"] ?? '',
        '#maxlength' => 2048,
        '#wrapper_attributes' => ['class' => ['vs-field-fallback']],
      ];

      $form['cards']['table'][$slot]['card']["{$key}_media_alt"] = [
        '#type' => 'textfield',
        '#title' => $this->t('Image alt text'),
        '#description' => $this->mediaAltDescription(),
        '#default_value' => $this->configuration["{$key}_media_alt"] ?? '',
        '#maxlength' => 255,
      ];

      $form['cards']['table'][$slot]['card']["{$key}_focal_x"] = [
        '#type' => 'number',
        '#title' => $this->t('Image focal point X (%)'),
        '#description' => $this->t('0 = left edge, 100 = right edge. Landscape images are cropped heavily on the sides, so set this to keep the subject in frame.'),
        '#default_value' => (float) ($this->configuration["{$key}_focal_x"] ?? 50),
        '#min' => 0,
        '#max' => 100,
        '#step' => 1,
      ];

      $form['cards']['table'][$slot]['card']["{$key}_focal_y"] = [
        '#type' => 'number',
        '#title' => $this->t('Image focal point Y (%)'),
        '#description' => $this->t('0 = top edge, 100 = bottom edge.'),
        '#default_value' => (float) ($this->configuration["{$key}_focal_y"] ?? 50),
        '#min' => 0,
        '#max' => 100,
        '#step' => 1,
      ];

      $form['cards']['table'][$slot]['card']["{$key}_title"] = [
        '#type' => 'textfield',
        '#title' => $this->t('Heading'),
        '#default_value' => $this->configuration["{$key}_title"] ?? '',
        '#maxlength' => 255,
      ];

      $form['cards']['table'][$slot]['card']["{$key}_body"] = [
        '#type' => 'text_format',
        '#title' => $this->t('Body copy'),
        '#description' => $this->t('Keep it to a sentence or two so it fits over the image.'),
        '#default_value' => $this->configuration["{$key}_body_value"] ?? '',
        '#format' => $this->configuration["{$key}_body_format"] ?? filter_default_format(),
        '#rows' => 3,
      ];

      $form['cards']['table'][$slot]['card']["{$key}_cta_label"] = [
        '#type' => 'textfield',
        '#title' => $this->t('CTA label'),
        '#default_value' => $this->configuration["{$key}_cta_label"] ?? '',
        '#maxlength' => 255,
      ];

      $form['cards']['table'][$slot]['card']["{$key}_cta_node_id"] = [
        '#type' => 'entity_autocomplete',
        '#title' => $this->t('CTA internal page'),
        '#target_type' => 'node',
        '#selection_settings' => [
          'target_bundles' => NULL,
        ],
        '#default_value' => $this->loadNodeFromConfig("{$key}_cta_node_id"),
        '#tags' => FALSE,
      ];

      $form['cards']['table'][$slot]['card']["{$key}_cta_url"] = [
        '#type' => 'textfield',
        '#element_validate' => [[static::class, 'validateCtaUrl']],
        '#maxlength' => 2048,
        '#title' => $this->t('Or enter a URL'),
        '#description' => $this->t('A path on this site, such as /research, or a full URL for other sites. Leave empty if you selected a page above.'),
        '#default_value' => $this->configuration["{$key}_cta_url"] ?? '',
        '#wrapper_attributes' => ['class' => ['vs-field-fallback']],
      ];

      $form['cards']['table'][$slot]['card']["{$key}_text_color"] = [
        '#type' => 'select',
        '#title' => $this->t('Text color'),
        '#description' => $this->t('Light text gets a dark overlay; dark text gets a light overlay.'),
        '#default_value' => $this->configuration["{$key}_text_color"] ?? 'light',
        '#options' => [
          'light' => $this->t('Light text'),
          'dark' => $this->t('Dark text'),
        ],
      ];

      $form['cards']['table'][$slot]['card']["{$key}_text_position"] = [
        '#type' => 'select',
        '#title' => $this->t('Text position'),
        '#default_value' => $this->configuration["{$key}_text_position"] ?? 'bottom',
        '#options' => [
          'top' => $this->t('Top'),
          'middle' => $this->t('Middle'),
          'bottom' => $this->t('Bottom'),
        ],
      ];

      $form['cards']['table'][$slot]['card']["{$key}_text_alignment"] = [
        '#type' => 'select',
        '#title' => $this->t('Text alignment'),
        '#default_value' => $this->configuration["{$key}_text_alignment"] ?? 'left',
        '#options' => [
          'left' => $this->t('Left'),
          'center' => $this->t('Center'),
          'right' => $this->t('Right'),
        ],
      ];

      $form['cards']['table'][$slot]['card']["{$key}_overlay_strength"] = [
        '#type' => 'select',
        '#title' => $this->t('Overlay strength'),
        '#description' => $this->t('Shading behind the text. It is strongest where the text sits and fades across the rest of the image. Increase it if the text is hard to read.'),
        '#default_value' => $this->configuration["{$key}_overlay_strength"] ?? 'medium',
        '#options' => [
          'none' => $this->t('None'),
          'light' => $this->t('Light'),
          'medium' => $this->t('Medium'),
          'heavy' => $this->t('Heavy'),
        ],
      ];

      $form['cards']['table'][$slot]['card']['remove'] = [
        '#type' => 'submit',
        '#value' => $this->t('Remove card'),
        '#name' => "vs_card_carousel_remove_{$slot}",
        '#vs_card_slot' => $slot,
        '#access' => count($slots) > 1,
        '#submit' => [[static::class, 'removeCardSubmit']],
        '#ajax' => [
          'callback' => [static::class, 'cardsAjaxCallback'],
          'wrapper' => self::CARDS_WRAPPER_ID,
        ],
        '#limit_validation_errors' => [],
        '#attributes' => ['class' => ['button--danger', 'button--small']],
      ];

      $form['cards']['table'][$slot]['weight'] = [
        '#type' => 'weight',
        '#title' => $this->t('Weight for card @num', ['@num' => $position + 1]),
        '#title_display' => 'invisible',
        '#default_value' => $position,
        '#delta' => self::MAX_ITEMS,
        '#attributes' => ['class' => ['vs-card-carousel-weight']],
      ];
    }

    $form['cards']['add_card'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add another card'),
      '#name' => 'vs_card_carousel_add_card',
      '#access' => count($slots) < self::MAX_ITEMS,
      '#submit' => [[static::class, 'addCardSubmit']],
      '#ajax' => [
        'callback' => [static::class, 'cardsAjaxCallback'],
        'wrapper' => self::CARDS_WRAPPER_ID,
      ],
      '#limit_validation_errors' => [],
      '#prefix' => '<div class="vs-card-carousel-add-card">',
      '#suffix' => '<div class="description">' . $this->t('Up to @max cards.', ['@max' => self::MAX_ITEMS]) . '</div></div>',
    ];

    return $form;
  }

  /**
   * Submit handler for "Add another card": appends an empty card slot.
   */
  public static function addCardSubmit(array &$form, FormStateInterface $form_state): void {
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
   * Submit handler for "Remove card": drops that card's slot.
   */
  public static function removeCardSubmit(array &$form, FormStateInterface $form_state): void {
    $removed = $form_state->getTriggeringElement()['#vs_card_slot'] ?? NULL;
    $slots = array_values(array_diff(static::slotsInSubmittedOrder($form_state), [$removed]));
    $form_state->set(self::SLOTS_KEY, $slots);
    $form_state->set(self::ADDED_SLOT_KEY, NULL);
    $form_state->setRebuild();
  }

  /**
   * AJAX callback for the add/remove buttons: returns the rebuilt cards.
   */
  public static function cardsAjaxCallback(array &$form, FormStateInterface $form_state): array {
    $parents = static::cardsParents($form_state->getTriggeringElement()['#array_parents']);
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
    $parents = static::cardsParents($form_state->getTriggeringElement()['#parents']);
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
   * Trims an element's parents down to the 'cards' container.
   */
  protected static function cardsParents(array $parents): array {
    return array_slice($parents, 0, array_search('cards', $parents, TRUE) + 1);
  }

  /**
   * Builds a card fieldset title: "Card N", plus the heading when set.
   */
  protected function cardSummaryTitle(int $number, string $heading): TranslatableMarkup {
    if ($heading === '') {
      return $this->t('Card <span data-vs-card-num>@num</span>', ['@num' => $number]);
    }
    return $this->t('Card <span data-vs-card-num>@num</span> <span class="vs-card-carousel-card-heading">· @heading</span>', [
      '@num' => $number,
      '@heading' => $heading,
    ]);
  }

  /**
   * Checks whether a saved card has any content worth showing in the form.
   */
  protected function itemHasContent(string $key): bool {
    foreach (['title', 'body_value', 'media_url', 'cta_label', 'cta_url'] as $field) {
      if (trim(strip_tags((string) ($this->configuration["{$key}_{$field}"] ?? ''))) !== '') {
        return TRUE;
      }
    }
    return (int) ($this->configuration["{$key}_media_id"] ?? 0) > 0
      || (int) ($this->configuration["{$key}_cta_node_id"] ?? 0) > 0;
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
    $this->configuration['card_shape'] = (string) $form_state->getValue('card_shape', 'portrait');
    $this->configuration['card_size'] = (string) $form_state->getValue('card_size', 'medium');
    $this->configuration['show_peek'] = (bool) $form_state->getValue('show_peek', TRUE);
    $this->configuration['loop'] = (bool) $form_state->getValue('loop', FALSE);

    // Save cards to item_1..item_N in their dragged order, then clear the
    // positions left over.
    $rows = $form_state->getValue(['cards', 'table']) ?: [];
    uasort($rows, fn($a, $b) => (int) ($a['weight'] ?? 0) <=> (int) ($b['weight'] ?? 0));
    $position = 0;
    foreach ($rows as $slot => $row) {
      $position++;
      $this->setItemConfiguration("item_{$position}", "item_{$slot}", $row['card'] ?? []);
    }
    for ($i = $position + 1; $i <= self::MAX_ITEMS; $i++) {
      $this->setItemConfiguration("item_{$i}", "item_{$i}", []);
    }
  }

  /**
   * Saves one card's submitted values into a card position.
   *
   * @param string $target
   *   The item_N configuration prefix to write.
   * @param string $source
   *   The item_N prefix the card's form elements were named with.
   * @param array $values
   *   The card's submitted values; empty to reset the position.
   */
  protected function setItemConfiguration(string $target, string $source, array $values): void {
    $body = $values["{$source}_body"] ?? [];

    $this->configuration["{$target}_title"] = trim((string) ($values["{$source}_title"] ?? ''));
    $this->configuration["{$target}_body_value"] = trim((string) ($body['value'] ?? ''));
    $this->configuration["{$target}_body_format"] = (string) ($body['format'] ?? filter_default_format());
    $this->configuration["{$target}_media_id"] = $this->extractMediaTargetId($values["{$source}_media_id"] ?? NULL);
    $this->configuration["{$target}_media_url"] = trim((string) ($values["{$source}_media_url"] ?? ''));
    $this->configuration["{$target}_media_alt"] = trim((string) ($values["{$source}_media_alt"] ?? ''));
    $this->configuration["{$target}_focal_x"] = (string) ($values["{$source}_focal_x"] ?? '50');
    $this->configuration["{$target}_focal_y"] = (string) ($values["{$source}_focal_y"] ?? '50');
    $this->configuration["{$target}_cta_label"] = trim((string) ($values["{$source}_cta_label"] ?? ''));
    $this->configuration["{$target}_cta_node_id"] = $this->extractNodeTargetId($values["{$source}_cta_node_id"] ?? NULL);
    $this->configuration["{$target}_cta_url"] = trim((string) ($values["{$source}_cta_url"] ?? ''));
    $this->configuration["{$target}_text_color"] = (string) ($values["{$source}_text_color"] ?? 'light');
    $this->configuration["{$target}_text_position"] = (string) ($values["{$source}_text_position"] ?? 'bottom');
    $this->configuration["{$target}_text_alignment"] = (string) ($values["{$source}_text_alignment"] ?? 'left');
    $this->configuration["{$target}_overlay_strength"] = (string) ($values["{$source}_overlay_strength"] ?? 'medium');
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
      '#theme' => 'custom_flexpage_story_card_carousel',
      '#eyebrow' => $this->configuration['eyebrow'] ?? '',
      '#headline' => $this->configuration['headline'] ?? '',
      '#intro' => trim(strip_tags($this->configuration['intro_value'] ?? '')) === '' ? '' : [
        '#type' => 'processed_text',
        '#text' => $this->configuration['intro_value'],
        '#format' => $this->configuration['intro_format'] ?? filter_default_format(),
      ],
      '#items' => $items,
      '#color_theme' => $this->configuration['theme'] ?? 'light',
      '#card_shape' => $this->configuration['card_shape'] ?? 'portrait',
      '#card_size' => $this->configuration['card_size'] ?? 'medium',
      '#show_peek' => (bool) ($this->configuration['show_peek'] ?? TRUE),
      '#loop' => (bool) ($this->configuration['loop'] ?? FALSE),
      '#attached' => [
        'library' => ['custom_flexpage_content/story_card_carousel_block'],
      ],
      '#cache' => [
        'tags' => array_values(array_unique($cache_tags)),
      ],
    ];
  }

  /**
   * Builds one carousel card, or NULL when it has no content.
   */
  protected function buildItem(string $key, array &$cache_tags): ?array {
    $title = trim((string) ($this->configuration["{$key}_title"] ?? ''));
    $body_value = (string) ($this->configuration["{$key}_body_value"] ?? '');
    $image = $this->resolveImageSource(
      (int) ($this->configuration["{$key}_media_id"] ?? 0),
      (string) ($this->configuration["{$key}_media_url"] ?? ''),
      (string) ($this->configuration["{$key}_media_alt"] ?? ''),
      $cache_tags
    );

    $has_body = trim(strip_tags($body_value)) !== '';
    if ($title === '' && !$has_body && !$image) {
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
      'media_url' => $image['url'] ?? '',
      'media_alt' => $image['alt'] ?? '',
      'focal_x' => $this->clampPercent($this->configuration["{$key}_focal_x"] ?? 50),
      'focal_y' => $this->clampPercent($this->configuration["{$key}_focal_y"] ?? 50),
      'cta_label' => trim((string) ($this->configuration["{$key}_cta_label"] ?? '')),
      'cta_url' => $this->resolveCtaUrl(
        (int) ($this->configuration["{$key}_cta_node_id"] ?? 0),
        (string) ($this->configuration["{$key}_cta_url"] ?? ''),
        $cache_tags
      ),
      'text_color' => (string) ($this->configuration["{$key}_text_color"] ?? 'light'),
      'text_position' => (string) ($this->configuration["{$key}_text_position"] ?? 'bottom'),
      'text_alignment' => (string) ($this->configuration["{$key}_text_alignment"] ?? 'left'),
      'overlay_strength' => (string) ($this->configuration["{$key}_overlay_strength"] ?? 'medium'),
    ];
  }

  /**
   * Clamps a focal point value to a 0-100 percentage string.
   */
  protected function clampPercent(mixed $value): string {
    return (string) max(0, min(100, (float) $value));
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

}
