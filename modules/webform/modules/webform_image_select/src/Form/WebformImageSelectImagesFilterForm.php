<?php

namespace Drupal\webform_image_select\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the webform image select images filter form.
 */
class WebformImageSelectImagesFilterForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'webform_image_select_images_filter_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $search = NULL, $category = NULL, array $categories = []) {
    $form['#attributes'] = ['class' => ['webform-filter-form']];
    $form['filter'] = [
      '#type' => 'details',
      '#title' => $this->t('Filter images'),
      '#open' => TRUE,
      '#attributes' => ['class' => ['container-inline']],
    ];
    $form['filter']['search'] = [
      '#type' => 'search',
      '#title' => $this->t('Keyword'),
      '#title_display' => 'invisible',
      '#autocomplete_route_name' => 'entity.webform_image_select_images.autocomplete',
      '#placeholder' => $this->t('Filter by title or images'),
      '#size' => 45,
      '#default_value' => $search,
    ];
    $form['filter']['category'] = [
      '#type' => 'select',
      '#title' => $this->t('Category'),
      '#title_display' => 'invisible',
      '#options' => $categories,
      '#empty_option' => ($category) ? $this->t('Show all images') : $this->t('Filter by category'),
      '#default_value' => $category,
    ];
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
