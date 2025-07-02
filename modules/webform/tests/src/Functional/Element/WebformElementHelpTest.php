<?php

namespace Drupal\Tests\webform\Functional\Element;

use Drupal\Component\Utility\DeprecationHelper;

/**
 * Tests for element help.
 *
 * @group webform
 */
class WebformElementHelpTest extends WebformElementBrowserTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_help'];

  /**
   * Test element help.
   */
  public function testHelp() {
    $assert_session = $this->assertSession();

    $this->drupalGet('/webform/test_element_help');

    // Check basic help.
    $assert_session->responseContains('<label for="edit-help">help<span class="webform-element-help js-webform-element-help" role="tooltip" tabindex="0" aria-label="help" data-webform-help="&lt;div class=&quot;webform-element-help--title&quot;&gt;help&lt;/div&gt;&lt;div class=&quot;webform-element-help--content&quot;&gt;{This is an example of help}&lt;/div&gt;"><span aria-hidden="true">?</span></span>');

    // Check help with required.
    $assert_session->responseContains('<label for="edit-help-required" class="js-form-required form-required">help_required<span class="webform-element-help js-webform-element-help" role="tooltip" tabindex="0" aria-label="help_required" data-webform-help="&lt;div class=&quot;webform-element-help--title&quot;&gt;help_required&lt;/div&gt;&lt;div class=&quot;webform-element-help--content&quot;&gt;{This is an example of help for a required element}&lt;/div&gt;"><span aria-hidden="true">?</span></span>');

    // Check help with custom title.
    $assert_session->responseContains('<label for="edit-help-title">help_title<span class="webform-element-help js-webform-element-help" role="tooltip" tabindex="0" aria-label="{Help custom title}" data-webform-help="&lt;div class=&quot;webform-element-help--title&quot;&gt;{Help custom title}&lt;/div&gt;&lt;div class=&quot;webform-element-help--content&quot;&gt;{This is an example of help with a custom help title}&lt;/div&gt;"><span aria-hidden="true">?</span></span>');

    // Check help with HTML markup.
    $assert_session->responseContains('<label for="edit-help-html">help_html<span class="webform-element-help js-webform-element-help" role="tooltip" tabindex="0" aria-label="help_html" data-webform-help="&lt;div class=&quot;webform-element-help--title&quot;&gt;help_html&lt;/div&gt;&lt;div class=&quot;webform-element-help--content&quot;&gt;{This is an example of help with &lt;b&gt;HTML markup&lt;/b&gt;}&lt;/div&gt;"><span aria-hidden="true">?</span></span>');

    // Check help with XSS.
    $assert_session->responseContains('<label for="edit-help-xss">help_xss<span class="webform-element-help js-webform-element-help" role="tooltip" tabindex="0" aria-label="{This is an example of help title with &lt;b&gt;XSS &lt;script&gt;alert(&quot;XSS&quot;)&lt;/script&gt;&lt;/b&gt; &lt;img src=&quot;x&quot; onerror=&quot;confirm(document.domain)&quot; /&gt;}" data-webform-help="&lt;div class=&quot;webform-element-help--title&quot;&gt;{This is an example of help title with &lt;b&gt;XSS alert(&quot;XSS&quot;)&lt;/b&gt; &lt;img src=&quot;x&quot; /&gt;}&lt;/div&gt;&lt;div class=&quot;webform-element-help--content&quot;&gt;{This is an example of help content with &lt;b&gt;XSS alert(&quot;XSS&quot;)&lt;/b&gt; &lt;img src=&quot;x&quot; /&gt;}&lt;/div&gt;"><span aria-hidden="true">?</span></span></label>');

    // Check help empty.
    $assert_session->responseContains('<label for="edit-help-empty">help_empty</label>');

    // Check help with inline title.
    $assert_session->responseContains('<label for="edit-help-checkbox" class="option">help_checkbox<span class="webform-element-help js-webform-element-help" role="tooltip" tabindex="0" aria-label="help_checkbox" data-webform-help="&lt;div class=&quot;webform-element-help--title&quot;&gt;help_checkbox&lt;/div&gt;&lt;div class=&quot;webform-element-help--content&quot;&gt;{This is an example of help}&lt;/div&gt;"><span aria-hidden="true">?</span></span>');
    $assert_session->responseContains('<label for="edit-help-inline"><span class="webform-element-help js-webform-element-help" role="tooltip" tabindex="0" aria-label="help_inline" data-webform-help="&lt;div class=&quot;webform-element-help--title&quot;&gt;help_inline&lt;/div&gt;&lt;div class=&quot;webform-element-help--content&quot;&gt;{This is an example of help with an inline title}&lt;/div&gt;"><span aria-hidden="true">?</span></span>');

    // Check radios (fieldset).
    $assert_session->responseContains('<span class="fieldset-legend">help_radios<span class="webform-element-help js-webform-element-help" role="tooltip" tabindex="0" aria-label="help_radios" data-webform-help="&lt;div class=&quot;webform-element-help--title&quot;&gt;help_radios&lt;/div&gt;&lt;div class=&quot;webform-element-help--content&quot;&gt;{This is an example of help for radio buttons}&lt;/div&gt;"><span aria-hidden="true">?</span></span>');

    // Check fieldset.
    $assert_session->responseContains('<span class="fieldset-legend">help_radios<span class="webform-element-help js-webform-element-help" role="tooltip" tabindex="0" aria-label="help_radios" data-webform-help="&lt;div class=&quot;webform-element-help--title&quot;&gt;help_radios&lt;/div&gt;&lt;div class=&quot;webform-element-help--content&quot;&gt;{This is an example of help for radio buttons}&lt;/div&gt;"><span aria-hidden="true">?</span></span>');

    // Check details.
    DeprecationHelper::backwardsCompatibleCall(
      currentVersion: \Drupal::VERSION,
      deprecatedVersion: '10.3',
      currentCallable: fn() => $assert_session->responseContains('<summary role="button" aria-controls="edit-help-details" aria-expanded="false">help_details<span class="webform-element-help js-webform-element-help" role="tooltip" tabindex="0" aria-label="help_details" data-webform-help="&lt;div class=&quot;webform-element-help--title&quot;&gt;help_details&lt;/div&gt;&lt;div class=&quot;webform-element-help--content&quot;&gt;{This is an example of help for a details element}&lt;/div&gt;"><span aria-hidden="true">?</span>'),
      deprecatedCallable: fn() => $assert_session->responseContains('<summary role="button" aria-controls="edit-help-details" aria-expanded="false" aria-pressed="false">help_details<span class="webform-element-help js-webform-element-help" role="tooltip" tabindex="0" aria-label="help_details" data-webform-help="&lt;div class=&quot;webform-element-help--title&quot;&gt;help_details&lt;/div&gt;&lt;div class=&quot;webform-element-help--content&quot;&gt;{This is an example of help for a details element}&lt;/div&gt;"><span aria-hidden="true">?</span>'),
    );

    // Check section.
    $assert_session->responseContains('<h2 class="webform-section-title">help_section<span class="webform-element-help js-webform-element-help" role="tooltip" tabindex="0" aria-label="help_section" data-webform-help="&lt;div class=&quot;webform-element-help--title&quot;&gt;help_section&lt;/div&gt;&lt;div class=&quot;webform-element-help--content&quot;&gt;{This is an example of help for a section element}&lt;/div&gt;"><span aria-hidden="true">?</span></span>');

    // Check help display title after.
    $assert_session->responseContains('<label for="edit-help-after-title">help_after_title<span class="webform-element-help js-webform-element-help" role="tooltip" tabindex="0" aria-label="help_after_title" data-webform-help="&lt;div class=&quot;webform-element-help--title&quot;&gt;help_after_title&lt;/div&gt;&lt;div class=&quot;webform-element-help--content&quot;&gt;{This is an example of help}&lt;/div&gt;"><span aria-hidden="true">?</span></span></label>');

    // Check help display title before.
    $assert_session->responseContains('<label for="edit-help-before-title"><span class="webform-element-help js-webform-element-help" role="tooltip" tabindex="0" aria-label="help_before_title" data-webform-help="&lt;div class=&quot;webform-element-help--title&quot;&gt;help_before_title&lt;/div&gt;&lt;div class=&quot;webform-element-help--content&quot;&gt;{This is an example of help}&lt;/div&gt;"><span aria-hidden="true">?</span></span>help_before_title</label>');

    // Check help display element after.
    $assert_session->responseContains('<span class="field-suffix"><span class="webform-element-help js-webform-element-help" role="tooltip" tabindex="0" aria-label="help_after_element" data-webform-help="&lt;div class=&quot;webform-element-help--title&quot;&gt;help_after_element&lt;/div&gt;&lt;div class=&quot;webform-element-help--content&quot;&gt;{This is an example of help}&lt;/div&gt;"><span aria-hidden="true">?</span></span></span>');

    // Check help display element before.
    $assert_session->responseContains('<span class="field-prefix"><span class="webform-element-help js-webform-element-help" role="tooltip" tabindex="0" aria-label="help_before_element" data-webform-help="&lt;div class=&quot;webform-element-help--title&quot;&gt;help_before_element&lt;/div&gt;&lt;div class=&quot;webform-element-help--content&quot;&gt;{This is an example of help}&lt;/div&gt;"><span aria-hidden="true">?</span></span></span>');
  }

}
