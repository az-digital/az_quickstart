<?php

namespace Drupal\field_group_accordion\Plugin\field_group\FieldGroupFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Render\Markup;
use Drupal\field_group\FieldGroupFormatterBase;

/**
 * Plugin implementation of the 'accordion_item' formatter.
 *
 * @FieldGroupFormatter(
 *   id = "accordion_item",
 *   label = @Translation("Accordion Item (Deprecated)"),
 *   description = @Translation("This fieldgroup renders the content in a div, part of accordion group."),
 *   format_types = {
 *     "open",
 *     "closed",
 *   },
 *   supported_contexts = {
 *     "form",
 *     "view",
 *   },
 * )
 */
class AccordionItem extends FieldGroupFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function process(&$element, $processed_object) {

    // Keep using preRender parent for BC.
    parent::preRender($element, $processed_object);

    $element += [
      '#type' => 'field_group_accordion_item',
      '#effect' => $this->getSetting('effect'),
      '#title' => $this->getSetting('label_as_html') ? Markup::create(Xss::filterAdmin($this->getLabel())) : Markup::create(Html::escape($this->getLabel())),
      // Prevent \Drupal\content_translation\ContentTranslationHandler::addTranslatabilityClue()
      // from adding an incorrect suffix to the field group title.
      '#multilingual' => TRUE,
    ];

    if ($this->getSetting('id')) {
      $element['#id'] = Html::getUniqueId($this->getSetting('id'));
    }

    $classes = $this->getClasses();
    if (!empty($classes)) {
      $element += ['#attributes' => ['class' => $classes]];
    }

    if ($this->getSetting('required_fields')) {
      $element['#attached']['library'][] = 'field_group/formatter.details';
    }

    if ($this->getSetting('formatter') == 'open') {
      $element['#open'] = TRUE;
    }

    foreach ($element as $value) {
      if (is_array($value) && !empty($value['#children_errors'])) {
        $element['#open'] = TRUE;
      }
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
  public function settingsSummary() {

    $summary = [];

    if ($this->getSetting('required_fields')) {
      $summary[] = $this->t('Mark as required');
    }

    if ($this->getSetting('description')) {
      $summary[] = $this->t('Description : @description',
        ['@description' => $this->getSetting('description')]
      );
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultContextSettings($context) {
    $defaults = [
      'formatter' => 'closed',
      'description' => '',
    ] + parent::defaultSettings($context);

    if ($context == 'form') {
      $defaults['required_fields'] = 1;
    }

    return $defaults;
  }

}
