<?php

namespace Drupal\field_group\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\Element\RenderElement;

/**
 * Provides a render element for horizontal tabs.
 *
 * Formats all child details and all non-child details whose #group is
 * assigned this element's name as horizontal tabs.
 *
 * @FormElement("horizontal_tabs")
 */
class HorizontalTabs extends RenderElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);

    return [
      '#default_tab' => '',
      '#process' => [
        [$class, 'processHorizontalTabs'],
        [$class, 'processGroup'],
      ],
      '#pre_render' => [
        [$class, 'preRenderGroup'],
      ],
      '#theme_wrappers' => ['horizontal_tabs'],
    ];
  }

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
  public static function preRenderGroup($element) {
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
      // Otherwise, this element belongs to a group and the group exists, so we
      // do not render it.
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
   * Creates a group formatted as horizontal tabs.
   *
   * @param array $element
   *   An associative array containing the properties and children of the
   *   details element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param bool $on_form
   *   Are the tabs rendered on a form or not.
   *
   * @return array
   *   The processed element.
   */
  public static function processHorizontalTabs(array &$element, FormStateInterface $form_state, $on_form = TRUE) {

    // Inject a new details as child, so that form_process_details() processes
    // this details element like any other details.
    $element['group'] = [
      '#type' => 'details',
      '#theme_wrappers' => [],
      '#parents' => $element['#parents'],
    ];

    // Add an invisible label for accessibility.
    if (!isset($element['#title'])) {
      $element['#title'] = t('Horizontal Tabs');
      $element['#title_display'] = 'invisible';
    }

    // Add required JavaScript and Stylesheet.
    $element['#attached']['library'][] = 'field_group/element.horizontal_tabs';

    // Only add forms library on forms.
    if ($on_form) {
      $element['#attached']['library'][] = 'core/drupal.form';
    }

    $name = implode('__', $element['#parents']);
    if ($form_state->hasValue($name . '__active_tab')) {
      $element['#default_tab'] = $form_state->getValue($name . '__active_tab');
    }

    $displayed_tab = $element['#default_tab'] ?? '';

    // The JavaScript stores the currently selected tab in this hidden
    // field so that the active tab can be restored the next time the
    // form is rendered, e.g. on preview pages or when form validation
    // fails.
    $element['#default_tab'] = $displayed_tab;
    $element[$name . '__active_tab'] = [
      '#type' => 'hidden',
      '#default_value' => $element['#default_tab'],
      '#attributes' => ['class' => ['horizontal-tabs-active-tab']],
    ];

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
  public static function processGroup(&$element, FormStateInterface $form_state, &$complete_form) {

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
