<?php

namespace Drupal\webform\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Plugin\WebformElementWizardPageInterface;
use Drupal\webform\Utility\WebformElementHelper;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a 'webform_wizard_page' element.
 *
 * @WebformElement(
 *   id = "webform_wizard_page",
 *   label = @Translation("Wizard page"),
 *   description = @Translation("Provides an element to display multiple form elements as a page in a multi-step form wizard."),
 *   category = @Translation("Wizard"),
 *   hidden = TRUE,
 * )
 */
class WebformWizardPage extends Details implements WebformElementWizardPageInterface {

  /**
   * {@inheritdoc}
   */
  protected function defineDefaultProperties() {
    $properties = [
      'title' => '',
      'open' => FALSE,
      'prev_button_label' => '',
      'next_button_label' => '',
      // Attributes.
      'attributes' => [],
      // Submission display.
      'format' => $this->getItemDefaultFormat(),
      'format_html' => '',
      'format_text' => '',
      'format_attributes' => [],
    ] + $this->defineDefaultBaseProperties();
    unset($properties['flex']);
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  protected function defineTranslatableProperties() {
    return array_merge(parent::defineTranslatableProperties(), ['prev_button_label', 'next_button_label']);
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
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function isRoot() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function preview() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  protected function formatHtmlItem(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    $build = parent::formatHtmlItem($element, $webform_submission, $options);

    // Add edit page link container to preview.
    // @see Drupal.behaviors.webformWizardPagesLink
    if ($build && isset($options['view_mode']) && $options['view_mode'] === 'preview' && $webform_submission->getWebform()->getSetting('wizard_preview_link')) {
      $build['#children']['wizard_page_link'] = [
        '#type' => 'container',
        '#attributes' => [
          'data-webform-page' => $element['#webform_key'],
          'class' => ['webform-wizard-page-edit'],
        ],
      ];
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = $form_state->getFormObject()->getWebform();

    $form['wizard_page'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Page settings'),
    ];
    $form['wizard_page']['prev_button_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Previous page button label'),
      '#description' => $this->t('This is used for the Previous Page button on the page before this page break.') . '<br /><br />' .
      $this->t('Defaults to: %value', ['%value' => $webform->getSetting('wizard_prev_button_label', TRUE)]),
    ];
    $form['wizard_page']['next_button_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Next page button label'),
      '#description' => $this->t('This is used for the Next Page button on the page after this page break.') . '<br /><br />' .
      $this->t('Defaults to: %value', ['%value' => $webform->getSetting('wizard_next_button_label', TRUE)]),
    ];

    // Wizard pages only support visible or hidden state.
    $form['conditional_logic']['states']['#multiple'] = FALSE;

    return $form;
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
  public function getElementStateOptions() {
    return [
      'visible' => $this->t('Visible'),
      'invisible' => $this->t('Hidden'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function showPage(array &$element) {
    // When showing a wizard page, page render it as a container, fieldset or
    // section instead of the default details element.
    // @see \Drupal\webform\Element\WebformWizardPage
    $webform_id = $element['#webform'];
    $webform = Webform::load($webform_id);
    $page_type = $webform->getSetting('wizard_page_type') ?: 'container';
    $element['#type'] = $page_type;

    // Set section title tag.
    // @see \Drupal\webform\Plugin\WebformElement\WebformSection
    if ($page_type === 'webform_section') {
      $element['#title_tag'] = $webform->getSetting('wizard_page_title_tag');
    }

    // Unset default details properties.
    unset($element['#open']);
  }

  /**
   * {@inheritdoc}
   */
  public function hidePage(array &$element) {
    // Set #access to FALSE which will suppresses webform #required validation.
    WebformElementHelper::setPropertyRecursive($element, '#access', FALSE);
  }

}
