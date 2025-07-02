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
    'filename' => 'sites/all/modules/contrib/flag/flag.module',
    'name' => 'flag',
    'type' => 'module',
    'owner' => '',
    'status' => '1',
    'bootstrap' => '0',
    'schema_version' => '7306',
    'weight' => '0',
    'info' => 'a:5:{s:4:"name";s:4:"Flag";s:11:"description";s:55:"Create customized flags that users can set on entities.";s:4:"core";s:3:"7.x";s:7:"package";s:4:"Flag";s:9:"configure";s:21:"admin/structure/flags";}',
  ])
  ->execute();

$connection->schema()->createTable('flags', [
  'fields' => [
    'fid' => [
      'type' => 'serial',
      'size' => 'small',
      'unsigned' => TRUE,
      'not null' => TRUE,
    ],
    'content_type' => [
      'type' => 'varchar',
      'length' => '128',
      'not null' => TRUE,
      'default' => '',
    ],
    'name' => [
      'type' => 'varchar',
      'length' => '32',
      'not null' => FALSE,
      'default' => '',
    ],
    'title' => [
      'type' => 'varchar',
      'length' => '255',
      'not null' => FALSE,
      'default' => '',
    ],
    'global' => [
      'type' => 'int',
      'size' => 'tiny',
      'not null' => FALSE,
      'default' => 0,
    ],
    'options' => [
      'type' => 'text',
      'not null' => FALSE,
    ],
  ],
  'primary key' => ['fid'],
  'unique keys' => [
    'name' => ['name'],
  ],
]);

$connection->schema()->createTable('flag_content', [
  'fields' => [
    'fcid' => [
      'type' => 'serial',
      'unsigned' => TRUE,
      'not null' => TRUE,
    ],
    'fid' => [
      'type' => 'int',
      'size' => 'small',
      'unsigned' => TRUE,
      'not null' => TRUE,
      'default' => 0,
    ],
    'content_type' => [
      'type' => 'varchar',
      'length' => '128',
      'not null' => TRUE,
      'default' => '',
    ],
    'content_id' => [
      'type' => 'int',
      'unsigned' => TRUE,
      'not null' => TRUE,
      'default' => 0,
    ],
    'uid' => [
      'type' => 'int',
      'unsigned' => TRUE,
      'not null' => TRUE,
      'default' => 0,
    ],
    'sid' => [
      'type' => 'int',
      'unsigned' => TRUE,
      'not null' => TRUE,
      'default' => 0,
    ],
    'timestamp' => [
      'type' => 'int',
      'unsigned' => TRUE,
      'not null' => TRUE,
      'default' => 0,
      'disp-size' => 11,
    ],
  ],
  'primary key' => ['fcid'],
  'unique keys' => [
    'fid_content_id_uid_sid' => ['fid', 'content_id', 'uid', 'sid'],
  ],
  'indexes' => [
    'content_type_uid_sid' => ['content_type', 'uid', 'sid'],
    'content_type_content_id_uid_sid' => [
      'content_type',
      'content_id',
      'uid',
      'sid',
    ],
    'content_id_fid' => ['content_id', 'fid'],
  ],
]);

$connection->schema()->createTable('flag_types', [
  'fields' => [
    'fid' => [
      'type' => 'int',
      'size' => 'small',
      'unsigned' => TRUE,
      'not null' => TRUE,
      'default' => 0,
    ],
    'type' => [
      'description' => 'The entity bundles that can be flagged by this fid.',
      'type' => 'varchar',
      'length' => '128',
      'not null' => TRUE,
      'default' => '',
    ],
  ],
  'indexes' => ['fid' => ['fid']],
]);

$connection->schema()->createTable('flag_counts', [
  'fields' => [
    'fid' => [
      'type' => 'int',
      'size' => 'small',
      'unsigned' => TRUE,
      'not null' => TRUE,
      'default' => 0,
    ],
    'content_type' => [
      'type' => 'varchar',
      'length' => '128',
      'not null' => TRUE,
      'default' => '',
    ],
    'content_id' => [
      'type' => 'int',
      'unsigned' => TRUE,
      'not null' => TRUE,
      'default' => 0,
      'disp-width' => '10',
    ],
    'count' => [
      'type' => 'int',
      'unsigned' => TRUE,
      'not null' => TRUE,
      'default' => 0,
      'disp-width' => '10',
    ],
  ],
  'primary key' => ['fid', 'content_id'],
  'indexes' => [
    'fid_content_type' => ['fid', 'content_type'],
    'content_type_content_id' => ['content_type', 'content_id'],
    'fid_count' => ['fid', 'count'],
  ],
]);

