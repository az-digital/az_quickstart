<?php

declare(strict_types = 1);

namespace Drupal\Tests\migrate_plus\Unit\process;

use Drupal\migrate_plus\Plugin\migrate\process\StrReplace;
use Drupal\Tests\migrate\Unit\process\MigrateProcessTestCase;

/**
 * Tests the str replace process plugin.
 *
 * @group migrate
 * @coversDefaultClass \Drupal\migrate_plus\Plugin\migrate\process\StrReplace
 */
final class StrReplaceTest extends MigrateProcessTestCase {

  /**
   * Test for a simple str_replace string.
   */
  public function testStrReplace(): void {
    $configuration = [];
    $value = 'vero eos et accusam et justo vero';
    $configuration['search'] = 'et';
    $configuration['replace'] = 'that';
    $plugin = new StrReplace($configuration, 'str_replace', []);
    $actual = $plugin->transform($value, $this->migrateExecutable, $this->row, 'destinationproperty');
    $this->assertSame('vero eos that accusam that justo vero', $actual);

  }

   /**
   * Test for a simple str_replace given NULL.
   */
  public function testStrReplaceNull(): void {
    $configuration = [];
    $value = NULL;
    $configuration['search'] = '';
    $configuration['replace'] = 'that';
    $plugin = new StrReplace($configuration, 'str_replace', []);
    $actual = $plugin->transform($value, $this->migrateExecutable, $this->row, 'destinationproperty');
    $this->assertSame('', $actual);

  }

   /**
   * Test for a simple str_replace given int 1.
   */
  public function testStrReplaceInt(): void {
    $configuration = [];
    $value = 1;
    $configuration['search'] = '1';
    $configuration['replace'] = 'that';
    $plugin = new StrReplace($configuration, 'str_replace', []);
    $actual = $plugin->transform($value, $this->migrateExecutable, $this->row, 'destinationproperty');
    $this->assertSame('that', $actual);

  }


   /**
   * Test for a simple str_replace given TRUE.
   */
  public function testStrReplaceTrue(): void {
    $configuration = [];
    $value = TRUE;
    $configuration['search'] = '1';
    $configuration['replace'] = 'that';
    $plugin = new StrReplace($configuration, 'str_replace', []);
    $actual = $plugin->transform($value, $this->migrateExecutable, $this->row, 'destinationproperty');
    $this->assertSame('that', $actual);

  }


   /**
   * Test for a simple str_replace given FALSE.
   */
  public function testStrReplaceFalse(): void {
    $configuration = [];
    $value = FALSE;
    $configuration['search'] = '';
    $configuration['replace'] = 'that';
    $plugin = new StrReplace($configuration, 'str_replace', []);
    $actual = $plugin->transform($value, $this->migrateExecutable, $this->row, 'destinationproperty');
    $this->assertSame('', $actual);

  }

  /**
   * Test for case-insensitive searches.
   */
  public function testStrIreplace(): void {
    $configuration = [];
    $value = 'VERO eos et accusam et justo vero';
    $configuration['search'] = 'vero';
    $configuration['replace'] = 'that';
    $configuration['case_insensitive'] = TRUE;
    $plugin = new StrReplace($configuration, 'str_replace', []);
    $actual = $plugin->transform($value, $this->migrateExecutable, $this->row, 'destinationproperty');
    $this->assertSame('that eos et accusam et justo that', $actual);

  }

  /**
   * Test for regular expressions.
   */
  public function testPregReplace(): void {
    $configuration = [];
    $value = 'vero eos et 123 accusam et justo 123 duo';
    $configuration['search'] = '/[0-9]{3}/';
    $configuration['replace'] = 'the';
    $configuration['regex'] = TRUE;
    $plugin = new StrReplace($configuration, 'str_replace', []);
    $actual = $plugin->transform($value, $this->migrateExecutable, $this->row, 'destinationproperty');
    $this->assertSame('vero eos et the accusam et justo the duo', $actual);
  }

  /**
   * Test for InvalidArgumentException for "search" configuration.
   */
  public function testSearchInvalidArgumentException(): void {
    $configuration = [];
    $configuration['replace'] = 'that';
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('The "search" must be set.');
    new StrReplace($configuration, 'str_replace', []);
  }

  /**
   * Test for InvalidArgumentException for "replace" configuration.
   */
  public function testReplaceInvalidArgumentException(): void {
    $configuration = [];
    $configuration['search'] = 'et';
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('The "replace" must be set.');
    new StrReplace($configuration, 'str_replace', []);
  }

  /**
   * Test for multiple.
   */
  public function testIsMultiple(): void {
    $configuration = [];
    $value = [
      'vero eos et accusam et justo vero',
      'et eos vero accusam vero justo et',
    ];

    $expected = [
      'vero eos that accusam that justo vero',
      'that eos vero accusam vero justo that',
    ];
    $configuration['search'] = 'et';
    $configuration['replace'] = 'that';
    $plugin = new StrReplace($configuration, 'str_replace', []);
    $actual = $plugin->transform($value, $this->migrateExecutable, $this->row, 'destinationproperty');
    $this->assertSame($expected, $actual);

    $this->assertTrue($plugin->multiple());
  }

}
