<?php

namespace Drupal\Tests\webform\Functional\Element;

use Drupal\Component\Utility\DeprecationHelper;

/**
 * Tests for details element.
 *
 * @group webform
 */
class WebformElementDetailsTest extends WebformElementBrowserTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_details'];

  /**
   * Test details element.
   */
  public function testDetails() {
    $assert_session = $this->assertSession();

    $this->drupalGet('/webform/test_element_details');

    // Check details with help, field prefix, field suffix, description,
    // and more. Also, check that invalid 'required' and 'aria-required'
    // attributes are removed.
    $assert_session->responseContains('<details data-webform-key="details" data-drupal-selector="edit-details" aria-describedby="edit-details--description" id="edit-details" class="js-form-wrapper form-wrapper required webform-element-help-container--title webform-element-help-container--title-after" open="open">');
    DeprecationHelper::backwardsCompatibleCall(
      currentVersion: \Drupal::VERSION,
      deprecatedVersion: '10.3',
      currentCallable: fn() => $assert_session->responseContains('<summary role="button" aria-controls="edit-details" aria-expanded="true" class="js-form-required form-required">details<span class="webform-element-help js-webform-element-help" role="tooltip" tabindex="0" aria-label="details" data-webform-help="&lt;div class=&quot;webform-element-help--title&quot;&gt;details&lt;/div&gt;&lt;div class=&quot;webform-element-help--content&quot;&gt;This is help text.&lt;/div&gt;"><span aria-hidden="true">?</span>'),
      deprecatedCallable: fn() => $assert_session->responseContains('<summary role="button" aria-controls="edit-details" aria-expanded="true" aria-pressed="true" class="js-form-required form-required">details<span class="webform-element-help js-webform-element-help" role="tooltip" tabindex="0" aria-label="details" data-webform-help="&lt;div class=&quot;webform-element-help--title&quot;&gt;details&lt;/div&gt;&lt;div class=&quot;webform-element-help--content&quot;&gt;This is help text.&lt;/div&gt;"><span aria-hidden="true">?</span>'),
    );
    $assert_session->responseContains('<div id="edit-details--description" class="webform-element-description">This is a description.</div>');
    $assert_session->responseContains('<div id="edit-details--more" class="js-webform-element-more webform-element-more">');

    // Check details title_display: invisible.
    DeprecationHelper::backwardsCompatibleCall(
      currentVersion: \Drupal::VERSION,
      deprecatedVersion: '10.3',
      currentCallable: fn() => $assert_session->responseContains('<summary role="button" aria-controls="edit-details-title-invisible" aria-expanded="false"><span class="visually-hidden">Details title invisible</span></summary>'),
      deprecatedCallable: fn() => $assert_session->responseContains('<summary role="button" aria-controls="edit-details-title-invisible" aria-expanded="false" aria-pressed="false"><span class="visually-hidden">Details title invisible</span></summary>'),
    );
  }

}