$connection->schema()->createTable('flag_actions', [
  'fields' => [
    'aid' => [
      'type' => 'serial',
      'not null' => TRUE,
      'disp-width' => '5',
    ],
    'fid' => [
      'type' => 'int',
      'size' => 'small',
      'not null' => FALSE,
      'disp-width' => '5',
    ],
    'event' => [
      'type' => 'varchar',
      'length' => '255',
      'not null' => FALSE,
    ],
    'threshold' => [
      'type' => 'int',
      'size' => 'small',
      'not null' => TRUE,
      'default' => 0,
      'disp-width' => '5',
    ],
    'repeat_threshold' => [
      'type' => 'int',
      'size' => 'small',
      'not null' => TRUE,
      'default' => 0,
      'disp-width' => '5',
    ],
    'callback' => [
      'type' => 'varchar',
      'length' => '255',
      'not null' => TRUE,
      'default' => '',
    ],
    'parameters' => [
      'type' => 'text',
      'size' => 'big',
      'not null' => TRUE,
    ],
  ],
  'primary key' => ['aid'],
]);

$connection->insert('flags')
  ->fields([
    'fid',
    'content_type',
    'name',
    'title',
    'global',
    'options',
  ])
  ->values([
    '1',
    'node',
    'node_flag',
    'Node Flag',
    '0',
    'a:15:{s:4:"il8n";i:0;s:13:"show_in_links";a:1:{s:7:"default";s:7:"default";}s:13:"access_author";s:6:"others";s:13:"show_as_field";b:0;s:12:"show_on_form";b:0;s:20:"show_contextual_link";b:0;s:6:"weight";i:0;s:10:"flag_short";s:4:"Flag";s:9:"flag_long";s:23:"Add a flag to this item";s:12:"flag_message";s:22:"Item has been flagged.";s:12:"unflag_short";s:6:"Unflag";s:11:"unflag_long";s:27:"Remove flag from this item.";s:14:"unflag_message";s:22:"Flag has been removed.";s:18:"unflag_denied_text";s:47:"You do not have permission to remove this flag.";s:9:"link_type";s:6:"toggle";}',
  ])
  ->values([
    '2',
    'user',
    'user_flag',
    'User flag',
    '0',
    'a:16:{s:10:"access_uid";b:0;s:13:"access_author";s:0:"";s:13:"show_in_links";a:0:{}s:13:"show_as_field";b:0;s:12:"show_on_form";b:0;s:20:"show_contextual_link";b:0;s:15:"show_on_profile";b:1;s:6:"weight";i:0;s:10:"flag_short";s:4:"Flag";s:9:"flag_long";s:23:"Add a flag to this item";s:12:"flag_message";s:22:"Item has been flagged.";s:12:"unflag_short";s:6:"Unflag";s:11:"unflag_long";s:27:"Remove flag from this item.";s:14:"unflag_message";s:22:"Flag has been removed.";s:18:"unflag_denied_text";s:47:"You do not have permission to remove this flag.";s:9:"link_type";s:6:"toggle";}',
  ])
  ->values([
    '3',
    'node',
    'node_global_flag',
    'Node global flag',
    '1',
    'a:17:{s:4:"il8n";i:0;s:13:"show_in_links";a:1:{s:7:"default";s:7:"default";}s:13:"access_author";s:0:"";s:13:"show_as_field";b:0;s:12:"show_on_form";b:0;s:20:"show_contextual_link";b:0;s:17:"flag_confirmation";s:40:"Are you sure you want to flag this item?";s:19:"unflag_confirmation";s:42:"Are you sure you want to remove this flag?";s:6:"weight";i:0;s:10:"flag_short";s:0:"";s:9:"flag_long";s:0:"";s:12:"flag_message";s:0:"";s:12:"unflag_short";s:0:"";s:11:"unflag_long";s:0:"";s:14:"unflag_message";s:0:"";s:18:"unflag_denied_text";s:0:"";s:9:"link_type";s:7:"confirm";}',
  ])
  ->values([
    '4',
    'comment',
    'comment_flag',
    'Comment flag',
    '0',
    'a:14:{s:13:"access_author";s:6:"others";s:13:"show_in_links";a:1:{s:7:"default";s:7:"default";}s:13:"show_as_field";b:0;s:12:"show_on_form";b:0;s:20:"show_contextual_link";b:0;s:6:"weight";i:0;s:10:"flag_short";s:4:"Flag";s:9:"flag_long";s:23:"Add a flag to this item";s:12:"flag_message";s:22:"Item has been flagged.";s:12:"unflag_short";s:6:"Unflag";s:11:"unflag_long";s:27:"Remove flag from this item.";s:14:"unflag_message";s:22:"Flag has been removed.";s:18:"unflag_denied_text";s:47:"You do not have permission to remove this flag.";s:9:"link_type";s:6:"toggle";}',
  ])
  ->execute();

