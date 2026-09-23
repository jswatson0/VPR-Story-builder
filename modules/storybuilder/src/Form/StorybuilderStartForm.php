<?php

namespace Drupal\storybuilder\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Start form: pick a story template or start from scratch.
 */
class StorybuilderStartForm extends FormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  public function __construct(EntityTypeManagerInterface $entity_type_manager, ConfigFactoryInterface $config_factory, AccountProxyInterface $current_user) {
    $this->entityTypeManager = $entity_type_manager;
    $this->configFactory = $config_factory;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('config.factory'),
      $container->get('current_user'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'storybuilder_start_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $options = [];
    $descriptions = [];
    foreach ($this->getAvailableTemplates() as $key => $template) {
      $options[$key] = $template['label'];
      $descriptions[$key] = $template['description'];
    }
    $options['scratch'] = $this->t('Blank page');
    $descriptions['scratch'] = $this->t('An empty Flex Page. Add Visual Storytelling blocks yourself from the block picker.');

    $form['intro'] = [
      '#markup' => '<p>' . $this->t('Start a new story page from a pre-built template, or begin with a blank page. See the <a href=":docs" target="_blank" rel="noopener">Block Reference &amp; Tips</a> for what each Visual Storytelling block can do.', [':docs' => Url::fromRoute('custom_flexpage_content.vs_block_docs')->toString()]) . '</p>',
    ];

    $form['template'] = [
      '#type' => 'radios',
      '#title' => $this->t('How would you like to begin?'),
      '#options' => $options,
      '#required' => TRUE,
    ];
    foreach ($descriptions as $key => $description) {
      $form['template'][$key]['#description'] = $description;
    }
    // utexas_form_elements globally forces #description_display to 'before'
    // for all radio/checkbox/etc. elements, so descriptions land directly
    // under their labels on ordinary fields (where the label displays
    // before the input). Individual radio options display their label
    // *after* the input, so that same 'before' setting instead places each
    // option's description above the *previous* option, reading as if it
    // belongs to the wrong choice. Reset it to 'after' here, in #after_build
    // (which runs once all children - and that site-wide #process override -
    // have already been built) so it sticks for just this radio group.
    $form['template']['#after_build'][] = [static::class, 'moveOptionDescriptionsAfter'];

    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Page title'),
      '#description' => $this->t('The title for your new story page. You can change it later.'),
      '#maxlength' => 255,
      '#states' => [
        'visible' => [
          ':input[name="template"]' => ['!value' => 'scratch'],
        ],
      ],
    ];

    if ($this->currentUser->hasPermission('administer storybuilder')) {
      $form['settings_link'] = [
        '#markup' => '<p><a href="' . Url::fromRoute('storybuilder.settings')->toString() . '">' . $this->t('Configure templates') . '</a></p>',
        '#weight' => 100,
      ];
    }

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Create story page'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * Form #after_build callback: renders option descriptions below each label.
   *
   * @see ::buildForm()
   */
  public static function moveOptionDescriptionsAfter(array $element, FormStateInterface $form_state): array {
    foreach (Element::children($element) as $key) {
      if (isset($element[$key]['#description'])) {
        $element[$key]['#description_display'] = 'after';
      }
    }
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $choice = $form_state->getValue('template');
    if ($choice === 'scratch') {
      return;
    }
    if (trim((string) $form_state->getValue('title')) === '') {
      $form_state->setErrorByName('title', $this->t('Enter a title for your new story page.'));
    }
    $templates = $this->getAvailableTemplates();
    if (!isset($templates[$choice])) {
      $form_state->setErrorByName('template', $this->t('The selected template is no longer available.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $choice = $form_state->getValue('template');

    if ($choice === 'scratch') {
      $form_state->setRedirect('node.add', ['node_type' => 'utexas_flex_page']);
      return;
    }

    $templates = $this->getAvailableTemplates();
    $template_info = $templates[$choice];
    $title = trim((string) $form_state->getValue('title'));

    $storage = $this->entityTypeManager->getStorage('node');
    /** @var \Drupal\node\NodeInterface $template */
    $template = $storage->load($template_info['nid']);

    /** @var \Drupal\node\NodeInterface $duplicate */
    $duplicate = $template->createDuplicate();
    // Clear the inherited alias so pathauto generates one from the new title.
    if ($duplicate->hasField('path')) {
      $duplicate->set('path', ['alias' => '', 'pathauto' => 1]);
    }
    // Draft state must be set before cloning: the clone handler saves the
    // entity, and if that first save is published, the follow-up title/draft
    // save below becomes a non-default forward revision.
    $duplicate->setUnpublished();
    if ($duplicate->hasField('moderation_state')) {
      $duplicate->set('moderation_state', 'draft');
    }

    /** @var \Drupal\entity_clone\EntityClone\EntityCloneInterface $handler */
    $handler = $this->entityTypeManager->getHandler('node', 'entity_clone');
    // The clone handler duplicates layout_builder inline blocks with new
    // UUIDs, but its label logic overrides any pre-set title, so the final
    // title and draft state are applied in a second save below.
    /** @var \Drupal\node\NodeInterface $new_node */
    $new_node = $handler->cloneEntity($template, $duplicate, ['take_ownership' => 1]);

    $new_node->setTitle($title);
    $new_node->setUnpublished();
    if ($new_node->hasField('moderation_state')) {
      $new_node->set('moderation_state', 'draft');
    }
    $new_node->setNewRevision(TRUE);
    $new_node->setRevisionUserId((int) $this->currentUser->id());
    $new_node->setRevisionLogMessage('Created via Storybuilder from template: ' . $template_info['label']);
    $new_node->save();

    $this->messenger()->addStatus($this->t('Your story page %title has been created as a draft. Build out your content below, then publish when ready.', ['%title' => $title]));
    $form_state->setRedirect('layout_builder.overrides.node.view', ['node' => $new_node->id()]);
  }

  /**
   * Returns configured templates whose master node exists, keyed by delta.
   *
   * @return array[]
   *   Arrays with 'label', 'nid', 'description' keys.
   */
  protected function getAvailableTemplates() {
    $templates = [];
    $storage = $this->entityTypeManager->getStorage('node');
    $configured = $this->configFactory->get('storybuilder.settings')->get('templates') ?: [];
    foreach ($configured as $delta => $template) {
      $nid = (int) ($template['nid'] ?? 0);
      if (!$nid) {
        continue;
      }
      $node = $storage->load($nid);
      if (!$node || $node->bundle() !== 'utexas_flex_page') {
        $this->logger('storybuilder')->warning('Storybuilder template "@label" points to missing or non-flex-page node @nid.', [
          '@label' => $template['label'] ?? $delta,
          '@nid' => $nid,
        ]);
        continue;
      }
      $templates['template_' . $delta] = [
        'label' => $template['label'] ?? $node->label(),
        'nid' => $nid,
        'description' => $template['description'] ?? '',
      ];
    }
    return $templates;
  }

}
