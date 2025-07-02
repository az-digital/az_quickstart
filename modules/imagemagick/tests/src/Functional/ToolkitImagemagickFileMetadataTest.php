<?php

declare(strict_types=1);

namespace Drupal\Tests\imagemagick\Functional;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\File\FileExists;
use Drupal\Core\File\FileSystemInterface;
use Drupal\file_mdm\FileMetadataInterface;
use Drupal\file_mdm\FileMetadataManagerInterface;
use Drupal\imagemagick\Plugin\ImageToolkit\ImagemagickToolkit;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\imagemagick\Kernel\ToolkitSetupTrait;

/**
 * Tests that Imagemagick integrates properly with File Metadata Manager.
 *
 * @group imagemagick
 */
class ToolkitImagemagickFileMetadataTest extends BrowserTestBase {

  use ToolkitSetupTrait;

  /**
   * Modules to enable.
   *
   * Enable 'file_test' to be able to work with dummy_remote:// stream wrapper.
   *
   * @var string[]
   */
  protected static $modules = [
    'system',
    'imagemagick',
    'file_mdm',
    'file_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Provides a list of available modules.
   */
  protected ModuleExtensionList $moduleList;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();
    $this->moduleList = \Drupal::service('extension.list.module');
  }

  /**
   * Test image toolkit integration with file metadata manager.
   *
   * @param string $toolkit_id
   *   The id of the toolkit to set up.
   * @param string $toolkit_config
   *   The config object of the toolkit to set up.
   * @param array $toolkit_settings
   *   The settings of the toolkit to set up.
   *
   * @dataProvider providerToolkitConfiguration
   */
  public function testFileMetadata(string $toolkit_id, string $toolkit_config, array $toolkit_settings): void {
    $this->setUpToolkit($toolkit_id, $toolkit_config, $toolkit_settings);
    $this->prepareImageFileHandling();

    $config_mdm = \Drupal::configFactory()->getEditable('file_mdm.settings');

    // Reset file_mdm settings.
    $config_mdm
      ->set('metadata_cache.enabled', TRUE)
      ->set('metadata_cache.disallowed_paths', [])
      ->save();

    // A list of files that will be tested.
    $files = [
      'public://image-test.png' => [
        'width' => 40,
        'height' => 20,
        'frames' => 1,
        'mimetype' => 'image/png',
        'colorspace' => 'SRGB',
        'profiles' => [],
      ],
      'public://image-test.gif' => [
        'width' => 40,
        'height' => 20,
        'frames' => 1,
        'mimetype' => 'image/gif',
        'colorspace' => 'SRGB',
        'profiles' => [],
      ],
      'dummy-remote://image-test.jpg' => [
        'width' => 40,
        'height' => 20,
        'frames' => 1,
        'mimetype' => 'image/jpeg',
        'colorspace' => 'SRGB',
        'profiles' => [],
      ],
      'public://test-multi-frame.gif' => [
        'skip_dimensions_check' => TRUE,
        'frames' => 13,
        'mimetype' => 'image/gif',
        'colorspace' => 'SRGB',
        'profiles' => [],
      ],
      'public://test-exif.jpeg' => [
        'skip_dimensions_check' => TRUE,
        'frames' => 1,
        'mimetype' => 'image/jpeg',
        'colorspace' => 'SRGB',
        'profiles' => ['exif'],
      ],
      'public://test-exif-icc.jpeg' => [
        'skip_dimensions_check' => TRUE,
        'frames' => 1,
        'mimetype' => 'image/jpeg',
        'colorspace' => 'SRGB',
        'profiles' => ['exif', 'icc'],
      ],
    ];

    // Setup a list of tests to perform on each type.
    $operations = [
      'resize' => [
        'function' => 'resize',
        'arguments' => ['width' => 20, 'height' => 10],
        'width' => 20,
        'height' => 10,
      ],
      'scale_x' => [
        'function' => 'scale',
        'arguments' => ['width' => 20],
        'width' => 20,
        'height' => 10,
      ],
      'convert_jpg' => [
        'function' => 'convert',
        'width' => 40,
        'height' => 20,
        'arguments' => ['extension' => 'jpeg'],
        'mimetype' => 'image/jpeg',
      ],
    ];

    // The file metadata manager service.
    $fmdm = $this->container->get(FileMetadataManagerInterface::class);

    // Prepare a copy of test files.
    $this->fileSystem->copy($this->moduleList->getPath('imagemagick') . '/misc/test-multi-frame.gif', 'public://', FileExists::Replace);
    $this->fileSystem->copy($this->moduleList->getPath('imagemagick') . '/misc/test-exif.jpeg', 'public://', FileExists::Replace);
    $this->fileSystem->copy($this->moduleList->getPath('imagemagick') . '/misc/test-exif-icc.jpeg', 'public://', FileExists::Replace);

    // Perform tests without caching.
    $config_mdm->set('metadata_cache.enabled', FALSE)->save();
    foreach ($files as $source_uri => $source_image_data) {
      $this->assertFalse($fmdm->has($source_uri));
      $source_image_md = $fmdm->uri($source_uri);
      $this->assertTrue($fmdm->has($source_uri), $source_uri);
      $first = TRUE;
      $this->fileSystem->deleteRecursive($this->testDirectory);
      $this->fileSystem->prepareDirectory($this->testDirectory, FileSystemInterface::CREATE_DIRECTORY);
      foreach ($operations as $op => $values) {
        // Load up a fresh image.
        if ($first) {
          $this->assertSame(FileMetadataInterface::NOT_LOADED, $source_image_md->isMetadataLoaded(ImagemagickToolkit::FILE_METADATA_PLUGIN_ID));
        }
        else {
          $this->assertSame(FileMetadataInterface::LOADED_FROM_FILE, $source_image_md->isMetadataLoaded(ImagemagickToolkit::FILE_METADATA_PLUGIN_ID));
        }
        $source_image = $this->imageFactory->get($source_uri);
        /** @var \Drupal\imagemagick\Plugin\ImageToolkit\ImagemagickToolkit $toolkit */
        $toolkit = $source_image->getToolkit();
        $this->assertSame(FileMetadataInterface::LOADED_FROM_FILE, $source_image_md->isMetadataLoaded(ImagemagickToolkit::FILE_METADATA_PLUGIN_ID));
        $this->assertSame($source_image_data['mimetype'], $source_image->getMimeType());
        if ($toolkit_settings['binaries'] === 'imagemagick') {
          $this->assertSame($source_image_data['colorspace'], $toolkit->getColorspace());
          $this->assertEquals($source_image_data['profiles'], $toolkit->getProfiles());
        }
        if (!isset($source_image_data['skip_dimensions_check'])) {
          $this->assertSame($source_image_data['height'], $source_image->getHeight());
          $this->assertSame($source_image_data['width'], $source_image->getWidth());
        }

        // Perform our operation.
        $source_image->apply($values['function'], $values['arguments']);

        // Save image.
        $saved_uri = $this->testDirectory . '/' . $op . substr($source_uri, -4);
        $this->assertFalse($fmdm->has($saved_uri));
        $this->assertTrue($source_image->save($saved_uri));
        $this->assertFalse($fmdm->has($saved_uri));

        // Reload saved image and check data.
        $saved_image_md = $fmdm->uri($saved_uri);
        $saved_image = $this->imageFactory->get($saved_uri);
        $this->assertEquals(FileMetadataInterface::LOADED_FROM_FILE, $saved_image_md->isMetadataLoaded(ImagemagickToolkit::FILE_METADATA_PLUGIN_ID));
        $this->assertSame($values['function'] === 'convert' ? $values['mimetype'] : $source_image_data['mimetype'], $saved_image->getMimeType());
        if ($toolkit_settings['binaries'] === 'imagemagick') {
          $this->assertSame($source_image_data['colorspace'], $toolkit->getColorspace());
          $this->assertEquals($source_image_data['profiles'], $toolkit->getProfiles());
        }
        if (!isset($source_image_data['skip_dimensions_check'])) {
          $this->assertEquals($values['height'], $saved_image->getHeight());
          $this->assertEquals($values['width'], $saved_image->getWidth());
        }
        $fmdm->release($saved_uri);

        // Get metadata via the file_mdm service.
        $saved_image_md = $fmdm->uri($saved_uri);
        // Should not be available at this stage.
        $this->assertSame(FileMetadataInterface::NOT_LOADED, $saved_image_md->isMetadataLoaded(ImagemagickToolkit::FILE_METADATA_PLUGIN_ID));
        // Get metadata from file.
        $saved_image_md->getMetadata(ImagemagickToolkit::FILE_METADATA_PLUGIN_ID);
        $this->assertEquals(FileMetadataInterface::LOADED_FROM_FILE, $saved_image_md->isMetadataLoaded(ImagemagickToolkit::FILE_METADATA_PLUGIN_ID));
        if (!isset($source_image_data['skip_dimensions_check'])) {
          $this->assertEquals($values['height'], $saved_image_md->getMetadata(ImagemagickToolkit::FILE_METADATA_PLUGIN_ID, 'height'));
          $this->assertEquals($values['width'], $saved_image_md->getMetadata(ImagemagickToolkit::FILE_METADATA_PLUGIN_ID, 'width'));
        }
        $fmdm->release($saved_uri);

        $first = FALSE;
      }
      $fmdm->release($source_uri);
      $this->assertFalse($fmdm->has($source_uri));
    }

    // Perform tests with caching.
    $config_mdm->set('metadata_cache.enabled', TRUE)->save();
    foreach ($files as $source_uri => $source_image_data) {
      $first = TRUE;
      $this->fileSystem->deleteRecursive($this->testDirectory);
      $this->fileSystem->prepareDirectory($this->testDirectory, FileSystemInterface::CREATE_DIRECTORY);
      foreach ($operations as $op => $values) {
        // Load up a fresh image.
        $this->assertFalse($fmdm->has($source_uri));
        $source_image_md = $fmdm->uri($source_uri);
        $this->assertTrue($fmdm->has($source_uri));
        $this->assertSame(FileMetadataInterface::NOT_LOADED, $source_image_md->isMetadataLoaded(ImagemagickToolkit::FILE_METADATA_PLUGIN_ID));
        $source_image = $this->imageFactory->get($source_uri);
        /** @var \Drupal\imagemagick\Plugin\ImageToolkit\ImagemagickToolkit $toolkit */
        $toolkit = $source_image->getToolkit();
        if ($first) {
          // First time load, metadata loaded from file.
          $this->assertEquals(FileMetadataInterface::LOADED_FROM_FILE, $source_image_md->isMetadataLoaded(ImagemagickToolkit::FILE_METADATA_PLUGIN_ID));
        }
        else {
          // Further loads, metadata loaded from cache.
          $this->assertSame(FileMetadataInterface::LOADED_FROM_CACHE, $source_image_md->isMetadataLoaded(ImagemagickToolkit::FILE_METADATA_PLUGIN_ID));
        }
        $this->assertSame($source_image_data['mimetype'], $source_image->getMimeType());
        if ($toolkit_settings['binaries'] === 'imagemagick') {
          $this->assertSame($source_image_data['colorspace'], $toolkit->getColorspace());
          $this->assertEquals($source_image_data['profiles'], $toolkit->getProfiles());
        }
        if (!isset($source_image_data['skip_dimensions_check'])) {
          $this->assertSame($source_image_data['height'], $source_image->getHeight());
          $this->assertSame($source_image_data['width'], $source_image->getWidth());
        }

        // Perform our operation.
        $source_image->apply($values['function'], $values['arguments']);

        // Save image.
        $saved_uri = $this->testDirectory . '/' . $op . substr($source_uri, -4);
        $this->assertFalse($fmdm->has($saved_uri));
        $this->assertTrue($source_image->save($saved_uri));
        $this->assertFalse($fmdm->has($saved_uri));

        // Reload saved image and check data.
        $saved_image_md = $fmdm->uri($saved_uri);
        $saved_image = $this->imageFactory->get($saved_uri);
        $this->assertEquals(FileMetadataInterface::LOADED_FROM_FILE, $saved_image_md->isMetadataLoaded(ImagemagickToolkit::FILE_METADATA_PLUGIN_ID));
        $this->assertSame($values['function'] === 'convert' ? $values['mimetype'] : $source_image_data['mimetype'], $saved_image->getMimeType());
        if ($toolkit_settings['binaries'] === 'imagemagick') {
          $this->assertSame($source_image_data['colorspace'], $toolkit->getColorspace());
          $this->assertEquals($source_image_data['profiles'], $toolkit->getProfiles());
        }
        if (!isset($source_image_data['skip_dimensions_check'])) {
          $this->assertEquals($values['height'], $saved_image->getHeight());
          $this->assertEquals($values['width'], $saved_image->getWidth());
        }
        $fmdm->release($saved_uri);

        // Get metadata via the file_mdm service. Should be cached.
        $saved_image_md = $fmdm->uri($saved_uri);
        // Should not be available at this stage.
        $this->assertSame(FileMetadataInterface::NOT_LOADED, $saved_image_md->isMetadataLoaded(ImagemagickToolkit::FILE_METADATA_PLUGIN_ID));
        // Get metadata from cache.
        $saved_image_md->getMetadata(ImagemagickToolkit::FILE_METADATA_PLUGIN_ID);
        $this->assertSame(FileMetadataInterface::LOADED_FROM_CACHE, $saved_image_md->isMetadataLoaded(ImagemagickToolkit::FILE_METADATA_PLUGIN_ID));
        if (!isset($source_image_data['skip_dimensions_check'])) {
          $this->assertEquals($values['height'], $saved_image_md->getMetadata(ImagemagickToolkit::FILE_METADATA_PLUGIN_ID, 'height'));
          $this->assertEquals($values['width'], $saved_image_md->getMetadata(ImagemagickToolkit::FILE_METADATA_PLUGIN_ID, 'width'));
        }
        $fmdm->release($saved_uri);

        // We release the source image FileMetadata at each cycle to ensure
        // that metadata is read from cache.
        $fmdm->release($source_uri);
        $this->assertFalse($fmdm->has($source_uri));

        $first = FALSE;
      }
    }

    // Open source images again after deleting the temp folder files.
    // Source image data should now be cached, but temp files non existing.
    // Therefore we test that the toolkit can create a new temp file copy.
    // Note: on Windows, temp imagemagick file names have a
    // imaNNN.tmp.[image_extension] pattern so we cannot scan for
    // 'imagemagick'.
    $directory_scan = $this->fileSystem->scanDirectory('temporary://', '/ima.*/');
    $this->assertGreaterThan(0, count($directory_scan));
    foreach ($directory_scan as $file) {
      $this->fileSystem->delete($file->uri);
    }
    $directory_scan = $this->fileSystem->scanDirectory('temporary://', '/ima.*/');
    $this->assertCount(0, $directory_scan);
    foreach ($files as $source_uri => $source_image_data) {
      $this->fileSystem->deleteRecursive($this->testDirectory);
      $this->fileSystem->prepareDirectory($this->testDirectory, FileSystemInterface::CREATE_DIRECTORY);
      foreach ($operations as $op => $values) {
        // Load up the source image. Parsing should be fully cached now.
        $fmdm->release($source_uri);
        $source_image_md = $fmdm->uri($source_uri);
        $this->assertSame(FileMetadataInterface::NOT_LOADED, $source_image_md->isMetadataLoaded(ImagemagickToolkit::FILE_METADATA_PLUGIN_ID));
        $source_image = $this->imageFactory->get($source_uri);
        /** @var \Drupal\imagemagick\Plugin\ImageToolkit\ImagemagickToolkit $toolkit */
        $toolkit = $source_image->getToolkit();
        $this->assertEquals(FileMetadataInterface::LOADED_FROM_CACHE, $source_image_md->isMetadataLoaded(ImagemagickToolkit::FILE_METADATA_PLUGIN_ID));
        $this->assertSame($source_image_data['mimetype'], $source_image->getMimeType());
        if ($toolkit_settings['binaries'] === 'imagemagick') {
          $this->assertSame($source_image_data['colorspace'], $toolkit->getColorspace());
          $this->assertEquals($source_image_data['profiles'], $toolkit->getProfiles());
        }
        if (!isset($source_image_data['skip_dimensions_check'])) {
          $this->assertSame($source_image_data['height'], $source_image->getHeight());
          $this->assertSame($source_image_data['width'], $source_image->getWidth());
        }

        // Perform our operation.
        $source_image->apply($values['function'], $values['arguments']);

        // Save image.
        $saved_uri = $this->testDirectory . '/' . $op . substr($source_uri, -4);
        $this->assertFalse($fmdm->has($saved_uri));
        $this->assertTrue($source_image->save($saved_uri));
        $this->assertFalse($fmdm->has($saved_uri));

        // Reload saved image and check data.
        $saved_image_md = $fmdm->uri($saved_uri);
        $saved_image = $this->imageFactory->get($saved_uri);
        $this->assertEquals(FileMetadataInterface::LOADED_FROM_FILE, $saved_image_md->isMetadataLoaded(ImagemagickToolkit::FILE_METADATA_PLUGIN_ID));
        $this->assertSame($values['function'] === 'convert' ? $values['mimetype'] : $source_image_data['mimetype'], $saved_image->getMimeType());
        if ($toolkit_settings['binaries'] === 'imagemagick') {
          $this->assertSame($source_image_data['colorspace'], $toolkit->getColorspace());
          $this->assertEquals($source_image_data['profiles'], $toolkit->getProfiles());
        }
        if (!isset($source_image_data['skip_dimensions_check'])) {
          $this->assertEquals($values['height'], $saved_image->getHeight());
          $this->assertEquals($values['width'], $saved_image->getWidth());
        }
        $fmdm->release($saved_uri);

        // Get metadata via the file_mdm service. Should be cached.
        $saved_image_md = $fmdm->uri($saved_uri);
        // Should not be available at this stage.
        $this->assertSame(FileMetadataInterface::NOT_LOADED, $saved_image_md->isMetadataLoaded(ImagemagickToolkit::FILE_METADATA_PLUGIN_ID));
        // Get metadata from cache.
        $saved_image_md->getMetadata(ImagemagickToolkit::FILE_METADATA_PLUGIN_ID);
        $this->assertEquals(FileMetadataInterface::LOADED_FROM_CACHE, $saved_image_md->isMetadataLoaded(ImagemagickToolkit::FILE_METADATA_PLUGIN_ID));
        if (!isset($source_image_data['skip_dimensions_check'])) {
          $this->assertEquals($values['height'], $saved_image_md->getMetadata(ImagemagickToolkit::FILE_METADATA_PLUGIN_ID, 'height'));
          $this->assertEquals($values['width'], $saved_image_md->getMetadata(ImagemagickToolkit::FILE_METADATA_PLUGIN_ID, 'width'));
        }
        $fmdm->release($saved_uri);
      }
      $fmdm->release($source_uri);
      $this->assertFalse($fmdm->has($source_uri));
    }

    // Files in temporary:// must not be cached.
    $this->fileSystem->copy($this->moduleList->getPath('imagemagick') . '/misc/test-multi-frame.gif', 'temporary://', FileExists::Replace);
    $source_uri = 'temporary://test-multi-frame.gif';
    $fmdm->release($source_uri);
    $source_image_md = $fmdm->uri($source_uri);
    $this->assertSame(FileMetadataInterface::NOT_LOADED, $source_image_md->isMetadataLoaded(ImagemagickToolkit::FILE_METADATA_PLUGIN_ID));
    $source_image = $this->imageFactory->get($source_uri);
    $this->assertEquals(FileMetadataInterface::LOADED_FROM_FILE, $source_image_md->isMetadataLoaded(ImagemagickToolkit::FILE_METADATA_PLUGIN_ID));
    $fmdm->release($source_uri);
    $source_image_md = $fmdm->uri($source_uri);
    $source_image = $this->imageFactory->get($source_uri);
    $this->assertEquals(FileMetadataInterface::LOADED_FROM_FILE, $source_image_md->isMetadataLoaded(ImagemagickToolkit::FILE_METADATA_PLUGIN_ID));

    // Invalidate cache, and open source images again. Now, all files should be
    // parsed again.
    Cache::InvalidateTags([
      'config:imagemagick.file_metadata_plugin.imagemagick_identify',
    ]);
    // Disallow caching on the test results directory.
    $config_mdm->set('metadata_cache.disallowed_paths', ['public://imagetest/*'])->save();
    foreach ($files as $source_uri => $source_image_data) {
      $fmdm->release($source_uri);
    }
    foreach ($files as $source_uri => $source_image_data) {
      $this->assertFalse($fmdm->has($source_uri));
      $source_image_md = $fmdm->uri($source_uri);
      $this->assertTrue($fmdm->has($source_uri));
      $first = TRUE;
      $this->fileSystem->deleteRecursive($this->testDirectory);
      $this->fileSystem->prepareDirectory($this->testDirectory, FileSystemInterface::CREATE_DIRECTORY);
      foreach ($operations as $op => $values) {
        // Load up a fresh image.
        if ($first) {
          $this->assertSame(FileMetadataInterface::NOT_LOADED, $source_image_md->isMetadataLoaded(ImagemagickToolkit::FILE_METADATA_PLUGIN_ID));
        }
        else {
          $this->assertEquals(FileMetadataInterface::LOADED_FROM_FILE, $source_image_md->isMetadataLoaded(ImagemagickToolkit::FILE_METADATA_PLUGIN_ID));
        }
        $source_image = $this->imageFactory->get($source_uri);
        /** @var \Drupal\imagemagick\Plugin\ImageToolkit\ImagemagickToolkit $toolkit */
        $toolkit = $source_image->getToolkit();
        $this->assertEquals(FileMetadataInterface::LOADED_FROM_FILE, $source_image_md->isMetadataLoaded(ImagemagickToolkit::FILE_METADATA_PLUGIN_ID));
        $this->assertSame($source_image_data['mimetype'], $source_image->getMimeType());
        if ($toolkit_settings['binaries'] === 'imagemagick') {
          $this->assertSame($source_image_data['colorspace'], $toolkit->getColorspace());
          $this->assertEquals($source_image_data['profiles'], $toolkit->getProfiles());
        }
        if (!isset($source_image_data['skip_dimensions_check'])) {
          $this->assertSame($source_image_data['height'], $source_image->getHeight());
          $this->assertSame($source_image_data['width'], $source_image->getWidth());
        }

        // Perform our operation.
        $source_image->apply($values['function'], $values['arguments']);

        // Save image.
        $saved_uri = $this->testDirectory . '/' . $op . substr($source_uri, -4);
        $this->assertFalse($fmdm->has($saved_uri));
        $this->assertTrue($source_image->save($saved_uri));
        $this->assertFalse($fmdm->has($saved_uri));

        // Reload saved image and check data.
        $saved_image_md = $fmdm->uri($saved_uri);
        $saved_image = $this->imageFactory->get($saved_uri);
        $this->assertEquals(FileMetadataInterface::LOADED_FROM_FILE, $saved_image_md->isMetadataLoaded(ImagemagickToolkit::FILE_METADATA_PLUGIN_ID));
        $this->assertSame($values['function'] === 'convert' ? $values['mimetype'] : $source_image_data['mimetype'], $saved_image->getMimeType());
        if ($toolkit_settings['binaries'] === 'imagemagick') {
          $this->assertSame($source_image_data['colorspace'], $toolkit->getColorspace());
          $this->assertEquals($source_image_data['profiles'], $toolkit->getProfiles());
        }
        if (!isset($source_image_data['skip_dimensions_check'])) {
          $this->assertEquals($values['height'], $saved_image->getHeight());
          $this->assertEquals($values['width'], $saved_image->getWidth());
        }
        $fmdm->release($saved_uri);

        // Get metadata via the file_mdm service.
        $saved_image_md = $fmdm->uri($saved_uri);
        // Should not be available at this stage.
        $this->assertSame(FileMetadataInterface::NOT_LOADED, $saved_image_md->isMetadataLoaded(ImagemagickToolkit::FILE_METADATA_PLUGIN_ID));
        // Get metadata from file.
        $saved_image_md->getMetadata(ImagemagickToolkit::FILE_METADATA_PLUGIN_ID);
        $this->assertEquals(FileMetadataInterface::LOADED_FROM_FILE, $saved_image_md->isMetadataLoaded(ImagemagickToolkit::FILE_METADATA_PLUGIN_ID));
        if (!isset($source_image_data['skip_dimensions_check'])) {
          $this->assertEquals($values['height'], $saved_image_md->getMetadata(ImagemagickToolkit::FILE_METADATA_PLUGIN_ID, 'height'));
          $this->assertEquals($values['width'], $saved_image_md->getMetadata(ImagemagickToolkit::FILE_METADATA_PLUGIN_ID, 'width'));
        }
        $fmdm->release($saved_uri);

        $first = FALSE;
      }
      $fmdm->release($source_uri);
      $this->assertFalse($fmdm->has($source_uri));
    }
  }

