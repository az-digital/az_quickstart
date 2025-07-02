<?php

namespace Drupal\smart_date_recur\Plugin\views\filter;

use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\filter\InOperator;
use Drupal\views\ViewExecutable;

/**
 * Filter class which filters by the available teams.
 *
 * @ViewsFilter("recur_freq")
 */
class Frequency extends InOperator {

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, ?array &$options = NULL) {
    parent::init($view, $display, $options);
    $this->valueTitle = $this->t('Frequency');
    // @todo Switch to getFrequencyLabelsOrNull to allow filtering on
    // non-recurring events. Currently returns no results if selected.
    // @phpstan-ignore-next-line
    $this->valueOptions = \Drupal::service('smart_date_recur.manager')->getFrequencyLabels();
  }

  /**
   * Override query so no filtering happens if the user doesn't select options.
   */
  public function query() {
    if (!empty($this->value)) {
      parent::query();
    }
  }

  /**
   * Skip validation if no options chosen so we can use it as a non-filter.
   */
  public function validate() {
    if (!empty($this->value)) {
      parent::validate();
    }
  }

}
