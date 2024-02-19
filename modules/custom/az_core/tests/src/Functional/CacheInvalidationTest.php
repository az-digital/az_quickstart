<?php

namespace Drupal\Tests\az_core\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests cache invalidation when nodes are updated.
 *
 * @group az_core
 */
class CacheInvalidationTest extends BrowserTestBase {

  /**
   * The profile to install as a basis for testing.
   *
   * @var string
   */
  protected $profile = 'az_quickstart';

  /**
   * @var bool
   */
  protected $strictConfigSchema = FALSE;

  /**
   * @var string
   */
  protected $defaultTheme = 'stark';

  /**
   * Modules to install.
   *
   * @var array
   */
  protected static $modules = [
    'az_core',
    'az_event',
    'az_news',
    'az_person',
  ];

  /**
   * Tests that updating a node invalidates the appropriate cache.
   */
  public function testCacheInvalidation() {
    $contentTypes = [
      'az_event',
      'az_news',
      'az_person',
    ];
    $fieldToUpdate = 'field_az_body';
    $initialValue = 'Test body';
    $updatedValue = 'Updated test body';

    foreach ($contentTypes as $contentType) {
      // Create a new node.
      $node = $this->createNode([
        'type' => $contentType,
        'title' => $this->randomString(),
        $fieldToUpdate => $initialValue,
      ]);

      // Load the node page to prime the cache.
      $this->drupalGet('node/' . $node->id());

      // Update the node.
      $node->set($fieldToUpdate, $updatedValue);
      $node->save();

      // Load the node page again to see if the cache was invalidated.
      $this->drupalGet('node/' . $node->id());

      // Assert that the updated value is present on the page.
      $this->assertSession()->pageTextContains($updatedValue);
    }
  }

}
