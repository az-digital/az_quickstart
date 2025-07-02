<?php

namespace Drupal\Tests\paragraphs\Traits;

/**
 * Provide data to the paragraphs source plugin tests.
 */
trait ParagraphsSourceData {

  /**
   * Provides a source data array for the source tests.
   *
   * @return array
   *   The source data
   */
  protected static function getSourceData() {
    $data = [];

    $data[0]['source_data'] = [
      'paragraphs_bundle' => [
        [
          'bundle' => 'paragraphs_field',
          'name' => 'Paragraphs Field',
          'locked' => '1',
        ],
      ],
      'field_config_instance' => [
        [
          'field_name' => 'field_text',
          'entity_type' => 'paragraphs_item',
          'bundle' => 'paragraphs_field',
          'data' => 'Serialized Instance Data',
          'deleted' => '0',
          'field_id' => '1',
        ],
      ],
      'field_config' => [
        [
          'id' => '1',
          'field_name' => 'field_text',
          'translatable' => '1',
        ],
      ],
      'field_revision_field_text' => [
        [
          'entity_type' => 'paragraphs_item',
          'bundle' => 'paragraphs_field',
          'deleted' => '0',
          'entity_id' => '1',
          'revision_id' => '1',
          'language' => 'und',
          'delta' => '0',
          'field_text_value' => 'PID1R1 text',
        ],
        [
          'entity_type' => 'paragraphs_item',
          'bundle' => 'paragraphs_field',
          'deleted' => '0',
          'entity_id' => '2',
          'revision_id' => '2',
          'language' => 'und',
          'delta' => '0',
          'field_text_value' => 'PID2R2 text',
        ],
        [
          'entity_type' => 'paragraphs_item',
          'bundle' => 'paragraphs_field',
          'deleted' => '0',
          'entity_id' => '2',
          'revision_id' => '3',
          'language' => 'und',
          'delta' => '0',
          'field_text_value' => 'PID2R3 text',
        ],
      ],
      'paragraphs_item' => [
        [
          'item_id' => '1',
          'revision_id' => '1',
          'field_name' => 'field_paragraphs_field',
          'bundle' => 'paragraphs_field',
          'archived' => '0',
        ],
        [
          'item_id' => '2',
          'revision_id' => '3',
          'field_name' => 'field_paragraphs_field',
          'bundle' => 'paragraphs_field',
          'archived' => 0,
        ],
      ],
      'paragraphs_item_revision' => [
        [
          'item_id' => '1',
          'revision_id' => '1',
        ],
        [
          'item_id' => '2',
          'revision_id' => '2',
        ],
        [
          'item_id' => '2',
          'revision_id' => '3',
        ],
      ],
      'field_data_field_paragraphs_field' => [
        [
          'entity_type' => 'node',
          'entity_id' => '5',
          // @todo Don't we have to match also entity revision IDs?
          // 'revision_id' => 'something',
          'field_paragraphs_field_value' => '1',
          'field_paragraphs_field_revision_id' => '1',
        ],
        [
          'entity_type' => 'taxonomy_term',
          'entity_id' => '42',
          'field_paragraphs_field_value' => '2',
          'field_paragraphs_field_revision_id' => '3',
        ],
      ],
    ];
    $data[0]['source_data']['field_revision_field_paragraphs_field'] = array_merge($data[0]['source_data']['field_data_field_paragraphs_field'], [
      [
        'entity_type' => 'taxonomy_term',
        'entity_id' => '42',
        'field_paragraphs_field_value' => '2',
        'field_paragraphs_field_revision_id' => '2',
      ],
    ]);
    return $data;
  }

}
