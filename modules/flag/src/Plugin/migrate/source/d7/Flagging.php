<?php

namespace Drupal\flag\Plugin\migrate\source\d7;

use Drupal\migrate_drupal\Plugin\migrate\source\d7\FieldableEntity;
use Drupal\migrate\Row;

/**
 * Drupal 7 Flag source from database.
 *
 * @MigrateSource(
 *   id = "d7_flagging",
 *   source_module = "flag"
 * )
 */
class Flagging extends FieldableEntity {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('flagging', 'fi');

    $query->join('flag', 'f', 'f.fid = fi.fid');

    $query->fields('fi', [
      'flagging_id',
      'fid',
      'entity_type',
      'entity_id',
      'uid',
      'sid',
      'timestamp',
    ]);

    $query->fields('f', [
      'name',
      'global',
    ]);

    // If a flag type is provided, only migrate flaggings for that flag.
    if (isset($this->configuration['flag_type'])) {
      $query->condition('f.name', (array) $this->configuration['flag_type'], 'IN');
    }

    $query->orderBy('fi.timestamp');

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'flagging_id' => $this->t('The unique ID for this particular tag.'),
      'fid' => $this->t('The unique flag ID this object has been flagged with, from flag.'),
      'entity_type' => $this->t('The entity type of the flagged entity.'),
      'entity_id' => $this->t('The unique ID of the flagged entity, for example the uid, cid, or nid.'),
      'uid' => $this->t('The user ID by whom this object was flagged.'),
      'sid' => $this->t('The user’s numeric sid from the session_api table.'),
      'timestamp' => $this->t('The UNIX time stamp representing when the flag was set.'),
      'name' => $this->t('The machine name of the flag.'),
      'global' => $this->t('If the flag is global.'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    // Get Field API field values.
    foreach (array_keys($this->getFields('flagging', $row->getSourceProperty('name'))) as $field) {
      $flagging_id = $row->getSourceProperty('flagging_id');
      $row->setSourceProperty($field, $this->getFieldValues('flagging', $field, $flagging_id));
    }

    return parent::prepareRow($row);
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['flagging_id']['type'] = 'integer';
    return $ids;
  }

}
