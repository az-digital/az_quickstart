<?php

/**
 * @file
 * Example Metatag v1 configuration.
 */

/**
 * Notes on how to use this file.
 *
 * When adding tests for changes to meta tags provided by a submodule, that
 * submodule must be listed in the modules list below.
 *
 * It is easiest to not add meta tag default configuration changes here that
 * depend upon submodules, it works better to make those changes in the
 * appropriate update script.
 *
 * There is currently only one Metatag field defined, on the Article content
 * type.
 *
 * Each meta tag value to be tested is added to the fields lower down.
 *
 * @todo Finish documenting this file.
 * @todo Expand to handle multiple languages.
 * @todo Expand to handle revisions.
 * @todo Expand to have Metatag fields on multiple entity types.
 * @todo Expand to have multiple Metatag fields, with different field names.
 * @todo Work out a better way of handling field specification changes.
 */

use Drupal\Component\Serialization\Yaml;
use Drupal\Component\Uuid\Php as Uuid;
use Drupal\Core\Database\Database;

$config_fields = ['collection', 'name', 'data'];
$keyvalue_fields = ['collection', 'name', 'value'];

$connection = Database::getConnection();

// Classes that are allowed in serialized arrays.
$allowed_classes = [
  'Drupal\Core\Field\BaseFieldDefinition',
  'Drupal\field\Entity\FieldStorageConfig',
];

// Enable Metatag (and Token).
$extensions = $connection->select('config')
  ->fields('config', ['data'])
  ->condition('collection', '')
  ->condition('name', 'core.extension')
  ->execute()
  ->fetchField();
$extensions = unserialize($extensions, ['allowed_classes' => FALSE]);
$extensions['module']['metatag'] = 0;
/**
 * Additional submodules must be added here if their meta tags are being tested.
 */
$extensions['module']['metatag_google_plus'] = 0;
$extensions['module']['metatag_twitter_cards'] = 0;
$extensions['module']['token'] = 0;
$connection->update('config')
  ->fields(['data' => serialize($extensions)])
  ->condition('collection', '')
  ->condition('name', 'core.extension')
  ->execute();

// Schema configuration for the two modules.
$connection->insert('key_value')
  ->fields($keyvalue_fields)
  ->values([
    'collection' => 'system.schema',
    'name' => 'metatag',
    'value' => 'i:8109;',
  ])
  ->values([
    'collection' => 'system.schema',
    'name' => 'token',
    'value' => 'i:8000;',
  ])
  ->execute();

// Indicate that the Metatag post_update scripts had already executed.
$data = $connection->select('key_value')
  ->fields('key_value', ['value'])
  ->condition('collection', 'post_update')
  ->condition('name', 'existing_updates')
  ->execute()
  ->fetchField();
$data = unserialize($data, ['allowed_classes' => FALSE]);
$data[] = 'metatag_post_update_convert_author_config';
$data[] = 'metatag_post_update_convert_author_data';
$data[] = 'metatag_post_update_convert_mask_icon_to_array_values';
$connection->update('key_value')
  ->fields(['value' => serialize($data)])
  ->condition('collection', 'post_update')
  ->condition('name', 'existing_updates')
  ->execute();

// Load Token configuration.
$connection->insert('key_value')
  ->fields($keyvalue_fields)
  ->values([
    'collection' => '',
    'name' => 'core.entity_view_mode.node.token',
    'value' => serialize([
      'uuid' => '8e09c5fa-e94f-440c-9650-68e32e973444',
      'langcode' => 'en',
      'status' => TRUE,
      'dependencies' => [
        'module' => [
          'node',
        ],
      ],
      'id' => 'node.token',
      'label' => 'Token',
      'targetEntityType' => 'node',
      'cache' => TRUE,
    ]),
  ])
  ->execute();

