<?php

declare(strict_types=1);

namespace Drupal\Tests\file_mdm\Kernel;

use Drupal\Core\File\FileExists;
use Drupal\Core\File\FileSystemInterface;
use Drupal\file_mdm\FileMetadataInterface;
use Drupal\file_mdm\FileMetadataManagerInterface;

/**
 * Tests that File Metadata Manager works properly.
 *
 * @group file_mdm
 */
class FileMetadataManagerTest extends FileMetadataManagerTestBase {

  protected static $modules = ['system', 'file_mdm', 'file_test'];

  /**
   * Tests using the 'getimagesize' plugin.
   */
  public function testFileMetadata(): void {
    // Prepare a copy of test files.
    $this->fileSystem->copy('core/tests/fixtures/files/image-test.png', 'public://', FileExists::Replace);
    $this->fileSystem->copy($this->moduleList->getPath('file_mdm') . '/tests/files/test-exif.jpeg', 'public://', FileExists::Replace);
    // The image files that will be tested.
    $image_files = [
      [
        // Pass a path instead of the URI.
        'uri' => $this->moduleList->getPath('file_mdm') . '/tests/files/test-exif.jpeg',
        'count_keys' => 7,
        'test_keys' => [
          [0, 100],
          [1, 75],
          [2, IMAGETYPE_JPEG],
          ['bits', 8],
          ['channels', 3],
          ['mime', 'image/jpeg'],
        ],
      ],
      [
        // Pass a URI.
        'uri' => 'public://test-exif.jpeg',
        'count_keys' => 7,
        'test_keys' => [
          [0, 100],
          [1, 75],
          [2, IMAGETYPE_JPEG],
          ['bits', 8],
          ['channels', 3],
          ['mime', 'image/jpeg'],
        ],
      ],
      [
        // PHP getimagesize works on remote stream wrappers.
        'uri' => 'dummy-remote://test-exif.jpeg',
        'count_keys' => 7,
        'test_keys' => [
          [0, 100],
          [1, 75],
          [2, IMAGETYPE_JPEG],
          ['bits', 8],
          ['channels', 3],
          ['mime', 'image/jpeg'],
        ],
      ],
      [
        // JPEG Image with GPS data.
        'uri' => $this->moduleList->getPath('file_mdm') . '/tests/files/1024-2006_1011_093752.jpg',
        'count_keys' => 7,
        'test_keys' => [
          [0, 1024],
          [1, 768],
          [2, IMAGETYPE_JPEG],
          ['bits', 8],
          ['channels', 3],
          ['mime', 'image/jpeg'],
        ],
      ],
      [
        // TIFF image.
        'uri' => $this->moduleList->getPath('file_mdm') . '/tests/files/sample-1.tiff',
        'count_keys' => 5,
        'test_keys' => [
          [0, 174],
          [1, 38],
          [2, IMAGETYPE_TIFF_MM],
          ['mime', 'image/tiff'],
        ],
      ],
      [
        // PNG image.
        'uri' => 'public://image-test.png',
        'count_keys' => 6,
        'test_keys' => [
          [0, 40],
          [1, 20],
          [2, IMAGETYPE_PNG],
          ['bits', 8],
          ['mime', 'image/png'],
        ],
      ],
    ];

    // Get the file metadata manager service.
    $fmdm = $this->container->get(FileMetadataManagerInterface::class);

    // Walk through test files.
    foreach ($image_files as $image_file) {
      $file_metadata = $fmdm->uri($image_file['uri']);
      $this->assertNotNull($file_metadata->getMetadata('getimagesize'));
      // Read from file.
      $this->assertEquals($image_file['count_keys'], $this->countMetadataKeys($file_metadata, 'getimagesize'));
      foreach ($image_file['test_keys'] as $test) {
        $entry = $file_metadata->getMetadata('getimagesize', $test[0]);
        $this->assertEquals($test[1], $entry);
      }
      // Try getting an unsupported key.
      $this->assertNull($file_metadata->getMetadata('getimagesize', 'baz'));
      // Try getting an invalid key.
      $this->assertNull($file_metadata->getMetadata('getimagesize', ['qux' => 'laa']));
      // Change MIME type.
      $this->assertTrue($file_metadata->setMetadata('getimagesize', 'mime', 'foo/bar'));
      $this->assertEquals('foo/bar', $file_metadata->getMetadata('getimagesize', 'mime'));
      // Try adding an unsupported key.
      $this->assertFalse($file_metadata->setMetadata('getimagesize', 'baz', 'qux'));
      $this->assertNull($file_metadata->getMetadata('getimagesize', 'baz'));
      // Try adding an invalid key.
      $this->assertFalse($file_metadata->setMetadata('getimagesize', ['qux' => 'laa'], 'hoz'));
      // Remove MIME type.
      $this->assertTrue($file_metadata->removeMetadata('getimagesize', 'mime'));
      $this->assertEquals($image_file['count_keys'] - 1, $this->countMetadataKeys($file_metadata, 'getimagesize'));
      $this->assertNull($file_metadata->getMetadata('getimagesize', 'mime'));
      // Try removing an unsupported key.
      $this->assertFalse($file_metadata->removeMetadata('getimagesize', 'baz'));
      // Try removing an invalid key.
      $this->assertFalse($file_metadata->removeMetadata('getimagesize', ['qux' => 'laa']));
      // Try getting/setting/removing metadata for a non-existing plugin.
      $this->assertNull($file_metadata->getMetadata('laila', 'han'));
      $this->assertFalse($file_metadata->setMetadata('laila', 'han', 'solo'));
      $this->assertFalse($file_metadata->removeMetadata('laila', 'han'));
    }

    // Test releasing URI.
    $this->assertEquals(6, $fmdm->count());
    $this->assertTrue($fmdm->has($image_files[0]['uri']));
    $this->assertTrue($fmdm->release($image_files[0]['uri']));
    $this->assertEquals(5, $fmdm->count());
    $this->assertFalse($fmdm->has($image_files[0]['uri']));
    $this->assertFalse($fmdm->release($image_files[0]['uri']));

    // Test loading metadata from an in-memory object.
    $file_metadata_from = $fmdm->uri($image_files[0]['uri']);
    $this->assertEquals(6, $fmdm->count());
    $metadata = $file_metadata_from->getMetadata('getimagesize');
    $new_file_metadata = $fmdm->uri('public://test-output.jpeg');
    $this->assertEquals(7, $fmdm->count());
    $new_file_metadata->loadMetadata('getimagesize', $metadata);
    $this->assertEquals($image_files[0]['count_keys'], $this->countMetadataKeys($new_file_metadata, 'getimagesize'));
    foreach ($image_files[0]['test_keys'] as $test) {
      $entry = $file_metadata->getMetadata('getimagesize', $test[0]);
      $this->assertEquals($test[1], $new_file_metadata->getMetadata('getimagesize', $test[0]));
    }
  }

