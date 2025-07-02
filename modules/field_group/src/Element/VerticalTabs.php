<?php

namespace Drupal\field_group\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\Element\RenderCallbackInterface;

/**
 * Provides extra processing and pre rendering on the vertical tabs.
 */
class VerticalTabs implements RenderCallbackInterface {

  /**
   * Pre render the group to support #group parameter.
   *
   * @param array $element
   *   An associative array containing the properties and children of the
   *   element.
   *
   * @return array
   *   The modified element with all group members.
   */
  public static function preRenderGroup(array $element) {
    // The element may be rendered outside of a Form API context.
    if (!isset($element['#parents']) || !isset($element['#groups'])) {
      return $element;
    }

    if (isset($element['#group'])) {
      // Contains form element summary functionalities.
      $element['#attached']['library'][] = 'core/drupal.form';

      $group = $element['#group'];
      // If this element belongs to a group, but the group-holding element does
      // not exist, we need to render it (at its original location).
      if (!isset($element['#groups'][$group]['#group_exists'])) {
        // Intentionally empty to clarify the flow; we simply return $element.
      }
      // If we injected this element into the group, then we want to render it.
      elseif (!empty($element['#group_details'])) {
        // Intentionally empty to clarify the flow; we simply return $element.
      }
      // Otherwise, this element belongs to a group and the group exists,
      // so we do not render it.
      elseif (Element::children($element['#groups'][$group])) {
        $element['#printed'] = TRUE;
      }
    }

    // Search for the correct default active tab.
    $group_identifier = implode('][', $element['#parents']);
    if (!empty($element['#groups'][$group_identifier])) {
      $children = Element::children($element['#groups'][$group_identifier], TRUE);
      foreach ($children as $key) {
        if (!empty($element['#groups'][$group_identifier][$key]['#open'])) {
          $element['#default_tab'] = $element['#groups'][$group_identifier][$key]['#id'];
          $element[str_replace('][', '__', $group_identifier) . '__active_tab']['#value'] = $element['#default_tab'];
        }
      }
    }

    return $element;
  }

  /**
   * Arranges elements into groups.
   *
   * This method is useful for non-input elements that can be used in and
   * outside the context of a form.
   *
   * @param array $element
   *   An associative array containing the properties and children of the
   *   element. Note that $element must be taken by reference here, so processed
   *   child elements are taken over into $form_state.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   *
   * @return array
   *   The processed element.
   */
  public static function processGroup(array &$element, FormStateInterface $form_state, array &$complete_form) {

    $groups = &$form_state->getGroups();
    $element['#groups'] = &$groups;

    if (isset($element['#group'])) {
      // Add this element to the defined group (by reference).
      $group = $element['#group'];
      $groups[$group][] = &$element;
    }

    return $element;
  }

}
