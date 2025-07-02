<?php

declare(strict_types = 1);

namespace Drupal\Tests\migrate_plus\Kernel\Plugin\migrate\process;

use Drupal\Core\File\FileSystemInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Plugin\MigratePluginManagerInterface;
use Drupal\migrate\Row;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * Tests the file_blob plugin.
 *
 * @coversDefaultClass \Drupal\migrate_plus\Plugin\migrate\process\FileBlob
 * @group migrate_plus
 */
final class FileBlobTest extends KernelTestBase {

  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'migrate',
    'migrate_plus',
    'system',
  ];

  /**
   * The process plugin manager.
   */
  protected ?MigratePluginManagerInterface $pluginManager;

  /**
   * The blob representation of a cat image.
   */
  protected ?string $blob;

  /**
   * The destination filename.
   */
  protected ?string $filename;

  /**
   * The sha1sum of the blob.
   */
  protected ?string $sha1sum;

  /**
   * The filesystem interface.
   */
  protected ?FileSystemInterface $filesystem = NULL;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->pluginManager = $this->container->get('plugin.manager.migrate.process');
    $this->filesystem = $this->container->get('file_system');
    $this->filename = 'public://subdir/cat.jpeg';
    $this->sha1sum = 'a8eb2b9a987cfda507356d884f0498289bd0f620';
    $this->blob = <<<EOT
/9j/4AAQSkZJRgABAQEAYABgAAD//gA7Q1JFQVRPUjogZ2QtanBlZyB2MS4wICh1c2luZyBJSkcg
SlBFRyB2NjIpLCBxdWFsaXR5ID0gNjUK/9sAQwALCAgKCAcLCgkKDQwLDREcEhEPDxEiGRoUHCkk
KyooJCcnLTJANy0wPTAnJzhMOT1DRUhJSCs2T1VORlRAR0hF/9sAQwEMDQ0RDxEhEhIhRS4nLkVF
RUVFRUVFRUVFRUVFRUVFRUVFRUVFRUVFRUVFRUVFRUVFRUVFRUVFRUVFRUVFRUVF/8AAEQgBLADI
AwEiAAIRAQMRAf/EAB8AAAEFAQEBAQEBAAAAAAAAAAABAgMEBQYHCAkKC//EALUQAAIBAwMCBAMF
BQQEAAABfQECAwAEEQUSITFBBhNRYQcicRQygZGhCCNCscEVUtHwJDNicoIJChYXGBkaJSYnKCkq
NDU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6g4SFhoeIiYqSk5SVlpeYmZqi
o6Slpqeoqaqys7S1tre4ubrCw8TFxsfIycrS09TV1tfY2drh4uPk5ebn6Onq8fLz9PX29/j5+v/E
AB8BAAMBAQEBAQEBAQEAAAAAAAABAgMEBQYHCAkKC//EALURAAIBAgQEAwQHBQQEAAECdwABAgMR
BAUhMQYSQVEHYXETIjKBCBRCkaGxwQkjM1LwFWJy0QoWJDThJfEXGBkaJicoKSo1Njc4OTpDREVG
R0hJSlNUVVZXWFlaY2RlZmdoaWpzdHV2d3h5eoKDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKz
tLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uLj5OXm5+jp6vLz9PX29/j5+v/aAAwDAQACEQMRAD8A
jA4pwFCqRUgHNcZ1iAcVIKQU8CmAoFOFA6UUCFpcUmaWgBRS0lKAaBC0UlGaAHZoBqMnilDcUASi
jFIDTqAExSGnU00AMYCmECnnrSEUhkeKYVqU1GxpDGEU04FDnFNGKB3ExminZzRQFyQJTvLqUKKf
5Z28VRNyALSqM5qTbgc0BMHPrQK5HjBxT9ppzJnFOAxQFxm00u2pNooI4oC5GBTsUxnxxmhHycZo
AcaaalxUbjFMCMkmnAetN46U4ZFAEg6/Sl3UzPNAPrQBJnimg45NNJ4psjYAFIBVGcn1odto96QE
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
2Q==
EOT;
  }

  /**
   * Tests file creation.
   *
   * @covers ::transform
   */
  public function testFileCreation(): void {
    /** @var \Drupal\migrate\MigrateExecutableInterface $executable */
    $executable = $this->prophesize(MigrateExecutableInterface::class)->reveal();

    // Delete the target directory in case it already exists
    // to make sure target directories are automatically created
    if (is_dir(dirname($this->filename))) {
      rmdir(dirname($this->filename));
    }

    // Run the plugin
    $row = new Row([], []);
    $value = [$this->filename, base64_decode($this->blob, TRUE)];
    /** @var \Drupal\migrate_plus\Plugin\migrate\process\FileBlob $file_blob */
    $file_blob = $this->pluginManager->createInstance('file_blob');
    $file = $file_blob->transform($value, $executable, $row, 'destination_property');
    $this->assertEquals($this->filename, $file);
    $this->assertEquals($this->sha1sum, sha1_file($file));

    // Run the plugin again, but error if the file already exists
    $configuration = [
      'reuse' => FileSystemInterface::EXISTS_ERROR,
    ];
    /** @var \Drupal\migrate_plus\Plugin\migrate\process\FileBlob $file_blob */
    $file_blob = $this->pluginManager->createInstance('file_blob', $configuration);
    /** @var \Drupal\migrate\MigrateExecutableInterface $executable */
    $file = $file_blob->transform($value, $executable, $row, 'destination_property');
    $this->assertEquals($this->filename, $file);
    $this->assertEquals($this->sha1sum, sha1_file($file));
  }

}
