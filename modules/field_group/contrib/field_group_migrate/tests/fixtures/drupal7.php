<?php

/**
 * @file
 * A database agnostic dump for testing purposes.
 */

use Drupal\Core\Database\Database;

$connection = Database::getConnection();

$connection->schema()->createTable('field_group', [
  'fields' => [
    'id' => [
      'type' => 'serial',
      'not null' => TRUE,
      'size' => 'normal',
    ],
    'identifier' => [
      'type' => 'varchar',
      'not null' => TRUE,
      'length' => '255',
      'default' => '',
    ],
    'group_name' => [
      'type' => 'varchar',
      'not null' => TRUE,
      'length' => '32',
      'default' => '',
    ],
    'entity_type' => [
      'type' => 'varchar',
      'not null' => TRUE,
      'length' => '32',
      'default' => '',
    ],
    'bundle' => [
      'type' => 'varchar',
      'not null' => TRUE,
      'length' => '128',
      'default' => '',
    ],
    'mode' => [
      'type' => 'varchar',
      'not null' => TRUE,
      'length' => '128',
      'default' => '',
    ],
    'parent_name' => [
      'type' => 'varchar',
      'not null' => TRUE,
      'length' => '32',
      'default' => '',
    ],
    'data' => [
      'type' => 'blob',
      'not null' => TRUE,
      'size' => 'big',
    ],
  ],
  'primary key' => [
    'id',
  ],
  'unique keys' => [
    'identifier' => [
      'identifier',
    ],
  ],
  'indexes' => [
    'group_name' => [
      'group_name',
    ],
  ],
  'mysql_character_set' => 'utf8',
]);

