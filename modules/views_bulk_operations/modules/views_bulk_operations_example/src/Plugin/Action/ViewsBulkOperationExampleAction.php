<?php

namespace Drupal\views_bulk_operations_example\Plugin\Action;

use Drupal\Core\Action\Attribute\Action;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\views_bulk_operations\Action\ViewsBulkOperationsActionBase;
use Drupal\views_bulk_operations\Action\ViewsBulkOperationsPreconfigurationInterface;

/**
 * An example action covering most of the possible options.
 *
 * If type is left empty, action will be selectable for all
 * entity types.
 *
 * The api_version annotation parameter specifies the behaviour of displayed
 * messages: anything other than "1" displays counts of exactly what's returned
 * by the action execute() and executeMultiple() method.
 */
#[Action(
  id: 'views_bulk_operations_example',
  label: new TranslatableMarkup('VBO example action'),
  type: ''
)]
class ViewsBulkOperationExampleAction extends ViewsBulkOperationsActionBase implements ViewsBulkOperationsPreconfigurationInterface, PluginFormInterface {

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    /*
     * All config resides in $this->configuration.
     * Passed view rows will be available in $this->context.
     * Data about the view used to select results and optionally
     * the batch context are available in $this->context or externally
     * through the public getContext() method.
     * The entire ViewExecutable object  with selected result
     * rows is available in $this->view or externally through
     * the public getView() method.
     */

    // Do some processing..
    // ...
    $this->messenger()->addMessage($entity->label() . ' - ' . $entity->language()->getId() . ' - ' . $entity->id());
    return $this->t('Example action (configuration: @configuration)', [
      '@configuration' => Markup::create(\print_r($this->configuration, TRUE)),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function buildPreConfigurationForm(array $form, array $values, FormStateInterface $form_state): array {
    $form['example_preconfig_setting'] = [
      '#title' => $this->t('Example setting'),
      '#type' => 'textfield',
      '#default_value' => $values['example_preconfig_setting'] ?? '',
    ];
    return $form;
  }

  /**
   * Configuration form builder.
   *
   * If this method has implementation, the action is
   * considered to be configurable.
   *
   * @param array $form
   *   Form array.
   * @param \Drupal\views_bulk_operations_example\Plugin\Action\Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   *
   * @return array
   *   The configuration form.
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form['example_config_setting'] = [
      '#title' => $this->t('Example setting pre-execute'),
      '#type' => 'textfield',
      '#default_value' => $form_state->getValue('example_config_setting'),
    ];
    return $form;
  }

  /**
   * Submit handler for the action configuration form.
   *
   * If not implemented, the cleaned form values will be
   * passed directly to the action $configuration parameter.
   *
   * @param array $form
   *   Form array.
   * @param \Drupal\views_bulk_operations_example\Plugin\Action\Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    // This is not required here, when this method is not defined,
    // form values are assigned to the action configuration by default.
    // This function is a must only when user input processing is needed.
    $this->configuration['example_config_setting'] = $form_state->getValue('example_config_setting');
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, ?AccountInterface $account = NULL, $return_as_object = FALSE) {
    // If certain fields are updated, access should be checked against them
    // as well. @see Drupal\Core\Field\FieldUpdateActionBase::access().
    return $object->access('update', $account, $return_as_object);
  }

}
