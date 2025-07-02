<?php

namespace Drupal\Tests\migmag_process\Kernel;

use Drupal\Tests\migrate\Kernel\MigrateStubTest;

/**
 * Tests migmag_process.lookup.stub's compatibility with core's stub service.
 *
 * @group migmag_process
 */
class MigMagMigrateStubCompatibilityTest extends MigrateStubTest {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'migmag_process',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->migrateStub = $this->container->get('migmag_process.lookup.stub');
  }

}
