<?php

namespace Drupal\az_migration\Plugin\migrate\source;

use Drupal\migrate\Row;
use Drupal\migrate\Plugin\migrate\source\SqlBase;

/**
 * Drupal 7 file source from database.
 *
 * @todo Support file migration, copy all fid files.
 *
 * @MigrateSource(
 *   id = "az_person_file_migration",
 *   source_provider = "file"
 * )
 */
class PersonFileHandle extends SqlBase {

  /**
   * The public file directory path, if any.
   *
   * @var string
   */
  protected $publicPath;

  /**
   * The private file directory path, if any.
   *
   * @var string
   */
  protected $privatePath;

  /**
   * The temporary file directory path, if any.
   *
   * @var string
   */
  protected $temporaryPath;

  /**
   * The Drupal 7 file base directory path.
   *
   * @var string
   */
  protected $d7BaseFilePath;

  /**
   * {@inheritdoc}
   */
  public function query() {

    $fids = [];
    // Fetching all fids related to person.
    $result = $this->getDatabase()->query("
      SELECT
        pfid.field_uaqs_photo_fid
      FROM
        {field_data_field_uaqs_photo} pfid
      WHERE
        bundle = 'uaqs_person'
      ");
    foreach ($result as $record) {
      $fids[] = $record->field_uaqs_photo_fid;
    }

    $result = $this->getDatabase()->query("
      SELECT
        cvfid.field_uaqs_cv_documents_fid
      FROM
        {field_data_field_uaqs_cv_documents} cvfid
      WHERE
        bundle = 'uaqs_person'  
      ");
    foreach ($result as $record) {
      $fids[] = $record->field_uaqs_cv_documents_fid;
    }

    $query = $this->select('file_managed', 'f')
      ->fields('f')
      ->condition('f.fid', $fids, "IN")
      ->condition('f.uri', 'temporary://%', 'NOT LIKE')
      ->orderBy('f.fid');

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'fid' => $this->t('File ID'),
      'uid' => $this->t('The {users}.uid who added the file. If set to 0, this file was added by an anonymous user.'),
      'filename' => $this->t('File name'),
      'uri' => $this->t('The URI to access the file'),
      'filemime' => $this->t('File MIME Type'),
      'status' => $this->t('The published status of a file.'),
      'timestamp' => $this->t('The time that the file was added.'),
      'type' => $this->t('The type of this file.'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {

    $this->publicPath = \Drupal::config('az_migration.settings')->get('migrate_d7_public_path');
    $this->privatePath = \Drupal::config('az_migration.settings')->get('migrate_d7_private_path');
    $this->temporaryPath = \Drupal::config('az_migration.settings')->get('migrate_d7_temporary_path');

    $migrate_d7_protocol = \Drupal::config('az_migration.settings')->get('migrate_d7_protocol');
    $migrate_d7_filebasepath = \Drupal::config('az_migration.settings')->get('migrate_d7_filebasepath');

    if ($migrate_d7_filebasepath != " " && $migrate_d7_filebasepath != "") {
      $row->setSourceProperty('constants/old_files_path', $migrate_d7_protocol . "://" . $migrate_d7_filebasepath);
    }

    $site_name = \Drupal::config('system.site')->get('name');
    // Setting the path to fetch the files.
    $path = str_replace(['public:/', 'private:/', 'temporary:/'],
    [$this->publicPath, $this->privatePath, $this->temporaryPath],
    $row->getSourceProperty('uri'));
    $row->setSourceProperty('filepath', $path);

    return parent::prepareRow($row);
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['fid']['type'] = 'integer';
    return $ids;
  }

}
