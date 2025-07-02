<?php

namespace Drupal\webform\Element;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\Element\Container;
use Drupal\webform\Utility\WebformDialogHelper;
use Drupal\webform\Utility\WebformElementHelper;

/**
 * Provides a wrapper element to group one or more Webform buttons in a form.
 *
 * @RenderElement("webform_actions")
 *
 * @see \Drupal\Core\Render\Element\Actions
 */
class WebformActions extends Container {

  /**
   * Buttons.
   *
   * @var string[]
   */
  public static $buttons = [
    'submit',
    'reset',
    'delete',
    'draft',
    'wizard_prev',
    'wizard_next',
    'preview_prev',
    'preview_next',
  ];

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#process' => [
        [$class, 'processWebformActions'],
        [$class, 'processContainer'],
      ],
      '#theme_wrappers' => ['container'],
    ];
  }

  /**
   * Processes a form actions container element.
   */
  public static function processWebformActions(&$element, FormStateInterface $form_state, &$complete_form) {
    /** @var \Drupal\webform\WebformSubmissionForm $form_object */
    $form_object = $form_state->getFormObject();
    /** @var \Drupal\webform\WebformSubmissionInterface $webform_submission */
    $webform_submission = $form_object->getEntity();

    $prefix = ($element['#webform_key']) ? 'edit-' . $element['#webform_key'] . '-' : '';

    // Add class names only if form['actions']['#type'] is set to 'actions'.
    if (WebformElementHelper::isType($complete_form['actions'], 'actions')) {
      $element['#attributes']['class'][] = 'form-actions';
      $element['#attributes']['class'][] = 'webform-actions';
    }

    // Copy the form's actions to this element.
    $element += $complete_form['actions'];

    // Custom processing for the delete (link) action.
    if (isset($element['delete'])) {
      // Clone the URL so that each delete URL can have custom attributes.
      $element['delete']['#url'] = clone $element['delete']['#url'];

      // Add dialog attributes to the delete button.
      if (!empty($element['#delete__dialog'])) {
        $element['delete'] += ['#attributes' => []];
        $element['delete']['#attributes'] += ['class' => []];

        $dialog_attributes = WebformDialogHelper::getModalDialogAttributes(WebformDialogHelper::DIALOG_NARROW, $element['delete']['#attributes']['class']);
        $element['delete']['#attributes'] += $dialog_attributes;
        $element['delete']['#attributes']['class'] = $dialog_attributes['class'];

        WebformDialogHelper::attachLibraries($element);
      }

      // Restore access checking to the delete button.
      // @see \Drupal\webform\WebformSubmissionForm::actions
      if (isset($element['#delete_hide']) && $element['#delete_hide'] === FALSE) {
        $element['delete']['#access'] = $webform_submission->access('delete');
        unset($element['#delete_hide']);
      }
    }

    // Track if buttons are visible.
    $has_visible_button = FALSE;
    foreach (static::$buttons as $button_name) {
      // Make sure the button exists.
      if (!isset($element[$button_name])) {
        continue;
      }

      // Get settings name.
      // The 'submit' button is used for creating and updating submissions.
      $is_update_button = ($button_name === 'submit' && !($webform_submission->isNew() || $webform_submission->isDraft()));
      $settings_name = ($is_update_button) ? 'update' : $button_name;

      // Set unique id for each button.
      if ($prefix) {
        $element[$button_name]['#id'] = Html::getUniqueId("$prefix$button_name");
      }

      // Hide buttons using #access.
      if (!empty($element['#' . $settings_name . '_hide'])) {
        $element[$button_name]['#access'] = FALSE;
      }

      // Apply custom label.
      $has_custom_label = !empty($element[$button_name]['#webform_actions_button_custom']);
      if (!empty($element['#' . $settings_name . '__label']) && !$has_custom_label) {
        if (isset($element[$button_name]['#type']) && ($element[$button_name]['#type'] === 'link')) {
          $element[$button_name]['#title'] = $element['#' . $settings_name . '__label'];
        }
        else {
          $element[$button_name]['#value'] = $element['#' . $settings_name . '__label'];
        }
      }

      // Apply custom name when needed for multiple submit buttons with
      // the same label.
      // @see https://www.drupal.org/project/webform/issues/3069240
      if (!empty($element['#' . $settings_name . '__name'])) {
        $element[$button_name]['#name'] = $element['#' . $settings_name . '__name'];
      }

      // Apply attributes (class, style, properties).
      if (!empty($element['#' . $settings_name . '__attributes'])) {
        $element[$button_name] += ['#attributes' => []];
        foreach ($element['#' . $settings_name . '__attributes'] as $attribute_name => $attribute_value) {
          if ($attribute_name === 'class') {
            $element[$button_name]['#attributes'] += ['class' => []];
            // Merge class names.
            $element[$button_name]['#attributes']['class'] = array_merge($element[$button_name]['#attributes']['class'], $attribute_value);
          }
          else {
            $element[$button_name]['#attributes'][$attribute_name] = $attribute_value;
          }
        }
      }

      if (Element::isVisibleElement($element[$button_name])) {
        $has_visible_button = TRUE;
      }
    }

    // Hide form actions only if the element is accessible.
    // This prevents form from having no actions.
    if (Element::isVisibleElement($element)) {
      $complete_form['actions']['#access'] = FALSE;
    }

    // Hide actions element if no buttons are visible (i.e. #access = FALSE).
    if (!$has_visible_button) {
      $element['#access'] = FALSE;
    }

    return $element;
  }

}