  /**
   * Tests caching.
   */
  public function testFileMetadataCaching(): void {
    // Prepare a copy of test files.
    $this->fileSystem->copy($this->moduleList->getPath('file_mdm') . '/tests/files/test-exif.jpeg', 'public://', FileExists::Replace);
    $this->fileSystem->copy('core/tests/fixtures/files/image-test.gif', 'public://', FileExists::Replace);
    $this->fileSystem->copy('core/tests/fixtures/files/image-test.png', 'public://', FileExists::Replace);

    // The image files that will be tested.
    $image_files = [
      [
        // Pass a URI.
        'uri' => 'public://image-test.gif',
        'cache' => TRUE,
        'delete' => TRUE,
        'count_keys' => 7,
        'test_keys' => [
          [0, 40],
          [1, 20],
          [2, IMAGETYPE_GIF],
          ['mime', 'image/gif'],
        ],
      ],
      [
        // Pass a path instead of the URI.
        'uri' => $this->moduleList->getPath('file_mdm') . '/tests/files/test-exif.jpeg',
        'cache' => FALSE,
        'delete' => FALSE,
        'count_keys' => 7,
        'test_keys' => [
          [0, 100],
          [1, 75],
          [2, IMAGETYPE_JPEG],
          ['mime', 'image/jpeg'],
        ],
      ],
      [
        // PHP getimagesize works on remote stream wrappers.
        'uri' => 'dummy-remote://image-test.png',
        'cache' => TRUE,
        'delete' => TRUE,
        'count_keys' => 6,
        'test_keys' => [
          [0, 40],
          [1, 20],
          [2, IMAGETYPE_PNG],
          ['mime', 'image/png'],
        ],
      ],
    ];

    // Get the file metadata manager service.
    $fmdm = $this->container->get(FileMetadataManagerInterface::class);

    // Walk through test files.
    foreach ($image_files as $image_file) {
      // Read from file.
      $file_metadata = $fmdm->uri($image_file['uri']);
      $this->assertNotNull($file_metadata->getMetadata('getimagesize'));
      $this->assertSame(FileMetadataInterface::LOADED_FROM_FILE, $file_metadata->isMetadataLoaded('getimagesize'));

      // Release URI.
      $file_metadata = NULL;
      $this->assertTrue($fmdm->release($image_file['uri']));
      $this->assertEquals(0, $fmdm->count());

      if ($image_file['delete']) {
        // Delete file.
        $this->fileSystem->delete($image_file['uri']);
        // No file to be found at URI.
        $this->assertFalse(file_exists($image_file['uri']));
      }

      // Read from cache if possible.
      $file_metadata = $fmdm->uri($image_file['uri']);
      $this->assertNotNull($file_metadata->getMetadata('getimagesize'));
      if ($image_file['cache']) {
        $this->assertSame(FileMetadataInterface::LOADED_FROM_CACHE, $file_metadata->isMetadataLoaded('getimagesize'));
      }
      else {
        $this->assertSame(FileMetadataInterface::LOADED_FROM_FILE, $file_metadata->isMetadataLoaded('getimagesize'));
      }
      $this->assertEquals($image_file['count_keys'], $this->countMetadataKeys($file_metadata, 'getimagesize'));
      foreach ($image_file['test_keys'] as $test) {
        $entry = $file_metadata->getMetadata('getimagesize', $test[0]);
        $this->assertEquals($test[1], $entry);
      }

      // Change MIME type and remove 0, 1, 2, 3.
      $this->assertTrue($file_metadata->setMetadata('getimagesize', 'mime', 'foo/bar'));
      $this->assertTrue($file_metadata->removeMetadata('getimagesize', 0));
      $this->assertTrue($file_metadata->removeMetadata('getimagesize', 1));
      $this->assertTrue($file_metadata->removeMetadata('getimagesize', 2));
      $this->assertTrue($file_metadata->removeMetadata('getimagesize', 3));

      // Save again to cache.
      if ($image_file['cache']) {
        $this->assertTrue($file_metadata->saveMetadataToCache('getimagesize'));
      }
      else {
        $this->assertFalse($file_metadata->saveMetadataToCache('getimagesize'));
      }

      if ($image_file['cache']) {
        // Release URI.
        $file_metadata = NULL;
        $this->assertTrue($fmdm->release($image_file['uri']));
        $this->assertCount(0, $fmdm);

        // Read from cache.
        $file_metadata = $fmdm->uri($image_file['uri']);
        $this->assertSame($image_file['count_keys'] - 4, $this->countMetadataKeys($file_metadata, 'getimagesize'));
        $this->assertSame('foo/bar', $file_metadata->getMetadata('getimagesize', 'mime'));
        $this->assertSame(FileMetadataInterface::LOADED_FROM_CACHE, $file_metadata->isMetadataLoaded('getimagesize'));
      }

      $file_metadata = NULL;
      $this->assertTrue($fmdm->release($image_file['uri']));
      $this->assertCount(0, $fmdm);
    }
  }