// Metatag configuration.
// @todo Load additional configurations.
$connection->insert('key_value')
  ->fields($keyvalue_fields)
  ->values([
    'collection' => 'config.entity.key_store.entity_view_mode',
    'name' => 'uuid:8e09c5fa-e94f-440c-9650-68e32e973444',
    'value' => serialize(['core.entity_view_mode.node.token']),
  ])
  ->values([
    'collection' => 'config.entity.key_store.metatag_defaults',
    'name' => 'uuid:6185b80a-8c5a-4a87-a73d-895a278ad83c',
    'value' => serialize(['metatag.metatag_defaults.global']),
  ])
  ->values([
    'collection' => 'config.entity.key_store.metatag_defaults',
    'name' => 'uuid:b6f8083d-a2b4-4555-9b65-eab2c1eb2b9f',
    'value' => serialize(['metatag.metatag_defaults.node']),
  ])
  ->values([
    'collection' => 'entity.definitions.installed',
    'name' => 'metatag_defaults.entity_type',
    // @todo Find another way of storing this definition.
    'value' => 'O:42:"Drupal\Core\Config\Entity\ConfigEntityType":43:{s:16:"' . "\0" . '*' . "\0" . 'config_prefix";s:16:"metatag_defaults";s:15:"' . "\0" . '*' . "\0" . 'static_cache";b:0;s:14:"' . "\0" . '*' . "\0" . 'lookup_keys";a:1:{i:0;s:4:"uuid";}s:16:"' . "\0" . '*' . "\0" . 'config_export";a:3:{i:0;s:2:"id";i:1;s:5:"label";i:2;s:4:"tags";}s:21:"' . "\0" . '*' . "\0" . 'mergedConfigExport";a:0:{}s:15:"' . "\0" . '*' . "\0" . 'render_cache";b:1;s:19:"' . "\0" . '*' . "\0" . 'persistent_cache";b:1;s:14:"' . "\0" . '*' . "\0" . 'entity_keys";a:8:{s:2:"id";s:2:"id";s:5:"label";s:5:"label";s:8:"revision";s:0:"";s:6:"bundle";s:0:"";s:8:"langcode";s:8:"langcode";s:16:"default_langcode";s:16:"default_langcode";s:29:"revision_translation_affected";s:29:"revision_translation_affected";s:4:"uuid";s:4:"uuid";}s:5:"' . "\0" . '*' . "\0" . 'id";s:16:"metatag_defaults";s:16:"' . "\0" . '*' . "\0" . 'originalClass";s:37:"Drupal\metatag\Entity\MetatagDefaults";s:11:"' . "\0" . '*' . "\0" . 'handlers";a:4:{s:12:"list_builder";s:41:"Drupal\metatag\MetatagDefaultsListBuilder";s:4:"form";a:4:{s:3:"add";s:39:"Drupal\metatag\Form\MetatagDefaultsForm";s:4:"edit";s:39:"Drupal\metatag\Form\MetatagDefaultsForm";s:6:"delete";s:45:"Drupal\metatag\Form\MetatagDefaultsDeleteForm";s:6:"revert";s:45:"Drupal\metatag\Form\MetatagDefaultsRevertForm";}s:6:"access";s:45:"Drupal\Core\Entity\EntityAccessControlHandler";s:7:"storage";s:45:"Drupal\Core\Config\Entity\ConfigEntityStorage";}s:19:"' . "\0" . '*' . "\0" . 'admin_permission";s:20:"administer meta tags";s:25:"' . "\0" . '*' . "\0" . 'permission_granularity";s:11:"entity_type";s:8:"' . "\0" . '*' . "\0" . 'links";a:4:{s:9:"edit-form";s:52:"/admin/config/search/metatag/{metatag_defaults}/edit";s:11:"delete-form";s:54:"/admin/config/search/metatag/{metatag_defaults}/delete";s:11:"revert-form";s:54:"/admin/config/search/metatag/{metatag_defaults}/revert";s:10:"collection";s:28:"/admin/config/search/metatag";}s:21:"' . "\0" . '*' . "\0" . 'bundle_entity_type";N;s:12:"' . "\0" . '*' . "\0" . 'bundle_of";N;s:15:"' . "\0" . '*' . "\0" . 'bundle_label";N;s:13:"' . "\0" . '*' . "\0" . 'base_table";N;s:22:"' . "\0" . '*' . "\0" . 'revision_data_table";N;s:17:"' . "\0" . '*' . "\0" . 'revision_table";N;s:13:"' . "\0" . '*' . "\0" . 'data_table";N;s:11:"' . "\0" . '*' . "\0" . 'internal";b:0;s:15:"' . "\0" . '*' . "\0" . 'translatable";b:0;s:19:"' . "\0" . '*' . "\0" . 'show_revision_ui";b:0;s:8:"' . "\0" . '*' . "\0" . 'label";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:"' . "\0" . '*' . "\0" . 'string";s:16:"Metatag defaults";s:12:"' . "\0" . '*' . "\0" . 'arguments";a:0:{}s:10:"' . "\0" . '*' . "\0" . 'options";a:0:{}}s:19:"' . "\0" . '*' . "\0" . 'label_collection";s:0:"";s:17:"' . "\0" . '*' . "\0" . 'label_singular";s:0:"";s:15:"' . "\0" . '*' . "\0" . 'label_plural";s:0:"";s:14:"' . "\0" . '*' . "\0" . 'label_count";a:0:{}s:15:"' . "\0" . '*' . "\0" . 'uri_callback";N;s:8:"' . "\0" . '*' . "\0" . 'group";s:13:"configuration";s:14:"' . "\0" . '*' . "\0" . 'group_label";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:"' . "\0" . '*' . "\0" . 'string";s:13:"Configuration";s:12:"' . "\0" . '*' . "\0" . 'arguments";a:0:{}s:10:"' . "\0" . '*' . "\0" . 'options";a:1:{s:7:"context";s:17:"Entity type group";}}s:22:"' . "\0" . '*' . "\0" . 'field_ui_base_route";N;s:26:"' . "\0" . '*' . "\0" . 'common_reference_target";b:0;s:22:"' . "\0" . '*' . "\0" . 'list_cache_contexts";a:0:{}s:18:"' . "\0" . '*' . "\0" . 'list_cache_tags";a:1:{i:0;s:28:"config:metatag_defaults_list";}s:14:"' . "\0" . '*' . "\0" . 'constraints";a:0:{}s:13:"' . "\0" . '*' . "\0" . 'additional";a:1:{s:10:"token_type";s:16:"metatag_defaults";}s:8:"' . "\0" . '*' . "\0" . 'class";s:37:"Drupal\metatag\Entity\MetatagDefaults";s:11:"' . "\0" . '*' . "\0" . 'provider";s:7:"metatag";s:14:"' . "\0" . '*' . "\0" . '_serviceIds";a:0:{}s:18:"' . "\0" . '*' . "\0" . '_entityStorages";a:0:{}s:20:"' . "\0" . '*' . "\0" . 'stringTranslation";N;}',
  ])
  ->execute();

