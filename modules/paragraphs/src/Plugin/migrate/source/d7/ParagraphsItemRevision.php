<?php

namespace Drupal\paragraphs\Plugin\migrate\source\d7;

/**
 * Paragraphs Item Revision source plugin.
 *
 * Available configuration keys:
 * - bundle: (optional) If supplied, this will only return paragraphs
 *   of that particular type.
 *
 * @MigrateSource(
 *   id = "d7_paragraphs_item_revision",
 *   source_module = "paragraphs",
 * )
 */
class ParagraphsItemRevision extends ParagraphsItem {

  /**
   * {@inheritdoc}
   */
  const JOIN = "p.item_id=pr.item_id AND p.revision_id <> pr.revision_id";

  /**
   * {@inheritdoc}
   */
  const PARENT_FIELD_TABLE_PREFIX = 'field_revision_';

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'revision_id' => [
        'type' => 'integer',
        'alias' => 'pr',
      ],
    ];
  }

}
