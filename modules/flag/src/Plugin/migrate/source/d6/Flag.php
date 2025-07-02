<?php

namespace Drupal\flag\Plugin\migrate\source\d6;

use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Drupal 6 Flag source from database.
 *
 * @MigrateSource(
 *   id = "d6_flag_source",
 *   source_module = "flag"
 * )
 */
class Flag extends DrupalSqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    return $this->select('flags', 'f')->fields('f');
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'fid' => $this->t('The unique ID for this particular flag.'),
      'entity_type' => $this->t('The entity type of the flagged entity.'),
      'name' => $this->t('The machine name of the flag.'),
      'title' => $this->t('The human readable title of the flag.'),
      'global' => $this->t('Whether this flag state should act as a single toggle to all users across the site.'),
      'options' => $this->t('Flag options.'),
      'default_weight' => $this->t('Default weight applied to new items.'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $source_options = unserialize($row->getSourceProperty('options'));
    $row->setSourceProperty('options', $source_options);

    $flag_entity_type = $row->getSourceProperty('content_type');
    $row->setSourceProperty('flag_type', 'entity:' . $flag_entity_type);

    $bundles = [];
    $d6_bundles = $this->select('flag_types', 'ft')
      ->fields('ft', ['type'])
      ->condition('fid', $row->getSourceProperty('fid'))
      ->execute();

    while ($bundle = $d6_bundles->fetchAssoc()) {
      $bundles[] = $bundle['type'];
    }
    $row->setSourceProperty('bundles', $bundles);

    $row->setSourceProperty('flagTypeConfig', []);
    $row->setSourceProperty('linkTypeConfig', []);

    return parent::prepareRow($row);
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['fid']['type'] = 'integer';

    return $ids;
  }

}
