<?php

declare(strict_types=1);

namespace Drupal\Tests\linkit\Kernel;

use Drupal\Core\Site\Settings;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\file\Entity\File;
use Drupal\file\FileInterface;
use Drupal\linkit\Plugin\Linkit\Substitution\Canonical as CanonicalSubstitutionPlugin;
use Drupal\linkit\Plugin\Linkit\Substitution\File as FileSubstitutionPlugin;
use Drupal\linkit\Plugin\Linkit\Substitution\Media as MediaSubstitutionPlugin;
use Drupal\media\Entity\Media;
use Drupal\media\Entity\MediaType;
use Drupal\Core\DependencyInjection\ContainerBuilder;

/**
 * Tests the substitution plugins.
 *
 * @group linkit
 */
class SubstitutionPluginTest extends LinkitKernelTestBase {

  /**
   * The substitution manager.
   *
   * @var \Drupal\linkit\SubstitutionManagerInterface
   */
  protected $substitutionManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Additional modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'file',
    'entity_test',
    'media',
    'media_test_source',
    'image',
    'field',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();
    $this->substitutionManager = $this->container->get('plugin.manager.linkit.substitution');
    $this->entityTypeManager = $this->container->get('entity_type.manager');

    $this->installEntitySchema('file');
    $this->installEntitySchema('entity_test');
    $this->installEntitySchema('media');
    $this->installEntitySchema('media_type');
    $this->installEntitySchema('field_storage_config');
    $this->installEntitySchema('field_config');
    $this->installSchema('file', ['file_usage']);
    $this->installConfig(['media']);
    \Drupal::entityTypeManager()->clearCachedDefinitions();

    unset($GLOBALS['config']['system.file']);
    \Drupal::configFactory()->getEditable('system.file')->set('default_scheme', 'public')->save();
  }

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {
    parent::register($container);

    $container->register('stream_wrapper.public', 'Drupal\Core\StreamWrapper\PublicStream')
      ->addTag('stream_wrapper', ['scheme' => 'public']);
  }

  /**
   * {@inheritdoc}
   */
  protected function setUpFilesystem() {
    $public_file_directory = $this->siteDirectory . '/files';

    mkdir($this->siteDirectory, 0775);
    mkdir($this->siteDirectory . '/files', 0775);
    mkdir($this->siteDirectory . '/files/config/' . Settings::get('config_sync_directory'), 0775, TRUE);

    $this->setSetting('file_public_path', $public_file_directory);

    $GLOBALS['config_directories'] = [
      Settings::get('config_sync_directory') => $this->siteDirectory . '/files/config/sync',
    ];
  }

  /**
   * Test the file substitution.
   */
  public function testFileSubstitutions() {
    $fileSubstitution = $this->substitutionManager->createInstance('file');
    $file = File::create([
      'uid' => 1,
      'filename' => 'druplicon.txt',
      'uri' => 'public://druplicon.txt',
      'filemime' => 'text/plain',
      'status' => FileInterface::STATUS_PERMANENT,
    ]);
    $file->save();
    $this->assertEquals('/' . $this->siteDirectory . '/files/druplicon.txt', $fileSubstitution->getUrl($file)->toString());

    $entity_type = $this->entityTypeManager->getDefinition('file');
    $this->assertTrue(FileSubstitutionPlugin::isApplicable($entity_type), 'The entity type File is applicable the file substitution.');

    $entity_type = $this->entityTypeManager->getDefinition('entity_test');
    $this->assertFalse(FileSubstitutionPlugin::isApplicable($entity_type), 'The entity type EntityTest is not applicable the file substitution.');
  }

  /**
   * Test the canonical substitution.
   */
  public function testCanonicalSubstitution() {
    $canonicalSubstitution = $this->substitutionManager->createInstance('canonical');
    $entity = EntityTest::create([]);
    $entity->save();
    $this->assertEquals('/entity_test/1', $canonicalSubstitution->getUrl($entity)->toString());

    $entity_type = $this->entityTypeManager->getDefinition('entity_test');
    $this->assertTrue(CanonicalSubstitutionPlugin::isApplicable($entity_type), 'The entity type EntityTest is applicable the canonical substitution.');

    $entity_type = $this->entityTypeManager->getDefinition('file');
    $this->assertFalse(CanonicalSubstitutionPlugin::isApplicable($entity_type), 'The entity type File is not applicable the canonical substitution.');
  }

  /**
   * Test the media substitution.
   */
  public function testMediaSubstitution() {
    // Set up media bundle and fields.
    $media_type = MediaType::create([
      'label' => 'test',
      'id' => 'test',
      'description' => 'Test type.',
      'source' => 'file',
    ]);
    $media_type->save();
    $source_field = $media_type->getSource()->createSourceField($media_type);
    $source_field->getFieldStorageDefinition()->save();
    $source_field->save();
    $media_type->set('source_configuration', [
      'source_field' => $source_field->getName(),
    ])->save();

    $file = File::create([
      'uid' => 1,
      'filename' => 'druplicon.txt',
      'uri' => 'public://druplicon.txt',
      'filemime' => 'text/plain',
      'status' => FileInterface::STATUS_PERMANENT,
    ]);
    $file->save();

    $media = Media::create([
      'bundle' => 'test',
      $source_field->getName() => ['target_id' => $file->id()],
    ]);
    $media->save();

    $media_substitution = $this->substitutionManager->createInstance('media');
    $expected = '/' . $this->siteDirectory . '/files/druplicon.txt';
    $this->assertEquals($expected, $media_substitution->getUrl($media)->toString());

    // Ensure the url is identical when media entities have a standalone URL
    // enabled.
    \Drupal::configFactory()->getEditable('media.settings')->set('standalone_url', TRUE)->save();
    $this->assertEquals($expected, $media_substitution->getUrl($media)->toString());

    $entity_type = $this->entityTypeManager->getDefinition('media');
    $this->assertTrue(MediaSubstitutionPlugin::isApplicable($entity_type), 'The entity type Media is applicable the media substitution.');

    $entity_type = $this->entityTypeManager->getDefinition('file');
    $this->assertFalse(MediaSubstitutionPlugin::isApplicable($entity_type), 'The entity type File is not applicable the media substitution.');
  }

  /**
   * Test the media substitution when there is no supported source field.
   */
  public function testMediaSubstitutionWithoutFileSource() {
    // Set up media bundle and fields.
    $media_type = MediaType::create([
      'label' => 'test',
      'id' => 'test',
      'description' => 'Test type.',
      'source' => 'test',
    ]);
    $media_type->save();
    $source_field = $media_type->getSource()->createSourceField($media_type);
    $source_field->getFieldStorageDefinition()->save();
    $source_field->save();
    $media_type->set('source_configuration', [
      'source_field' => $source_field->getName(),
    ])->save();

    $media = Media::create([
      'bundle' => 'test',
      $source_field->getName() => ['value' => 'foobar'],
    ]);
    $media->save();

    $media_substitution = $this->substitutionManager->createInstance('media');
    $this->assertNull($media_substitution->getUrl($media));

    $this->config('media.settings')->set('standalone_url', TRUE)->save();
    \Drupal::entityTypeManager()->clearCachedDefinitions();
    $this->assertEquals('/media/' . $media->id(), $media_substitution->getUrl($media)->toString());
  }

}
