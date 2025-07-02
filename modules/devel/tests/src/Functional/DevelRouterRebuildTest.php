<?php

namespace Drupal\Tests\devel\Functional;

/**
 * Tests routes rebuild.
 *
 * @group devel
 */
class DevelRouterRebuildTest extends DevelBrowserTestBase {

  /**
   * Test routes rebuild.
   */
  public function testRouterRebuildConfirmForm(): void {
    // Reset the state flag.
    \Drupal::state()->set('devel_test_route_rebuild', NULL);

    $this->drupalGet('devel/menu/reset');
    $this->assertSession()->statusCodeEquals(403);

    $this->drupalLogin($this->adminUser);

    $this->drupalGet('devel/menu/reset');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Are you sure you want to rebuild the router?');
    $route_rebuild_state = \Drupal::state()->get('devel_test_route_rebuild');
    $this->assertEmpty($route_rebuild_state);

    $this->submitForm([], 'Rebuild');
    $this->assertSession()->pageTextContains('The router has been rebuilt.');
    $route_rebuild_state = \Drupal::state()->get('devel_test_route_rebuild');
    $this->assertEquals('Router rebuild fired', $route_rebuild_state);
  }

}
