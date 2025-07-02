<?php

declare(strict_types=1);

namespace Drupal\Tests\google_tag\Kernel;

/**
 * Helper trait to assert drupal settings for google_tag.
 */
trait AssertGoogleTagTrait {

  /**
   * Helper method for asserting gtag in drupal settings.
   *
   * @param array $events
   *   Gtag drupal settings array to assert.
   */
  protected function assertGoogleTagEvents(array $events): void {
    if ($events === []) {
      self::assertArrayNotHasKey('gtag', $this->drupalSettings);
    }
    else {
      self::assertArrayHasKey('gtag', $this->drupalSettings);
      $gtag_data = $this->drupalSettings['gtag'];
      self::assertEquals($events, $gtag_data['events']);
    }
  }

}
