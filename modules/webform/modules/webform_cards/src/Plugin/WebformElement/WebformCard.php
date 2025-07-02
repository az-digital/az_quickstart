<?php

namespace Drupal\webform_cards\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Plugin\WebformElement\ContainerBase;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a 'card' element.
 *
 * @WebformElement(
 *   id = "webform_card",
 *   label = @Translation("Card"),
 *   description = @Translation("Provides an element for a fast clientside pagination."),
 *   category = @Translation("Cards"),
 *   hidden = TRUE,
 * )
 */
class WebformCard extends ContainerBase {

  /**
   * {@inheritdoc}
   */
  protected function defineDefaultProperties() {
    $properties = [
      'title' => '',
      'title_tag' => \Drupal::config('webform.settings')->get('element.default_section_title_tag'),
      'title_display' => '',
      'title_attributes' => [],
      'prev_button_label' => '',
      'next_button_label' => '',
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
  public function isRoot() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getItemDefaultFormat() {
    return 'details';
  }

  /**
   * {@inheritdoc}
   */
  public function prepare(array &$element, WebformSubmissionInterface $webform_submission = NULL) {
    parent::prepare($element, $webform_submission);

    if (empty($element['#title_tag'])) {
      $element['#title_tag'] = $this->getDefaultProperty('title_tag');
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function formatHtmlItem(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    $build = parent::formatHtmlItem($element, $webform_submission, $options);

    // Add edit page link container to preview.
    // @see Drupal.behaviors.webformCards
    if ($build && isset($options['view_mode']) && $options['view_mode'] === 'preview' && $webform_submission->getWebform()->getSetting('wizard_preview_link')) {
      $build['#children']['wizard_page_link'] = [
        '#type' => 'container',
        '#attributes' => [
          'title' => $element['#title'],
          'class' => ['webform-card-edit'],
          'data-webform-card' => $element['#webform_key'],
        ],
      ];
    }

    return $build;
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
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = $form_state->getFormObject()->getWebform();

    $form['card'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Card settings'),
    ];
    $form['card']['title_tag'] = [
      '#type' => 'webform_select_other',
      '#title' => $this->t('Title tag'),
      '#description' => $this->t("The card's title HTML tag."),
      '#options' => [
        'h1' => $this->t('Header 1 (h1)'),
        'h2' => $this->t('Header 2 (h2)'),
        'h3' => $this->t('Header 3 (h3)'),
        'h4' => $this->t('Header 4 (h4)'),
        'h5' => $this->t('Header 5 (h5)'),
        'h6' => $this->t('Header 6 (h6)'),
        'label' => $this->t('Label (label)'),
      ],
    ];
    $form['card']['prev_button_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Previous page button label'),
      '#description' => $this->t('This is used for the Next Page button on the card.') . '<br /><br />' .
      $this->t('Defaults to: %value', ['%value' => $webform->getSetting('wizard_prev_button_label', TRUE)]),
    ];
    $form['card']['next_button_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Next page button label'),
      '#description' => $this->t('This is used for the Previous button on the card.') . '<br /><br />' .
      $this->t('Defaults to: %value', ['%value' => $webform->getSetting('wizard_next_button_label', TRUE)]),
    ];

    $form['form']['display_container']['title_display']['#options'] = [
      'none' => $this->t('None'),
      'invisible' => $this->t('Invisible'),
    ];

    return $form;
  }

}