$config = Yaml::decode(file_get_contents(__DIR__ . '/../../config/install/metatag.metatag_defaults.global.yml'));
// Need to hardcode a UUID value to avoid problems with the config system.
$config['uuid'] = (new Uuid())->generate();
$connection->insert('config')
  ->fields($config_fields)
  ->values([
    'collection' => '',
    'name' => 'metatag.metatag_defaults.global',
    'data' => serialize($config),
  ])
  ->execute();

// Node configuration.
$config = Yaml::decode(file_get_contents(__DIR__ . '/../../config/install/metatag.metatag_defaults.node.yml'));
// Need to hardcode a UUID value to avoid problems with the config system.
$config['uuid'] = (new Uuid())->generate();
$connection->insert('config')
  ->fields($config_fields)
  ->values([
    'collection' => '',
    'name' => 'metatag.metatag_defaults.node',
    'data' => serialize($config),
  ])
  ->execute();

// Create a field on the Article content type.
$connection->insert('config')
  ->fields($config_fields)
  ->values([
    'collection' => '',
    'name' => 'field.field.node.article.field_meta_tags',
    'data' => serialize([
      'uuid' => '109353f9-c0f7-4e30-a1a7-b7f8ebaa940d',
      'langcode' => 'en',
      'status' => TRUE,
      'dependencies' => [
        'config' => [
          'field.storage.node.field_meta_tags',
          'node.type.article',
        ],
        'module' => [
          'metatag',
        ],
      ],
      'id' => 'node.article.field_meta_tags',
      'field_name' => 'field_meta_tags',
      'entity_type' => 'node',
      'bundle' => 'article',
      'label' => 'Meta tags',
      'description' => '',
      'required' => FALSE,
      'translatable' => FALSE,
      'default_value' => [],
      'default_value_callback' => '',
      'settings' => [],
      'field_type' => 'metatag',
    ]),
  ])
  ->values([
    'collection' => '',
    'name' => 'field.storage.node.field_meta_tags',
    'data' => serialize([
      'uuid' => '6aaab457-3728-4319-afa3-938e753ed342',
      'langcode' => 'en',
      'status' => TRUE,
      'dependencies' => [
        'module' => [
          'metatag',
          'node',
        ],
      ],
      'id' => 'node.field_meta_tags',
      'field_name' => 'field_meta_tags',
      'entity_type' => 'node',
      'type' => 'metatag',
      'settings' => [],
      'module' => 'metatag',
      'locked' => FALSE,
      'cardinality' => 1,
      'translatable' => TRUE,
      'indexes' => [],
      'persist_with_no_fields' => FALSE,
      'custom_storage' => FALSE,
    ]),
  ])
  ->execute();

