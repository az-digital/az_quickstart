<?php

namespace Drupal\webform\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\EntityReferenceAutocompleteWidget;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'webform_entity_reference_autocomplete' widget.
 *
 * @FieldWidget(
 *   id = "webform_entity_reference_autocomplete",
 *   label = @Translation("Autocomplete"),
 *   description = @Translation("An autocomplete text field."),
 *   field_types = {
 *     "webform"
 *   }
 * )
 */
class WebformEntityReferenceAutocompleteWidget extends EntityReferenceAutocompleteWidget {

  use WebformEntityReferenceWidgetTrait;

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'default_data' => TRUE,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);
    $element['default_data'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable default submission data (YAML)'),
      '#description' => $this->t('If checked, site builders will be able to define default submission data (YAML)'),
      '#default_value' => $this->getSetting('default_data'),
    ];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    $summary[] = $this->t('Default submission data: @default_data', ['@default_data' => $this->getSetting('default_data') ? $this->t('Yes') : $this->t('No')]);
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function getTargetIdElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    // Get default value.
    $referenced_entities = $items->referencedEntities();
    $default_value = $referenced_entities[$delta] ?? NULL;

    // Append the match operation to the selection settings.
    $selection_settings = $this->getFieldSetting('handler_settings') + ['match_operator' => $this->getSetting('match_operator')];

    return [
      '#type' => 'entity_autocomplete',
      '#target_type' => $this->getFieldSetting('target_type'),
      '#selection_handler' => $this->getFieldSetting('handler'),
      '#selection_settings' => $selection_settings,
      // Entity reference field items are handling validation themselves via
      // the 'ValidReference' constraint.
      '#validate_reference' => FALSE,
      '#maxlength' => 1024,
      '#default_value' => $default_value,
      '#size' => $this->getSetting('size'),
      '#placeholder' => $this->getSetting('placeholder'),
    ];
  }

}
