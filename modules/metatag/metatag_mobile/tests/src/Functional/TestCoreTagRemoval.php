<?php

namespace Drupal\Tests\metatag_mobile\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\field_ui\Traits\FieldUiTestTrait;
use Drupal\Tests\metatag\Functional\MetatagHelperTrait;

/**
 * Verify that the configured defaults load as intended.
 *
 * @group metatag
 */
class TestCoreTagRemoval extends BrowserTestBase {

  // Contains helper methods.
  use FieldUiTestTrait;
  use MetatagHelperTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    // These are needed for the tests.
    'node',
    'field_ui',

    // This module.
    'metatag_mobile',
  ];

  /**
   * Use the full install profile, with the full theme.
   *
   * @var string
   */
  protected $profile = 'standard';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Log in as user 1.
    $this->loginUser1();

    // Add the Metatag field to the content type.
    $this->fieldUIAddNewField('admin/structure/types/manage/page', 'metatag', 'Metatag', 'metatag');
  }

  /**
   * Verify that core's duplicate meta tags are removed.
   */
  public function testRemovalCoreTag() {
    // Create a node that does not override core's meta tags.
    $this->drupalGet('node/add/page');
    $this->assertSession()->statusCodeEquals(200);
    $edit = [
      'title[0][value]' => 'Testing core tags',
    ];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Basic page Testing core tags has been created');

    // Verify each of the meta tags that should be removed by core..
    $xpath = $this->xpath("//meta[@name='HandheldFriendly']");
    $this->assertEquals(count($xpath), 1);
    $this->assertEquals((string) $xpath[0]->getAttribute('content'), 'true');
    $xpath = $this->xpath("//meta[@name='MobileOptimized']");
    $this->assertEquals(count($xpath), 1);
    $this->assertEquals((string) $xpath[0]->getAttribute('content'), 'width');
    $xpath = $this->xpath("//meta[@name='viewport']");
    $this->assertEquals(count($xpath), 1);
    $this->assertEquals((string) $xpath[0]->getAttribute('content'), 'width=device-width, initial-scale=1.0');

    // Create a second node that overrides core's meta tags.
    $this->drupalGet('node/add/page');
    $this->assertSession()->statusCodeEquals(200);
    $edit = [
      'title[0][value]' => 'Testing removal of core tags',
      'field_metatag[0][mobile][handheldfriendly]' => 'handheld friendly tag',
      'field_metatag[0][mobile][mobileoptimized]' => 'mobile optimized tag',
      'field_metatag[0][mobile][viewport]' => 'viewport tag',
    ];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Basic page Testing removal of core tags has been created');

    // Verify that Metatag's tags are showing correctly.
    $xpath = $this->xpath("//meta[@name='HandheldFriendly']");
    $this->assertEquals(count($xpath), 1);
    $this->assertEquals((string) $xpath[0]->getAttribute('content'), 'handheld friendly tag');
    $xpath = $this->xpath("//meta[@name='MobileOptimized']");
    $this->assertEquals(count($xpath), 1);
    $this->assertEquals((string) $xpath[0]->getAttribute('content'), 'mobile optimized tag');
    $xpath = $this->xpath("//meta[@name='viewport']");
    $this->assertEquals(count($xpath), 1);
    $this->assertEquals((string) $xpath[0]->getAttribute('content'), 'viewport tag');
  }

}
