<?php

namespace Drupal\webform_templates\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\EntityStorage\WebformEntityStorageTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the webform templates filter webform.
 */
class WebformTemplatesFilterForm extends FormBase {

  use WebformEntityStorageTrait;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'webform_templates_filter_form';
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $search = NULL, $category = NULL) {
    $form['#attributes'] = ['class' => ['webform-filter-form']];
    $form['filter'] = [
      '#type' => 'details',
      '#title' => $this->t('Filter templates'),
      '#open' => TRUE,
      '#attributes' => ['class' => ['container-inline']],
    ];
    $form['filter']['search'] = [
      '#type' => 'search',
      '#title' => $this->t('Filter by title, description, or elements'),
      '#title_display' => 'invisible',
      '#placeholder' => $this->t('Filter by title, description, or elements'),
      '#maxlength' => 128,
      '#size' => 40,
      '#autocomplete_route_name' => 'entity.webform.templates.autocomplete',
      '#default_value' => $search,
    ];
    $form['filter']['category'] = [
      '#type' => 'select',
      '#title' => $this->t('Category'),
      '#title_display' => 'invisible',
      '#options' => $this->getWebformStorage()->getCategories(TRUE),
      '#empty_option' => ($category) ? $this->t('Show all webforms') : $this->t('Filter by category'),
      '#default_value' => $category,
    ];
    if (empty($form['filter']['category']['#options'])) {
      $form['filter']['category']['#access'] = FALSE;
    }
    $form['filter']['submit'] = [
      '#type' => 'submit',
      '#button_type' => 'primary',
      '#value' => $this->t('Filter'),
    ];
    if (!empty($search) || !empty($category)) {
      $form['filter']['reset'] = [
        '#type' => 'submit',
        '#submit' => ['::resetForm'],
        '#value' => $this->t('Reset'),
      ];
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $search = $form_state->getValue('search') ?? '';
    $category = $form_state->getValue('category') ?? '';
    $query = [
      'search' => trim($search),
      'category' => trim($category),
    ];
    $form_state->setRedirect($this->getRouteMatch()->getRouteName(), $this->getRouteMatch()->getRawParameters()->all(), [
      'query' => $query ,
    ]);
  }

  /**
   * Resets the filter selection.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function resetForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirect($this->getRouteMatch()->getRouteName(), $this->getRouteMatch()->getRawParameters()->all());
  }

}
