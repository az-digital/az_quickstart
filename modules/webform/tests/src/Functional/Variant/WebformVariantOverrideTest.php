<?php

namespace Drupal\Tests\webform\Functional\Variant;

use Drupal\Tests\webform\Functional\WebformBrowserTestBase;
use Drupal\webform\Entity\Webform;

/**
 * Tests for the webform variant override.
 *
 * @group webform
 */
class WebformVariantOverrideTest extends WebformBrowserTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_variant_override'];

  /**
   * Test variant override.
   */
  public function testVariantOverride() {
    $assert_session = $this->assertSession();

    $webform = Webform::load('test_variant_override');

    $this->drupalLogin($this->rootUser);

    // Check override settings enables preview.
    $this->drupalGet('/webform/test_variant_override');
    $assert_session->responseNotContains('<div class="webform-progress">');
    $this->drupalGet('/webform/test_variant_override', ['query' => ['_webform_variant[variant]' => 'settings']]);
    $assert_session->responseContains('<div class="webform-progress">');

    // Check override elements adds placeholder.
    $this->drupalGet('/webform/test_variant_override');
    $assert_session->responseNotContains('placeholder="This is a placeholder"');
    $this->drupalGet('/webform/test_variant_override', ['query' => ['_webform_variant[variant]' => 'elements']]);
    $assert_session->responseContains('placeholder="This is a placeholder"');

    // Check override handlers enables debug.
    $this->postSubmission($webform);
    $assert_session->responseNotContains('Submitted values are:');
    $this->postSubmission($webform, [], NULL, ['query' => ['_webform_variant[variant]' => 'handlers']]);
    $assert_session->responseContains('Submitted values are:');

    // Check override no results changes the confirmation message.
    $this->postSubmission($webform);
    $assert_session->responseContains('New submission added to Test: Variant override.');
    $assert_session->responseNotContains('No results were saved to the database.');
    $this->postSubmission($webform, [], NULL, ['query' => ['_webform_variant[variant]' => 'No-Results']]);
    $assert_session->responseNotContains('New submission added to Test: Variant override.');
    $assert_session->responseContains('No results were saved to the database.');

    // Check overriding form properties such as method and action.
    $this->drupalGet('/webform/test_variant_override', ['query' => ['_webform_variant[variant]' => 'Custom-Form-Properties']]);
    $assert_session->responseContains('action="https://drupal.org" method="get"');

    // Check missing variant instance displays a warning.
    $this->drupalGet('/webform/test_variant_override');
    $this->drupalGet('/webform/test_variant_override', ['query' => ['_webform_variant[variant]' => 'missing']]);
    $assert_session->responseContains("The 'missing' variant id is missing for the 'variant (variant)' variant type. <strong>No variant settings have been applied.</strong>");
  }

}
