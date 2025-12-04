<?php

namespace Drupal\az_migration\Plugin\migrate\source;

use Drupal\migrate\Row;
use Drupal\migrate\Attribute\MigrateSource;
use Drupal\node\Plugin\migrate\source\d7\Node as D7Node;

/**
 * Provides a 'AZNode' migrate source plugin.
 *
 * @deprecated in az_quickstart:3.2.0 and is removed from az_quickstart:4.0.0.
 * There is no replacement.
 *
 * @see https://www.drupal.org/node/3533564
 *
 * @see Drupal\node\Plugin\migrate\source\d7\Node
 *
 * Additions to D7Node:
 *  - alias: string containing the content's relative path alias beginning
 *    with '/'
 *  - filter_date: Accepts a single value, either a numeric
 *    STRING value representing a UNIX timestamp or a string value
 *    representing a calendar date in the YYYY-MM-DD format.
 *    This is used as the "cutoff" date, or the oldest possible
 *    date to accept content, based on the **last update date**.
 *
 * Examples:
 * Consider a site with hundreds of pages, most of which is from before
 * 2016. None of the older content has been looked at or updated in nearly
 * a decade. How to easily cut that out of the migration:
 * @code
 *  source:
 *    plugin: az_node
 *    node_type: uagc_page
 *    filter_date: "2016-01-01"
 * @endcode
 */
#[MigrateSource('az_node')]
class AZNode extends D7Node {

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return ['alias' => $this->t('Path alias')] + parent::fields();
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    // Include path alias.
    $nid = $row->getSourceProperty('nid');
    $query = $this->select('url_alias', 'ua')
      ->fields('ua', ['alias']);
    $query->condition('ua.source', 'node/' . $nid);
    $alias = $query->execute()->fetchField();
    if (!empty($alias)) {
      $row->setSourceProperty('alias', '/' . $alias);
    }

    return parent::prepareRow($row);
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = parent::query();
    /*
     * Filter date from migration script can be a
     * string date or a UNIX timestamp so get
     * it into correct format
     */
    if (array_key_exists("filter_date", $this->configuration)) {
      if ($this->isTimestamp($this->configuration['filter_date'])) {
        $date_cutoff = $this->configuration['filter_date'];
      }
      else {
        $date_cutoff = (new \DateTime($this->configuration['filter_date']))->getTimestamp();
      }

      $query->condition('n.changed', $date_cutoff, '>');
    }

    return $query;
  }

  /**
   * Check if the passed string or integer is a valid timestamp.
   *
   * This should filter out datetime strings.
   *
   * @param mixed $timestamp
   *   The string that we are checking.
   *
   * @return bool
   *   True or False
   *
   * @code
   * $this->is_timestamp('2019-1-11');
   * // returns FALSE;
   * $this->is_timestamp('1654554652');
   * // returns TRUE;
   * $this->is_timestamp(1654554652);
   * // returns TRUE;
   * @endcode
   */
  private function isTimestamp($timestamp): bool {
    return ((string) (int) $timestamp === $timestamp)
     && ($timestamp <= PHP_INT_MAX)
     && ($timestamp >= ~PHP_INT_MAX)
     && (!strtotime($timestamp));
  }

}
