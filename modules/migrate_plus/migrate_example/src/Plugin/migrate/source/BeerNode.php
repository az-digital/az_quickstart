<?php

declare(strict_types = 1);

namespace Drupal\migrate_example\Plugin\migrate\source;

use Drupal\Core\Database\Query\SelectInterface;
use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Row;

/**
 * Source plugin for beer content.
 *
 * @MigrateSource(
 *   id = "beer_node"
 * )
 */
final class BeerNode extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query(): SelectInterface {
    // An important point to note is that your query *must* return a single row
    // for each item to be imported. Here we might be tempted to add a join to
    // migrate_example_beer_topic_node in our query, to pull in the
    // relationships to our categories. Doing this would cause the query to
    // return multiple rows for a given node, once per related value, thus
    // processing the same node multiple times, each time with only one of the
    // multiple values that should be imported. To avoid that, we simply query
    // the base node data here, and pull in the relationships in prepareRow()
    // below.
    $fields = [
      'bid',
      'name',
      'body',
      'excerpt',
      'aid',
      'countries',
      'image',
      'image_alt',
      'image_title',
      'image_description',
    ];
    return $this->select('migrate_example_beer_node', 'b')
      ->fields('b', $fields);
  }

  /**
   * {@inheritdoc}
   */
  public function fields(): array {
    return [
      'bid' => $this->t('Beer ID'),
      'name' => $this->t('Name of beer'),
      'body' => $this->t('Full description of the beer'),
      'excerpt' => $this->t('Abstract for this beer'),
      'aid' => $this->t('Account ID of the author'),
      'countries' => $this->t('Countries of origin. Multiple values, delimited by pipe'),
      'image' => $this->t('Image path'),
      'image_alt' => $this->t('Image ALT'),
      'image_title' => $this->t('Image title'),
      'image_description' => $this->t('Image description'),
      // Note that this field is not part of the query above - it is populated
      // by prepareRow() below. You should document all source properties that
      // are available for mapping after prepareRow() is called.
      'terms' => $this->t('Applicable styles'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getIds(): array {
    return [
      'bid' => [
        'type' => 'integer',
        'alias' => 'b',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row): bool {
    // As explained above, we need to pull the style relationships into our
    // source row here, as an array of 'style' values (the unique ID for
    // the beer_term migration).
    $terms = $this->select('migrate_example_beer_topic_node', 'bt')
      ->fields('bt', ['style'])
      ->condition('bid', $row->getSourceProperty('bid'))
      ->execute()
      ->fetchCol();
    $row->setSourceProperty('terms', $terms);

    // As we did for favorite beers in the user migration, we need to explode
    // the multi-value country names.
    if ($value = $row->getSourceProperty('countries')) {
      $row->setSourceProperty('countries', explode('|', $value));
    }
    return parent::prepareRow($row);
  }

}
