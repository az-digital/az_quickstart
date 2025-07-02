<?php
// @codingStandardsIgnoreFile
/**
 * @file
 * A database agnostic dump for testing purposes.
 */

use Drupal\Core\Database\Database;

$connection = Database::getConnection();

$connection->insert('system')
  ->fields([
    'filename',
    'name',
    'type',
    'owner',
    'status',
    'bootstrap',
    'schema_version',
    'weight',
    'info',
  ])
  ->values([
    'filename' => 'sites/all/modules/contrib/extlink/extlink.module',
    'name' => 'extlink',
    'type' => 'module',
    'owner' => '',
    'status' => '1',
    'bootstrap' => '0',
    'schema_version' => '7001',
    'weight' => '0',
    'info' => 'a:14:{s:4:\"name\";s:12:\"Entity Print\";s:4:\"core\";s:3:\"7.x\";s:7:\"package\";s:12:\"Entity Print\";s:9:\"configure\";s:32:\"admin/config/user-interface/extlink\";}s:5:\"files\";a:1:{i:0;s:23:\"tests/extlink.test\";}s:7:\"version\";s:7:\"7.x-1.5\";s:7:\"project\";s:12:\"extlink\";s:9:\"datestamp\";s:10:\"1481237300\";s:5:\"mtime\";i:1481237300;s:11:\"description\";s:0:\"\";s:3:\"php\";s:5:\"5.2.4\";s:9:\"bootstrap\";i:0;}'
  ])
  ->execute();


$connection->insert('variable')
  ->fields([
    'name',
    'value',
  ])
  ->values([
    'name' => 'extlink_default_css',
    'value' => 'i:0;',
  ])
  ->execute();
