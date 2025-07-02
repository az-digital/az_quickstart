<?php

namespace Drupal\views_bulk_operations\Action;

use Drupal\Core\Form\FormStateInterface;

/**
 * Defines methods for a preconfigurable Views Bulk Operations action.
 */
interface ViewsBulkOperationsPreconfigurationInterface {

  /**
   * Build preconfigure action form elements.
   *
   * @param array $element
   *   Element of the views API form where configuration resides.
   * @param array $values
   *   Current values of the plugin pre-configuration.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state interface object.
   *
   * @return array
   *   The action configuration form element.
   */
  public function buildPreConfigurationForm(array $element, array $values, FormStateInterface $form_state): array;

}
