<?php

namespace Drupal\redirect\Plugin\migrate\source\d7;

use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Drupal 7 path redirect source from database.
 *
 * @MigrateSource(
 *   id = "d7_path_redirect",
 *   source_module = "redirect"
 * )
 */
class PathRedirect extends DrupalSqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Select path redirects.
    $query = $this->select('redirect', 'p')->fields('p');

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    static $default_status_code;
    if (!isset($default_status_code)) {
      // The default status code not necessarily saved to the source database.
      // In this case, redirects should get the default value from the Drupal 7
      // version's variable_get() calls, which is 301.
      // @see https://git.drupalcode.org/project/redirect/-/blob/7f9531d08/redirect.admin.inc#L16
      $default_status_code = $this->variableGet('redirect_default_status_code', 301);
    }
    $current_status_code = $row->getSourceProperty('status_code');
    $status_code = !empty($current_status_code) ? $current_status_code : $default_status_code;
    $row->setSourceProperty('status_code', $status_code);
    return parent::prepareRow($row);
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'rid' => $this->t('Redirect ID'),
      'hash' => $this->t('Hash'),
      'type' => $this->t('Type'),
      'uid' => $this->t('UID'),
      'source' => $this->t('Source'),
      'source_options' => $this->t('Source Options'),
      'redirect' => $this->t('Redirect'),
      'redirect_options' => $this->t('Redirect Options'),
      'language' => $this->t('Language'),
      'status_code' => $this->t('Status Code'),
      'count' => $this->t('Count'),
      'access' => $this->t('Access'),
    ];
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['rid']['type'] = 'integer';
    return $ids;
  }

}
