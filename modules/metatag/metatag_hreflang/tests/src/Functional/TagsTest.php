<?php

namespace Drupal\Tests\metatag_hreflang\Functional;

use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Tests\metatag\Functional\TagsTestBase;

/**
 * Tests that each of the Metatag hreflang tags work correctly.
 *
 * @group metatag
 */
class TagsTest extends TagsTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['language', 'metatag_hreflang'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Enable additional languages that will be used in the test coverage.
    foreach (['es', 'fr'] as $langcode) {
      ConfigurableLanguage::createFromLangcode($langcode)->save();
    }

    drupal_flush_all_caches();
  }

}
