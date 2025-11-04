<?php

namespace Drupal\az_eds\Plugin\migrate\source;

use Drupal\migrate\Attribute\MigrateSource;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Plugin\migrate\source\SourcePluginBase;

/**
 * Allows source data to be pulled from a defined LDAP query.
 *
 * For additional configuration keys, refer to the parent class:
 *
 * @see \Drupal\migrate\Plugin\migrate\source\SourcePluginBase
 */
#[MigrateSource('az_ldap')]
class AzLdap extends SourcePluginBase {

  /**
   * Query controller.
   *
   * @var \Drupal\ldap_query\Controller\QueryController
   *   Controller helper to use for LDAP queries.
   */
  protected $ldapQuery;

  /**
   * Queries to use.
   *
   * @var array[]
   *   An array of machine names of queries to use.
   */
  protected $queries = [];

  /**
   * Description of the unique ID fields for this source.
   *
   * @var array[]
   *   Each array member is keyed by a field name, with a value that is an
   *   array with a single member with key 'type' and value a column type such
   *   as 'integer'.
   */
  protected $ids = [];

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration);
    $this->queries = $configuration['queries'];
    $this->ids = $configuration['ids'];
    // @todo Use injection for this.
    $this->ldapQuery = \Drupal::service('ldap.query');
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function initializeIterator() {
    $rows = [];
    // @todo apply lazy loading rather than loading results in bulk.
    foreach ($this->queries as $query) {
      // Load the query via ldap controller.
      $this->ldapQuery->load($query);
      $this->ldapQuery->execute();
      // Get the results of the query.
      $results = $this->ldapQuery->getRawResults();
      foreach ($results as $result) {
        $row = $result->getAttributes();
        // Make sure UID is a scalar.
        // Field imports handle arrays, but this is also used for entity lookup.
        $uid = $row['uid'] ?? [];
        $row['uid'] = reset($uid);
        // DN is the preferred id for LDAP migrations.
        $row['dn'] = $result->getDn();
        $rows[] = $row;
      }
    }
    return new \ArrayIterator($rows);
  }

  /**
   * {@inheritdoc}
   */
  public function __toString() {
    return 'LDAP data';
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return $this->ids;
  }

}
