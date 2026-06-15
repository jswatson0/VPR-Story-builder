<?php

namespace Drupal\custom_flexpage_content\Plugin\Block;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\NodeInterface;

/**
 * Provides a text section storytelling block.
 *
 * @Block(
 *   id = "custom_flexpage_story_text_section_block",
 *   admin_label = @Translation("Story Text Section"),
 *   category = @Translation("Visual Storytelling")
 * )
 */
class StoryTextSectionBlock extends VisualStoryBlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'eyebrow' => '',
      'headline' => '',
      'lead' => '',
      'body_value' => '',
      'body_format' => 'basic_html',
      'body_2_value' => '',
      'body_2_format' => 'basic_html',
      'columns' => '1',
      'content_width' => 'normal',
      'text_alignment' => 'left',
      'color_theme' => 'light',
      'drop_cap' => FALSE,
      'fade_in' => FALSE,
      'cta_label' => '',
      'cta_url' => '',
      'cta_node_id' => 0,
    ];
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
      '#markup' => $this->t('<p>This block renders a clean editorial text section with optional eyebrow, headline, lead paragraph, and rich-text body. Use it for narrative prose, pull quotes, or any long-form content between visual sections.</p><p><strong>Recommended flow:</strong> 1) write body copy, 2) add optional headline and lead, 3) choose column layout and width, 4) set color theme.</p>'),
    ];

    $form['eyebrow'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Eyebrow'),
      '#description' => $this->t('Short label above the headline, e.g. a topic category or section tag.'),
      '#default_value' => $this->configuration['eyebrow'] ?? '',
      '#maxlength' => 255,
    ];

    $form['headline'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Headline'),
      '#description' => $this->t('Optional section heading. Leave blank for body-only prose sections.'),
      '#default_value' => $this->configuration['headline'] ?? '',
      '#maxlength' => 255,
    ];

    $form['lead'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Lead paragraph'),
      '#description' => $this->t('Optional larger introductory paragraph displayed above the body.'),
      '#default_value' => $this->configuration['lead'] ?? '',
      '#rows' => 3,
    ];

    $form['body'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Body copy'),
      '#description' => $this->t('Main rich-text content. Supports headings, lists, links, and inline elements.'),
      '#default_value' => $this->configuration['body_value'] ?? '',
      '#format' => $this->configuration['body_format'] ?? filter_default_format(),
      '#required' => TRUE,
      '#rows' => 10,
    ];

    $form['drop_cap'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable drop cap'),
      '#description' => $this->t('Styles the first letter of the first body paragraph as a drop cap.'),
      '#default_value' => (bool) ($this->configuration['drop_cap'] ?? FALSE),
    ];

    $form['fade_in'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Fade in on scroll'),
      '#description' => $this->t('Body content fades in as the section scrolls into view. Skipped for users who prefer reduced motion.'),
      '#default_value' => (bool) ($this->configuration['fade_in'] ?? FALSE),
    ];

    $form['columns'] = [
      '#type' => 'select',
      '#title' => $this->t('Column layout'),
      '#description' => $this->t('Split body copy into one or two columns.'),
      '#default_value' => $this->configuration['columns'] ?? '1',
      '#options' => [
        '1' => $this->t('Single column'),
        '2' => $this->t('Two columns'),
      ],
    ];

    $form['body_2'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Second column body copy'),
      '#description' => $this->t('Rich-text content for the second column when two-column layout is selected.'),
      '#default_value' => $this->configuration['body_2_value'] ?? '',
      '#format' => $this->configuration['body_2_format'] ?? filter_default_format(),
      '#required' => ($this->configuration['columns'] ?? '1') === '2',
      '#rows' => 10,
      '#states' => [
        'visible' => [
          ':input[name="settings[columns]"]' => ['value' => '2'],
        ],
        'required' => [
          ':input[name="settings[columns]"]' => ['value' => '2'],
        ],
      ],
    ];

    $form['content_width'] = [
      '#type' => 'select',
      '#title' => $this->t('Content width'),
      '#description' => $this->t('Controls the maximum width of the text container. Narrow improves long-form readability.'),
      '#default_value' => $this->configuration['content_width'] ?? 'normal',
      '#options' => [
        'narrow' => $this->t('Narrow (reading width)'),
        'normal' => $this->t('Normal'),
        'wide' => $this->t('Wide'),
      ],
    ];

    $form['text_alignment'] = [
      '#type' => 'select',
      '#title' => $this->t('Text alignment'),
      '#description' => $this->t('Horizontal alignment of the text content.'),
      '#default_value' => $this->configuration['text_alignment'] ?? 'left',
      '#options' => [
        'left' => $this->t('Left'),
        'center' => $this->t('Center'),
      ],
    ];

    $form['color_theme'] = [
      '#type' => 'select',
      '#title' => $this->t('Color theme'),
      '#description' => $this->t('Light for editorial style, dark for high-contrast narrative sections.'),
      '#default_value' => $this->configuration['color_theme'] ?? 'light',
      '#options' => [
        'light' => $this->t('Light'),
        'dark' => $this->t('Dark'),
      ],
    ];

    $form['cta_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('CTA label'),
      '#description' => $this->t('Optional button text, e.g. "Read more".'),
      '#default_value' => $this->configuration['cta_label'] ?? '',
      '#maxlength' => 255,
    ];

    $cta_node_id = (int) ($this->configuration['cta_node_id'] ?? 0);
    $cta_node = $cta_node_id > 0 ? \Drupal::entityTypeManager()->getStorage('node')->load($cta_node_id) : NULL;
    if (!$cta_node instanceof NodeInterface) {
      $cta_node = NULL;
    }

    $form['cta_node_id'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('CTA internal page'),
      '#description' => $this->t('Select a node for internal links. If selected, this is used instead of external URL.'),
      '#target_type' => 'node',
      '#selection_settings' => ['target_bundles' => NULL],
      '#default_value' => $cta_node,
      '#tags' => FALSE,
    ];

    $form['cta_url'] = [
      '#type' => 'url',
      '#title' => $this->t('CTA external URL'),
      '#description' => $this->t('Use for external destinations. Leave empty if using internal page selector above.'),
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
    $this->configuration['lead'] = trim((string) $form_state->getValue('lead', ''));
    $body = $form_state->getValue('body', []);
    $body_2 = $form_state->getValue('body_2', []);
    $this->configuration['body_value'] = trim((string) ($body['value'] ?? ''));
    $this->configuration['body_format'] = (string) ($body['format'] ?? filter_default_format());
    $this->configuration['body_2_value'] = trim((string) ($body_2['value'] ?? ''));
    $this->configuration['body_2_format'] = (string) ($body_2['format'] ?? filter_default_format());
    $this->configuration['columns'] = (string) $form_state->getValue('columns', '1');
    $this->configuration['content_width'] = (string) $form_state->getValue('content_width', 'normal');
    $this->configuration['text_alignment'] = (string) $form_state->getValue('text_alignment', 'left');
    $this->configuration['color_theme'] = (string) $form_state->getValue('color_theme', 'light');
    $this->configuration['drop_cap'] = (bool) $form_state->getValue('drop_cap', FALSE);
    $this->configuration['fade_in'] = (bool) $form_state->getValue('fade_in', FALSE);
    $this->configuration['cta_label'] = trim((string) $form_state->getValue('cta_label', ''));
    $this->configuration['cta_node_id'] = $this->extractNodeTargetId($form_state->getValue('cta_node_id'));
    $this->configuration['cta_url'] = trim((string) $form_state->getValue('cta_url', ''));
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $body_value = (string) ($this->configuration['body_value'] ?? '');
    if (trim(strip_tags($body_value)) === '') {
      return [];
    }

    $columns = (string) ($this->configuration['columns'] ?? '1');
    $body_2_value = (string) ($this->configuration['body_2_value'] ?? '');
    if ($columns === '2' && trim(strip_tags($body_2_value)) === '') {
      return [];
    }

    $cta_url = trim((string) ($this->configuration['cta_url'] ?? ''));
    $cta_node_id = (int) ($this->configuration['cta_node_id'] ?? 0);
    $cache_tags = [];

    if ($cta_node_id > 0) {
      $node = \Drupal::entityTypeManager()->getStorage('node')->load($cta_node_id);
      if ($node instanceof NodeInterface && $node->access('view')) {
        $cache_tags = Cache::mergeTags($cache_tags, $node->getCacheTags());
        $cta_url = $node->toUrl()->toString();
      }
    }

    return [
      '#theme' => 'custom_flexpage_story_text_section',
      '#eyebrow' => $this->configuration['eyebrow'] ?? '',
      '#headline' => $this->configuration['headline'] ?? '',
      '#lead' => $this->configuration['lead'] ?? '',
      '#body' => [
        '#type' => 'processed_text',
        '#text' => $body_value,
        '#format' => $this->configuration['body_format'] ?? filter_default_format(),
      ],
      '#body_2' => [
        '#type' => 'processed_text',
        '#text' => $body_2_value,
        '#format' => $this->configuration['body_2_format'] ?? filter_default_format(),
      ],
      '#columns' => $columns,
      '#content_width' => $this->configuration['content_width'] ?? 'normal',
      '#text_alignment' => $this->configuration['text_alignment'] ?? 'left',
      '#color_theme' => $this->configuration['color_theme'] ?? 'light',
      '#drop_cap' => $this->configuration['drop_cap'] ?? FALSE,
      '#fade_in' => $this->configuration['fade_in'] ?? FALSE,
      '#cta_label' => $this->configuration['cta_label'] ?? '',
      '#cta_url' => $cta_url,
      '#attached' => [
        'library' => ['custom_flexpage_content/story_text_section_block'],
      ],
      '#cache' => [
        'tags' => $cache_tags,
      ],
    ];
  }

}
