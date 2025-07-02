<?php

namespace Drupal\field_group\Plugin\field_group\FieldGroupFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Render\Markup;
use Drupal\field_group\FieldGroupFormatterBase;

/**
 * Plugin implementation of the 'details' formatter.
 *
 * @FieldGroupFormatter(
 *   id = "details",
 *   label = @Translation("Details"),
 *   description = @Translation("Add a details element"),
 *   supported_contexts = {
 *     "form",
 *     "view"
 *   }
 * )
 */
class Details extends FieldGroupFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function process(&$element, $processed_object) {

    $element += [
      '#type' => 'details',
      '#title' => $this->getSetting('label_as_html') ? Markup::create(Xss::filterAdmin($this->getLabel())) : Markup::create(Html::escape($this->getLabel())),
      '#open' => $this->getSetting('open'),
      '#show_empty_fields' => $this->getSetting('show_empty_fields'),
      '#description' => $this->getSetting('description'),
    ];

    if ($this->getSetting('id')) {
      $element['#id'] = Html::getUniqueId($this->getSetting('id'));
    }

    $classes = $this->getClasses();
    if (!empty($classes)) {
      $element += [
        '#attributes' => ['class' => $classes],
      ];
    }

    if ($this->getSetting('required_fields')) {
      $element['#attached']['library'][] = 'field_group/formatter.details';
      $element['#attached']['library'][] = 'field_group/core';
    }

  }

  /**
   * {@inheritdoc}
   */
  public function preRender(&$element, $rendering_object) {
    parent::preRender($element, $rendering_object);
    $this->process($element, $rendering_object);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm() {
    $form = parent::settingsForm();

    $form['description'] = [
      '#title' => $this->t('Description'),
      '#type' => 'textarea',
      '#default_value' => $this->getSetting('description'),
      '#weight' => -4,
    ];

    $form['open'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display element open by default.'),
      '#default_value' => $this->getSetting('open'),
    ];

    if ($this->context == 'form') {
      $form['required_fields'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Mark group as required if it contains required fields.'),
        '#default_value' => $this->getSetting('required_fields'),
        '#weight' => 2,
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {

    $summary = [];
    if ($this->getSetting('open')) {
      $summary[] = $this->t('Default state open');
    }
    else {
      $summary[] = $this->t('Default state closed');
    }

    if ($this->getSetting('required_fields')) {
      $summary[] = $this->t('Mark as required');
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultContextSettings($context) {
    $defaults = [
      'open' => FALSE,
      'required_fields' => $context == 'form',
    ] + parent::defaultSettings($context);

    if ($context == 'form') {
      $defaults['required_fields'] = 1;
    }

    return $defaults;
  }

}
