<?php

namespace Drupal\Tests\blazy\Traits;

/**
 * A Trait common for Blazy tests.
 */
trait BlazyPropertiesTestTrait {

  /**
   * The entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $entityStorage;

  /**
   * The entity view builder.
   *
   * @var \Drupal\Core\Entity\EntityViewBuilderInterface
   */
  protected $entityViewBuilder;

  /**
   * The entity mockup.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface
   */
  protected $entityTypeMock;

  /**
   * The entity mockup.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The cache.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandler
   */
  protected $moduleHandler;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManager
   */
  protected $languageManager;

  /**
   * The token.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The blazy admin service.
   *
   * @var \Drupal\blazy\Form\BlazyAdminInterface
   */
  protected $blazyAdmin;

  /**
   * The blazy admin service.
   *
   * @var \Drupal\blazy\Form\BlazyAdminFormatter
   */
  protected $blazyAdminFormatter;

  /**
   * The blazy admin service.
   *
   * @var \Drupal\blazy\Form\BlazyAdminInterface
   *
   * @todo remove for $blazyAdminFormatter post blazy:2.17 after sub-modules.
   */
  protected $blazyAdminExtended;

  /**
   * The blazy formatter service.
   *
   * @var \Drupal\blazy\BlazyFormatterInterface
   */
  protected $blazyFormatter;

  /**
   * The blazy manager service.
   *
   * @var \Drupal\blazy\BlazyManagerInterface
   */
  protected $blazyManager;

  /**
   * The blazy entity service.
   *
   * @var \Drupal\blazy\BlazyEntity
   */
  protected $blazyEntity;

  /**
   * The blazy media service.
   *
   * @var \Drupal\blazy\Media\BlazyMedia
   */
  protected $blazyMedia;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The field type manager.
   *
   * @var \Drupal\Core\Field\FieldTypePluginManager
   */
  protected $fieldTypePluginManager;

  /**
   * The entity display.
   *
   * @var \Drupal\Core\Entity\Display\EntityViewDisplayInterface
   */
  protected $display;

  /**
   * The node entity.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $node;

  /**
   * The entity.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $entity;

  /**
   * The entity.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $entities;

  /**
   * The libraries service.
   *
   * @var \Drupal\blazy\Asset\LibrariesInterface
   */
  protected $libraries;

  /**
   * The node entity.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $referencingEntity;

  /**
   * The referenced node entity.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $referencedEntity;

  /**
   * The referenced formatter display.
   *
   * @var object
   */
  protected $referencedDisplay;

  /**
   * The referencing formatter display.
   *
   * @var object
   */
  protected $referencingDisplay;

  /**
   * The blazy oembed service.
   *
   * @var \Drupal\blazy\Media\BlazyOEmbedInterface
   */
  protected $blazyOembed;

  /**
   * The bundle name.
   *
   * @var string
   */
  protected $bundle;

  /**
   * The target bundle name.
   *
   * @var string
   */
  protected $targetBundle;

  /**
   * The target bundle names.
   *
   * @var array
   */
  protected $targetBundles;

  /**
   * The tested entity field name.
   *
   * @var string
   */
  protected $entityFieldName;

  /**
   * The tested entity type.
   *
   * @var string
   */
  protected $entityType;

  /**
   * The created item.
   *
   * @var \Drupal\image\Plugin\Field\FieldType\ImageItem
   */
  protected $testItem;

  /**
   * The created image item.
   *
   * @var \Drupal\image\Plugin\Field\FieldType\ImageItem
   */
  protected $image;

  /**
   * The created items.
   *
   * @var array
   */
  protected $testItems = [];

  /**
   * The formatter definition.
   *
   * @var array
   */
  protected $formatterDefinition = [];

  /**
   * The formatter plugin manager.
   *
   * @var \Drupal\Core\Field\FormatterPluginManager
   */
  protected $formatterPluginManager;

  /**
   * The tested type definitions.
   *
   * @var array
   */
  protected $typeDefinition = [];

  /**
   * The tested field name.
   *
   * @var string
   */
  protected $testFieldName;

  /**
   * The tested field type.
   *
   * @var string
   */
  protected $testFieldType;

  /**
   * The tested empty field name.
   *
   * @var string
   */
  protected $testEmptyName;

  /**
   * The tested empty field type.
   *
   * @var string
   */
  protected $testEmptyType;

  /**
   * The tested formatter ID.
   *
   * @var string
   */
  protected $testPluginId;

  /**
   * The tested entity reference formatter ID.
   *
   * @var string
   */
  protected $entityPluginId;

  /**
   * The maximum number of created paragraphs.
   *
   * @var int
   */
  protected $maxParagraphs = 1;

  /**
   * The maximum number of created images.
   *
   * @var int
   */
  protected $maxItems = 1;

  /**
   * The tested skins.
   *
   * @var array
   */
  protected $skins = [];

  /**
   * The filter format.
   *
   * @var \Drupal\filter\Entity\FilterFormat
   */
  protected $filterFormatFull = NULL;

  /**
   * The filter format.
   *
   * @var \Drupal\filter\Entity\FilterFormat
   */
  protected $filterFormatRestricted = NULL;

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystem
   */
  protected $fileSystem;

  /**
   * The formatter instance.
   *
   * @var \Drupal\blazy\Plugin\Field\FieldFormatter\BlazyImageFormatter
   */
  protected $formatterInstance;

  /**
   * Test directory path.
   *
   * @var string
   */
  protected $testDirPath;

  /**
   * Test node type.
   *
   * @var string
   */
  protected $testNodeType;

  /**
   * Test dummy data.
   *
   * @var array
   */
  protected $dummyData;

  /**
   * Test dummy image item.
   *
   * @var object
   */
  protected $dummyItem;

  /**
   * Test dummy URI.
   *
   * @var string
   */
  protected $dummyUri;

  /**
   * Test dummy url.
   *
   * @var string
   */
  protected $dummyUrl;

  /**
   * Test script loader.
   *
   * @var string
   */
  protected $scriptLoader;

  /**
   * Test data.
   *
   * @var array
   */
  protected $data;

  /**
   * Test dummy URI.
   *
   * @var string
   */
  protected $uri;

  /**
   * Test dummy url.
   *
   * @var string
   */
  protected $url;

}
