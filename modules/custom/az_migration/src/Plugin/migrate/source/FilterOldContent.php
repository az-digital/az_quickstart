<?php

namespace Drupal\az_migration\Plugin\migrate\source;

use Drupal\node\Plugin\migrate\source\d7\Node as D7Node;

/**
 * Provides a 'FilterOldContent' migrate source plugin.
 *
 * Available configuration keys
 * - filter_date: Accepts a single value, either a numberic
 *   value representing a UNIX timestamp or a string value
 *   representing a calendar date in the YYYY-MM-DD format.
 *   This is used as the "cutoff" date, or the oldest possible
 *   date to accept content, based on the last update date.
 *
 * Examples:
 *
 * Consider a site with hundreds of pages, most of which is from before
 * 2016. None of the older content has been looked at or updated in nearly
 * a decade. How to easily cut that out of the migration:
 * @code
 *  source:
 *    plugin: filter_old_content
 *    node_type: uagc_page
 *    filter_date: "2016-01-01"
 * @endcode
 *
 * @MigrateSource(
 *   id = "filter_old_content"
 * )
 */
class FilterOldContent extends D7Node {

  /**
   * {@inheritdoc}
   */
  public function query() {
    /*
     * Filter date from migration script can be a
     * string date or a UNIX timestamp so get
     * it into correct format
     */
    if (is_int($this->configuration['filter_date'])) {
      $date_cutoff = $this->configuration['filter_date'];
    }
    else {
      $date_cutoff = (new \DateTime($this->configuration['filter_date']))->getTimestamp();
    }

    $query = parent::query();
    $query->condition('n.changed', $date_cutoff, '>');

    return $query;
  }

}