$connection->insert('key_value')
  ->fields($keyvalue_fields)
  ->values([
    'collection' => 'config.entity.key_store.field_config',
    'name' => 'uuid:109353f9-c0f7-4e30-a1a7-b7f8ebaa940d',
    'value' => serialize(['field.field.node.article.field_meta_tags']),
  ])
  ->values([
    'collection' => 'config.entity.key_store.field_storage_config',
    'name' => 'uuid:6aaab457-3728-4319-afa3-938e753ed342',
    'value' => serialize(['field.storage.node.field_meta_tags']),
  ])
  ->values([
    'collection' => 'entity.storage_schema.sql',
    'name' => 'node.field_schema_data.field_meta_tags',
    'value' => serialize([
      'node__field_meta_tags' => [
        'description' => 'Data storage for node field field_meta_tags.',
        'fields' => [
          'bundle' => [
            'type' => 'varchar_ascii',
            'length' => 128,
            'not null' => TRUE,
            'default' => '',
            'description' => 'The field instance bundle to which this row belongs, used when deleting a field instance',
          ],
          'deleted' => [
            'type' => 'int',
            'size' => 'tiny',
            'not null' => TRUE,
            'default' => 0,
            'description' => 'A boolean indicating whether this data item has been deleted',
          ],
          'entity_id' => [
            'type' => 'int',
            'unsigned' => TRUE,
            'not null' => TRUE,
            'description' => 'The entity id this data is attached to',
          ],
          'revision_id' => [
            'type' => 'int',
            'unsigned' => TRUE,
            'not null' => TRUE,
            'description' => 'The entity revision id this data is attached to',
          ],
          'langcode' => [
            'type' => 'varchar_ascii',
            'length' => 32,
            'not null' => TRUE,
            'default' => '',
            'description' => 'The language code for this data item.',
          ],
          'delta' => [
            'type' => 'int',
            'unsigned' => TRUE,
            'not null' => TRUE,
            'description' => 'The sequence number for this data item, used for multi-value fields',
          ],
          'field_meta_tags_value' => [
            'type' => 'text',
            'size' => 'big',
            'not null' => TRUE,
          ],
        ],
        'primary key' => [
          'entity_id',
          'deleted',
          'delta',
          'langcode',
        ],
        'indexes' => [
          'bundle' => [
            'bundle',
          ],
          'revision_id' => [
            'revision_id',
          ],
        ],
      ],
      'node_revision__field_meta_tags' => [
        'description' => 'Revision archive storage for node field field_meta_tags.',
        'fields' => [
          'bundle' => [
            'type' => 'varchar_ascii',
            'length' => 128,
            'not null' => TRUE,
            'default' => '',
            'description' => 'The field instance bundle to which this row belongs, used when deleting a field instance',
          ],
          'deleted' => [
            'type' => 'int',
            'size' => 'tiny',
            'not null' => TRUE,
            'default' => 0,
            'description' => 'A boolean indicating whether this data item has been deleted',
          ],
          'entity_id' => [
            'type' => 'int',
            'unsigned' => TRUE,
            'not null' => TRUE,
            'description' => 'The entity id this data is attached to',
          ],
          'revision_id' => [
            'type' => 'int',
            'unsigned' => TRUE,
            'not null' => TRUE,
            'description' => 'The entity revision id this data is attached to',
          ],
          'langcode' => [
            'type' => 'varchar_ascii',
            'length' => 32,
            'not null' => TRUE,
            'default' => '',
            'description' => 'The language code for this data item.',
          ],
          'delta' => [
            'type' => 'int',
            'unsigned' => TRUE,
            'not null' => TRUE,
            'description' => 'The sequence number for this data item, used for multi-value fields',
          ],
          'field_meta_tags_value' => [
            'type' => 'text',
            'size' => 'big',
            'not null' => 1,
          ],
        ],
        'primary key' => [
          'entity_id',
          'revision_id',
          'deleted',
          'delta',
          'langcode',
        ],
        'indexes' => [
          'bundle' => [
            'bundle',
          ],
          'revision_id' => [
            'revision_id',
          ],
        ],
      ],
    ]),
  ])
  ->execute();

