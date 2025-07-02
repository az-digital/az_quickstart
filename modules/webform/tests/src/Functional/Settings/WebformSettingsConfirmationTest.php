<?php

namespace Drupal\Tests\webform\Functional\Settings;

use Drupal\Tests\webform\Functional\WebformBrowserTestBase;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;

/**
 * Tests for webform submission form confirmation.
 *
 * @group webform
 */
class WebformSettingsConfirmationTest extends WebformBrowserTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = [
    'test_confirmation_message',
    'test_confirmation_modal',
    'test_confirmation_inline',
    'test_confirmation_page',
    'test_confirmation_page_custom',
    'test_confirmation_url',
    'test_confirmation_url_message',
    'test_confirmation_none',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Set page.front (aka <front>) to /node instead of /user/login.
    \Drupal::configFactory()->getEditable('system.site')->set('page.front', '/node')->save();
  }

  /**
   * Tests webform submission form confirmation.
   */
  public function testConfirmation() {
    $assert_session = $this->assertSession();

    // Login the admin user.
    $this->drupalLogin($this->rootUser);

    /* Test confirmation message (confirmation_type=message) */

    $webform_confirmation_message = Webform::load('test_confirmation_message');

    // Check confirmation message.
    $this->postSubmission($webform_confirmation_message);
    $assert_session->responseContains('This is a <b>custom</b> confirmation message.');
    $assert_session->responseNotContains('New submission added to <em class="placeholder">Test: Confirmation: Message</em>');
    $assert_session->addressEquals('webform/test_confirmation_message');

    // Check confirmation page with custom query parameters.
    $sid = $this->postSubmission($webform_confirmation_message, [], NULL, ['query' => ['custom' => 'param']]);
    $assert_session->addressEquals('webform/test_confirmation_message?custom=param');

    // Sleep for 1 second to ensure the submission's timestamp indicates
    // it was update.
    sleep(1);

    // Check default message when submission is updated.
    $this->drupalGet("/admin/structure/webform/manage/test_confirmation_message/submission/$sid/edit");
    $this->submitForm([], 'Save');
    $assert_session->responseNotContains('This is a <b>custom</b> confirmation message. (test: )');
    $assert_session->responseContains('Submission updated in <em class="placeholder">Test: Confirmation: Message</em>.');

    // Set display confirmation when submission is updated.
    $webform_confirmation_message->setSetting('confirmation_update', TRUE)
      ->save();

    // Check default message when submission is updated.
    $this->drupalGet("/admin/structure/webform/manage/test_confirmation_message/submission/$sid/edit");
    $this->submitForm([], 'Save');
    $assert_session->responseContains('This is a <b>custom</b> confirmation message. (test: )');
    $assert_session->responseNotContains('Submission updated in <em class="placeholder">Test: Confirmation: Message</em>.');

    /* Test confirmation message (confirmation_type=modal) */

    $webform_confirmation_modal = Webform::load('test_confirmation_modal');

    // Check confirmation modal.
    $sid = $this->postSubmission($webform_confirmation_modal, ['test' => 'value']);
    $assert_session->responseContains('This is a <b>custom</b> confirmation modal.');
    $assert_session->responseContains('<div class="js-hide webform-confirmation-modal js-webform-confirmation-modal webform-message js-webform-message js-form-wrapper form-wrapper" data-drupal-selector="edit-webform-confirmation-modal" id="edit-webform-confirmation-modal">');
    $assert_session->responseContains('<div role="contentinfo" aria-label="Status message">');
    $assert_session->responseContains('<b class="webform-confirmation-modal--title">Custom confirmation modal</b><br />');
    $assert_session->responseContains('<div class="webform-confirmation-modal--content">This is a <b>custom</b> confirmation modal. (test: value)</div>');
    $assert_session->addressEquals('webform/test_confirmation_modal');

    // Check confirmation modal update does not display modal.
    $this->drupalGet("/admin/structure/webform/manage/test_confirmation_modal/submission/$sid/edit");
    $this->submitForm([], 'Save');
    $assert_session->responseContains('Submission updated in <em class="placeholder">Test: Confirmation: Modal</em>.');

    // Set display confirmation modal when submission is updated.
    $webform_confirmation_modal->setSetting('confirmation_update', TRUE)
      ->save();

    // Check confirmation modal update does display modal.
    $this->drupalGet("/admin/structure/webform/manage/test_confirmation_modal/submission/$sid/edit");
    $this->submitForm([], 'Save');
    $assert_session->responseContains('<b class="webform-confirmation-modal--title">Custom confirmation modal</b><br /><div class="webform-confirmation-modal--content">This is a <b>custom</b> confirmation modal. (test: value)</div>');

    /* Test confirmation inline (confirmation_type=inline) */

    $webform_confirmation_inline = Webform::load('test_confirmation_inline');

    // Check confirmation inline.
    $this->drupalGet('/webform/test_confirmation_inline');
    $this->submitForm([], 'Submit');
    $assert_session->responseContains('<a href="' . $webform_confirmation_inline->toUrl('canonical', ['absolute' => TRUE])->toString() . '" rel="prev">Back to form</a>');
    $assert_session->addressEquals('webform/test_confirmation_inline');

    // Check confirmation inline with custom query parameters.
    $options = ['query' => ['custom' => 'param']];
    $this->drupalGet('/webform/test_confirmation_inline', $options);
    $this->submitForm([], 'Submit');
    $assert_session->responseContains('<a href="' . $webform_confirmation_inline->toUrl('canonical', ['absolute' => TRUE, 'query' => ['custom' => 'param']])->toString() . '" rel="prev">Back to form</a>');
    $assert_session->addressEquals('webform/test_confirmation_inline?custom=param');

    /* Test confirmation page (confirmation_type=page) */

    $webform_confirmation_page = Webform::load('test_confirmation_page');

    // Check confirmation page.
    $sid = $this->postSubmission($webform_confirmation_page);
    $webform_submission = WebformSubmission::load($sid);
    $assert_session->responseContains('This is a custom confirmation page.');
    $assert_session->responseContains('<a href="' . $webform_confirmation_page->toUrl('canonical', ['absolute' => TRUE])->toString() . '" rel="prev">Back to form</a>');
    $assert_session->addressEquals('webform/test_confirmation_page/confirmation?token=' . urlencode($webform_submission->getToken()));

    // Check that the confirmation page's 'Back to form 'link includes custom
    // query parameters.
    $this->drupalGet('/webform/test_confirmation_page/confirmation', ['query' => ['custom' => 'param']]);

    // Check confirmation page with custom query parameters.
    $sid = $this->postSubmission($webform_confirmation_page, [], NULL, ['query' => ['custom' => 'param']]);
    $webform_submission = WebformSubmission::load($sid);
    $assert_session->addressEquals('webform/test_confirmation_page/confirmation?custom=param&token=' . urlencode($webform_submission->getToken()));

    // Check confirmation page with token excluded.
    $webform_confirmation_page->setSetting('confirmation_exclude_token', TRUE);
    $webform_confirmation_page->save();
    $this->postSubmission($webform_confirmation_page, [], NULL, ['query' => ['custom' => 'param']]);
    $assert_session->addressEquals('webform/test_confirmation_page/confirmation?custom=param');

    // Check confirmation page with token and query excluded.
    $webform_confirmation_page->setSetting('confirmation_exclude_query', TRUE);
    $webform_confirmation_page->save();
    $this->postSubmission($webform_confirmation_page);
    $assert_session->addressEquals('webform/test_confirmation_page/confirmation');

    // Check confirmation page with default noindex rule.
    $this->drupalGet('/webform/test_confirmation_page/confirmation');
    $assert_session->responseContains('<meta name="robots" content="noindex" />');

    // Check confirmation page without noindex rule.
    \Drupal::configFactory()->getEditable('webform.settings')
      ->set('settings.default_confirmation_noindex', FALSE)
      ->save();
    $this->drupalGet('/webform/test_confirmation_page/confirmation');
    $assert_session->responseNotContains('<meta name="robots" content="noindex" />');

    // Install the metatag.module to handle robots noindex.
    \Drupal::service('module_installer')->install(['metatag']);

    // Check confirmation page with default noindex rule with metatag.module.
    \Drupal::configFactory()->getEditable('webform.settings')
      ->set('settings.default_confirmation_noindex', TRUE)
      ->save();
    $this->drupalGet('/webform/test_confirmation_page/confirmation');
    $assert_session->responseContains('<meta name="robots" content="noindex" />');

    // Check confirmation page without noindex rule with metatag.module.
    $config = \Drupal::configFactory()->getEditable('webform.settings');
    $config->set('settings.default_confirmation_noindex', FALSE);
    $config->save();
    $this->drupalGet('/webform/test_confirmation_page/confirmation');
    $assert_session->responseNotContains('<meta name="robots" content="noindex" />');

    // phpcs:disable
    // @todo (TESTING) Figure out why the inline confirmation link is not including the query string parameters.
    // $assert_session->responseContains('<a href="' . $webform_confirmation_page->toUrl()->toString() . '?custom=param">Back to form</a>');.
    // phpcs:enable

    /* Test confirmation page custom (confirmation_type=page) */

    $webform_confirmation_page_custom = Webform::load('test_confirmation_page_custom');

    // Check custom confirmation page.
    $this->postSubmission($webform_confirmation_page_custom);
    $assert_session->responseContains('<h1>Custom confirmation page title</h1>');
    $assert_session->responseContains('<div style="border: 10px solid red; padding: 1em;" class="webform-confirmation">');
    $assert_session->responseContains('<a href="' . $webform_confirmation_page_custom->toUrl()->setAbsolute()->toString() . '" rel="prev" class="button">Custom back to link</a>');

    // Check back link is hidden.
    $webform_confirmation_page_custom->setSetting('confirmation_back', FALSE);
    $webform_confirmation_page_custom->save();
    $this->postSubmission($webform_confirmation_page_custom);
    $assert_session->responseNotContains('<a href="' . $webform_confirmation_page_custom->toUrl()->toString() . '" rel="prev" class="button">Custom back to link</a>');

    /* Test confirmation URL (confirmation_type=url) */

    $webform_confirmation_url = Webform::load('test_confirmation_url');

    // Check confirmation URL using special path <front>.
    $this->postSubmission($webform_confirmation_url);
    $assert_session->responseNotContains('<h2 class="visually-hidden">Status message</h2>');
    $assert_session->addressEquals('/');

    // Check confirmation URL using an internal: URI.
    $webform_confirmation_url
      ->setSetting('confirmation_url', 'internal:/some-internal-path')
      ->save();
    $this->postSubmission($webform_confirmation_url);
    $assert_session->addressEquals('/some-internal-path');

    // Check confirmation URL using absolute path.
    $webform_confirmation_url
      ->setSetting('confirmation_url', '/some-absolute-path')
      ->save();
    $this->postSubmission($webform_confirmation_url);
    $assert_session->addressEquals('/some-absolute-path');

    // Check confirmation URL using absolute path with querystring.
    $webform_confirmation_url
      ->setSetting('confirmation_url', '/some-absolute-path?some=parameter')
      ->setSetting('confirmation_exclude_token', TRUE)
      ->save();
    $this->postSubmission($webform_confirmation_url);
    $this->assertEquals(parse_url($this->getSession()->getCurrentUrl(), PHP_URL_QUERY), 'some=parameter');
    $this->postSubmission($webform_confirmation_url, [], NULL, ['query' => ['test' => 'parameter']]);
    $this->assertEquals(parse_url($this->getSession()->getCurrentUrl(), PHP_URL_QUERY), 'some=parameter&test=parameter');

    // Check confirmation URL using relative path with querystring.
    $webform_confirmation_url
      ->setSetting('confirmation_url', 'webform/test_confirmation_url?some=parameter')
      ->setSetting('confirmation_exclude_token', TRUE)
      ->save();
    $this->postSubmission($webform_confirmation_url);
    $this->assertEquals(parse_url($this->getSession()->getCurrentUrl(), PHP_URL_QUERY), 'some=parameter');
    $this->postSubmission($webform_confirmation_url, [], NULL, ['query' => ['test' => 'parameter']]);
    $this->assertEquals(parse_url($this->getSession()->getCurrentUrl(), PHP_URL_QUERY), 'some=parameter&test=parameter');

    // Check confirmation URL using invalid path.
    $webform_confirmation_url
      ->setSetting('confirmation_url', 'invalid')
      ->save();
    $sid = $this->postSubmission($webform_confirmation_url);
    $assert_session->responseContains('Confirmation URL <em class="placeholder">invalid</em> is not valid.');
    $assert_session->addressEquals('/webform/test_confirmation_url');

    /* Test confirmation URL (confirmation_type=url_message) */

    $webform_confirmation_url_message = Webform::load('test_confirmation_url_message');

    // Check confirmation URL.
    $this->postSubmission($webform_confirmation_url_message);
    $assert_session->responseContains('<h2 class="visually-hidden">Status message</h2>');
    $assert_session->responseContains('This is a custom confirmation message.');
    $assert_session->addressEquals('/');

    /* Test confirmation none (confirmation_type=none) */

    $this->drupalLogout();
    $webform_confirmation_url_message = Webform::load('test_confirmation_none');

    // Check no confirmation message.
    $this->postSubmission($webform_confirmation_url_message);
    $assert_session->responseNotContains('<h2 class="visually-hidden">Status message</h2>');

  }

}
