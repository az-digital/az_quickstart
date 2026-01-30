<?php

namespace Drupal\Tests\az_core\Functional;

use Drupal\Tests\BrowserTestBase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests cache invalidation when nodes are updated.
 */
#[Group('az_core')]
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
    'az_course',
    'az_publication',
    'az_carousel',
  ];

  /**
   * Tests that updating a node invalidates the appropriate cache.
   */
  public function testCacheInvalidation() {
    $contentTypeFields = [
      'az_event' => 'field_az_body',
      'az_news' => 'field_az_body',
      'az_person' => 'field_az_body',
      'az_course' => 'field_az_course_description',
      'az_publication' => 'field_az_publication_abstract',
      'az_carousel_item' => 'field_az_carousel_short_summary',
    ];
    $initialValue = 'Test value';
    $updatedValue = 'Updated test value';

    foreach ($contentTypeFields as $contentType => $fieldToUpdate) {
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
