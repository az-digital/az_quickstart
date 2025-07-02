<?php

namespace Drupal\webform\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Plugin\WebformElementBase;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a 'webform_codemirror' element.
 *
 * @WebformElement(
 *   id = "webform_codemirror",
 *   label = @Translation("CodeMirror"),
 *   description = @Translation("Provides a form element for editing code in a number of programming languages and markup. Code editing support is provided by the <a href=""https://codemirror.net"">CodeMirror</a> library."),
 *   category = @Translation("Advanced elements"),
 *   multiline = TRUE,
 * )
 */
class WebformCodeMirror extends WebformElementBase {

  /**
   * {@inheritdoc}
   */
  protected function defineDefaultProperties() {
    $properties = [
      // Codemirror settings.
      'placeholder' => '',
      'mode' => 'text',
      'wrap' => TRUE,
    ] + parent::defineDefaultProperties();
    unset(
      $properties['format_items'],
      $properties['format_items_html'],
      $properties['format_items_text']
    );
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  protected function defineTranslatableProperties() {
    return array_merge(parent::defineTranslatableProperties(), ['default_value']);
  }

  /* ************************************************************************ */

  /**
   * {@inheritdoc}
   */
  protected function formatHtmlItem(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    $value = $this->getValue($element, $webform_submission, $options);

    if (empty($value)) {
      return '';
    }

    $element += ['#mode' => 'text'];
    $format = $this->getItemFormat($element);
    switch ($format) {
      case 'code':
        return [
          '#theme' => 'webform_codemirror',
          '#code' => $value,
          '#type' => $element['#mode'],
        ];

      default:
        return parent::formatHtmlItem($element, $webform_submission, $options);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getItemDefaultFormat() {
    return 'code';
  }

  /**
   * {@inheritdoc}
   */
  public function getItemFormats() {
    return parent::getItemFormats() + [
      'code' => $this->t('Code'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function preview() {
    return parent::preview() + [
      '#mode' => 'yaml',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getTestValues(array $element, WebformInterface $webform, array $options = []) {
    $element += ['#mode' => 'text'];
    switch ($element['#mode']) {
      case 'html':
        return ['<p><b>Hello World!!!</b></p>'];

      case 'yaml':
        return ["message: 'Hello World'"];

      case 'text':
        return ["Hello World"];

      default:
        return [];

    }
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $form['default']['default_value']['#type'] = 'webform_codemirror';
    $form['default']['default_value']['#rows'] = 3;

    $form['codemirror'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('CodeMirror settings'),
    ];
    $form['codemirror']['mode'] = [
      '#title' => $this->t('Mode'),
      '#type' => 'select',
      '#options' => [
        'text' => $this->t('Plain text'),
        'yaml' => $this->t('YAML'),
        'html' => $this->t('HTML'),
        'htmlmixed' => $this->t('HTML (CSS & JavaScript)'),
        'css' => $this->t('CSS'),
        'javascript' => $this->t('JavaScript'),
        'php' => $this->t('PHP'),
        'twig' => $this->t('Twig'),
      ],
      '#required' => TRUE,
    ];
    $form['codemirror']['wrap'] = [
      '#title' => $this->t('Wrap long lines of text'),
      '#type' => 'checkbox',
      '#return_value' => TRUE,
    ];
    return $form;
  }

}
