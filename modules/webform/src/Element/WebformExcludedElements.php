<?php

namespace Drupal\webform\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\Role;
use Drupal\webform\Entity\Webform as WebformEntity;
use Drupal\webform\Plugin\WebformElement\WebformActions as WebformActionsWebformElement;
use Drupal\webform\Utility\WebformArrayHelper;
use Drupal\webform\WebformInterface;

/**
 * Provides a webform element for webform excluded elements.
 *
 * @FormElement("webform_excluded_elements")
 */
class WebformExcludedElements extends WebformExcludedBase {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return parent::getInfo() + [
      '#exclude_markup' => TRUE,
      '#exclude_composite' => TRUE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function processWebformExcluded(&$element, FormStateInterface $form_state, &$complete_form) {
    parent::processWebformExcluded($element, $form_state, $complete_form);
    $element['#attached']['library'][] = 'webform/webform.element.excluded_elements';
    return $element;
  }

  /**
   * Get header for the excluded tableselect element.
   *
   * @return array
   *   An array container the header for the excluded tableselect element.
   */
  public static function getWebformExcludedHeader() {
    $header = [];
    $header['title'] = [
      'data' => t('Title'),
    ];
    $header['key'] = [
      'data' => t('Key'),
      'class' => [RESPONSIVE_PRIORITY_LOW],
    ];
    $header['type'] = [
      'data' => t('Type'),
      'class' => [RESPONSIVE_PRIORITY_LOW],
    ];
    $header['private'] = [
      'data' => t('Private'),
    ];
    $header['access'] = [
      'data' => t('Access'),
    ];
    return $header;
  }

  /**
   * Get options for excluded tableselect element.
   *
   * @param array $element
   *   An associative array containing the properties and children of the
   *   generic element element.
   *
   * @return array
   *   An array of options containing title, name, and type of items for a
   *   tableselect element.
   */
  public static function getWebformExcludedOptions(array $element) {
    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = WebformEntity::load($element['#webform_id'])
      ?: \Drupal::service('webform.request')->getCurrentWebform();

    $options = [];
    $form_elements = static::getElements($element, $webform);
    foreach ($form_elements as $key => $form_element) {
      if (!empty($form_element['#access_view_roles'])) {
        $roles = array_map(function ($item) {
          return $item->label();
        }, Role::loadMultiple($form_element['#access_view_roles']));
      }
      else {
        $roles = [];
      }

      $options[$key] = [
        'title' => $form_element['#admin_title'] ?: $form_element['#title'] ?: $key,
        'key' => $key,
        'type' => $form_element['#type'] ?? '',
        'private' => empty($form_element['#private']) ? t('No') : t('Yes'),
        'access' => $roles ? WebformArrayHelper::toString($roles) : t('All roles'),
      ];

      // Add warning to private elements.
      if (!empty($form_element['#private']) || $roles) {
        $options[$key]['#attributes']['class'][] = 'color-warning';
      }

      // Add composite attributes and classes to allow composite sub-element
      // to be styled and enhanced.
      if (!empty($form_element['#webform_composite'])) {
        $options[$key]['#attributes']['data-composite'] = $form_element['#webform_key'];
      }
      if (isset($form_element['#webform_composite_key'])) {
        $options[$key]['#attributes']['class'][] = 'webform-excluded-elements--child';
        $options[$key]['#attributes']['data-composite-parent'] = $form_element['#webform_composite_parent_key'];
      }
    }
    return $options;
  }

  /**
   * Get elements with or without markup and composite sub elements.
   *
   * @param array $element
   *   The excluded elements form element.
   * @param \Drupal\webform\WebformInterface $webform
   *   A webform.
   *
   * @return array
   *   An associative array of elements with or without markup and composite sub elements.
   */
  protected static function getElements(array $element, WebformInterface $webform) {
    if ($element['#exclude_markup']) {
      $form_elements = $webform->getElementsInitializedFlattenedAndHasValue();
    }
    else {
      $form_elements = $webform->getElementsInitializedAndFlattened();

      // Skip markup elements that are containers or actions.
      /** @var \Drupal\webform\Plugin\WebformElementManagerInterface $element_manager */
      $element_manager = \Drupal::service('plugin.manager.webform.element');
      foreach ($form_elements as $key => $form_element) {
        $form_element_plugin = $element_manager->getElementInstance($form_element);
        if ($form_element_plugin->isContainer($form_element) || $form_element_plugin instanceof WebformActionsWebformElement) {
          unset($form_elements[$key]);
        }
      }
    }

    // If composite sub elements are excluded return the elements AS-IS.
    if ($element['#exclude_composite']) {
      return $form_elements;
    }

    // Build array of all elements with composite sub elements.
    $all_form_elements = [];
    foreach ($form_elements as $key => $form_element) {
      // Append the element.
      $all_form_elements[$key] = $form_element;
      // Append composite elements.
      $composite_elements = $form_element['#webform_composite_elements'] ?? [];
      foreach ($composite_elements as $composite_element) {
        if (isset($composite_element['#webform_composite_key'])) {
          $composite_key = $composite_element['#webform_composite_key'];
          $all_form_elements[$composite_key] = $composite_element;
        }
      }
    }

    return $all_form_elements;
  }

}
