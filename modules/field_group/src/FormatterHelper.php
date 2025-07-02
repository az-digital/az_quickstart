<?php

namespace Drupal\field_group;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Security\TrustedCallbackInterface;

/**
 * Static methods for fieldgroup formatters.
 */
class FormatterHelper implements TrustedCallbackInterface {

  /**
   * Return an array of field_group_formatter options.
   */
  public static function formatterOptions($type) {
    $options = &drupal_static(__FUNCTION__);

    if (!isset($options)) {
      $options = [];

      $manager = \Drupal::service('plugin.manager.field_group.formatters');
      $formatters = $manager->getDefinitions();

      foreach ($formatters as $formatter) {
        if (in_array($type, $formatter['supported_contexts'])) {
          $options[$formatter['id']] = $formatter['label'];
        }
      }
    }

    return $options;
  }

  /**
   * Pre render callback for rendering groups on entities without theme hook.
   *
   * @param array $element
   *   Entity being rendered.
   *
   * @return array
   *   The update entity view.
   */
  public static function entityViewPreRender(array $element) {
    field_group_build_entity_groups($element, 'view');
    return $element;
  }

  /**
   * Process callback for field groups.
   *
   * @param array $element
   *   Form that is being processed.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $form
   *   The complete form structure.
   *
   * @return array
   *   The updated form.
   */
  public static function formProcess(array &$element, FormStateInterface $form_state = NULL, array &$form = []) {
    if (empty($element['#field_group_form_process'])) {
      $element['#field_group_form_process'] = TRUE;
      if (empty($element['#fieldgroups'])) {
        return $element;
      }

      // Create all groups and keep a flat list of references to these groups.
      $group_references = [];
      foreach ($element['#fieldgroups'] as $group_name => $group) {
        if (!isset($element[$group_name])) {
          $element[$group_name] = [];
        }

        $group_parents = $element['#array_parents'];
        if (empty($group->parent_name)) {
          if (isset($group->region)) {
            $group_parents[] = $group->region;
          }
        }
        else {
          $group_parents[] = $group->parent_name;
        }
        $group_references[$group_name] = &$element[$group_name];
        $element[$group_name]['#group'] = implode('][', $group_parents);

        // Use array parents to set the group name.
        // This will cover multilevel forms (eg paragraphs).
        $parents = $element['#array_parents'];
        $parents[] = $group_name;
        $element[$group_name]['#parents'] = $parents;
        $group_children_parent_group = implode('][', $parents);
        if (isset($group->children)) {
          foreach ($group->children as $child) {
            if (!empty($element[$child]['#field_group_ignore'])) {
              continue;
            }
            $element[$child]['#group'] = $group_children_parent_group;
          }
        }
      }

      foreach ($element['#fieldgroups'] as $group_name => $group) {
        $field_group_element = &$element[$group_name];

        // Let modules define their wrapping element.
        // Note that the group element has no properties, only elements.
        // The intention here is to have the opportunity to alter the
        // elements, as defined in hook_field_group_formatter_info.
        // Note, implement $element by reference!
        if (method_exists(\Drupal::moduleHandler(), 'invokeAllWith')) {
          // On Drupal >= 9.4 use the new method.
          \Drupal::moduleHandler()->invokeAllWith('field_group_form_process', function (callable $hook) use (&$field_group_element, &$group, &$element) {
            $hook($field_group_element, $group, $element);
          });
        }
        else {
          // @phpstan-ignore-next-line
          foreach (\Drupal::moduleHandler()->getImplementations('field_group_form_process') as $module) {
            $function = $module . '_field_group_form_process';
            $function($field_group_element, $group, $element);
          }
        }

        // Allow others to alter the pre_render.
        \Drupal::moduleHandler()->alter('field_group_form_process', $field_group_element, $group, $element);
      }

      // Allow others to alter the complete processed build.
      \Drupal::moduleHandler()->alter('field_group_form_process_build', $element, $form_state, $form);
    }

    return $element;
  }

  /**
   * Pre render callback for rendering groups in forms.
   *
   * @param array $element
   *   Form that is being rendered.
   *
   * @return array
   *   The updated group.
   */
  public static function formGroupPreRender(array $element) {
    // Open any closed field groups that contain elements with errors.
    if (!empty($element['#fieldgroups'])) {
      foreach ($element['#fieldgroups'] as $fieldgroup) {
        $closed = isset($element[$fieldgroup->group_name]['#open']) && !$element[$fieldgroup->group_name]['#open'];
        if ($closed) {
          foreach ($fieldgroup->children as $child) {
            if (isset($element[$child]) && static::groupElementsContainErrors($element[$child])) {
              $element[$fieldgroup->group_name]['#open'] = TRUE;
              break;
            }
          }
        }
      }
    }

    return $element;
  }

  /**
   * Determines if an elements array contains validation errors.
   *
   * @param array $elements
   *   The elements array to check for errors.
   *
   * @return bool
   *   TRUE if the elements array contains validation errors, otherwise FALSE.
   */
  protected static function groupElementsContainErrors(array $elements) {
    // Any errors at this level of the elements array?
    if (!empty($elements['#errors']) || !empty($elements['#children_errors'])) {
      return TRUE;
    }

    // Dive down.
    foreach (Element::children($elements) as $child) {
      if (static::groupElementsContainErrors($elements[$child])) {
        return TRUE;
      }
    }

    // No errors.
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return ['entityViewPreRender', 'formProcess', 'formGroupPreRender'];
  }

}
