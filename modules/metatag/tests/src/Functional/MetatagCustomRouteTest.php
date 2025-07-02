<?php

namespace Drupal\Tests\metatag\Functional;

use Drupal\entity_test\Entity\EntityTest;
use Drupal\metatag\Entity\MetatagDefaults;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests custom route integration.
 *
 * @group metatag
 *
 * @see hook_metatag_route_entity()
 */
class MetatagCustomRouteTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    // Dependencies.
    'token',
    // Metatag itself.
    'metatag',
    // This module will be used to load a static page which will inherit the
    // global defaults, without loading values from other configs.
    'metatag_test_custom_route',
    'entity_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Run tests on the custom route.
   */
  public function testCustomRoute() {
    $entity_test = EntityTest::create([
      'name' => 'test name',
      'type' => 'entity_test',
    ]);
    $entity_test->save();

    MetatagDefaults::create([
      'id' => 'entity_test__entity_test',
      'tags' => [
        'keywords' => 'test',
      ],
    ])->save();

    $this->drupalGet('metatag_test_custom_route/' . $entity_test->id());
    $this->assertSession()->statusCodeEquals(200);
    $xpath = $this->xpath("//meta[@name='keywords']");
    $this->assertCount(1, $xpath);
    $this->assertEquals('test', $xpath[0]->getAttribute('content'));
  }

}
