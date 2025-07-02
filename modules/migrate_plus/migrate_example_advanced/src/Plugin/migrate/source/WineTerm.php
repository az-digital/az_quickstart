<?php

declare(strict_types = 1);

namespace Drupal\migrate_example_advanced\Plugin\migrate\source;

use Drupal\Core\Database\Query\SelectInterface;
use Drupal\migrate\Plugin\migrate\source\SqlBase;

/**
 * A SQL-based source plugin, to retrieve category data from a source database.
 *
 * @MigrateSource(
 *   id = "wine_term"
 * )
 */
final class WineTerm extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query(): SelectInterface {
    $fields = [
      'categoryid',
      'type',
      'name',
      'details',
      'category_parent',
      'ordering',
    ];
    return $this->select('migrate_example_advanced_categories', 'wc')
      ->fields('wc', $fields)
      // This sort assures that parents are saved before children.
      ->orderBy('category_parent', 'ASC');
  }

  /**
   * {@inheritdoc}
   */
  public function fields(): array {
    return [
      'categoryid' => $this->t('Unique ID of the category'),
      'type' => $this->t('Category type corresponding to Drupal vocabularies'),
      'name' => $this->t('Category name'),
      'details' => $this->t('Description of the category'),
      'category_parent' => $this->t('ID of the parent category'),
      'ordering' => $this->t('Order in which to display this category'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getIds(): array {
    return ['categoryid' => ['type' => 'integer']];
  }

}
