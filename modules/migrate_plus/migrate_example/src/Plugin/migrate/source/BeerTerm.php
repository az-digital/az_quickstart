<?php

declare(strict_types = 1);

namespace Drupal\migrate_example\Plugin\migrate\source;

use Drupal\Core\Database\Query\SelectInterface;
use Drupal\migrate\Plugin\migrate\source\SqlBase;

/**
 * This is an example of a simple SQL-based source plugin.
 *
 * Source plugins are classes which deliver source data to the processing
 * pipeline. For SQL sources, the SqlBase class provides most of the
 * functionality needed - for a specific migration, you are required to
 * implement the three simple public methods you see below.
 *
 * This annotation tells Drupal that the name of the MigrateSource plugin
 * implemented by this class is "beer_term". This is the name that the migration
 * configuration references with the source "plugin" key.
 *
 * @MigrateSource(
 *   id = "beer_term"
 * )
 */
final class BeerTerm extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query(): SelectInterface {
    // The most important part of a SQL source plugin is the SQL query to
    // retrieve the data to be imported. Note that the query is not executed
    // here - the migration process will control execution of the query. Also
    // note that it is constructed from a $this->select() call - this ensures
    // that the query is executed against the database configured for this
    // source plugin.
    $fields = ['style', 'details', 'style_parent', 'region', 'hoppiness'];
    return $this->select('migrate_example_beer_topic', 'met')
      ->fields('met', $fields)
      // We sort this way to ensure parent terms are imported first.
      ->orderBy('style_parent', 'ASC');
  }

  /**
   * {@inheritdoc}
   */
  public function fields(): array {
    // This method simply documents the available source fields provided by the
    // source plugin, for use by front-end tools. It returns an array keyed by
    // field/column name, with the value being a translated string explaining
    // to humans what the field represents.
    return [
      'style' => $this->t('Beer style'),
      'details' => $this->t('Style details'),
      'style_parent' => $this->t('Parent style'),
      // These values are not currently migrated - it's OK to skip fields you
      // don't need.
      'region' => $this->t('Region the style is associated with'),
      'hoppiness' => $this->t('Hoppiness of the style'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getIds(): array {
    // This method indicates what field(s) from the source row uniquely identify
    // that source row, and what their types are. This is critical information
    // for managing the migration. The keys of the returned array are the field
    // names from the query which comprise the unique identifier. The values are
    // arrays indicating the type of the field, used for creating compatible
    // columns in the map tables that track processed items.
    return [
      'style' => [
        'type' => 'string',
        // 'alias' is the alias for the table containing 'style' in the query
        // defined above. Optional in this case, but necessary if the same
        // column may occur in multiple tables in a join.
        'alias' => 'met',
      ],
    ];
  }

}
