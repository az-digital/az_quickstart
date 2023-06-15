<?php

namespace Drupal\az_core\Plugin\Field\FieldWidget;

use Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsSelectWidget;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Render\Element;

/**
 * Plugin implementation of the 'attributes_select' widget.
 *
 * @FieldWidget(
 *   id = "attributes_select",
 *   label = @Translation("Attribute list"),
 *   field_types = {
 *     "entity_reference",
 *   },
 *   multiple_values = TRUE
 * )
 */
class AZAttributeWidget extends OptionsSelectWidget {

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create(
      $container,
      $configuration,
      $plugin_id,
      $plugin_definition,
    );

    $instance->entityTypeManager = $container->get('entity_type.manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'allowed_attributes' => [],
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $options = [];

    // Get field settings to find target taxonomies.
    $field_definition = $this->fieldDefinition;
    $field_settings = $field_definition->getSettings();
    $vocabularies = $field_settings['handler_settings']['target_bundles'] ?? [];

    // Build form elements based on vocabularies.
    foreach ($vocabularies as $vocabulary => $value) {
      $terms = $this->entityTypeManager->getStorage('taxonomy_term')->loadTree($vocabulary, 0, 1, TRUE);
      /** @var \Drupal\taxonomy\Entity\Term $term */
      foreach ($terms as $term) {
        if ($term->hasField('field_az_attribute_key') && !empty($term->field_az_attribute_key->value)) {
          $options[$term->field_az_attribute_key->value] = $term->getName();
        }
      }
    }

    // Present form for choosing allowed attributes.
    $element['allowed_attributes'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Allowed Attributes'),
      '#default_value' => $this->getSetting('allowed_attributes'),
      '#options' => $options,
      '#description' => $this->t('Select which enterprise attributes are allowed for this content type.'),
      '#multiple' => TRUE,
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    // Show selected attributes.
    $options = array_filter($this->getSetting('allowed_attributes'));
    $options = implode(', ', $options);
    $summary[] = $this->t('Allowed Attributes: @attributes', ['@attributes' => $options]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {

    // Initial form.
    $element += [
      '#type' => 'details',
      '#open' => TRUE,
      '#attributes' => ['class' => ['az-enterprise-attributes']],
    ];

    // Add widget library.
    $element['#attached']['library'][] = 'az_core/az-enterprise-attributes';

    // Get options selected.
    $selected = $this->getSelectedOptions($items);
    $selected_keys = array_flip($selected);

    // Get field settings to find target taxonomies.
    $field_definition = $items->getFieldDefinition();
    $field_settings = $field_definition->getSettings();
    $vocabularies = $field_settings['handler_settings']['target_bundles'] ?? [];

    // Build form elements based on vocabularies.
    foreach ($vocabularies as $vocabulary => $value) {
      $terms = $this->entityTypeManager->getStorage('taxonomy_term')->loadTree($vocabulary);
      foreach ($terms as $term) {
        if ($term->depth === 0) {
          $element[$term->tid] = [
            '#type' => 'select',
            '#title' => $this->t($term->name),
            '#options' => [],
            '#default_value' => [],
            '#multiple' => TRUE,
          ];
        }
        else {
          $parent = reset($term->parents);
          $element[$parent]['#options'][$term->tid] = $term->name;
          if (isset($selected_keys[$term->tid])) {
            $element[$parent]['#default_value'][] = $term->tid;
          }
        }
      }
    }

    $allowed = $this->getSetting('allowed_attributes');

    // Retroactively edit or remove atribute elements with special cases.
    foreach ($vocabularies as $vocabulary => $value) {
      $terms = $this->entityTypeManager->getStorage('taxonomy_term')->loadTree($vocabulary, 0, 1, TRUE);
      /** @var \Drupal\taxonomy\Entity\Term $term */
      foreach ($terms as $term) {
        $tid = $term->id();
        // Check if this attribute type has special form element considerations.
        if ($term->hasField('field_az_attribute_type') && !empty($term->field_az_attribute_type->value)) {
          switch ($term->field_az_attribute_type->value) {
            case 'single-select picklist':
              $element[$tid]['#multiple'] = FALSE;
              $element[$tid]['#empty_value'] = '';
              break;

            case 'multi-select picklist':
            default:
              break;
          }
        }
        // Check if the attribute is allowed for this field.
        if ($term->hasField('field_az_attribute_key') && !empty($term->field_az_attribute_key->value)) {
          if (empty($allowed[$term->field_az_attribute_key->value])) {
            unset($element[$tid]);
          }
        }
        else {
          // Remove attributes that are missing an attribute key.
          unset($element[$tid]);
        }
      }
    }

    // Hide the widget if we have no valid attributes.
    if (empty(Element::children($element))) {
      $element['#access'] = FALSE;
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {

    // Collapse down to a single array.
    $modified = [];
    foreach ($values as $group) {
      if (!is_array($group)) {
        // Single select elements are not yet arrays.
        $group = !empty($group) ? [$group => $group] : [];
      }
      foreach ($group as $key => $attribute) {
        $modified[$key] = $key;
      }
    }
    unset($modified['_none']);
    return $modified;
  }

}