$key_value = $connection->select('key_value')
  ->fields('key_value', ['value'])
  ->condition('collection', 'entity.definitions.bundle_field_map')
  ->condition('name', 'node')
  ->execute()
  ->fetchField();
$key_value = unserialize($key_value, ['allowed_classes' => FALSE]);
$key_value['field_meta_tags'] = [
  'type' => 'metatag',
  'bundles' => [
    'article' => 'article',
  ],
];
$connection->update('key_value')
  ->fields(['value' => serialize($key_value)])
  ->condition('collection', 'entity.definitions.bundle_field_map')
  ->condition('name', 'node')
  ->execute();

// This is not a good way of doing it, but there may not be many good ways of
// doing it.
// @todo Find another way of storing this definition so it doesn't require
// messing with a serialized object.
$key_value = $connection->select('key_value')
  ->fields('key_value', ['value'])
  ->condition('collection', 'entity.definitions.installed')
  ->condition('name', 'node.field_storage_definitions')
  ->execute()
  ->fetchField();
$key_value = unserialize($key_value, [
  'allowed_classes' => $allowed_classes,
]);
$key_value['field_meta_tags'] = @unserialize('O:38:"Drupal\field\Entity\FieldStorageConfig":35:{s:5:"' . "\0" . '*' . "\0" . 'id";s:20:"node.field_meta_tags";s:13:"' . "\0" . '*' . "\0" . 'field_name";s:15:"field_meta_tags";s:14:"' . "\0" . '*' . "\0" . 'entity_type";s:4:"node";s:7:"' . "\0" . '*' . "\0" . 'type";s:7:"metatag";s:9:"' . "\0" . '*' . "\0" . 'module";s:7:"metatag";s:11:"' . "\0" . '*' . "\0" . 'settings";a:0:{}s:14:"' . "\0" . '*' . "\0" . 'cardinality";i:1;s:15:"' . "\0" . '*' . "\0" . 'translatable";b:1;s:9:"' . "\0" . '*' . "\0" . 'locked";b:0;s:25:"' . "\0" . '*' . "\0" . 'persist_with_no_fields";b:0;s:14:"custom_storage";b:0;s:10:"' . "\0" . '*' . "\0" . 'indexes";a:0:{}s:10:"' . "\0" . '*' . "\0" . 'deleted";b:0;s:13:"' . "\0" . '*' . "\0" . 'originalId";s:20:"node.field_meta_tags";s:9:"' . "\0" . '*' . "\0" . 'status";b:1;s:7:"' . "\0" . '*' . "\0" . 'uuid";s:36:"6aaab457-3728-4319-afa3-938e753ed342";s:11:"' . "\0" . '*' . "\0" . 'langcode";s:2:"en";s:23:"' . "\0" . '*' . "\0" . 'third_party_settings";a:0:{}s:8:"' . "\0" . '*' . "\0" . '_core";a:0:{}s:14:"' . "\0" . '*' . "\0" . 'trustedData";b:0;s:15:"' . "\0" . '*' . "\0" . 'entityTypeId";s:20:"field_storage_config";s:15:"' . "\0" . '*' . "\0" . 'enforceIsNew";N;s:12:"' . "\0" . '*' . "\0" . 'typedData";N;s:16:"' . "\0" . '*' . "\0" . 'cacheContexts";a:0:{}s:12:"' . "\0" . '*' . "\0" . 'cacheTags";a:0:{}s:14:"' . "\0" . '*' . "\0" . 'cacheMaxAge";i:-1;s:14:"' . "\0" . '*' . "\0" . '_serviceIds";a:0:{}s:18:"' . "\0" . '*' . "\0" . '_entityStorages";a:0:{}s:15:"' . "\0" . '*' . "\0" . 'dependencies";a:1:{s:6:"module";a:2:{i:0;s:7:"metatag";i:1;s:4:"node";}}s:12:"' . "\0" . '*' . "\0" . 'isSyncing";b:0;s:18:"cardinality_number";i:1;s:6:"submit";O:48:"Drupal\Core\StringTranslation\TranslatableMarkup":3:{s:9:"' . "\0" . '*' . "\0" . 'string";s:19:"Save field settings";s:12:"' . "\0" . '*' . "\0" . 'arguments";a:0:{}s:10:"' . "\0" . '*' . "\0" . 'options";a:0:{}}s:13:"form_build_id";s:48:"form-LK9HeARuUzcwIVvCAA4jG2MscwGjLAUJ9GLYxuzSo7o";s:10:"form_token";s:43:"eengi9MkLSqT-YFMEKD18fJ6cOvVyS_XRq1He7qhq4s";s:7:"form_id";s:30:"field_storage_config_edit_form";}}', [
  'allowed_classes' => $allowed_classes,
]);
$connection->update('key_value')
  ->fields(['value' => serialize($key_value)])
  ->condition('collection', 'entity.definitions.installed')
  ->condition('name', 'node.field_storage_definitions')
  ->execute();

