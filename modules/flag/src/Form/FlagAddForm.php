<?php

namespace Drupal\flag\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Provides the flag add form.
 *
 * Like FlagEditForm, this class derives from FlagFormBase. This class modifies
 * the base class behavior in two key ways: It alters the text of the submit
 * button, and form where default values are loaded.
 *
 * @see \Drupal\flag\Form\FlagFormBase
 */
class FlagAddForm extends FlagFormBase {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $entity_type = NULL) {
    $form = parent::buildForm($form, $form_state, $entity_type);
    $form['global']['#description'] = $this->t('The scope cannot be changed once a flag has been saved.');

    // While editing, the default value reflects the current link type.
    // Here set the initial value to be an AJAX action link.
    $form['display']['link_type']['#default_value'] = 'ajax_link';
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityFromRouteMatch(RouteMatchInterface $route_match, $entity_type_id) {
    /** @var \Drupal\flag\FlagInterface $flag */
    $flag = parent::getEntityFromRouteMatch($route_match, $entity_type_id);

    // Set the flag type from the route parameter. This is set by the redirect
    // in FlagAddPageForm::submitForm().
    $type = $route_match->getRawParameter('entity_type');

    $flag->setFlagTypePlugin($type);

    return $flag;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#value'] = $this->t('Create Flag');
    return $actions;
  }

}
