<?php

namespace Drupal\Tests\webform\Functional;

use Drupal\webform\Entity\Webform;

/**
 * Tests for webform config translation API.
 *
 * @group webform
 */
class WebformEntityTranslationApiTest extends WebformBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['webform_test_translation'];

  /**
   * Tests webform translation API.
   */
  public function testTranslationApu() {

    /* ********************************************************************** */
    // Untranslated (English) webform.
    /* ********************************************************************** */

    /** @var \Drupal\webform\WebformInterface $untranslated_webform */
    $untranslated_webform = Webform::load('contact');

    // Check has translation for untranslated (English) webform.
    $this->assertTrue($untranslated_webform->hasTranslation('en'));
    $this->assertFalse($untranslated_webform->hasTranslation('es'));

    // Check has translations for untranslated (English) webform.
    $this->assertFalse($untranslated_webform->hasTranslations());

    // Check get translations for untranslated (English) webform.
    $this->assertEquals(['en'], array_keys($untranslated_webform->getTranslationLanguages()));
    $this->assertFalse($untranslated_webform->hasTranslation('es'));

    /* ********************************************************************** */
    // Translated (English & Spanish) webform.
    /* ********************************************************************** */

    /** @var \Drupal\webform\WebformInterface $webform */
    $translated_webform = Webform::load('test_translation');

    // Check has translation for translated (English & Spanish) webform.
    $this->assertTrue($translated_webform->hasTranslation('en'));
    $this->assertTrue($translated_webform->hasTranslation('es'));

    // Check has translations for translated (English & Spanish) webform.
    $this->assertTrue($translated_webform->hasTranslations());

    // Check get translations for translated (English & Spanish) webform.
    $this->assertEquals(['en', 'es'], array_keys($translated_webform->getTranslationLanguages()));
  }

}
