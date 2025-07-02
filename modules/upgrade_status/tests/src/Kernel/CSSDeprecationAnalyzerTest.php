<?php

namespace Drupal\Tests\upgrade_status\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\upgrade_status\CSSDeprecationAnalyzer;

/**
 * Tests analysing CSS files.
 *
 * @group upgrade_status
 * @coversDefaultClass \Drupal\upgrade_status\CSSDeprecationAnalyzer
 */
class CSSDeprecationAnalyzerTest extends KernelTestBase {

  /**
   * The temporary directory path.
   */
  protected $tempPath;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->tempPath = @tempnam($this->root, 'upgrade_status_test');
    if (file_exists($this->tempPath)) {
      $this->container->get('file_system')->deleteRecursive($this->tempPath);
    }
    mkdir($this->tempPath);
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    $this->container->get('file_system')->deleteRecursive($this->tempPath);

    parent::tearDown();
  }

  /**
   * @covers ::getAllCSSFiles
   */
  public function testGetAllCSSFiles() {
    touch($this->tempPath . '/test.css');
    mkdir($this->tempPath . '/subdir');
    touch($this->tempPath . '/subdir/test.css');

    // Edge case: a directory with a .css extension.
    mkdir($this->tempPath . '/subdir.css');
    touch($this->tempPath . '/subdir.css/test.txt');
    touch($this->tempPath . '/subdir.css/test.css');

    // Directories are ignored from file_scan_ignore_directories.
    mkdir($this->tempPath . '/node_modules');
    touch($this->tempPath . '/node_modules/test.css');
    mkdir($this->tempPath . '/bower_components');
    touch($this->tempPath . '/bower_components/test.css');
    mkdir($this->tempPath . '/subdir.css/node_modules');
    touch($this->tempPath . '/subdir.css/node_modules/test.css');

    $class = new \ReflectionClass(CSSDeprecationAnalyzer::class);
    $method = $class->getMethod('getAllCSSFiles');
    $method->setAccessible(TRUE);

    $expected = [
      $this->tempPath . '/subdir/test.css',
      $this->tempPath . '/subdir.css/test.css',
      $this->tempPath . '/test.css',
    ];
    $actual = $method->invokeArgs(new CSSDeprecationAnalyzer(), [$this->tempPath]);

    $this->assertEmpty(array_diff($expected, $actual), 'Checking for missing files.');
    $this->assertEmpty(array_diff($actual, $expected), 'Checking for extra files.');
  }

}
