<?php

namespace Drupal\az_migration\Plugin\migrate\source;

use Drupal\migrate\Row;
use Drupal\paragraphs\Plugin\migrate\source\d7\ParagraphsItem;
use Drush\Drush;

/**
 * Drupal 7 Person node source plugin.
 *
 * @MigrateSource(
 *   id = "az_paragraphs_item"
 * )
 */
class AZParagraphsItem extends ParagraphsItem {

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'item_id' => $this->t('The paragraph_item id'),
      'revision_id' => $this->t('The paragraph_item revision id'),
      'bundle' => $this->t('The paragraph bundle'),
      'field_name' => $this->t('The paragraph field_name'),
      'bottom_space' => $this->t('The paragraph Bottom Space'),
    ];
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {

    // Get the source paragraph item id.
    $item_id = $row->getSourceProperty('item_id');

    $paragraph_bottom_spacing = $this->select('paragraphs_item', 'pi')
      ->fields('pi', ['bottom_spacing'])
      ->condition('pi.item_id', $item_id)
      ->execute()
      ->fetchCol();
    $row->setSourceProperty('bottom_spacing', $paragraph_bottom_spacing);
    return parent::prepareRow($row);
  }
  
}
