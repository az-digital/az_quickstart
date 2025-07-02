<?php

declare(strict_types = 1);

namespace Drupal\Tests\migrate_plus\Unit\process;

use Drupal\migrate_plus\Plugin\migrate\process\MultipleValues;
use Drupal\Tests\migrate\Unit\process\MigrateProcessTestCase;

/**
 * @coversDefaultClass \Drupal\migrate_plus\Plugin\migrate\process\MultipleValues
 * @group migrate
 */
final class MultipleValuesTest extends MigrateProcessTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    $this->plugin = new MultipleValues([], 'multiple_values', []);
    parent::setUp();
  }

  /**
   * Test input treated as multiple value output.
   */
  public function testTreatAsMultiple(): void {
    $value = ['v1', 'v2', 'v3'];
    $output = $this->plugin->transform($value, $this->migrateExecutable, $this->row, 'destinationproperty');
    $this->assertSame($output, $value);
    $this->assertTrue($this->plugin->multiple());
  }

}
