<?php

declare(strict_types=1);

namespace Drupal\views_remote_data\Plugin\views;

/**
 * Trait used by views plugins to support property paths in remote data.
 */
trait PropertyPluginTrait {

  /**
   * Adds property_path to the plugin's options.
   *
   * @param array $options
   *   The options.
   */
  protected function definePropertyPathOption(array &$options): void {
    $options['property_path'] = ['default' => ''];
  }

  /**
   * Adds the property path element to a settings form.
   *
   * @param array $form
   *   The form.
   * @param array $options
   *   The plugin options.
   */
  protected function propertyPathElement(array &$form, array $options): void {
    $form['property_path'] = [
      '#title' => $this->t('Property path from incoming object'),
      '#type' => 'textfield',
      '#default_value' => $options['property_path'] ?? '',
      '#required' => TRUE,
    ];
  }

}
