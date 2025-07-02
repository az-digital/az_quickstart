<?php

namespace Drupal\flag\Plugin\views\sort;

use Drupal\views\Plugin\views\sort\SortPluginBase;

/**
 * Sorts entities by flagged or unflagged in a view.
 *
 * @ViewsSort("flag_sort")
 */
class FlagViewsSortFlagged extends SortPluginBase {

  /**
   * Provide a list of options for the default sort form.
   *
   * Should be overridden by classes that don't override sort_form.
   */
  protected function sortOptions() {
    return [
      'ASC' => $this->t('Unflagged first'),
      'DESC' => $this->t('Flagged first'),
    ];
  }

  /**
   * Display whether or not the sort order is ascending or descending.
   */
  public function adminSummary() {
    if (!empty($this->options['exposed'])) {
      return $this->t('Exposed');
    }

    // Get the labels defined in sortOptions().
    $sort_options = $this->sortOptions();
    return $sort_options[strtoupper($this->options['order'])];
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->ensureMyTable();

    // @phpstan-ignore-next-line
    $this->query->addOrderBy(NULL, "$this->tableAlias.uid", $this->options['order']);
  }

}