$connection->insert('field_group')
  ->fields([
    'id',
    'identifier',
    'group_name',
    'entity_type',
    'bundle',
    'mode',
    'parent_name',
    'data',
  ])
  ->values([
    'id' => '1',
    'identifier' => 'group_page|node|page|default',
    'group_name' => 'group_page',
    'entity_type' => 'node',
    'bundle' => 'page',
    'mode' => 'default',
    'parent_name' => '',
    'data' => 'a:5:{s:5:"label";s:10:"Node group";s:6:"weight";i:0;s:8:"children";a:0:{}s:11:"format_type";s:5:"htabs";s:15:"format_settings";a:1:{s:17:"instance_settings";a:0:{}}}',
  ])
  ->values([
    'id' => '2',
    'identifier' => 'group_user|user|user|default',
    'group_name' => 'group_user',
    'entity_type' => 'user',
    'bundle' => 'user',
    'mode' => 'default',
    'parent_name' => '',
    'data' => 'a:5:{s:5:"label";s:17:"User group parent";s:6:"weight";i:1;s:8:"children";a:1:{i:0;s:16:"group_user_child";}s:11:"format_type";s:3:"div";s:15:"format_settings";a:1:{s:17:"instance_settings";a:0:{}}}',
  ])
  ->values([
    'id' => '3',
    'identifier' => 'group_user_child|user|user|default',
    'group_name' => 'group_user_child',
    'entity_type' => 'user',
    'bundle' => 'user',
    'mode' => 'default',
    'parent_name' => 'group_user',
    'data' => 'a:5:{s:5:"label";s:16:"User group child";s:6:"weight";s:2:"99";s:8:"children";a:2:{i:0;s:15:"group_user_tab1";i:1;s:15:"group_user_tab2";}s:11:"format_type";s:4:"tabs";s:15:"format_settings";a:2:{s:5:"label";s:16:"User group child";s:17:"instance_settings";a:2:{s:2:"id";s:33:"group_article_node_article_teaser";s:7:"classes";s:16:"user-group-child";}}}',
  ])
  ->values([
    'id' => '4',
    'identifier' => 'group_article|node|article|teaser',
    'group_name' => 'group_article',
    'entity_type' => 'node',
    'bundle' => 'article',
    'mode' => 'teaser',
    'parent_name' => 'group_article_htabs',
    'data' => 'a:5:{s:5:"label";s:10:"htab group";s:6:"weight";s:1:"2";s:8:"children";a:1:{i:0;s:11:"field_image";}s:11:"format_type";s:4:"htab";s:15:"format_settings";a:1:{s:17:"instance_settings";a:1:{s:7:"classes";s:10:"htab-group";}}}',
  ])
  ->values([
    'id' => '5',
    'identifier' => 'group_page|node|page|form',
    'group_name' => 'group_page',
    'entity_type' => 'node',
    'bundle' => 'page',
    'mode' => 'form',
    'parent_name' => '',
    'data' => 'a:5:{s:5:"label";s:15:"Node form group";s:6:"weight";s:1:"0";s:8:"children";a:1:{i:0;s:14:"group_page_tab";}s:11:"format_type";s:5:"htabs";s:15:"format_settings";a:1:{s:17:"instance_settings";a:0:{}}}',
  ])
  ->values([
    'id' => '6',
    'identifier' => 'group_article|node|article|form',
    'group_name' => 'group_article',
    'entity_type' => 'node',
    'bundle' => 'article',
    'mode' => 'form',
    'parent_name' => 'group_article_htabs',
    'data' => 'a:5:{s:5:"label";s:15:"htab form group";s:6:"weight";s:1:"2";s:8:"children";a:1:{i:0;s:11:"field_image";}s:11:"format_type";s:4:"htab";s:15:"format_settings";a:1:{s:17:"instance_settings";a:0:{}}}',
  ])
  ->values([
    'id' => '7',
    'identifier' => 'group_user_tab1|user|user|default',
    'group_name' => 'group_user_tab1',
    'entity_type' => 'user',
    'bundle' => 'user',
    'mode' => 'default',
    'parent_name' => 'group_user_child',
    'data' => 'a:5:{s:5:"label";s:10:"User tab 1";s:6:"weight";s:2:"99";s:8:"children";a:1:{i:0;s:10:"field_file";}s:11:"format_type";s:3:"tab";s:15:"format_settings";a:3:{s:5:"label";s:10:"User tab 1";s:17:"instance_settings";a:2:{s:7:"classes";s:15:"vtab vtab--open";s:11:"description";s:0:"";}s:9:"formatter";s:4:"open";}}',
  ])
  ->values([
    'id' => '8',
    'identifier' => 'group_user_tab2|user|user|default',
    'group_name' => 'group_user_tab2',
    'entity_type' => 'user',
    'bundle' => 'user',
    'mode' => 'default',
    'parent_name' => 'group_user_child',
    'data' => 'a:5:{s:5:"label";s:10:"User tab 2";s:6:"weight";s:3:"100";s:8:"children";a:1:{i:0;s:13:"field_integer";}s:11:"format_type";s:3:"tab";s:15:"format_settings";a:3:{s:5:"label";s:10:"User tab 2";s:17:"instance_settings";a:2:{s:7:"classes";s:17:"vtab vtab--closed";s:11:"description";s:0:"";}s:9:"formatter";s:6:"closed";}}',
  ])
  ->values([
    'id' => '9',
    'identifier' => 'group_article_htabs|node|article|form',
    'group_name' => 'group_article_htabs',
    'entity_type' => 'node',
    'bundle' => 'article',
    'mode' => 'form',
    'parent_name' => '',
    'data' => 'a:5:{s:5:"label";s:15:"Horizontal tabs";s:6:"weight";s:1:"1";s:8:"children";a:1:{i:0;s:13:"group_article";}s:11:"format_type";s:5:"htabs";s:15:"format_settings";a:2:{s:9:"formatter";s:0:"";s:17:"instance_settings";a:2:{s:7:"classes";s:37:"group-article-htabs field-group-htabs";s:2:"id";s:0:"";}}}',
  ])
  ->values([
    'id' => '10',
    'identifier' => 'group_article_htabs|node|article|teaser',
    'group_name' => 'group_article_htabs',
    'entity_type' => 'node',
    'bundle' => 'article',
    'mode' => 'teaser',
    'parent_name' => '',
    'data' => 'a:5:{s:5:"label";s:15:"Horizontal tabs";s:6:"weight";s:1:"1";s:8:"children";a:1:{i:0;s:13:"group_article";}s:11:"format_type";s:5:"htabs";s:15:"format_settings";a:2:{s:5:"label";s:15:"Horizontal tabs";s:17:"instance_settings";a:2:{s:2:"id";s:0:"";s:7:"classes";s:0:"";}}}',
  ])
  ->values([
    'id' => '11',
    'identifier' => 'group_page_tab|node|page|form',
    'group_name' => 'group_page_tab',
    'entity_type' => 'node',
    'bundle' => 'page',
    'mode' => 'form',
    'parent_name' => 'group_page',
    'data' => 'a:5:{s:5:"label";s:14:"Horizontal tab";s:6:"weight";s:2:"17";s:8:"children";a:1:{i:0;s:16:"field_text_plain";}s:11:"format_type";s:4:"htab";s:15:"format_settings";a:3:{s:5:"label";s:14:"Horizontal tab";s:17:"instance_settings";a:3:{s:15:"required_fields";i:1;s:7:"classes";s:31:"group-page-tab field-group-htab";s:11:"description";s:0:"";}s:9:"formatter";s:4:"open";}}',
  ])
  ->execute();

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
    'filename' => 'sites/all/modules/field_group/field_group.module',
    'name' => 'field_group',
    'type' => 'module',
    'owner' => '',
    'status' => '1',
    'bootstrap' => '0',
    'schema_version' => '7008',
    'weight' => '1',
    // cspell:ignore ctools
    'info' => 'a:12:{s:4:"name";s:11:"Field Group";s:11:"description";s:67:"Provides the ability to group your fields on both form and display.";s:7:"package";s:6:"Fields";s:12:"dependencies";a:2:{i:0;s:5:"field";i:1;s:6:"ctools";}s:4:"core";s:3:"7.x";s:5:"files";a:2:{i:0;s:25:"tests/field_group.ui.test";i:1;s:30:"tests/field_group.display.test";}s:7:"version";s:7:"7.x-1.5";s:7:"project";s:11:"field_group";s:9:"datestamp";s:10:"1452033709";s:5:"mtime";i:1486548096;s:3:"php";s:5:"5.2.4";s:9:"bootstrap";i:0;}',
  ])
  ->execute();
