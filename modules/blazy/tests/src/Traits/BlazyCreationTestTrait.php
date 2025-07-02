<?php

namespace Drupal\Tests\blazy\Traits;

use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\File\FileSystemInterface;
use Drupal\blazy\Blazy;
use Drupal\blazy\internals\Internals;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\file\Entity\File;
use Drupal\file\FileInterface;
use Drupal\filter\Entity\FilterFormat;
use Drupal\image\Plugin\Field\FieldType\ImageItem;
use Drupal\node\Entity\NodeType;

/**
 * A Trait common for Blazy tests.
 *
 * @todo Consider using ContentTypeCreationTrait, TestFileCreationTrait.
 */
trait BlazyCreationTestTrait {

  /**
   * Testing node type.
   *
   * @var \Drupal\node\Entity\NodeType
   */
  protected $nodeType;

  /**
   * Setup formatter displays, default to image, and update its settings.
   *
   * @param string $bundle
   *   The bundle name.
   * @param array $data
   *   May contain formatter settings to be added to defaults.
   *
   * @return \Drupal\Core\Entity\Entity\EntityViewDisplay
   *   The formatter display instance.
   */
  protected function setUpFormatterDisplay($bundle = '', array $data = []) {
    $settings   = $data['settings'] ?? [];
    $view_mode  = empty($data['view_mode']) ? 'default' : $data['view_mode'];
    $plugin_id  = empty($data['plugin_id']) ? $this->testPluginId : $data['plugin_id'];
    $field_name = empty($data['field_name']) ? $this->testFieldName : $data['field_name'];
    $display_id = $this->entityType . '.' . $bundle . '.' . $view_mode;
    $storage    = $this->blazyManager->getStorage('entity_view_display');
    $display    = $storage->load($display_id);

    if (!$display) {
      $values = [
        'targetEntityType' => $this->entityType,
        'bundle'           => $bundle,
        'mode'             => $view_mode,
        'status'           => TRUE,
      ];

      $display = $storage->create($values);
    }

    $settings['view_mode'] = $view_mode;
    $display->setComponent($field_name, [
      'type'     => $plugin_id,
      'settings' => $settings,
      'label'    => 'hidden',
    ]);

    $display->save();

    return $display;
  }

  /**
   * Gets the field definition.
   *
   * @param string $field_name
   *   Formatted field name.
   *
   * @return \Drupal\Core\Field\FieldDefinitionInterface
   *   The field definition.
   *
   * @see BaseFieldDefinition::createFromFieldStorageDefinition()
   */
  protected function getBlazyFieldDefinition($field_name = '') {
    $field_name = empty($field_name) ? $this->testFieldName : $field_name;
    $field_storage_config = $this->getBlazyFieldStorageDefinition($field_name);
    return $field_storage_config ? BaseFieldDefinition::createFromFieldStorageDefinition($field_storage_config) : FALSE;
  }

  /**
   * Gets the field storage configuration.
   *
   * @param string $field_name
   *   Formatted field name.
   *
   * @return \Drupal\field\FieldStorageConfigInterface
   *   The field storage definition.
   */
  protected function getBlazyFieldStorageDefinition($field_name = '') {
    $field_name = empty($field_name) ? $this->testFieldName : $field_name;
    $field_storage_definitions = $this->entityFieldManager->getFieldStorageDefinitions($this->entityType);
    return $field_storage_definitions[$field_name] ?? FALSE;
  }

  /**
   * Returns the field formatter instance.
   *
   * @param string $plugin_id
   *   Formatter plugin ID.
   * @param string $field_name
   *   Formatted field name.
   *
   * @return \Drupal\Core\Field\FormatterInterface|null
   *   The field formatter instance.
   */
  protected function getFormatterInstance($plugin_id = '', $field_name = '') {
    $plugin_id  = empty($plugin_id) ? $this->testPluginId : $plugin_id;
    $field_name = empty($field_name) ? $this->testFieldName : $field_name;
    $settings   = $this->getFormatterSettings() + $this->formatterPluginManager->getDefaultSettings($plugin_id);

    if (!$this->getBlazyFieldDefinition($field_name)) {
      return NULL;
    }

    $options = [
      'field_definition' => $this->getBlazyFieldDefinition($field_name),
      'configuration' => [
        'type' => $plugin_id,
        'settings' => $settings,
      ],
      'view_mode' => 'default',
    ];

    return $this->formatterPluginManager->getInstance($options);
  }

