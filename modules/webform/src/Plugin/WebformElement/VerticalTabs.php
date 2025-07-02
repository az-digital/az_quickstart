<?php

namespace Drupal\webform\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Element\WebformMessage as WebformMessageElement;
use Drupal\webform\Plugin\WebformElementBase;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a hidden 'vertical_tabs' element.
 *
 * @WebformElement(
 *   id = "vertical_tabs",
 *   label = @Translation("Vertical tabs"),
 *   description = @Translation("Provides a vertical tabs element."),
 *   category = @Translation("Markup elements"),
 *   hidden = TRUE
 * )
 */
class VerticalTabs extends WebformElementBase {

  /**
   * {@inheritdoc}
   */
  protected function defineDefaultProperties() {
    return [
      // Element settings.
      'title' => $this->t('Vertical Tabs'),
      // Description/Help.
      'help' => '',
      'help_title' => '',
      'description' => '',
      'more' => '',
      'more_title' => '',
      // Form display.
      'title_display' => 'invisible',
      'description_display' => '',
      'help_display' => '',
      // Form validation.
      'required' => FALSE,
      // Attributes.
      'wrapper_attributes' => [],
      'label_attributes' => [],
      // Vertical tabs.
      'default_tab' => '',
    ] + $this->defineDefaultBaseProperties();
  }

  /**
   * {@inheritdoc}
   */
  protected function defineDefaultBaseProperties() {
    $properties = parent::defineDefaultBaseProperties();
    unset(
      $properties['prepopulate'],
      $properties['states_clear']
    );
    return $properties;
  }

  /* ************************************************************************ */

  /**
   * {@inheritdoc}
   */
  public function isInput(array $element) {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function isContainer(array $element) {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function prepare(array &$element, WebformSubmissionInterface $webform_submission = NULL) {
    parent::prepare($element, $webform_submission);

    $element += ['#title_display' => 'invisible'];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHtml(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    // Vertical tab are not rendered when a submission is viewed.
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function buildText(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    // Vertical tab are not rendered when a submission is viewed.
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getRelatedTypes(array $element) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getElementSelectorOptions(array $element) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    unset($form['element']['title']['#required']);

    $form['vertical_tabs'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Vertical tabs settings'),
      '#open' => TRUE,
    ];
    $form['vertical_tabs']['vertical_tabs_message'] = [
      '#type' => 'webform_message',
      '#message_type' => 'info',
      '#access' => TRUE,
      '#message_message' => $this->t("To add details and fieldsets this vertical tabs element, you must defined a custom <code>'#group': vertical_tab_key</code> property in the details or fieldset element with this vertical tabs' element key."),
      '#message_close' => TRUE,
      '#message_storage' => WebformMessageElement::STORAGE_SESSION,
    ];
    $form['vertical_tabs']['default_tab'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default tab'),
      '#description' => $this->t('The default tab must be the [id] attributes of the details or fieldset element.'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function preview() {
    return [];
  }

}
