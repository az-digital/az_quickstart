<?php

declare(strict_types=1);

namespace Drupal\Tests\redirect_404\Unit;

use Drupal\Core\Database\Connection;
use Drupal\Core\Language\LanguageInterface;
use Drupal\redirect_404\SqlRedirectNotFoundStorage;
use Drupal\Tests\UnitTestCase;

/**
 * Tests that overly long paths aren't logged.
 *
 * @group redirect_404
 */
class SqlRedirectNotFoundStorageTest extends UnitTestCase {

  /**
   * Mock database connection.
   *
   * @var \Drupal\Core\Database\Connection|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $database;

  /**
   * Mock config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->database = $this->createMock(Connection::class);
  }

  /**
   * Tests that long paths aren't stored in the database.
   */
  public function testLongPath() {
    $this->database->expects($this->never())
      ->method('merge');
    $storage = new SqlRedirectNotFoundStorage($this->database, $this->getConfigFactoryStub());
    $storage->logRequest($this->randomMachineName(SqlRedirectNotFoundStorage::MAX_PATH_LENGTH + 1), LanguageInterface::LANGCODE_DEFAULT);
  }

  /**
   * Tests that invalid UTF-8 paths are not stored in the database.
   */
  public function testInvalidUtf8Path() {
    $this->database->expects($this->never())
      ->method('merge');
    $storage = new SqlRedirectNotFoundStorage($this->database, $this->getConfigFactoryStub());
    $storage->logRequest("Caf\xc3", LanguageInterface::LANGCODE_DEFAULT);
  }

  /**
   * Tests that all logs are kept if row limit config is "All".
   */
  public function testPurgeOldRequests() {
    $this->configFactory = $this->getConfigFactoryStub(
      [
        'redirect_404.settings' => [
          'row_limit' => 0,
        ],
      ]
    );
    $storage = new SqlRedirectNotFoundStorage($this->database, $this->configFactory);
    $storage->purgeOldRequests();
    $this->database->expects($this->never())
      ->method('select');
  }

}