$connection->schema()->createTable('node__field_meta_tags', [
  'fields' => [
    'bundle' => [
      'type' => 'varchar_ascii',
      'not null' => TRUE,
      'length' => '128',
      'default' => '',
    ],
    'deleted' => [
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'tiny',
      'default' => '0',
    ],
    'entity_id' => [
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'normal',
      'unsigned' => TRUE,
    ],
    'revision_id' => [
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'normal',
      'unsigned' => TRUE,
    ],
    'langcode' => [
      'type' => 'varchar_ascii',
      'not null' => TRUE,
      'length' => '32',
      'default' => '',
    ],
    'delta' => [
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'normal',
      'unsigned' => TRUE,
    ],
    'field_meta_tags_value' => [
      'type' => 'text',
      'not null' => TRUE,
      'size' => 'big',
    ],
  ],
  'primary key' => [
    'entity_id',
    'deleted',
    'delta',
    'langcode',
  ],
  'indexes' => [
    'bundle' => [
      'bundle',
    ],
    'revision_id' => [
      'revision_id',
    ],
  ],
  'mysql_character_set' => 'utf8mb4',
]);
$connection->schema()->createTable('node_revision__field_meta_tags', [
  'fields' => [
    'bundle' => [
      'type' => 'varchar_ascii',
      'not null' => TRUE,
      'length' => '128',
      'default' => '',
    ],
    'deleted' => [
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'tiny',
      'default' => '0',
    ],
    'entity_id' => [
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'normal',
      'unsigned' => TRUE,
    ],
    'revision_id' => [
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'normal',
      'unsigned' => TRUE,
    ],
    'langcode' => [
      'type' => 'varchar_ascii',
      'not null' => TRUE,
      'length' => '32',
      'default' => '',
    ],
    'delta' => [
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'normal',
      'unsigned' => TRUE,
    ],
    'field_meta_tags_value' => [
      'type' => 'text',
      'not null' => TRUE,
      'size' => 'big',
    ],
  ],
  'primary key' => [
    'entity_id',
    'revision_id',
    'deleted',
    'delta',
    'langcode',
  ],
  'indexes' => [
    'bundle' => [
      'bundle',
    ],
    'revision_id' => [
      'revision_id',
    ],
  ],
  'mysql_character_set' => 'utf8mb4',
]);

