<?php

namespace Drupal\az_migration\Plugin\migrate\source;

use Drupal\file\Plugin\migrate\source\d7\File;
use Drupal\migrate\Attribute\MigrateSource;
use Drupal\migrate\Row;

/**
 * Drupal 7 file source from database.
 *
 * @deprecated in az_quickstart:3.2.0 and is removed from az_quickstart:4.0.0.
 * There is no replacement.
 * 
 * @todo Support file migration, copy all fid files.
 */
#[MigrateSource('az_file_migration')]
class AZFileHandle extends File {

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
  public function prepareRow(Row $row) {

    // @phpstan-ignore-next-line
    $this->publicPath = \Drupal::config('az_migration.settings')->get('migrate_d7_public_path');
    // @phpstan-ignore-next-line
    $this->privatePath = \Drupal::config('az_migration.settings')->get('migrate_d7_private_path');
    // @phpstan-ignore-next-line
    $this->temporaryPath = \Drupal::config('az_migration.settings')->get('migrate_d7_temporary_path');

    // @phpstan-ignore-next-line
    $migrate_d7_protocol = \Drupal::config('az_migration.settings')->get('migrate_d7_protocol');
    // @phpstan-ignore-next-line
    $migrate_d7_filebasepath = \Drupal::config('az_migration.settings')->get('migrate_d7_filebasepath');

    if ($migrate_d7_filebasepath !== " " && $migrate_d7_filebasepath !== "") {
      $row->setSourceProperty('constants/old_files_path', $migrate_d7_protocol . "://" . $migrate_d7_filebasepath);
    }

    // Setting the path to fetch the files.
    $path = str_replace(['public:/', 'private:/', 'temporary:/'],
    [$this->publicPath, $this->privatePath, $this->temporaryPath],
    $row->getSourceProperty('uri'));
    // Set the filepath for the source files.
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
