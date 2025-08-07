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
   * {@inheritdoc}
   */
  protected $profile = 'az_quickstart';

  /**
   * {@inheritdoc}
   */
  protected $strictConfigSchema = FALSE;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
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