  /**
   * Tests remote files, setting local temp path explicitly.
   */
  public function testRemoteFileSetLocalPath(): void {
    // The image files that will be tested.
    $image_files = [
      [
        // Remote storage file. Pass the path to a local copy of the file.
        'uri' => 'dummy-remote://test-exif.jpeg',
        'local_path' => $this->container->get('file_system')->realpath('temporary://test-exif.jpeg'),
        'count_keys' => 7,
        'test_keys' => [
          [0, 100],
          [1, 75],
          [2, IMAGETYPE_JPEG],
          ['bits', 8],
          ['channels', 3],
          ['mime', 'image/jpeg'],
        ],
      ],
    ];

    // Get the file metadata manager service.
    $fmdm = $this->container->get(FileMetadataManagerInterface::class);

    // Copy the test file to a temp location.
    $this->fileSystem->copy($this->moduleList->getPath('file_mdm') . '/tests/files/test-exif.jpeg', 'temporary://', FileExists::Replace);

    // Test setting local temp path explicitly. The files should be parsed
    // even if not available on the URI.
    foreach ($image_files as $image_file) {
      $file_metadata = $fmdm->uri($image_file['uri']);
      $file_metadata->setLocalTempPath($image_file['local_path']);
      // No file to be found at URI.
      $this->assertFalse(file_exists($image_file['uri']));
      // File to be found at local temp path.
      $this->assertTrue(file_exists($file_metadata->getLocalTempPath()));
      $this->assertEquals($image_file['count_keys'], $this->countMetadataKeys($file_metadata, 'getimagesize'));
      foreach ($image_file['test_keys'] as $test) {
        $entry = $file_metadata->getMetadata('getimagesize', $test[0]);
        $this->assertEquals($test[1], $entry);
      }
      // Copies temp to destination URI.
      $this->assertTrue($file_metadata->copyTempToUri());
      $this->assertTrue(file_exists($image_file['uri']));

      // Release URI and check metadata was cached.
      $file_metadata = NULL;
      $this->assertTrue($fmdm->release($image_file['uri']));
      $this->assertEquals(0, $fmdm->count());
      $file_metadata = $fmdm->uri($image_file['uri']);
      $this->assertNotNull($file_metadata->getMetadata('getimagesize'));
      $this->assertSame(FileMetadataInterface::LOADED_FROM_CACHE, $file_metadata->isMetadataLoaded('getimagesize'));
    }
  }

