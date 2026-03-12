<?php

namespace Drupal\az_search_api\Plugin\search_api\processor\Property;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\search_api\Item\FieldInterface;
use Drupal\search_api\Processor\ConfigurablePropertyBase;

/**
 * Defines a metatag selector.
 *
 * @see \Drupal\az_search_api\Plugin\search_api\processor\AZMetatag
 */
class AZMetatagProperty extends ConfigurablePropertyBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'value' => NULL,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(FieldInterface $field, array $form, FormStateInterface $form_state) {
    $configuration = $field->getConfiguration();
    // Load statically. Parent implementation does not seem to have container.
    $storage = \Drupal::entityTypeManager()->getStorage('metatag_defaults');
    // Get all the metatag defaults.
    $defaults = $storage->loadMultiple();
    $options = [];
    // Condense metatag tree into option list.
    // Ignore duplicates.
    foreach ($defaults as $default) {
      $tags = $default->get('tags') ?? [];
      foreach ($tags as $tag => $data) {
        if (!isset($options[$tag])) {
          $options[$tag] = $this->t('@tag', ['@tag' => $tag]);
        }
      }
    }
    ksort($options);
    $form['value'] = [
      '#type' => 'select',
      '#title' => $this->t('Metatag'),
      '#options' => $options,
      '#description' => $this->t('Select a metatag.'),
      '#default_value' => $configuration['value'] ?? NULL,
      '#required' => TRUE,
    ];

    return $form;
  }

}