// Create a node with values.
// @todo Create a few more.
$connection->insert('comment_entity_statistics')
  ->fields([
    'entity_id',
    'entity_type',
    'field_name',
    'cid',
    'last_comment_timestamp',
    'last_comment_name',
    'last_comment_uid',
    'comment_count',
  ])
  ->values([
    'entity_id' => '1',
    'entity_type' => 'node',
    'field_name' => 'comment',
    'cid' => '0',
    'last_comment_timestamp' => '1669762329',
    'last_comment_name' => NULL,
    'last_comment_uid' => '1',
    'comment_count' => '0',
  ])
  ->execute();
$connection->insert('node')
  ->fields([
    'nid',
    'vid',
    'type',
    'uuid',
    'langcode',
  ])
  ->values([
    'nid' => '1',
    'vid' => '1',
    'type' => 'article',
    'uuid' => 'fc2c9449-df04-4d41-beea-5a5b39bf6b89',
    'langcode' => 'en',
  ])
  ->execute();
$connection->insert('node__comment')
  ->fields([
    'bundle',
    'deleted',
    'entity_id',
    'revision_id',
    'langcode',
    'delta',
    'comment_status',
  ])
  ->values([
    'bundle' => 'article',
    'deleted' => '0',
    'entity_id' => '1',
    'revision_id' => '1',
    'langcode' => 'en',
    'delta' => '0',
    'comment_status' => '2',
  ])
  ->execute();
$connection->insert('node__field_meta_tags')
  ->fields([
    'bundle',
    'deleted',
    'entity_id',
    'revision_id',
    'langcode',
    'delta',
    'field_meta_tags_value',
  ])
  ->values([
    'bundle' => 'article',
    'deleted' => '0',
    'entity_id' => '1',
    'revision_id' => '1',
    'langcode' => 'en',
    'delta' => '0',
  /**
   * Expand this list as new meta tags need to be tested.
   */
    'field_meta_tags_value' => serialize([
      'description' => 'This is a Metatag v1 meta tag.',
      'title' => 'Testing | [site:name]',
      'robots' => 'index, nofollow, noarchive',

    // For #3065441.
      'google_plus_author' => 'GooglePlus Author tag test value for #3065441.',
      'google_plus_description' => 'GooglePlus Description tag test value for #3065441.',
      'google_plus_name' => 'GooglePlus Name tag test value for #3065441.',
      'google_plus_publisher' => 'GooglePlus Publisher tag test value for #3065441.',

    // For #2973351.
      'news_keywords' => 'News Keywords tag test value for #2973351.',
      'standout' => 'Standout tag test value for #2973351.',

    // For #3132065.
      'twitter_cards_data1' => 'Data1 tag test for #3132065.',
      'twitter_cards_data2' => 'Data2 tag test for #3132065.',
      'twitter_cards_dnt' => 'Do Not Track tag test for #3132065.',
      'twitter_cards_gallery_image0' => 'Gallery Image0 tag test for #3132065.',
      'twitter_cards_gallery_image1' => 'Gallery Image1 tag test for #3132065.',
      'twitter_cards_gallery_image2' => 'Gallery Image2 tag test for #3132065.',
      'twitter_cards_gallery_image3' => 'Gallery Image3 tag test for #3132065.',
      'twitter_cards_image_height' => 'Image Height tag test for #3132065.',
      'twitter_cards_image_width' => 'Image Width tag test for #3132065.',
      'twitter_cards_label1' => 'Label1 tag test for #3132065.',
      'twitter_cards_label2' => 'Label2 tag test for #3132065.',
      'twitter_cards_page_url' => 'Page URL tag test for #3132065.',

    // For #3217263.
      'content_language' => 'Content Language tag test for #3217263.',

    // For #3132062.
      'twitter_cards_type' => 'gallery',

    // For #3361816.
      'google_rating' => 'Google Rating tag test for #3361816',
    ]),
  ])
  ->execute();
