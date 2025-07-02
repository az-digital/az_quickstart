<?php

namespace Drupal\coffee\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\system\Entity\Menu;

/**
 * Configure Coffee for this site.
 */
class CoffeeConfigurationForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'coffee_configuration_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'coffee.configuration',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('coffee.configuration');

    $form['coffee_menus'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Menus to include'),
      '#description' => $this->t('Select the menus that should be used by Coffee to search.'),
      '#options' => $this->getMenuLabels(),
      '#default_value' => $config->get('coffee_menus'),
    ];

    $form['max_results'] = [
      '#type' => 'number',
      '#title' => $this->t('Max results'),
      '#description' => $this->t('Maximum number of items to show in the search results.'),
      '#default_value' => $config->get('max_results'),
      '#required' => TRUE,
      '#min' => 1,
      '#max' => 50,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->config('coffee.configuration')
      ->set('coffee_menus', array_filter($values['coffee_menus']))
      ->set('max_results', $values['max_results'])
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Return an associative array of menus names.
   *
   * @return array
   *   An array with the machine-readable names as the keys, and human-readable
   *   titles as the values.
   */
  protected function getMenuLabels() {
    $menus = [];
    foreach (Menu::loadMultiple() as $menu_name => $menu) {
      $menus[$menu_name] = $menu->label();
    }
    asort($menus);

    return $menus;
  }

}
