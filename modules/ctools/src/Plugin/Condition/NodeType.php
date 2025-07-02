<?php

namespace Drupal\ctools\Plugin\Condition;

use Drupal\Core\Form\FormStateInterface;

/**
 * Entity Bundle Constraints
 *
 * Adds constraints to the core NodeType condition.
 *
 * @deprecated in ctools:8.x-1.10.
 *   Use \Drupal\ctools\Plugin\Condition\EntityBundle instead.
 *
 * @see https://www.drupal.org/node/2983299
 */
//@phpstan-ignore-next-line
class NodeType extends EntityBundle {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $bundles = $this->entityTypeBundleInfo->getBundleInfo('node');
    $form['bundles'] = [
      '#title' => $this->pluginDefinition['label'],
      '#type' => 'checkboxes',
      '#options' => array_combine(array_keys($bundles), array_column($bundles, 'label')),
      '#default_value' => $this->configuration['bundles'],
    ];
    return $form;
  }

}
