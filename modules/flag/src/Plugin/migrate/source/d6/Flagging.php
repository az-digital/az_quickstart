<?php

namespace Drupal\flag\Plugin\migrate\source\d6;

use Drupal\migrate_drupal\Plugin\migrate\source\d7\FieldableEntity;

/**
 * Drupal 6 Flag source from database.
 *
 * @MigrateSource(
 *   id = "d6_flagging_source",
 *   source_module = "flag"
 * )
 */
class Flagging extends FieldableEntity {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('flag_content', 'fi');

    $query->join('flags', 'f', 'f.fid = fi.fid');

    $query->fields('fi', [
      'fid',
      'uid',
      'content_type',
      'content_id',
      'timestamp',
      'fcid',
    ]);

    $query->fields('f', [
      'name',
      'global',
    ]);

    $query->orderBy('fi.timestamp');

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'fcid' => $this->t('The unique ID for this particular tag.'),
      'fid' => $this->t('The unique flag ID this object has been flagged with, from flag.'),
      'content_type' => $this->t('The entity type of the flagged entity.'),
      'content_id' => $this->t('The unique ID of the flagged entity, for example the uid, cid, or nid.'),
      'uid' => $this->t('The user ID by whom this object was flagged.'),
      'timestamp' => $this->t('The UNIX time stamp representing when the flag was set.'),
      'name' => $this->t('The machine name of the flag.'),
      'global' => $this->t('If the flag is global.'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['fcid']['type'] = 'integer';
    $ids['fcid']['alias'] = 'fi';
    return $ids;
  }

}
