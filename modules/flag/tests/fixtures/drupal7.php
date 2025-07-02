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
  ->values([
     'filename' => 'sites/all/modules/contrib/flag/flag_actions.module',
     'name' => 'flag_actions',
     'type' => 'module',
     'owner' => '',
     'status' => '1',
     'bootstrap' => '0',
     'schema_version' => '0',
     'weight' => '0',
     'info' => 'a:6:{s:4:"name";s:12:"Flag actions";s:11:"description";s:31:"Execute actions on Flag events.";s:4:"core";s:3:"7.x";s:12:"dependencies";a:1:{i:0;s:4:"flag";}s:7:"package";s:5:"Flags";s:9:"configure";s:29:"admin/structure/flags/actions";}',
   ])
  ->values([
    'filename' => 'sites/all/modules/contrib/flag/flag_bookmark/flag_bookmark.module',
    'name' => 'flag_bookmark',
    'type' => 'module',
    'owner' => '',
    'status' => '1',
    'bootstrap' => '0',
    'schema_version' => '0',
    'weight' => '0',
    'info' => 'a:5:{s:4:"name";s:13:"Flag Bookmark";s:11:"description";s:55:"Provides an example bookmark flag and supporting views.";s:4:"core";s:3:"7.x";s:12:"dependencies";a:1:{i:0;s:4:"flag";}s:7:"package";s:5:"Flags";}',
  ])
  ->execute();

$connection->schema()->createTable('flag', [
  'fields' => [
    'fid' => [
      'type' => 'serial',
      'size' => 'small',
      'unsigned' => TRUE,
      'not null' => TRUE,
    ],
    'entity_type' => [
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

$connection->schema()->createTable('flagging', [
  'fields' => [
    'flagging_id' => [
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
    'entity_type' => [
      'type' => 'varchar',
      'length' => '128',
      'not null' => TRUE,
      'default' => '',
    ],
    'entity_id' => [
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
  'primary key' => ['flagging_id'],
  'unique keys' => [
    'fid_entity_id_uid_sid' => ['fid', 'entity_id', 'uid', 'sid'],
  ],
  'indexes' => [
    'entity_type_uid_sid' => ['entity_type', 'uid', 'sid'],
    'entity_type_entity_id_uid_sid' => [
      'entity_type',
      'entity_id',
      'uid',
      'sid',
    ],
    'entity_id_fid' => ['entity_id', 'fid'],
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
    'entity_type' => [
      'type' => 'varchar',
      'length' => '128',
      'not null' => TRUE,
      'default' => '',
    ],
    'entity_id' => [
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
    'last_updated' => [
      'type' => 'int',
      'unsigned' => TRUE,
      'not null' => TRUE,
      'default' => 0,
      'disp-size' => 11,
    ],
  ],
  'primary key' => ['fid', 'entity_id'],
  'indexes' => [
    'fid_entity_type' => ['fid', 'entity_type'],
    'entity_type_entity_id' => ['entity_type', 'entity_id'],
    'fid_count' => ['fid', 'count'],
    'fid_last_updated' => ['fid', 'last_updated'],
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

$connection->insert('flag')
  ->fields([
    'fid',
    'entity_type',
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

$connection->insert('flagging')
  ->fields([
    'flagging_id',
    'fid',
    'entity_id',
    'entity_type',
    'uid',
    'sid',
    'timestamp',
  ])
  ->values([
    '1',
    '1',
    '2',
    'node',
    '3',
    '0',
    '1564543637',
  ])
  ->values([
    '2',
    '1',
    '2',
    'node',
    '1',
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
    '3',
    '0',
    '1564543637',
  ])
  ->values([
    '5',
    '1',
    '6',
    'node',
    '2',
    '0',
    '1564543637',
  ])
  ->values([
    '6',
    '2',
    '2',
    'user',
    '3',
    '0',
    '1564543637',
  ])
  ->values([
    '7',
    '3',
    '5',
    'node',
    '2',
    '0',
    '1564543637',
  ])
  ->values([
    '8',
    '1',
    '8',
    'node',
    '3',
    '0',
    '1564543637',
  ])
  ->values([
    '9',
    '1',
    '11',
    'node',
    '3',
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
    '3',
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
    'entity_type',
    'entity_id',
    'count',
    'last_updated',
  ])
  ->values([
    '1',
    'node',
    '2',
    '2',
    '1564543637',
  ])
  ->values([
    '1',
    'node',
    '5',
    '2',
    '1564543637',
  ])
  ->values([
    '1',
    'node',
    '6',
    '1',
    '1564543637',
  ])
  ->values([
    '2',
    'user',
    '2',
    '1',
    '1564543637',
  ])
  ->values([
    '3',
    'node',
    '5',
    '1',
    '1564543637',
  ])
  ->values([
    '1',
    'node',
    '8',
    '1',
    '1564543637',
  ])
  ->values([
    '1',
    'node',
    '11',
    '1',
    '1564543637',
  ])
  ->values([
    '4',
    'comment',
    '1',
    '1',
    '1564543637',
  ])
  ->values([
    '4',
    'comment',
    '2',
    '2',
    '1564543637',
  ])
  ->execute();
