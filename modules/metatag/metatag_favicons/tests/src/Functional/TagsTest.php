<?php

namespace Drupal\Tests\metatag_favicons\Functional;

use Drupal\Component\Serialization\Json;
use Drupal\Tests\field_ui\Traits\FieldUiTestTrait;
use Drupal\Tests\metatag\Functional\TagsTestBase;

/**
 * Tests that each of the Metatag Favicons tags work correctly.
 *
 * @group metatag
 */
class TagsTest extends TagsTestBase {

  use FieldUiTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['metatag_favicons', 'field_ui'];

  /**
   * Legacy data for the MaskIcon tag just stored a single string, not an array.
   */
  public function testMaskIconLegacy() {
    $this->loginUser1();

    // Add a metatag field to the entity type test_entity.
    $this->createContentType(['type' => 'page']);
    $this->fieldUIAddNewField('admin/structure/types/manage/page', 'metatag', 'Metatag', 'metatag');

    // Create a demo node of this content type so it can be tested.
    $this->drupalGet('node/add/page');
    $this->assertSession()->statusCodeEquals(200);
    $edit = [
      'title[0][value]' => 'Hello, world!',
      'field_metatag[0][favicons][mask_icon][href]' => 'mask_icon_href',
    ];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('page Hello, World! has been created.');
    $xpath = $this->xpath("//link[@rel='mask-icon' and @href='mask_icon_href']");
    self::assertEquals((string) $xpath[0]->getAttribute('href'), 'mask_icon_href');

    // Update the database record.
    \Drupal::database()->update('node__field_metatag')
      ->fields([
        'field_metatag_value' => Json::encode([
          'mask_icon' => 'mask_icon_href',
        ]),
      ])
      ->condition('entity_id', 1)
      ->execute();

    // Clear caches to make sure the node is reloaded.
    drupal_flush_all_caches();

    // Reload the node.
    $this->drupalGet('node/1');
    $this->assertSession()->statusCodeEquals(200);

    // Confirm the mask icon value.
    $xpath = $this->xpath("//link[@rel='mask-icon' and @href='mask_icon_href']");
    self::assertEquals((string) $xpath[0]->getAttribute('href'), 'mask_icon_href');
  }

}
