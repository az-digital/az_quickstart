<?php

namespace Drupal\views_bulk_operations_test\Plugin\Action;

use Drupal\Core\Action\Attribute\Action;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\views\ViewExecutable;
use Drupal\views_bulk_operations\Action\ViewsBulkOperationsActionBase;
use Drupal\views_bulk_operations\Action\ViewsBulkOperationsPreconfigurationInterface;

/**
 * Action for test purposes only.
 */
#[Action(
  id: 'views_bulk_operations_advanced_test_action',
  label: new TranslatableMarkup('VBO example action'),
  type: ''
)]
class ViewsBulkOperationsAdvancedTestAction extends ViewsBulkOperationsActionBase implements ViewsBulkOperationsPreconfigurationInterface, PluginFormInterface {
  use MessengerTrait;

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    // Check if $this->view is an instance of ViewsExecutable.
    if (!($this->view instanceof ViewExecutable)) {
      throw new \Exception('View passed to action object is not an instance of \Drupal\views\ViewExecutable.');
    }

    // Check if context array has been passed to the action.
    if (empty($this->context)) {
      throw new \Exception('Context array empty in action object.');
    }

    $this->messenger()->addMessage(\sprintf('Test action (preconfig: %s, config: %s, label: %s)',
      $this->configuration['test_preconfig'],
      $this->configuration['test_config'],
      $entity->label()
    ));

    // Unpublish entity.
    if ($this->configuration['test_config'] === 'unpublish') {
      if (!$entity->isDefaultTranslation()) {
        $entity = \Drupal::service('entity_type.manager')->getStorage('node')->load($entity->id());
      }
      $entity->setUnpublished();
      $entity->save();
    }

    return $this->t('Test');
  }

  /**
   * {@inheritdoc}
   */
  public function buildPreConfigurationForm(array $element, array $values, FormStateInterface $form_state): array {
    $element['test_preconfig'] = [
      '#title' => $this->t('Preliminary configuration'),
      '#type' => 'textfield',
      '#default_value' => $values['preconfig'] ?? '',
    ];
    return $element;
  }

  /**
   * Configuration form builder.
   *
   * @param array $form
   *   Form array.
   * @param \Drupal\views_bulk_operations_test\Plugin\Action\Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   *
   * @return array
   *   The configuration form.
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form['test_config'] = [
      '#title' => $this->t('Config'),
      '#type' => 'textfield',
      '#default_value' => $form_state->getValue('config'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, ?AccountInterface $account = NULL, $return_as_object = FALSE) {
    return $object->access('update', $account, $return_as_object);
  }

}
