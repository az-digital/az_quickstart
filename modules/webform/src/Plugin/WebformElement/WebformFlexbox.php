<?php

namespace Drupal\webform\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a 'flexbox' element.
 *
 * @WebformElement(
 *   id = "webform_flexbox",
 *   default_key = "flexbox",
 *   api = "https://developer.mozilla.org/en-US/docs/Learn/CSS/CSS_layout/Flexbox",
 *   label = @Translation("Flexbox layout"),
 *   description = @Translation("Provides a flex(ible) box container used to layout elements in multiple columns."),
 *   category = @Translation("Containers"),
 * )
 */
class WebformFlexbox extends Container {

  /**
   * {@inheritdoc}
   */
  protected function defineDefaultProperties() {
    return [
      // Flexbox.
      'align_items' => 'flex-start',
    ] + parent::defineDefaultProperties();
  }

  /* ************************************************************************ */

  /**
   * {@inheritdoc}
   */
  protected function build($format, array &$element, WebformSubmissionInterface $webform_submission, array $options = []) {
    /** @var \Drupal\webform\WebformSubmissionViewBuilderInterface $view_builder */
    $view_builder = $this->entityTypeManager->getViewBuilder('webform_submission');
    return $view_builder->buildElements($element, $webform_submission, $options, $format);
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
    $form['flexbox'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Flexbox settings'),
    ];
    $form['flexbox']['align_items'] = [
      '#type' => 'select',
      '#title' => $this->t('Align items'),
      '#options' => [
        'flex-start' => $this->t('Top (flex-start)'),
        'flex-end' => $this->t('Bottom (flex-end)'),
        'center' => $this->t('Center (center)'),
      ],
      '#required' => TRUE,
    ];
    return $form;
  }

}