$connection->insert('flag_content')
  ->fields([
    'fcid',
    'fid',
    'content_id',
    'content_type',
    'uid',
    'sid',
    'timestamp',
  ])
  ->values([
    '1',
    '1',
    '2',
    'node',
    '2',
    '0',
    '1564543637',
  ])
  ->values([
    '2',
    '1',
    '2',
    'node',
    '8',
    '0',
    '1564543637',
  ])
  ->values([
    '3',
    '1',
    '5',
    'node',
    '2',
    '0',
    '1564543637',
  ])
  ->values([
    '4',
    '1',
    '5',
    'node',
    '8',
    '0',
    '1564543637',
  ])
  ->values([
    '5',
    '1',
    '6',
    'node',
    '8',
    '0',
    '1564543637',
  ])
  ->values([
    '6',
    '2',
    '2',
    'user',
    '8',
    '0',
    '1564543637',
  ])
  ->values([
    '7',
    '3',
    '5',
    'node',
    '8',
    '0',
    '1564543637',
  ])
  ->values([
    '8',
    '1',
    '8',
    'node',
    '8',
    '0',
    '1564543637',
  ])
  ->values([
    '9',
    '1',
    '11',
    'node',
    '8',
    '0',
    '1564543637',
  ])
  ->values([
    '10',
    '4',
    '1',
    'comment',
    '2',
    '0',
    '1564543637',
  ])
  ->values([
    '11',
    '4',
    '2',
    'comment',
    '2',
    '0',
    '1564543637',
  ])
  ->values([
    '12',
    '4',
    '2',
    'comment',
    '8',
    '0',
    '1564543637',
  ])
  ->execute();

$connection->insert('flag_types')
  ->fields([
    'fid',
    'type',
  ])
  ->values([
    '1',
    'article',
  ])
  ->values([
    '1',
    'blog',
  ])
  ->values([
    '2',
    'user',
  ])
  ->values([
    '3',
    'article',
  ])
  ->values([
    '4',
    'comment_test_content_type',
  ])
  ->values([
    '4',
    'article',
  ])
  ->execute();

$connection->insert('flag_counts')
  ->fields([
    'fid',
    'content_type',
    'content_id',
    'count',
  ])
  ->values([
    '1',
    'node',
    '2',
    '2',
  ])
  ->values([
    '1',
    'node',
    '5',
    '2',
  ])
  ->values([
    '1',
    'node',
    '6',
    '1',
  ])
  ->values([
    '2',
    'user',
    '2',
    '1',
  ])
  ->values([
    '3',
    'node',
    '5',
    '1',
  ])
  ->values([
    '1',
    'node',
    '8',
    '1',
  ])
  ->values([
    '1',
    'node',
    '11',
    '1',
  ])
  ->values([
    '4',
    'comment',
    '1',
    '1',
  ])
  ->values([
    '4',
    'comment',
    '2',
    '2',
  ])
  ->execute();
