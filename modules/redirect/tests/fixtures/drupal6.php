<?php

/**
 * @file
 * A database agnostic dump for testing purposes.
 */

use Drupal\Core\Database\Database;

$connection = Database::getConnection();

$connection->schema()->createTable('path_redirect', [
  'fields' => [
    'rid' => [
      'type' => 'serial',
      'not null' => TRUE,
      'size' => 'normal',
    ],
    'source' => [
      'type' => 'varchar',
      'length' => 255,
      'not null' => TRUE,
    ],
    'redirect' => [
      'type' => 'varchar',
      'length' => 255,
      'not null' => TRUE,
    ],
    'query' => [
      'type' => 'varchar',
      'length' => 255,
      'not null' => FALSE,
    ],
    'fragment' => [
      'type' => 'varchar',
      'length' => 50,
      'not null' => FALSE,
    ],
    'language' => [
      'type' => 'varchar',
      'length' => 12,
      'not null' => TRUE,
      'default' => '',
    ],
    'type' => [
      'type' => 'int',
      'size' => 'small',
      'not null' => TRUE,
    ],
    'last_used' => [
      'type' => 'int',
      'unsigned' => TRUE,
      'not null' => TRUE,
      'default' => 0,
    ],
  ],
  'primary key' => ['rid'],
  'unique keys' => ['source_language' => ['source', 'language']],
  'mysql_character_set' => 'utf8',
]);


$connection->insert('path_redirect')
  ->fields([
    'rid',
    'source',
    'redirect',
    'query',
    'fragment',
    'language',
    'type',
    'last_used',
  ])
  ->values([
    'rid' => 5,
    'source' => 'test/source/url',
    'redirect' => 'test/redirect/url',
    'query' => NULL,
    'fragment' => NULL,
    'language' => '',
    'type' => 301,
    'last_used' => 1449497138,
  ])
  ->values([
    'rid' => 7,
    'source' => 'test/source/url2',
    'redirect' => 'http://test/external/redirect/url',
    'query' => 'foo=bar&biz=buz',
    'fragment' => NULL,
    'language' => 'en',
    'type' => 302,
    'last_used' => 1449497139,
  ])
  ->execute();

$connection->schema()->createTable('system', [
  'fields' => [
    'filename' => [
      'type' => 'varchar',
      'not null' => TRUE,
      'length' => '255',
      'default' => '',
    ],
    'name' => [
      'type' => 'varchar',
      'not null' => TRUE,
      'length' => '255',
      'default' => '',
    ],
    'type' => [
      'type' => 'varchar',
      'not null' => TRUE,
      'length' => '255',
      'default' => '',
    ],
    'owner' => [
      'type' => 'varchar',
      'not null' => TRUE,
      'length' => '255',
      'default' => '',
    ],
    'status' => [
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'normal',
      'default' => '0',
    ],
    'throttle' => [
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'normal',
      'default' => '0',
    ],
    'bootstrap' => [
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'normal',
      'default' => '0',
    ],
    'schema_version' => [
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'normal',
      'default' => '-1',
    ],
    'weight' => [
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'normal',
      'default' => '0',
    ],
    'info' => [
      'type' => 'text',
      'not null' => FALSE,
      'size' => 'normal',
    ],
  ],
  'primary key' => [
    'filename',
  ],
  'mysql_character_set' => 'utf8',
]);

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
    'filename' => 'modules/contrib/path_redirect/path_redirect.module',
    'name' => 'path_redirect',
    'type' => 'module',
    'owner' => '',
    'status' => '1',
    'bootstrap' => '0',
    'schema_version' => '7000',
    'weight' => '0',
    'info' => 'a:10:{s:4:"name";s:13:"Path Redirect";s:11:"description";s:51:"Allows users to redirect from old URLs to new URLs.";s:7:"package";s:5:"Other";s:7:"version";s:3:"6.0";s:4:"core";s:3:"6.x";s:7:"project";s:13:"path_redirect";s:9:"datestamp";s:10:"1347989995";s:12:"dependencies";a:0:{}s:10:"dependents";a:0:{}s:3:"php";s:5:"4.3.5";}',
  ])
  ->execute();
