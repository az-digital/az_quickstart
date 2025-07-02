<?php

namespace Drupal\quick_node_clone\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Module settings form.
 */
class QuickNodeCloneNodeSettingsForm extends QuickNodeCloneEntitySettingsForm {

  /**
   * The machine name of the entity type.
   *
   * @var string
   *   The entity type id i.e. node
   */
  protected $entityTypeId = 'node';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'quick_node_clone_node_setting_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['text_to_prepend_to_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Text to prepend to title'),
      '#default_value' => $this->getSettings('text_to_prepend_to_title'),
      '#description' => $this->t('Enter text to add to the title of a cloned node to help content editors. A space will be added between this text and the title. Example: "Clone of"'),
    ];

    $default_clone_status = $this->getSettings('clone_status');
    $form['clone_status'] = [
      '#type' => 'radios',
      '#title' => $this->t('Publication status'),
      '#description' => $this->t('What should the cloned status be?'),
      '#default_value' => $default_clone_status,
      '#options' => [
        'default' => $this->t('Default - Node type default'),
        'original' => $this->t('Original - Clone will have the same status as the original'),
        'published' => $this->t('Published'),
        'unpublished' => $this->t('Unpublished'),
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->cleanValues();
    $form_values = $form_state->getValues();

    $settings = $this->config('quick_node_clone.settings');
    $settings
      ->set('text_to_prepend_to_title', $form_values['text_to_prepend_to_title'])
      ->set('clone_status', $form_values['clone_status'])
      ->save();

    parent::submitForm($form, $form_state);
  }

}
