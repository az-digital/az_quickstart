<?php

namespace Drupal\better_exposed_filters\Plugin\better_exposed_filters\filter;

use Drupal\Core\Form\FormStateInterface;

/**
 * Default widget implementation.
 *
 * @BetterExposedFiltersFilterWidget(
 *   id = "bef_hidden",
 *   label = @Translation("Hidden"),
 * )
 */
class Hidden extends FilterWidgetBase {

  /**
   * {@inheritdoc}
   */
  public function exposedFormAlter(array &$form, FormStateInterface $form_state): void {
    $field_id = $this->getExposedFilterFieldId();

    parent::exposedFormAlter($form, $form_state);

    if (empty($form[$field_id]['#multiple'])) {
      // Single entry filters can simply be changed to a different element
      // type.
      $form[$field_id]['#type'] = 'hidden';
    }
    else {
      // Hide the label.
      $form['#info']["filter-$field_id"]['label'] = '';
      $form[$field_id]['#title'] = '';

      // Use BEF's preprocess and template to output the hidden elements.
      $form[$field_id]['#theme'] = 'bef_hidden';
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(mixed $handler = NULL, array $options = []): bool {
    $is_applicable = parent::isApplicable($handler, $options);

    if ((is_a($handler, 'Drupal\views\Plugin\views\filter\Date') || !empty($handler->date_handler)) && !$handler->isAGroup()) {
      $is_applicable = TRUE;
    }

    return $is_applicable;
  }

}
