<?php

namespace Drupal\webform\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\EntityStorage\WebformEntityStorageTrait;
use Drupal\webform\WebformInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the webform filter form.
 */
class WebformEntityFilterForm extends FormBase {

  use WebformEntityStorageTrait;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'webform_filter_form';
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
  public function buildForm(array $form, FormStateInterface $form_state, $search = NULL, $category = NULL, $state = NULL, array $state_options = []) {
    $form['#attributes'] = ['class' => ['webform-filter-form']];
    $form['filter'] = [
      '#type' => 'details',
      '#title' => $this->t('Filter webforms'),
      '#open' => TRUE,
      '#attributes' => ['class' => ['container-inline']],
    ];
    $form['filter']['search'] = [
      '#type' => 'search',
      '#title' => $this->t('Keyword'),
      '#title_display' => 'invisible',
      '#autocomplete_route_name' => 'entity.webform.autocomplete' . ($state === WebformInterface::STATUS_ARCHIVED ? '.archived' : ''),
      '#placeholder' => $this->t('Filter by keyword'),
      // Allow autocomplete to use long webform titles.
      '#maxlength' => 500,
      '#size' => 45,
      '#default_value' => $search,
    ];
    $form['filter']['category'] = [
      '#type' => 'select',
      '#title' => $this->t('Category'),
      '#title_display' => 'invisible',
      '#options' => $this->getWebformStorage()->getCategories(FALSE),
      '#empty_option' => ($category) ? $this->t('Show all webforms') : $this->t('Filter by category'),
      '#default_value' => $category,
    ];
    if (empty($form['filter']['category']['#options'])) {
      $form['filter']['category']['#access'] = FALSE;
    }
    $form['filter']['state'] = [
      '#type' => 'select',
      '#title' => $this->t('State'),
      '#title_display' => 'invisible',
      '#options' => $state_options,
      '#default_value' => $state,
    ];
    $form['filter']['submit'] = [
      '#type' => 'submit',
      '#button_type' => 'primary',
      '#value' => $this->t('Filter'),
    ];
    if (!empty($search) || !empty($category) || !empty($state)) {
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
    $state = $form_state->getValue('state') ?? '';
    $query = [
      'search' => trim($search),
      'category' => trim($category),
      'state' => trim($state),
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
