<?php

declare(strict_types = 1);

namespace Drupal\migrate_tools_test\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SourcePluginBase;

/**
 * A simple migrate source for testing exception handling.
 *
 * @MigrateSource(
 *   id = "migrate_exception_source_test",
 *   source_module = "migrate_tools_test"
 * )
 */
final class ExceptionThrowingTestSource extends SourcePluginBase {

  /**
   * {@inheritdoc}
   */
  public function fields(): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function __toString(): string {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function getIds(): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  protected function initializeIterator(): \ArrayIterator {
    return new \ArrayIterator();
  }

  /**
   * {@inheritdoc}
   */
  public function rewind(): void {
    throw new \Exception('Rewind Failure');
  }

}
