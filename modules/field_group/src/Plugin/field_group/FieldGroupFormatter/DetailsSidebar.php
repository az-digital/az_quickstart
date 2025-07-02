<?php

namespace Drupal\field_group\Plugin\field_group\FieldGroupFormatter;

/**
 * Details Sidebar element.
 *
 * @FieldGroupFormatter(
 *   id = "details_sidebar",
 *   label = @Translation("Details Sidebar"),
 *   description = @Translation("Add a details sidebar element"),
 *   supported_contexts = {
 *     "form",
 *     "view"
 *   }
 * )
 */
class DetailsSidebar extends Details {

  /**
   * {@inheritdoc}
   */
  public function process(&$element, $processed_object) {
    parent::process($element, $processed_object);

    $element['#group'] = 'advanced';

    if ($this->getSetting('weight')) {
      $element['#weight'] = $this->getSetting('weight');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm() {
    $form = parent::settingsForm();

    $form['weight'] = [
      '#type' => 'number',
      '#title' => $this->t('Weight'),
      '#default_value' => $this->getSetting('weight'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    if ($this->getSetting('weight')) {
      $summary[] = $this->t('Weight: @weight',
        ['@weight' => $this->getSetting('weight')]
      );
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultContextSettings($context) {
    $defaults = parent::defaultContextSettings($context);
    $defaults['weight'] = 0;

    return $defaults;
  }

}
