<?php

namespace Drupal\Tests\xmlsitemap\Kernel;

/**
 * Tests directory functions.
 *
 * @group xmlsitemap
 */
class DirectoryTest extends KernelTestBase {

  /**
   * Test xmlsitemap_clear_directory().
   *
   * @covers ::xmlsitemap_get_directory
   * @covers ::xmlsitemap_clear_directory
   * @covers ::_xmlsitemap_delete_recursive
   */
  public function testClearDirectory() {
    /** @var \Drupal\Core\File\FileSystemInterface $fileSystem */
    $fileSystem = $this->container->get('file_system');

    // Set up a couple more directories and files.
    $directory = 'public://not-xmlsitemap';
    $fileSystem->prepareDirectory($directory, $fileSystem::CREATE_DIRECTORY | $fileSystem::MODIFY_PERMISSIONS);
    $directory = 'public://xmlsitemap/test';
    $fileSystem->prepareDirectory($directory, $fileSystem::CREATE_DIRECTORY | $fileSystem::MODIFY_PERMISSIONS);
    $fileSystem->saveData('File unrelated to XML Sitemap', 'public://not-xmlsitemap/file.txt');
    $fileSystem->saveData('File unrelated to XML Sitemap', 'public://file.txt');
    $fileSystem->saveData('Test contents', 'public://xmlsitemap/test/index.xml');

    // Test that only the xmlsitemap directory was deleted.
    $result = xmlsitemap_clear_directory(NULL, TRUE);
    $this->assertDirectoryDoesNotExist('public://xmlsitemap/test');
    $this->assertDirectoryExists('public://not-xmlsitemap');
    $this->assertFileExists('public://file.txt');
    $this->assertTrue($result);
  }

}
