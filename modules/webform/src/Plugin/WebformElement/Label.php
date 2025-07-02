<?php

namespace Drupal\webform\Plugin\WebformElement;

use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a 'label' element.
 *
 * @WebformElement(
 *   id = "label",
 *   api = "https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Render!Element!Label.php/class/Label",
 *   label = @Translation("Label"),
 *   description = @Translation("Provides an element for displaying the label for a form element."),
 *   category = @Translation("Markup elements"),
 *   states_wrapper = TRUE,
 * )
 */
class Label extends WebformMarkupBase {

  /**
   * {@inheritdoc}
   */
  protected function defineDefaultProperties() {
    return [
      'title' => '',
      // Form validation.
      'required' => FALSE,
      // Attributes.
      'attributes' => [],
    ] + parent::defineDefaultProperties();
  }

  /* ************************************************************************ */

  /**
   * {@inheritdoc}
   */
  public function buildHtml(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    // Unset #required which is not appropriate when displaying a label.
    unset($element['#required']);

    // Must define #title_display to prevent PHP warnings via the preprocessor.
    // @see template_preprocess_form_element_label()
    $element += ['#title_display' => NULL];

    return parent::buildHtml($element, $webform_submission, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function getItemDefaultFormat() {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getItemFormats() {
    return [];
  }

}
