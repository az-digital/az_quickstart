<?php

namespace Drupal\block_field\Plugin\block_field\BlockFieldSelection;

use Drupal\block_field\BlockFieldSelectionBase;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Provides a 'categories' BlockFieldSection.
 *
 * @BlockFieldSelection(
 *   id = "blocks",
 *   label = @Translation("Blocks"),
 * )
 */
class Blocks extends BlockFieldSelectionBase {

  use DependencySerializationTrait;
  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'plugin_ids' => [],
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    /** @var \Drupal\block_field\BlockFieldManagerInterface $block_field_manager */
    $block_field_manager = \Drupal::service('block_field.manager');
    $definitions = $block_field_manager->getBlockDefinitions();
    foreach ($definitions as $plugin_id => $definition) {
      $options[$plugin_id] = [
        ['category' => (string) $definition['category']],
        ['label' => $definition['admin_label'] . ' (' . $plugin_id . ')'],
        ['provider' => $definition['provider']],
      ];
    }
    $default_value = !empty($this->getConfiguration()['plugin_ids']) ? $this->getConfiguration()['plugin_ids'] : array_keys($options);
    $form['blocks'] = [
      '#type' => 'details',
      '#title' => $this->t('Blocks'),
      '#description' => $this->t('Please select available blocks.'),
      '#open' => empty($this->getConfiguration()['plugin_ids']),
      '#process' => [[$this, 'formProcessMergeParent']],
    ];
    $form['blocks']['plugin_ids'] = [
      '#type' => 'tableselect',
      '#header' => [
        $this->t('Category'),
        $this->t('Label/ID'),
        $this->t('Provider'),
      ],
      '#options' => $options,
      '#js_select' => TRUE,
      '#required' => TRUE,
      '#empty' => $this->t('No blocks are available.'),
      '#element_validate' => [[get_called_class(), 'validatePluginIds']],
      '#default_value' => array_combine($default_value, $default_value),
    ];
    return $form;
  }

  /**
   * Validates plugin_ids table select element.
   *
   * @param array $element
   *   A form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The $form_state from complete form.
   * @param array $complete_form
   *   Complete parent form.
   *
   * @return array
   *   Returns element with validated plugin ids.
   */
  public static function validatePluginIds(array &$element, FormStateInterface $form_state, array &$complete_form) {
    $value = array_filter($element['#value']);
    if (array_keys($element['#options']) == array_keys($value)) {
      $form_state->setValueForElement($element, []);
    }
    else {
      $form_state->setValueForElement($element, $value);
    }
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function getReferenceableBlockDefinitions() {
    $block_field_manager = \Drupal::service('block_field.manager');
    $definitions = $block_field_manager->getBlockDefinitions();
    $values = !empty($this->getConfiguration()['plugin_ids']) ? $this->getConfiguration()['plugin_ids'] : array_keys($definitions);
    $values = array_combine($values, $values);
    return array_intersect_key($definitions, $values);
  }

}
