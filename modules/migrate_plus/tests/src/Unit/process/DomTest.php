<?php

declare(strict_types = 1);

namespace Drupal\Tests\migrate_plus\Unit\process;

use Drupal\Component\Utility\Html;
use Drupal\migrate\MigrateException;
use Drupal\migrate_plus\Plugin\migrate\process\Dom;
use Drupal\Tests\migrate\Unit\process\MigrateProcessTestCase;

/**
 * Tests the dom process plugin.
 *
 * @group migrate
 * @coversDefaultClass \Drupal\migrate_plus\Plugin\migrate\process\Dom
 */
final class DomTest extends MigrateProcessTestCase {

  /**
   * @covers ::__construct
   */
  public function testConfigMethodEmpty(): void {
    $configuration = [];
    $value = '<p>A simple paragraph.</p>';
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('The "method" must be set.');
    (new Dom($configuration, 'dom', []))
      ->transform($value, $this->migrateExecutable, $this->row, 'destinationproperty');
  }

  /**
   * @covers ::__construct
   */
  public function testConfigMethodInvalid(): void {
    $configuration = [];
    $configuration['method'] = 'invalid';
    $value = '<p>A simple paragraph.</p>';
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('The "method" must be "import" or "export".');
    (new Dom($configuration, 'dom', []))
      ->transform($value, $this->migrateExecutable, $this->row, 'destinationproperty');
  }

  /**
   * @covers ::__construct
   */
  public function testInvalidImportMethod(): void {
    $configuration['method'] = 'import';
    $configuration['import_method'] = 'invalid';
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('The "import_method" must be "html", "html5", or "xml".');
    (new Dom($configuration, 'dom', []));
  }

  /**
   * @covers ::import
   */
  public function testImportNonRoot(): void {
    $configuration = [];
    $configuration['method'] = 'import';
    $value = '<p>A simple paragraph.</p>';
    $document = (new Dom($configuration, 'dom', []))
      ->transform($value, $this->migrateExecutable, $this->row, 'destinationproperty');
    $this->assertTrue($document instanceof \DOMDocument);
  }

  /**
   * @covers ::import
   */
  public function testImportMethodHtml5(): void {
    $configuration['method'] = 'import';
    $configuration['import_method'] = 'html5';
    $value = '<p>A simple paragraph.</p>';
    $document = (new Dom($configuration, 'dom', []))
      ->transform($value, $this->migrateExecutable, $this->row, 'destinationproperty');
    $this->assertTrue($document instanceof \DOMDocument);
  }

  /**
   * @covers ::import
   */
  public function testImportMethodXml(): void {
    $configuration['method'] = 'import';
    $configuration['import_method'] = 'xml';
    $value = '<item><value>A simple paragraph.</value></item>';
    $document = (new Dom($configuration, 'dom', []))
      ->transform($value, $this->migrateExecutable, $this->row, 'destinationproperty');
    $this->assertTrue($document instanceof \DOMDocument);
  }

  /**
   * @covers ::import
   */
  public function testImportNonRootInvalidInput(): void {
    $configuration = [];
    $configuration['method'] = 'import';
    $value = [1, 1];
    $this->expectException(MigrateException::class);
    $this->expectExceptionMessage('Cannot import a non-string value.');
    (new Dom($configuration, 'dom', []))
      ->transform($value, $this->migrateExecutable, $this->row, 'destinationproperty');
  }

  /**
   * @covers ::export
   */
  public function testExportNonRoot(): void {
    $configuration = [];
    $configuration['method'] = 'export';
    $partial = '<p>A simple paragraph.</p>';
    $document = Html::load($partial);
    $value = (new Dom($configuration, 'dom', []))
      ->transform($document, $this->migrateExecutable, $this->row, 'destinationproperty');
    $this->assertEquals($value, $partial);
  }

  /**
   * @covers ::export
   */
  public function testExportNonRootInvalidInput(): void {
    $configuration = [];
    $configuration['method'] = 'export';
    $this->expectException(MigrateException::class);
    $this->expectExceptionMessage('Cannot export a "string".');
    (new Dom($configuration, 'dom', []))
      ->transform('string is not DOMDocument', $this->migrateExecutable, $this->row, 'destinationproperty');
  }

}