  /**
   * Tests remote files, letting file_mdm manage setting local temp path.
   */
  public function testRemoteFileCopy(): void {
    // The image files that will be tested.
    $image_files = [
      [
        // Remote storage file. Pass the path to a local copy of the file.
        'uri' => 'dummy-remote://test-exif.jpeg',
        'count_keys' => 7,
        'test_keys' => [
          [0, 100],
          [1, 75],
          [2, IMAGETYPE_JPEG],
          ['bits', 8],
          ['channels', 3],
          ['mime', 'image/jpeg'],
        ],
      ],
    ];

    // Get the file metadata manager service.
    $fmdm = $this->container->get(FileMetadataManagerInterface::class);
    $file_system = $this->container->get('file_system');

    // Copy the test file to dummy-remote wrapper.
    $this->fileSystem->copy($this->moduleList->getPath('file_mdm') . '/tests/files/test-exif.jpeg', 'dummy-remote://', FileExists::Replace);

    foreach ($image_files as $image_file) {
      $file_metadata = $fmdm->uri($image_file['uri']);
      $file_metadata->copyUriToTemp();
      // File to be found at destination URI.
      $this->assertTrue(file_exists($image_file['uri']));
      // File to be found at local temp URI.
      $this->assertSame(0, strpos($file_system->basename($file_metadata->getLocalTempPath()), 'file_mdm_'));
      $this->assertTrue(file_exists($file_metadata->getLocalTempPath()));
      $this->assertEquals($image_file['count_keys'], $this->countMetadataKeys($file_metadata, 'getimagesize'));
      foreach ($image_file['test_keys'] as $test) {
        $entry = $file_metadata->getMetadata('getimagesize', $test[0]);
        $this->assertEquals($test[1], $entry);
      }

      // Release URI and check metadata was cached.
      $file_metadata = NULL;
      $this->assertTrue($fmdm->release($image_file['uri']));
      $this->assertEquals(0, $fmdm->count());
      $file_metadata = $fmdm->uri($image_file['uri']);
      $this->assertNotNull($file_metadata->getMetadata('getimagesize'));
      $this->assertSame(FileMetadataInterface::LOADED_FROM_CACHE, $file_metadata->isMetadataLoaded('getimagesize'));
    }
  }

  /**
   * Tests URI sanitization.
   */
  public function testSanitizedUri(): void {
    // Get the file metadata manager service.
    $fmdm = $this->container->get(FileMetadataManagerInterface::class);

    // Copy a test file to test directory.
    $test_directory = 'public://test-images/';
    $this->fileSystem->prepareDirectory($test_directory, FileSystemInterface::CREATE_DIRECTORY);
    $this->fileSystem->copy($this->moduleList->getPath('file_mdm') . '/tests/files/test-exif.jpeg', $test_directory, FileExists::Replace);

    // Get file metadata object.
    $file_metadata = $fmdm->uri('public://test-images/test-exif.jpeg');
    $this->assertEquals(7, $this->countMetadataKeys($file_metadata, 'getimagesize'));

    // Check that the file metadata manager has the URI in different forms.
    $this->assertTrue($fmdm->has('public://test-images/test-exif.jpeg'));
    $this->assertTrue($fmdm->has('public:///test-images/test-exif.jpeg'));
    $this->assertTrue($fmdm->has('public://test-images//test-exif.jpeg'));
    $this->assertTrue($fmdm->has('public://////test-images////test-exif.jpeg'));
    $this->assertFalse($fmdm->has('public:/test-images/test-exif.jpeg'));
  }

}
