<?php

namespace Drupal\chosen;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Security\TrustedCallbackInterface;

class ChosenFormRender implements TrustedCallbackInterface {

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return [
      'preRenderSelect',
      'preRenderDateCombo',
      'preRenderSelectOther',
    ];
  }

  /**
   * Render API callback: Apply Chosen to a select element.
   *
   * @param array $element
   *   The element.
   *
   * @return array
   *   The element.
   */
  public static function preRenderSelect($element) {
    // Exclude chosen from theme other than admin.
    $theme = \Drupal::theme()->getActiveTheme()->getName();
    $admin_theme = \Drupal::config('system.theme')->get('admin');
    $is_admin_path = \Drupal::service('router.admin_context')->isAdminRoute();
    $is_admin = $is_admin_path || $theme == $admin_theme;

    $chosen_include = \Drupal::config('chosen.settings')->get('chosen_include');
    if ($chosen_include != CHOSEN_INCLUDE_EVERYWHERE && $is_admin == $chosen_include) {
      return $element;
    }

    // If the #chosen FAPI property is set, then add the appropriate class.
    if (isset($element['#chosen'])) {
      if (!empty($element['#chosen'])) {
        // Element has opted-in for Chosen, ensure the library gets added.
        $element['#attributes']['class'][] = 'chosen-enable';
      }
      else {
        $element['#attributes']['class'][] = 'chosen-disable';
        // Element has opted-out of Chosen. Do not add the library now.
        return $element;
      }
    }
    elseif (isset($element['#attributes']['class']) && is_array($element['#attributes']['class'])) {
      if (array_intersect($element['#attributes']['class'], ['chosen-disable'])) {
        // Element has opted-out of Chosen. Do not add the library now.
        return $element;
      }
      elseif (array_intersect($element['#attributes']['class'], ['chosen-enable'])) {
        // Element has opted-in for Chosen, ensure the library gets added.
      }
    }
    else {
      // Neither the #chosen property was set, nor any chosen classes found.
      // This element still might match the site-wide critera, so add the library.
    }

    if (isset($element['#field_name']) && !empty($element['#multiple'])) {
      // Remove '_none' from multi-select options.
      unset($element['#options']['_none']);

      if (isset($element['#entity_type']) && isset($element['#bundle']) && isset($element['#field_name'])) {
        // Set data-cardinality for fields that aren't unlimited.
        $field = NULL;
        $field_config = FieldConfig::loadByName($element['#entity_type'], $element['#bundle'], $element['#field_name']);
        if ($field_config) {
            $field = $field_config->getFieldStorageDefinition();
        }
        else {
          /** @var \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager */
          $entity_field_manager = \Drupal::service('entity_field.manager');
          $fields = $entity_field_manager->getFieldDefinitions($element['#entity_type'], $element['#bundle']);
          if (isset($fields[$element['#field_name']])) {
            $field = $fields[$element['#field_name']]->getFieldStorageDefinition();
          }
        }
        $cardinality = ($field instanceof FieldStorageDefinitionInterface) ? $field->getCardinality() : NULL;

        if ($cardinality != FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED && $cardinality > 1) {
          $element['#attributes']['data-cardinality'] = $cardinality;
        }
      }
    }

    // Attach the library.
    chosen_attach_library($element);

    // Right to Left Support.
    $language_direction = \Drupal::languageManager()->getCurrentLanguage()->getDirection();
    if (LanguageInterface::DIRECTION_RTL == $language_direction) {
      $element['#attributes']['class'][] = 'chosen-rtl';
    }

    return $element;
  }

  /**
   * Render API callback: Apply Chosen to a date_combo element.
   *
   * @param array $element
   *   The element.
   *
   * @return array
   *   The element.
   */
  public static function preRenderDateCombo($element) {
    // Because the date_combo field contains many different select elements, we
    // need to recurse down and apply the FAPI property to each one.
    if (isset($element['#chosen'])) {
      chosen_element_apply_property_recursive($element, $element['#chosen']);
    }
    return $element;
  }

  /**
   * Render API callback: Apply Chosen to a select_or_other element.
   *
   * @param array $element
   *   The element.
   *
   * @return array
   *   The element.
   */
  public static function preRenderSelectOther($element) {
    if ($element['#select_type'] == 'select' && isset($element['#chosen'])) {
      $element['select']['#chosen'] = $element['#chosen'];
    }
    return $element;
  }


}
