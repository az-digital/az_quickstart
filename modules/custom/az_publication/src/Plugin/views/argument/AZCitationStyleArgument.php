<?php

namespace Drupal\az_publication\Plugin\views\argument;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\argument\ArgumentPluginBase;

/**
 * Argument handler that allows a placeholder citation style.
 *
 * @ingroup views_argument_handlers
 *
 * @ViewsArgument("az_citation_style_argument")
 */
class AZCitationStyleArgument extends ArgumentPluginBase {

  /**
   * Override buildOptionsForm() so that only the relevant options displayed.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    unset($form['exception']);
  }

  /**
   * Override defaultActions() to remove actions that don't make sense.
   */
  protected function defaultActions($which = NULL) {
    if ($which) {
      if (in_array($which, ['ignore', 'not found', 'empty', 'default'])) {
        return parent::defaultActions($which);
      }
      return;
    }
    $actions = parent::defaultActions();
    unset($actions['summary asc']);
    unset($actions['summary desc']);
    return $actions;
  }

  /**
   * Override the behavior of query() to prevent the query from being changed.
   */
  public function query($group_by = FALSE) {}

}
