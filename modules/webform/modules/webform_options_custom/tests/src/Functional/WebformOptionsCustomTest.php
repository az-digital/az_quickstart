<?php

namespace Drupal\Tests\webform_options_custom\Functional;

use Drupal\Tests\webform\Functional\WebformBrowserTestBase;
use Drupal\webform\Entity\Webform;
use Drupal\webform_options_custom\Entity\WebformOptionsCustom;

/**
 * Webform options custom test.
 *
 * @group webform_options_custom
 */
class WebformOptionsCustomTest extends WebformBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'webform',
    'webform_options_custom',
    'webform_options_custom_test',
  ];

  /**
   * Test options custom.
   */
  public function testOptionsCustom() {
    $assert_session = $this->assertSession();

    $webform = Webform::load('test_element_options_custom_html');

    /* ********************************************************************** */
    // Webform custom options element.
    /* ********************************************************************** */

    $this->drupalGet('/webform/test_element_options_custom_html');

    // Check that 'data-option-value' is added to the basic HTML markup.
    // @see webform_options_custom.webform_options_custom.test_html.yml
    $assert_session->responseContains('<div data-id="one" data-name="One" data-option-value="one">One</div>');
    $assert_session->responseContains('<div data-id="two" data-name="Two" data-option-value="two">Two</div>');
    $assert_session->responseContains('<div data-id="three" data-name="Three" data-option-value="three">Three</div>');

    // Check that 'data-option-value' is added to the advanced HTML markup.
    // @see webform_options_custom.webform_options_custom.test_html_advanced.yml
    $assert_session->responseContains('<div data-id="a" data-name="A -- This is the letter A" data-option-value="a">A</div>');
    $assert_session->responseContains('<div data-name="B" data-option-value="b">B</div>');
    $assert_session->responseContains('<div data-id="c" data-name="C" data-option-value="c">C</div>');

    // Check advanced HTML descriptions which all confirm that descriptions
    // can be overridden.
    $assert_session->responseContains('data-descriptions="{&quot;c&quot;:&quot;This is the letter C. [element#options]&quot;,&quot;b&quot;:&quot;\u003Cem\u003EThis is the letter B\u003C\/em\u003E alert(\u0027XSS\u0027);. [entity#options]&quot;,&quot;a&quot;:&quot;This is the letter A&quot;}"');

    // Check <script> tags are removed from descriptions.
    // @see \Drupal\webform_options_custom\Element\WebformOptionsCustom::processWebformOptionsCustom
    $assert_session->responseNotContains('\u003Cscript\u003Ealert(\u0027XSS\u0027);\u003C\/script\u003E');

    // Check validation.
    $this->postSubmission($webform);
    $assert_session->responseContains('webform_options_custom_html field is required.');
    $assert_session->responseContains('webform_options_custom_html_advanced field is required.');

    // Check preview.
    $this->postSubmission($webform, [
      'webform_options_custom_html[select]' => 'one',
      'webform_options_custom_html_advanced[select][]' => 'a',
    ], 'Preview');
    $assert_session->responseMatches('#<label>webform_options_custom_html</label>\s*One\s*</div>#');
    $assert_session->responseMatches('#<label>webform_options_custom_html_advanced</label>\s*A\s*</div>#');

    // Check processing.
    $this->postSubmission($webform, [
      'webform_options_custom_html[select]' => 'one',
      'webform_options_custom_html_advanced[select][]' => 'a',
    ]);
    $assert_session->responseContains('webform_options_custom_html: one
webform_options_custom_html_advanced:
  - a');

    // Check CSS asset.
    $this->drupalGet('/webform/css/test_element_options_custom_html/custom.css');
    $assert_session->responseContains('.webform-options-custom--test-html-advanced [data-option-value]');

    // Check JavaScript asset.
    $this->drupalGet('/webform/javascript/test_element_options_custom_html/custom.js');
    $assert_session->responseContains("window.console && window.console.log('Test: HTML advanced loaded.');");

    /* ********************************************************************** */
    // Webform custom options entity.
    /* ********************************************************************** */

    $this->drupalLogin($this->rootUser);

    // Get basic HTML with default settings.
    $this->drupalGet('/admin/structure/webform/options/custom/manage/test_html/preview');

    // Check 'data-fill' attribute.
    $this->assertCssSelect('.webform-options-custom--test-html[data-fill]');

    // Check 'data-tooltip' attribute.
    $this->assertCssSelect('.webform-options-custom--test-html[data-tooltip]');

    // Check no 'data-select-hidden' attribute.
    $this->assertNoCssSelect('.webform-options-custom--test-html[data-select-hidden]');

    // Update basic HTML settings.
    $webform_options_custom = WebformOptionsCustom::load('test_html');
    $webform_options_custom->set('fill', FALSE);
    $webform_options_custom->set('tooltip', FALSE);
    $webform_options_custom->set('show_select', FALSE);
    $webform_options_custom->save();

    // Get basic HTML with updated settings.
    $this->drupalGet('/admin/structure/webform/options/custom/manage/test_html/preview');

    // Check no 'data-fill' attribute.
    $this->assertNoCssSelect('.webform-options-custom--test-html[data-fill]');

    // Check no 'data-tooltip' attribute.
    $this->assertNoCssSelect('.webform-options-custom--test-html[data-tooltip]');

    // Check 'data-select-hidden' attribute.
    $this->assertCssSelect('.webform-options-custom--test-html[data-select-hidden]');

    /* ********************************************************************** */
    // Webform custom options Twig.
    /* ********************************************************************** */

    // Get preview has 3 options.
    $this->drupalGet('/admin/structure/webform/options/custom/manage/test_twig/preview');
    $assert_session->responseContains('<td data-option-value="1" style="text-align:center">1</td>');
    $assert_session->responseContains('<td data-option-value="2" style="text-align:center">2</td>');
    $assert_session->responseContains('<td data-option-value="3" style="text-align:center">3</td>');
    $assert_session->responseNotContains('<td data-option-value="4" style="text-align:center">4</td>');
    $assert_session->responseNotContains('<td data-option-value="5" style="text-align:center">5</td>');

    // Get instance has 5 options.
    $this->drupalGet('/webform/test_element_options_custom_twig');
    $assert_session->responseContains('<td data-option-value="1" style="text-align:center">1</td>');
    $assert_session->responseContains('<td data-option-value="2" style="text-align:center">2</td>');
    $assert_session->responseContains('<td data-option-value="3" style="text-align:center">3</td>');
    $assert_session->responseContains('<td data-option-value="4" style="text-align:center">4</td>');
    $assert_session->responseContains('<td data-option-value="5" style="text-align:center">5</td>');
  }

}
