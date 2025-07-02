<?php

declare(strict_types=1);

namespace Drupal\Tests\imagemagick\Kernel;

use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Image\ImageFactory;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\TestFileCreationTrait;

/**
 * Trait to manage toolkit setup tasks common across tests.
 */
trait ToolkitSetupTrait {

  use TestFileCreationTrait;

  /**
   * The file system service.
   */
  protected FileSystemInterface $fileSystem;

  /**
   * The image factory service.
   */
  protected ImageFactory $imageFactory;

  /**
   * A directory for image test file results.
   */
  protected string $testDirectory;

  /**
   * Sets up the image toolkit.
   *
   * @param string $toolkit_id
   *   The id of the toolkit to set up.
   * @param string $toolkit_config
   *   The config object of the toolkit to set up.
   * @param array $toolkit_settings
   *   The settings of the toolkit to set up.
   */
  protected function setUpToolkit(string $toolkit_id, string $toolkit_config, array $toolkit_settings): void {
    // Change the toolkit.
    \Drupal::configFactory()->getEditable('system.image')
      ->set('toolkit', $toolkit_id)
      ->save();

    // Configure the toolkit.
    $config = \Drupal::configFactory()->getEditable($toolkit_config);
    foreach ($toolkit_settings as $setting => $value) {
      $config->set($setting, $value);
    }
    $config->save();

    // Check that ImageMagick or GraphicsMagick binaries are installed, and
    // mark the test skipped if not.
    if ($toolkit_id === 'imagemagick') {
      $status = \Drupal::service('image.toolkit.manager')->createInstance('imagemagick')->getExecManager()->checkPath('');
      if (!empty($status['errors'])) {
        $this->markTestSkipped("Tests for '{$toolkit_settings['binaries']}' cannot run because the binaries are not available on the shell path.");
      }
    }

    // Set the toolkit on the image factory.
    $this->imageFactory = \Drupal::service('image.factory');
    $this->imageFactory->setToolkitId($toolkit_id);
  }

  /**
   * Provides toolkit configuration data for tests.
   *
   * @return array[]
   *   An associative array, with key the toolkit scenario to be tested, and
   *   value an associative array with the following keys:
   *   - 'toolkit_id': the toolkit to be used in the test.
   *   - 'toolkit_config': the config object of the toolkit.
   *   - 'toolkit_settings': an associative array of toolkit settings.
   */
  public static function providerToolkitConfiguration(): array {
    return [
      'ImageMagick-imagemagick-v6' => [
        'toolkit_id' => 'imagemagick',
        'toolkit_config' => 'imagemagick.settings',
        'toolkit_settings' => [
          'binaries' => 'imagemagick',
          'imagemagick_version' => 'v6',
          'quality' => 100,
          'debug' => TRUE,
        ],
      ],
      'ImageMagick-imagemagick-v7' => [
        'toolkit_id' => 'imagemagick',
        'toolkit_config' => 'imagemagick.settings',
        'toolkit_settings' => [
          'binaries' => 'imagemagick',
          'imagemagick_version' => 'v7',
          'quality' => 100,
          'debug' => TRUE,
        ],
      ],
      'ImageMagick-graphicsmagick' => [
        'toolkit_id' => 'imagemagick',
        'toolkit_config' => 'imagemagick.settings',
        'toolkit_settings' => [
          'binaries' => 'graphicsmagick',
          'quality' => 100,
          'debug' => TRUE,
        ],
      ],
    ];
  }

  /**
   * Prepares image files for test handling.
   *
   * This method is only working for functional tests. Kernel tests use a
   * vfsStream virtual file system, that is not compatible with invocation by
   * ImageMagick executables that require a physical file passed in as a real
   * path.
   */
  protected function prepareImageFileHandling(): void {
    if (!$this instanceof BrowserTestBase) {
      $this->fail(__CLASS__ . " is not a BrowserTestBase test class, and file system cannot be initialised properly for calling ImageMagick executables.");
    }

    $this->fileSystem = \Drupal::service('file_system');

    // Prepare a directory for test file results.
    $this->testDirectory = 'public://imagetest';
    $this->fileSystem->deleteRecursive($this->testDirectory);
    $this->fileSystem->prepareDirectory($this->testDirectory, FileSystemInterface::CREATE_DIRECTORY);

    // Prepare a copy of test files.
    $this->getTestFiles('image');

    /** @var \Drupal\Core\File\FileSystemInterface $file_system */
    $file_system = \Drupal::service('file_system');
    $original = \Drupal::root() . '/core/tests/fixtures/files';
    $files = $file_system->scanDirectory($original, '/img-.*/');
    foreach ($files as $file) {
      $file_system->copy($file->uri, 'public://');
    }
  }

}
