<?php

namespace Drupal\storybuilder\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;

/**
 * Settings form: designate the master template nodes.
 */
class StorybuilderSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'storybuilder_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['storybuilder.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $templates = $this->config('storybuilder.settings')->get('templates') ?: [];

    $form['help'] = [
      '#markup' => '<p>' . $this->t('Each template is a master Flex Page node (typically unpublished) that Storybuilder clones when an editor starts a new story. Edit the master nodes themselves to change what a template contains.') . '</p>',
    ];

    $form['templates'] = ['#tree' => TRUE];
    foreach ($templates as $delta => $template) {
      $nid = (int) ($template['nid'] ?? 0);
      $form['templates'][$delta] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Template @num', ['@num' => $delta + 1]),
      ];
      $form['templates'][$delta]['label'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Label'),
        '#default_value' => $template['label'] ?? '',
        '#required' => TRUE,
      ];
      $form['templates'][$delta]['nid'] = [
        '#type' => 'entity_autocomplete',
        '#target_type' => 'node',
        '#selection_settings' => ['target_bundles' => ['utexas_flex_page']],
        '#title' => $this->t('Master node'),
        '#description' => $this->t('The Flex Page node that will be cloned.'),
        '#default_value' => $nid ? Node::load($nid) : NULL,
      ];
      $form['templates'][$delta]['description'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Description'),
        '#description' => $this->t('Shown to editors under the template option.'),
        '#default_value' => $template['description'] ?? '',
        '#maxlength' => 255,
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $templates = [];
    foreach ($form_state->getValue('templates') as $template) {
      $templates[] = [
        'label' => $template['label'],
        'nid' => (int) ($template['nid'] ?? 0),
        'description' => $template['description'],
      ];
    }
    $this->config('storybuilder.settings')->set('templates', $templates)->save();
    parent::submitForm($form, $form_state);
  }

}
