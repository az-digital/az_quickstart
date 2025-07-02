<?php

namespace Drupal\paragraphs\Plugin\migrate\source\d7;

use Drupal\Core\Database\DatabaseExceptionWrapper;
use Drupal\migrate\Row;

/**
 * Paragraphs Item source plugin.
 *
 * Available configuration keys:
 * - bundle: (optional) If supplied, this will only return paragraphs
 *   of that particular type.
 *
 * @MigrateSource(
 *   id = "d7_paragraphs_item",
 *   source_module = "paragraphs",
 * )
 */
class ParagraphsItem extends FieldableEntity {

  /**
   * Join string for getting current revisions.
   *
   * @var string
   */
  const JOIN = "p.revision_id = pr.revision_id";

  /**
   * The prefix of the field table that contains the entity properties.
   *
   * @var string
   */
  const PARENT_FIELD_TABLE_PREFIX = 'field_data_';

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'bundle' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('paragraphs_item', 'p')
      ->fields('p',
        [
          'item_id',
          'bundle',
          'field_name',
          'archived',
        ])
      ->fields('pr', ['revision_id']);
    $query->innerJoin('paragraphs_item_revision', 'pr', static::JOIN);

    // This configuration item may be set by a deriver to restrict the
    // bundles retrieved.
    if ($this->configuration['bundle']) {
      $query->condition('p.bundle', $this->configuration['bundle']);
    }
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    [
      'item_id' => $paragraph_id,
      'revision_id' => $paragraph_revision_id,
      'field_name' => $paragraph_parent_field_name,
      'bundle' => $bundle,
    ] = $row->getSource();

    if (!$paragraph_parent_field_name || !is_string($paragraph_parent_field_name)) {
      return FALSE;
    }

    // Get Field API field values.
    foreach (array_keys($this->getFields('paragraphs_item', $bundle)) as $field_name) {
      $row->setSourceProperty($field_name, $this->getFieldValues('paragraphs_item', $field_name, $paragraph_id, $paragraph_revision_id));
    }

    // We have to find the corresponding parent entity (which might be an
    // another paragraph). Active revision only.
    try {
      $parent_data_query = $this->getDatabase()->select(static::PARENT_FIELD_TABLE_PREFIX . $paragraph_parent_field_name, 'fd');
      $parent_data_query->addField('fd', 'entity_type', 'parent_type');
      $parent_data_query->addField('fd', 'entity_id', 'parent_id');
      $parent_data = $parent_data_query
        ->condition("fd.{$paragraph_parent_field_name}_value", $paragraph_id)
        ->condition("fd.{$paragraph_parent_field_name}_revision_id", $paragraph_revision_id)
        ->execute()->fetchAssoc();
    }
    catch (DatabaseExceptionWrapper $e) {
      // The paragraphs field data|revision table is missing, we cannot get
      // the parent entity identifiers. This is a corrupted database.
      // @todo Shouldn't we have to throw an exception instead?
      return FALSE;
    }

    if (!is_iterable($parent_data)) {
      // We cannot get the parent entity identifiers.
      return FALSE;
    }

    foreach ($parent_data as $property_name => $property_value) {
      $row->setSourceProperty($property_name, $property_value);
    }

    return parent::prepareRow($row);
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'item_id' => $this->t('The paragraph_item id'),
      'revision_id' => $this->t('The paragraph_item revision id'),
      'bundle' => $this->t('The paragraph bundle'),
      'field_name' => $this->t('The paragraph field_name'),
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
        'alias' => 'p',
      ],
    ];
  }

}