  /**
   * Build dummy content types.
   *
   * @param string $bundle
   *   The bundle name.
   * @param array $settings
   *   (Optional) configurable settings.
   */
  protected function setUpContentTypeTest($bundle = '', array $settings = []) {
    $node_type = NodeType::load($bundle);
    $full_html = $this->blazyManager->load('full_html', 'filter_format');
    $restricted_html = $this->blazyManager->load('restricted_html', 'filter_format');

    if (empty($node_type)) {
      $node_type = NodeType::create([
        'type' => $bundle,
        'name' => $bundle,
      ]);
      $node_type->save();
    }

    if (!$restricted_html && is_null($this->filterFormatRestricted)) {
      $this->filterFormatRestricted = FilterFormat::create([
        'format'  => 'restricted_html',
        'name'    => 'Basic HML',
        'weight'  => 2,
        'filters' => [],
      ])->save();
    }
    else {
      $this->filterFormatRestricted = $restricted_html;
    }

    if (!$full_html && is_null($this->filterFormatFull)) {
      $this->filterFormatFull = FilterFormat::create([
        'format'  => 'full_html',
        'name'    => 'Full HML',
        'weight'  => 3,
      ])->save();
    }
    else {
      $this->filterFormatFull = $full_html;
    }

    node_add_body_field($node_type);

    if (!empty($this->testFieldName)) {
      $settings['fields'][$this->testFieldName] = empty($this->testFieldType) ? 'image' : $this->testFieldType;
    }
    if (!empty($settings['field_name']) && !empty($settings['field_type'])) {
      $settings['fields'][$settings['field_name']] = $settings['field_type'];
    }

    $data = [];
    if (!empty($settings['fields'])) {
      foreach ($settings['fields'] as $field_name => $field_type) {
        $data['field_name'] = $field_name;
        $data['field_type'] = $field_type;
        $this->setUpFieldConfig($bundle, $data);
      }
    }

    $node_type->save();

    return $node_type;
  }

  /**
   * Build dummy nodes with optional fields.
   *
   * @param string $bundle
   *   The bundle name.
   * @param array $settings
   *   (Optional) configurable settings.
   *
   * @return \Drupal\node\Entity\Node|null
   *   The node instance.
   */
  protected function setUpContentWithItems($bundle = '', array $settings = []) {
    $title  = empty($settings['title']) ? $this->testPluginId : $settings['title'];
    $data   = empty($settings['values']) ? [] : $settings['values'];
    $values = $data + [
      'title'  => $title . ' : ' . $this->randomMachineName(),
      'type'   => $bundle,
      'status' => TRUE,
    ];

    $node = $this->blazyManager->entityTypeManager()
      ->getStorage($this->entityType)
      ->create($values);

    $node->save();

    if (isset($node->body)) {
      $text = $this->getRandomGenerator()->paragraphs($this->maxParagraphs);
      if (!empty($settings['extra_text'])) {
        $text .= $settings['extra_text'];
      }

      /* @phpstan-ignore-next-line */
      if ($body = $node->get('body')) {
        $body->setValue(['value' => $text, 'format' => 'full_html']);
      }
    }

    if (!empty($this->testFieldName)) {
      $settings['fields'][$this->testFieldName] = empty($this->testFieldType) ? 'image' : $this->testFieldType;
    }
    if (!empty($settings['field_name']) && !empty($settings['field_type'])) {
      $settings['fields'][$settings['field_name']] = $settings['field_type'];
    }

    if (!empty($settings['fields'])) {
      foreach ($settings['fields'] as $field_name => $field_type) {
        $multiple = $field_type == 'image' || strpos($field_name, 'mul') !== FALSE;

        if (strpos($field_name, 'empty') !== FALSE) {
          continue;
        }

        if (isset($this->entityFieldName) && ($field_name == $this->entityFieldName)) {
          continue;
        }

        $max = $multiple ? $this->maxItems : 2;
        if (isset($node->{$field_name})) {
          // @see \Drupal\Core\Field\FieldItemListInterface::generateSampleItems
          /* @phpstan-ignore-next-line */
          if ($field = $node->get($field_name)) {
            $field->generateSampleItems($max);
          }
        }
      }
    }

    $node->save();
    $this->testItems = $node->{$this->testFieldName};
    $this->entity = $node;

    return $node;
  }

