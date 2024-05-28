<?php

namespace Drupal\az_migration\Plugin\migrate\source;

use Drupal\Core\Database\Driver\mysql\Connection as MySqlConnection;
use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\source\d7\FieldableEntity;

/**
 * Migration source plugin for Bean entities.
 *
 * @MigrateSource(
 *   id = "az_bean",
 *   source_module = "bean"
 * )
 */
class AZBean extends FieldableEntity {

  /**
   * The join options between the bean and the bean_revisions table.
   */
  const JOIN = '[b].[vid] = [br].[vid]';

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('bean_revision', 'br')
      ->fields('b', [
        'bid',
        'delta',
        'type',
        'uid',
      ])
      ->fields('br', [
        'vid',
        'label',
        'title',
        'view_mode',
        'data',
        'created',
        'changed',
        'log',
      ]);
    if ($this->getDatabase()->schema()->fieldExists('bean', 'uuid')) {
      $query->addField('b', 'uuid');
    }
    $query->addField('br', 'uid', 'revision_uid');
    $query->innerJoin('bean', 'b', static::JOIN);
    $query->orderBy('br.vid', 'ASC');
    // A newer revision of a bean content entity isn't always the default
    // revision - so we compute this by comparing the version IDs found in the
    // "bean" and "bean_revision" tables. We will re-use this as "status".
    $cast_type = $this->getDatabase() instanceof MySqlConnection
      ? 'UNSIGNED INTEGER'
      : 'INTEGER';
    $status_expression = "CAST(b.vid >= br.vid AS $cast_type)";
    $query->addExpression($status_expression, 'revision_default');
    $query->addExpression($status_expression, 'status');

    if ($type = $this->configuration['type'] ?? NULL) {
      $query->condition('b.type', $type, 'IN');
    }

    // We might have a type label, but nothing guarantees that every type has a
    // record in the "bean_type" table.
    if ($this->getDatabase()->schema()->tableExists('bean_type')) {
      $query->leftJoin('bean_type', 'bt', 'bt.name = b.type');
      $query->addField('bt', 'label', 'type_label');
    }

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    [
      'type' => $type,
      'bid' => $bean_id,
      'vid' => $revision_id,
    ] = $row->getSource();

    // Get Field API field values.
    $language_code = $row->getSourceProperty('language');
    foreach ($this->getFields('bean', $type) as $field_name => $field) {
      $row->setSourceProperty($field_name, $this->getFieldValues('bean', $field_name, $bean_id, $revision_id, $language_code));
    }

    // The "language" property is one of the IDs, so it always needs a value.
    if ($language_code === NULL) {
      $row->setSourceProperty('language', 'und');
    }

    return parent::prepareRow($row);
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'bid' => $this->t('The ID of the bean entity.'),
      'vid' => $this->t('The revision ID.'),
      'delta' => $this->t('The delta.'),
      'label' => $this->t('The administrative label.'),
      'title' => $this->t('The human label (title).'),
      'type' => $this->t('The bundle (type) of the bean.'),
      'view_mode' => $this->t('The view mode.'),
      'data' => $this->t('Additional data (a nested array which usually contains the view mode).'),
      'uid' => $this->t("The ID of the user who owns the bean's default revision"),
      'created' => $this->t('The time when the bean entity was created, as a Unix timestamp.'),
      'changed' => $this->t('The Unix timestamp when the revision was changed.'),
      'log' => $this->t('The revision log message.'),
      'status' => $this->t('The status (the published state) of the revision.'),
      'revision_default' => $this->t('Whether the revision is a default revision.'),
      'revision_uid' => $this->t('The ID of the user who created the revision.'),
      'language' => $this->t('The language code of the bean revision translation.'),
      'translate' => $this->t('Whether the current revision translation is outdated.'),
      'source_language' => $this->t('The source language of this revision translation.'),
      'uuid' => $this->t('The UUID of the bean entity if bean_uuid is enabled on source'),
      'type_label' => $this->t('Label of the bean type, if available.'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids = [
      'bid' => [
        'type' => 'integer',
        'alias' => 'br',
      ],
      'vid' => [
        'type' => 'integer',
        'alias' => 'br',
      ],
    ];
    if ($this->moduleExists('entity_translation')) {
      $ids['language'] = [
        'type' => 'string',
      ];
    }

    return $ids;
  }

}
