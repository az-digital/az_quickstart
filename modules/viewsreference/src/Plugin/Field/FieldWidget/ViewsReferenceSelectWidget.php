<?php

namespace Drupal\viewsreference\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsSelectWidget;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'options_select' widget.
 *
 * @FieldWidget(
 *   id = "viewsreference_select",
 *   label = @Translation("Views reference select list"),
 *   description = @Translation("An autocomplete views select list field."),
 *   field_types = {
 *     "viewsreference"
 *   }
 * )
 */
class ViewsReferenceSelectWidget extends OptionsSelectWidget {

  use ViewsReferenceTrait;

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $select_element['target_id'] = parent::formElement($items, $delta, $element, $form, $form_state);
    $select_element = $this->fieldElement($items, $delta, $select_element, $form, $form_state);
    $select_element['target_id']['#multiple'] = FALSE;
    if (!$this->isDefaultValueWidget($form_state)) {
      $selected_views = $items->getSetting('preselect_views');
      $selected_views = array_diff($selected_views, ['0']);
      $selected_views = $this->getViewNames($selected_views);
      if (count($selected_views) >= 1) {
        $first_option = ['_none' => $this->t('- Select a value -')];
        $select_element['target_id']['#options'] = array_merge($first_option, $selected_views);
      }
    }
    return $select_element;
  }

  /**
   * {@inheritdoc}
   */
  public static function validateElement(array $element, FormStateInterface $form_state) {
    parent::validateElement($element, $form_state);
    if (isset($element['#array_parents'][0])) {
      $key = $element['#array_parents'][0];
      $field_values = $form_state->getValue($key);
      if (is_array($field_values)) {
        self::validateDisplayId($field_values, $form_state, $key);
      }
    }
  }

}