  /**
   * Setup a new image field.
   *
   * @param string $bundle
   *   The bundle name.
   * @param array $data
   *   (Optional) A list of field data.
   */
  protected function setUpFieldConfig($bundle = '', array $data = []) {
    $config     = [];
    $default    = empty($this->testFieldType) ? 'image' : $this->testFieldType;
    $field_type = empty($data['field_type']) ? $default : $data['field_type'];
    $field_name = empty($data['field_name']) ? $this->testFieldName : $data['field_name'];
    $multiple   = strpos($field_name, 'mul') !== FALSE;

    if (in_array($field_type, ['file', 'image'])) {
      $config['file_directory'] = $this->testPluginId;
      $config['file_extensions'] = 'png gif jpg jpeg';

      if ($field_type == 'file') {
        $config['file_extensions'] .= ' txt';
      }

      if ($field_type == 'image') {
        $config['title_field'] = 1;
        $config['title_field_required'] = 1;
      }

      $multiple = TRUE;
    }

    $field_storage = FieldStorageConfig::loadByName($this->entityType, $field_name);

    $storage_settings = [];
    if ($field_type == 'entity_reference') {
      $storage_settings['target_type'] = $this->targetType ?? $this->entityType;
      $bundle = $this->bundle;
      $multiple = FALSE;
    }

    if ($field_name == 'field_image') {
      $multiple = FALSE;
    }

    if (!$field_storage) {
      FieldStorageConfig::create([
        'entity_type' => $this->entityType,
        'field_name'  => $field_name,
        'type'        => $field_type,
        'cardinality' => $multiple ? -1 : 1,
        'settings'    => $storage_settings,
      ])->save();
    }

    $field_config = FieldConfig::loadByName($this->entityType, $bundle, $field_name);

    if ($field_type == 'entity_reference' && !empty($this->targetBundles)) {
      $config['handler'] = 'default';
      $config['handler_settings']['target_bundles'] = $this->targetBundles;
      $config['handler_settings']['sort']['field'] = '_none';
      $bundle = $this->bundle;
    }

    if (!$field_config) {
      $field_config = FieldConfig::create([
        'field_storage' => $field_storage,
        'field_name'    => $field_name,
        'entity_type'   => $this->entityType,
        'bundle'        => $bundle,
        'label'         => str_replace('_', ' ', $field_name),
        'settings'      => $config,
      ]);

      $field_config->save();
    }

    return $field_config;
  }

  /**
   * Sets field values as built by FieldItemListInterface::view().
   *
   * @param \Drupal\Core\Entity\EntityInterface[] $referenced_entities
   *   An array of entity objects that will be referenced.
   * @param string $type
   *   The formatter plugin ID.
   * @param array $settings
   *   Settings specific to the formatter. Defaults to the formatter's defaults.
   *
   * @return array
   *   A render array.
   */
  protected function buildEntityReferenceRenderArray(array $referenced_entities, $type = '', array $settings = []) {
    $type = empty($type) ? $this->entityPluginId : $type;
    /* @phpstan-ignore-next-line */
    $items = $this->referencingEntity->get($this->entityFieldName);

    // Assign the referenced entities.
    foreach ($referenced_entities as $referenced_entity) {
      $items[] = ['entity' => $referenced_entity];
    }

    // Build the renderable array for the field.
    $data['type'] = $type;
    if ($settings) {
      $data['settings'] = $settings;
    }
    return $items->view($data);
  }

  /**
   * Sets field values as built by FieldItemListInterface::view().
   *
   * @param \Drupal\Core\Entity\EntityInterface[] $entity
   *   An entity object that will be displayed.
   * @param array $settings
   *   Settings specific to the formatter. Defaults to the formatter's defaults.
   *
   * @return array
   *   A render array.
   */
  protected function collectRenderDisplay(array $entity, array $settings = []) {
    $view_mode = empty($settings['view_mode']) ? 'default' : $settings['view_mode'];

    $display = EntityViewDisplay::collectRenderDisplay($entity, $view_mode);

    return $display->build($entity);
  }