  /**
   * Tests getSourceLocalPath() for re-creating local path.
   *
   * @param string $toolkit_id
   *   The id of the toolkit to set up.
   * @param string $toolkit_config
   *   The config object of the toolkit to set up.
   * @param array $toolkit_settings
   *   The settings of the toolkit to set up.
   *
   * @dataProvider providerToolkitConfiguration
   */
  public function testSourceLocalPath(string $toolkit_id, string $toolkit_config, array $toolkit_settings): void {
    $this->setUpToolkit($toolkit_id, $toolkit_config, $toolkit_settings);
    $this->prepareImageFileHandling();

    $config_mdm = \Drupal::configFactory()->getEditable('file_mdm.settings');

    // The file metadata manager service.
    $fmdm = $this->container->get(FileMetadataManagerInterface::class);

    // The file that will be tested.
    $source_uri = 'public://image-test.png';

    // Enable metadata caching.
    $config_mdm->set('metadata_cache.enabled', TRUE)->save();

    // Load up the image.
    $image = $this->imageFactory->get($source_uri);
    /** @var \Drupal\imagemagick\Plugin\ImageToolkit\ImagemagickToolkit $toolkit */
    $toolkit = $image->getToolkit();
    $this->assertEquals($source_uri, $toolkit->getSource());
    $this->assertEquals($this->fileSystem->realpath($source_uri), $toolkit->arguments()->getSourceLocalPath());

    // Free up the URI from the file metadata manager to force reload from
    // cache. Simulates that next imageFactory->get is from another request.
    $fmdm->release($source_uri);

    // Re-load the image, ensureLocalSourcePath should return the local path.
    $image1 = $this->imageFactory->get($source_uri);
    /** @var \Drupal\imagemagick\Plugin\ImageToolkit\ImagemagickToolkit $toolkit */
    $toolkit = $image1->getToolkit();
    $this->assertEquals($source_uri, $toolkit->getSource());
    $this->assertEquals($this->fileSystem->realpath($source_uri), $toolkit->ensureSourceLocalPath());
  }

}
