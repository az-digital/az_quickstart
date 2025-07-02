<?php

namespace Drupal\Tests\webform_share\Functional;

use Drupal\Tests\webform\Functional\WebformBrowserTestBase;
use Drupal\webform_share\Element\WebformShareIframe;

/**
 * Webform share variant test.
 *
 * @group webform_share
 */
class WebformShareVariantTest extends WebformBrowserTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = [
    'test_variant_multiple',
    'test_variant_randomize',
  ];

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'webform',
    'webform_share',
  ];

  /**
   * Test variant.
   */
  public function testVariant() {
    $assert_session = $this->assertSession();

    $library = WebformShareIframe::LIBRARY;
    $version = WebformShareIframe::VERSION;

    // Enable enable share for all webforms.
    $config = \Drupal::configFactory()->getEditable('webform.settings');
    $config->set('settings.default_share', TRUE)->save();

    /* ********************************************************************** */

    // Check default letter and number.
    $this->drupalGet("/webform/test_variant_multiple/share/$library/$version");
    $assert_session->responseContains('{X}');
    $assert_session->responseContains('{0}');

    // Check variant letter [A] and number [1].
    $this->drupalGet("/webform/test_variant_multiple/share/$library/$version", ['query' => ['_webform_variant' => ['letter' => 'a', 'number' => 1]]]);
    $assert_session->responseNotContains('{X}');
    $assert_session->responseNotContains('{0}');
    $assert_session->responseContains('[A]');
    $assert_session->responseContains('[1]');

    // Check variant letter [A] and number [1].
    $this->drupalGet("/webform/test_variant_multiple/share/$library/$version", ['query' => ['letter' => 'a', 'number' => 1]]);
    $assert_session->responseNotContains('{X}');
    $assert_session->responseNotContains('{0}');
    $assert_session->responseContains('[A]');
    $assert_session->responseContains('[1]');

    // Check variant randomize script is attached to shared page.
    // @see _webform_page_attachments()
    $this->drupalGet("/webform/test_variant_randomize/share/$library/$version");
    $assert_session->responseContains('var variants = {"letter":["a","b"]};');
  }

}
