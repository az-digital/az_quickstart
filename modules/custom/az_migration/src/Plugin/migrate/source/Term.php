<?php
 
/**
 * @file
 * Contains \Drupal\az_migration\Plugin\migrate\source\Term.
 */
 
namespace Drupal\az_migration\Plugin\migrate\source;
 
use Drupal\migrate\Row;
use Drupal\migrate\Plugin\migrate\source\SqlBase;
 
/**
 * Drupal 7 taxonomy terms source from database.
 *
 * @todo Support term_relation, term_synonym table if possible.
 *
 * @MigrateSource(
 *   id = "az_d7_taxonomy_term",
 *   source_provider = "taxonomy"
 * )
 */
class Term extends SqlBase {
 
  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('taxonomy_term_data', 'td')
      ->fields('td', array('tid', 'vid', 'name', 'description', 'weight', 'format'))
      ->distinct();
    
    $query->leftJoin('taxonomy_vocabulary', 'tv', 'td.vid = tv.vid');

    if(isset($this->configuration['bundle'])){
        $query->condition('tv.machine_name', (array) $this->configuration['bundle'], 'IN');
    }
 
    return $query;
  }
 
  /**
   * {@inheritdoc}
   */
  public function fields() {
    return array(
      'tid' => $this->t('The term ID.'),
      'vid' => $this->t('Existing term VID'),
      'name' => $this->t('The name of the term.'),
      'description' => $this->t('The term description.'),
      'weight' => $this->t('Weight'),
      'parent' => $this->t("The Drupal term IDs of the term's parents."),
    );
  }
 
  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
  
    // Find parents for this row.
    $parents = $this->select('taxonomy_term_hierarchy', 'th')
      ->fields('th', array('parent', 'tid'))
      ->condition('tid', $row->getSourceProperty('tid'))
      ->execute()
      ->fetchCol();
    $row->setSourceProperty('parent', $parents);
    return parent::prepareRow($row);
  }
 
  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['tid']['type'] = 'integer';
    return $ids;
  }
 
}