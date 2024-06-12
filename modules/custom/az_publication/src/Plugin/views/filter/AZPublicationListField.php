<?php

namespace Drupal\az_publication\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\options\Plugin\views\filter\ListField;
use Drupal\views\Views;

/**
 * Filter handler which uses list-fields as options.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("az_publication_list_field")
 */
class AZPublicationListField extends ListField {

  /**
   * {@inheritdoc}
   */
  protected function valueForm(&$form, FormStateInterface $form_state) {
    parent::valueForm($form, $form_state);

    // Only add modifications if this is the exposed filter.
    if ($exposed = $form_state->get('exposed')) {

      // Only attempt to get filtered options if we're not already doing so.
      if (empty($this->view->optionQuery)) {
        $options = $this->filteredOptions();
        // Don't trim if we found no options or there are no options.
        if (!empty($options) && (!empty($form['value']['#options']))) {
          // Prune options with no results.
          $form['value']['#options'] = array_intersect_key($form['value']['#options'], $options);
        }
      }
    }
  }

  /**
   * Clones the view and returns key options.
   *
   * @return array
   *   The keys that have results.
   */
  protected function filteredOptions() {
    $options = [];
    if (empty($this->view)) {
      return $options;
    }
    $view = Views::getView($this->view->id());
    if (empty($view)) {
      return $options;
    }
    $view = Views::executableFactory()->get($this->view->storage);
    // @phpstan-ignore-next-line
    $view->optionQuery = TRUE;

    $view->setDisplay($this->view->current_display);

    // Remove exposed input. This is unfortunate.
    // Note that execute() builds exposed input from query if empty.
    // Page is irrelevant junk input.
    $view->setExposedInput(['page' => 1]);

    // Copy over arguments.
    $args = $this->view->args;
    $view->setArguments($args);

    // Turn off the pager for the option query.
    $pager = $view->display_handler->getOption('pager');
    $pager['type'] = 'none';
    $view->display_handler->setOption('pager', $pager);

    $view->execute();
    // Find out which field this is for.
    $definition = $this->getfieldDefinition();
    $field = $definition->getName();
    // Skim the results and find out which options existed.
    foreach ($view->result as $index => $row) {
      if (!empty($row->_entity->{$field}->value)) {
        $options[$row->_entity->{$field}->value] = TRUE;
      }
    }

    $view->optionQuery = FALSE;
    $view->destroy();
    return $options;
  }

}
