<?php

namespace Drupal\field_group_accordion\Plugin\field_group\FieldGroupFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormState;
use Drupal\field_group\FieldGroupFormatterBase;
use Drupal\field_group_accordion\Element\Accordion as AccordionElement;

/**
 * Plugin implementation of the 'accordion' formatter.
 *
 * @FieldGroupFormatter(
 *   id = "accordion",
 *   label = @Translation("Accordion (Deprecated)"),
 *   description = @Translation("This fieldgroup renders child groups as jQuery accordion."),
 *   supported_contexts = {
 *     "form",
 *     "view",
 *   }
 * )
 */
class Accordion extends FieldGroupFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function process(&$element, $processed_object) {

    // Keep using preRender parent for BC.
    parent::preRender($element, $processed_object);

    $element += [
      '#type' => 'field_group_accordion',
      '#effect' => $this->getSetting('effect'),
    ];

    if ($this->getSetting('id')) {
      $element['#id'] = Html::getUniqueId($this->getSetting('id'));
    }

    $classes = $this->getClasses();
    if (!empty($classes)) {
      $element += ['#attributes' => ['class' => $classes]];
    }

  }

  /**
   * {@inheritdoc}
   */
  public function preRender(&$element, $rendering_object) {
    $this->process($element, $rendering_object);

    $form_state = new FormState();
    AccordionElement::processAccordion($element, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm() {

    $form = parent::settingsForm();

    $form['effect'] = [
      '#title' => $this->t('Effect'),
      '#type' => 'select',
      '#options' => [
        'none' => $this->t('None'),
        'bounceslide' => $this->t('Bounce slide'),
      ],
      '#default_value' => $this->getSetting('effect'),
      '#weight' => 2,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {

    $summary = [];
    $summary[] = $this->t('Effect : @effect',
      ['@effect' => $this->getSetting('effect')]
    );

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultContextSettings($context) {
    return [
      'effect' => 'none',
    ] + parent::defaultSettings($context);
  }

}
