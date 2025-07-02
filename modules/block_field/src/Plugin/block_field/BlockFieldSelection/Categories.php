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
 *   id = "categories",
 *   label = @Translation("Categories"),
 * )
 */
class Categories extends BlockFieldSelectionBase {

  use DependencySerializationTrait;
  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'categories' => [],
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    /** @var \Drupal\block_field\BlockFieldManagerInterface $block_field_manager */
    $block_field_manager = \Drupal::service('block_field.manager');
    $categories = $block_field_manager->getBlockCategories();
    $options = [];
    foreach ($categories as $category) {
      $category = (string) $category;
      $options[$category] = $category;
    }
    $form['categories'] = [
      '#type' => 'details',
      '#title' => $this->t('Categories'),
      '#description' => $this->t('Please select available categories.'),
      '#open' => empty($this->getConfiguration()['categories']) ,
      '#process' => [[$this, 'formProcessMergeParent']],
    ];
    $default_value = !empty($this->getConfiguration()['categories']) ? $this->getConfiguration()['categories'] : array_keys($options);
    $form['categories']['categories'] = [
      '#type' => 'checkboxes',
      '#header' => [
        'Category',
      ],
      '#options' => $options,
      '#js_select' => TRUE,
      '#empty' => $this->t('No categories are available.'),
      '#required' => TRUE,
      '#default_value' => array_combine($default_value, $default_value),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getReferenceableBlockDefinitions() {
    $block_field_manager = \Drupal::service('block_field.manager');
    $definitions = $block_field_manager->getBlockDefinitions();
    if (!empty($this->getConfiguration()['categories'])) {
      $categories = array_filter($this->getConfiguration()['categories']);
      $definitions = array_filter($definitions, function ($definition, $key) use ($categories) {
        return isset($categories[(string) $definition['category']]);
      }, ARRAY_FILTER_USE_BOTH);
    }
    return $definitions;
  }

}
