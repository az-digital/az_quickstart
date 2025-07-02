<?php

namespace Drupal\webform;

use Drupal\Core\Form\FormStateInterface;

/**
 * Defines an interface for webform config translation classes.
 */
interface WebformTranslationConfigManagerInterface {

  /**
   * Alter config translation form.
   *
   * @param array $form
   *   Nested array of form elements that comprise the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function alterForm(array &$form, FormStateInterface $form_state);

  /**
   * Validate the webform config translation form.
   *
   * @param array $form
   *   Nested array of form elements that comprise the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function validateWebformForm(array &$form, FormStateInterface $form_state);

}
