<?php

declare(strict_types = 1);

namespace Drupal\Tests\migrate_plus\Kernel;

/**
 * Verifies all tests pass with batching enabled, even batches.
 *
 * @group migrate
 */
final class MigrateTableIncrementEvenBatchTest extends MigrateTableIncrementTest {

  /**
   * The batch size to configure.
   *
   * @var int
   */
  protected static int $batchSize = 3;

}
