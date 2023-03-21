<?php

namespace Drupal\az_core\Plugin\Field\FieldWidget;

use Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsSelectWidget;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

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

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {

    // Collapse down to a single array.
    $modified = [];
    foreach ($values as $group) {
      foreach ($group as $key => $attribute) {
        $modified[$key] = $key;
      }
    }
    unset($modified['_none']);
    return $modified;
  }

}
