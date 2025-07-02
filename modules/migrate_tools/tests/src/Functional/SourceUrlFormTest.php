<?php

declare(strict_types = 1);

namespace Drupal\Tests\migrate_tools\Functional;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Tests\BrowserTestBase;

/**
 * Test the URL column alias edit form.
 *
 * @group migrate_tools
 */
final class SourceUrlFormTest extends BrowserTestBase {
  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'migrate',
    'migrate_plus',
    'migrate_tools',
    'url_source_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The migration group for the test migration.
   *
   * @var string
   */
  private string $group;

  /**
   * The test migration id.
   *
   * @var string
   */
  private string $migration;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Log in as user 1. Migrations in the UI can only be performed as user 1.
    $this->drupalLogin($this->rootUser);

    // Select the group and migration to test.
    $this->group = 'url_test';
    $this->migration = 'url_404_source_test';
  }

  /**
   * Tests the form ensure graceful 404 handling.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testSourceUrl404Form(): void {
    // Assert the test migration is listed.
    $this->drupalGet("/admin/structure/migrate/manage/{$this->group}/migrations");
    $session = $this->assertSession();
    $session->responseContains('Test 404 URLs in the UI');
  }

}