$connection->insert('node_field_data')
  ->fields([
    'nid',
    'vid',
    'type',
    'title',
    'created',
    'changed',
    'promote',
    'sticky',
    'revision_translation_affected',
    'default_langcode',
    'langcode',
    'status',
    'uid',
  ])
  ->values([
    'nid' => '1',
    'vid' => '1',
    'type' => 'article',
    'title' => 'Testing',
    'created' => '1669762311',
    'changed' => '1669762329',
    'promote' => '1',
    'sticky' => '0',
    'revision_translation_affected' => '1',
    'default_langcode' => '1',
    'langcode' => 'en',
    'status' => '1',
    'uid' => '1',
  ])
  ->execute();
$connection->insert('node_field_revision')
  ->fields([
    'nid',
    'vid',
    'title',
    'created',
    'changed',
    'promote',
    'sticky',
    'revision_translation_affected',
    'default_langcode',
    'langcode',
    'status',
    'uid',
  ])
  ->values([
    'nid' => '1',
    'vid' => '1',
    'title' => 'Testing',
    'created' => '1669762311',
    'changed' => '1669762329',
    'promote' => '1',
    'sticky' => '0',
    'revision_translation_affected' => '1',
    'default_langcode' => '1',
    'langcode' => 'en',
    'status' => '1',
    'uid' => '1',
  ])
  ->execute();
$connection->insert('node_revision__comment')
  ->fields([
    'bundle',
    'deleted',
    'entity_id',
    'revision_id',
    'langcode',
    'delta',
    'comment_status',
  ])
  ->values([
    'bundle' => 'article',
    'deleted' => '0',
    'entity_id' => '1',
    'revision_id' => '1',
    'langcode' => 'en',
    'delta' => '0',
    'comment_status' => '2',
  ])
  ->execute();
$connection->insert('node_revision__field_meta_tags')
  ->fields([
    'bundle',
    'deleted',
    'entity_id',
    'revision_id',
    'langcode',
    'delta',
    'field_meta_tags_value',
  ])
  ->values([
    'bundle' => 'article',
    'deleted' => '0',
    'entity_id' => '1',
    'revision_id' => '1',
    'langcode' => 'en',
    'delta' => '0',
  /**
   * Expand this list as new meta tags need to be tested.
   */
    'field_meta_tags_value' => serialize([
      'description' => 'This is a Metatag v1 meta tag.',
      'title' => 'Testing | [site:name]',
      'robots' => 'index, nofollow, noarchive',

    // For #3065441.
      'google_plus_author' => 'GooglePlus Author tag test value for #3065441.',
      'google_plus_description' => 'GooglePlus Description tag test value for #3065441.',
      'google_plus_name' => 'GooglePlus Name tag test value for #3065441.',
      'google_plus_publisher' => 'GooglePlus Publisher tag test value for #3065441.',

    // For #2973351.
      'news_keywords' => 'News Keywords tag test value for #2973351.',
      'standout' => 'Standout tag test value for #2973351.',

    // For #3132065.
      'twitter_cards_data1' => 'Data1 tag test for #3132065.',
      'twitter_cards_data2' => 'Data2 tag test for #3132065.',
      'twitter_cards_dnt' => 'Do Not Track tag test for #3132065.',
      'twitter_cards_gallery_image0' => 'Gallery Image0 tag test for #3132065.',
      'twitter_cards_gallery_image1' => 'Gallery Image1 tag test for #3132065.',
      'twitter_cards_gallery_image2' => 'Gallery Image2 tag test for #3132065.',
      'twitter_cards_gallery_image3' => 'Gallery Image3 tag test for #3132065.',
      'twitter_cards_image_height' => 'Image Height tag test for #3132065.',
      'twitter_cards_image_width' => 'Image Width tag test for #3132065.',
      'twitter_cards_label1' => 'Label1 tag test for #3132065.',
      'twitter_cards_label2' => 'Label2 tag test for #3132065.',
      'twitter_cards_page_url' => 'Page URL tag test for #3132065.',

    // For #3217263.
      'content_language' => 'Content Language tag test for #3217263.',

    // For #3132062.
      'twitter_cards_type' => 'gallery',

    // For #3361816.
      'google_rating' => 'Google Rating tag test for #3361816',
    ]),
  ])
  ->execute();
