<?php

namespace Drupal\paragraphs\Plugin\migrate\source\d7;

use Drupal\migrate\Row;
use Drupal\paragraphs\Plugin\migrate\field\FieldCollection;

/**
 * Field Collection Item source plugin.
 *
 * Available configuration keys:
 * - field_name: (optional) If supplied, this will only return field collections
 *   of that particular type.
 *
 * @MigrateSource(
 *   id = "d7_field_collection_item",
 *   source_module = "field_collection",
 * )
 */
class FieldCollectionItem extends FieldableEntity {

  /**
   * Join string for getting current revisions.
   */
  const JOIN = 'f.revision_id = fr.revision_id';

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'field_name' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('field_collection_item', 'f')
      ->fields('f', [
        'item_id',
        'field_name',
        'archived',
      ])
      ->fields('fr', ['revision_id']);
    $query->innerJoin('field_collection_item_revision', 'fr', static::JOIN);

    // This configuration item may be set by a deriver to restrict the
    // bundles retrieved.
    if ($this->configuration['field_name']) {
      $query->condition('f.field_name', $this->configuration['field_name']);
      $query->addField('fc', 'entity_type', 'parent_type');
      $query->addField('fc', 'entity_id', 'parent_id');
      $query->innerJoin('field_revision_' . $this->configuration['field_name'], 'fc', 'fc.' . $this->configuration['field_name'] . '_value = f.item_id and fc.' . $this->configuration['field_name'] . '_revision_id = f.revision_id');
    }
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    // Remove field_ prefix for new bundle.
    $bundle = $row->getSourceProperty('field_name');
    $bundle = substr($bundle, FieldCollection::FIELD_COLLECTION_PREFIX_LENGTH);
    $row->setSourceProperty('bundle', $bundle);

    // Get Field API field values.
    $field_names = array_keys($this->getFields('field_collection_item', $row->getSourceProperty('field_name')));
    $item_id = $row->getSourceProperty('item_id');
    $revision_id = $row->getSourceProperty('revision_id');

    foreach ($field_names as $field_name) {
      $value = $this->getFieldValues('field_collection_item', $field_name, $item_id, $revision_id);
      $row->setSourceProperty($field_name, $value);
    }

    return parent::prepareRow($row);
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'item_id' => $this->t('The field_collection_item id'),
      'revision_id' => $this->t('The field_collection_item revision id'),
      'bundle' => $this->t('The field_collection bundle'),
      'field_name' => $this->t('The field_collection field_name'),
      'parent_type' => $this->t('The type of the parent entity'),
      'parent_id' => $this->t('The identifier of the parent entity'),
    ];

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'item_id' => [
        'type' => 'integer',
        'alias' => 'f',
      ],
    ];
  }

}
