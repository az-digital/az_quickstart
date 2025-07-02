<?php

namespace Drupal\field_group\Plugin\field_group\FieldGroupFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Render\Markup;
use Drupal\field_group\FieldGroupFormatterBase;

/**
 * Plugin implementation of the 'tab' formatter.
 *
 * @FieldGroupFormatter(
 *   id = "tab",
 *   label = @Translation("Tab"),
 *   description = @Translation("This fieldgroup renders the content as a tab."),
 *   format_types = {
 *     "open",
 *     "closed",
 *   },
 *   supported_contexts = {
 *     "form",
 *     "view",
 *   }
 * )
 */
class Tab extends FieldGroupFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function process(&$element, $processed_object) {

    // Keep using preRender parent for BC.
    parent::preRender($element, $processed_object);

    $add = [
      '#type' => 'details',
      '#title' => $this->getSetting('label_as_html') ? Markup::create(Xss::filterAdmin($this->getLabel())) : Markup::create(Html::escape($this->getLabel())),
      '#description' => $this->getSetting('description'),
      '#group' => $this->group->parent_name,
    ];

    if ($this->getSetting('id')) {
      $add['#id'] = Html::getUniqueId($this->getSetting('id'));
    }
    else {
      $add['#id'] = Html::getUniqueId('edit-' . $this->group->group_name);
    }

    $classes = $this->getClasses();
    if (!empty($classes)) {
      $element += [
        '#attributes' => ['class' => $classes],
      ];
    }

    if ($this->getSetting('formatter') == 'open') {
      $element['#open'] = TRUE;
    }

    if ($this->getSetting('required_fields')) {
      $element['#attached']['library'][] = 'field_group/formatter.tabs';
      $element['#attached']['library'][] = 'field_group/core';
    }

    $element += $add;

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

    $form['formatter'] = [
      '#title' => $this->t('Default state'),
      '#type' => 'select',
      '#options' => array_combine($this->pluginDefinition['format_types'], $this->pluginDefinition['format_types']),
      '#default_value' => $this->getSetting('formatter'),
      '#weight' => -4,
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
  public static function defaultContextSettings($context) {
    $defaults = [
      'formatter' => 'closed',
      'description' => '',
      'show_empty_fields' => FALSE,
    ] + parent::defaultSettings($context);

    if ($context == 'form') {
      $defaults['required_fields'] = 1;
    }

    return $defaults;
  }

}
