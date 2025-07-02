<?php

namespace Drupal\flag\Plugin\migrate\source\d7;

use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Drupal 7 Flag source from database.
 *
 * @MigrateSource(
 *   id = "d7_flag",
 *   source_module = "flag"
 * )
 */
class Flag extends DrupalSqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    return $this->select('flag', 'f')->fields('f');
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

    $flag_entity_type = $row->getSourceProperty('entity_type');
    $row->setSourceProperty('flag_type', 'entity:' . $flag_entity_type);

    $bundles = [];
    $d7_bundles = $this->select('flag_types', 'ft')
      ->fields('ft', ['type'])
      ->condition('fid', $row->getSourceProperty('fid'))
      ->execute();

    while ($bundle = $d7_bundles->fetchAssoc()) {
      $bundles[] = $bundle['type'];
    }

    $row->setSourceProperty('bundles', $bundles);

    $flagTypeConfig = [];
    $flagTypeConfig['show_in_links'] = $source_options['show_in_links'];
    $flagTypeConfig['show_as_field'] = $source_options['show_as_field'];
    $flagTypeConfig['show_on_form'] = $source_options['show_on_form'];
    $flagTypeConfig['show_contextual_link'] = $source_options['show_contextual_link'];
    $row->setSourceProperty('flagTypeConfig', $flagTypeConfig);

    // @todo investigate this property.
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