  /**
   * Build dummy contents with entity references.
   *
   * @param array $settings
   *   (Optional) configurable settings.
   */
  protected function setUpContentWithEntityReference(array $settings = []) {
    $target_bundle   = $this->targetBundle;
    $bundle          = $this->bundle;
    $fields          = empty($settings['fields']) ? [] : $settings['fields'];
    $image_settings  = empty($settings['image_settings']) ? [] : $settings['image_settings'];
    $entity_settings = empty($settings['entity_settings']) ? [] : $settings['entity_settings'];
    $er_field_name   = empty($settings['entity_field_name']) ? $this->entityFieldName : $settings['entity_field_name'];
    $er_plugin_id    = empty($settings['entity_plugin_id']) ? $this->entityPluginId : $settings['entity_plugin_id'];

    // Create referenced entity.
    $referenced_data['title'] = 'Referenced ' . $this->testPluginId;

    // Create dummy fields.
    $referenced_data['fields'] = array_merge($this->getDefaultFields(), $fields);

    // Create referenced entity type.
    $this->setUpContentTypeTest($target_bundle, $referenced_data);

    // Create referencing entity type.
    $referencing_data['fields'] = [
      $er_field_name => 'entity_reference',
    ];
    $this->setUpContentTypeTest($bundle, $referencing_data);

    // 1. Build the referenced entities.
    $referenced_formatter_link = [
      'field_name' => 'field_link',
      'plugin_id'  => 'link',
      'settings'   => [],
    ];
    $this->setUpFormatterDisplay($target_bundle, $referenced_formatter_link);

    $referenced_formatter_data = [
      'field_name' => $this->testFieldName,
      'plugin_id'  => $this->testPluginId,
      'settings'   => $image_settings + $this->getFormatterSettings(),
    ];
    $this->referencedDisplay = $this->setUpFormatterDisplay($target_bundle, $referenced_formatter_data);

    // Create referenced entities.
    $this->referencedEntity = $this->setUpContentWithItems($target_bundle, $referenced_data);

    // 2. Build the referencing entity.
    $referencing_formatter_settings = $this->getDefaultFields(TRUE);
    $referencing_formatter_data = [
      'field_name' => $er_field_name,
      'plugin_id'  => $er_plugin_id,
      'settings'   => empty($entity_settings) ? $referencing_formatter_settings : array_merge($referencing_formatter_settings, $entity_settings),
    ];
    $this->referencingDisplay = $this->setUpFormatterDisplay($bundle, $referencing_formatter_data);
  }

  /**
   * Create referencing entity.
   */
  protected function createReferencingEntity(array $data = []) {
    if (empty($data['values']) && $this->referencedEntity->id()) {
      $data['values'] = [
        $this->entityFieldName => [
          ['target_id' => $this->referencedEntity->id()],
        ],
      ];
    }

    return $this->setUpContentWithItems($this->bundle, $data);
  }

  /**
   * Set up dummy image.
   */
  protected function setUpRealImage() {
    /* @phpstan-ignore-next-line */
    $this->uri = $this->getImagePath();
    $item = $this->dummyItem;

    if (isset($this->testItems[0])) {
      $item = $this->testItems[0];

      if ($item instanceof ImageItem) {
        /* @phpstan-ignore-next-line */
        $this->uri = ($entity = $item->entity) && empty($item->uri) ? $entity->getFileUri() : $item->uri;
        $this->url = Blazy::transformRelative($this->uri);
      }
    }

    if (empty($this->url)) {
      $source = $this->root . '/core/misc/druplicon.png';
      $uri = 'public://test.png';
      $replace = Internals::fileExistsReplace();
      $this->fileSystem->copy($source, $uri, $replace);
      $this->url = Blazy::createUrl($uri);
    }

    $this->testItem = $this->image = $item;

    $this->data = [
      '#settings' => $this->getFormatterSettings(),
      '#item'     => $item,
    ];
  }

  /**
   * Returns path to the stored image location.
   */
  protected function getImagePath($is_dir = FALSE) {
    $path            = $this->root . '/sites/default/files/simpletest/' . $this->testPluginId;
    $item            = $this->createDummyImage();
    $this->dummyUrl  = Blazy::transformRelative($this->dummyUri);
    $this->dummyItem = $item;
    $this->dummyData = [
      '#settings' => $this->getFormatterSettings(),
      '#item' => $item,
    ];

    return $is_dir ? $path : $this->dummyUri;
  }

  /**
   * Returns the created image file.
   */
  protected function createDummyImage($name = '', $source = '') {
    $path   = $this->root . '/sites/default/files/simpletest/' . $this->testPluginId;
    $name   = empty($name) ? $this->testPluginId . '.png' : $name;
    $source = empty($source) ? $this->root . '/core/misc/druplicon.png' : $source;
    $uri    = $path . '/' . $name;

    if (!is_file($uri)) {
      $this->prepareTestDirectory();
      $replace = Internals::fileExistsReplace();
      $this->fileSystem->saveData($source, $uri, $replace);
    }

    $uri = 'public://simpletest/' . $this->testPluginId . '/' . $name;
    $this->dummyUri = $uri;
    $item = File::create([
      'uri' => $uri,
      'uid' => 1,
      'status' => FileInterface::STATUS_PERMANENT,
      'filename' => $name,
    ]);

    $item->save();

    return $item;
  }

  /**
   * Prepares test directory to store screenshots, or images.
   */
  protected function prepareTestDirectory() {
    $this->testDirPath = $this->root . '/sites/default/files/simpletest/' . $this->testPluginId;
    $this->fileSystem->prepareDirectory($this->testDirPath, FileSystemInterface::CREATE_DIRECTORY);
  }

}
