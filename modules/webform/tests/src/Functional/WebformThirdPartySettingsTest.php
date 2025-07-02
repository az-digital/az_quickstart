<?php

namespace Drupal\Tests\webform\Functional;

use Drupal\webform\Entity\Webform;

/**
 * Tests for webform third party settings.
 *
 * @group webform
 */
class WebformThirdPartySettingsTest extends WebformBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['node', 'webform'];

  /**
   * Tests webform third party settings.
   */
  public function testThirdPartySettings() {
    $assert_session = $this->assertSession();

    $webform = Webform::load('contact');

    $this->drupalLogin($this->rootUser);

    /* ********************************************************************** */

    // Check honeypot (custom) third party setting does not exist.
    $this->assertNull($webform->getThirdPartySetting('honeypot', 'honeypot'));

    // Add honeypot (custom) third party setting, even though the honeypot
    // module is not installed.
    $webform = $this->reloadWebform('contact');
    $webform->setThirdPartySetting('honeypot', 'honeypot', TRUE);
    $webform->save();

    // Check honeypot (custom) third party setting.
    $webform = $this->reloadWebform('contact');
    $this->assertTrue($webform->getThirdPartySetting('honeypot', 'honeypot'));

    // Check 'Webform: Settings' shows no modules installed.
    $this->drupalGet('/admin/structure/webform/config');
    $assert_session->responseContains('There are no third party settings available.');

    // Check 'Contact: Settings' does not show 'Third party settings'.
    $this->drupalGet('/admin/structure/webform/manage/contact/settings');
    $assert_session->responseNotContains('Third party settings');

    // Install test third party settings module.
    $this->drupalGet('admin/modules');
    $edit = ['modules[webform_test_third_party_settings][enable]' => TRUE];
    $this->submitForm($edit, 'Install');

    // Check 'Webform: Settings' shows no modules installed.
    $this->drupalGet('/admin/structure/webform/config');
    $assert_session->responseNotContains('There are no third party settings available.');

    // Check 'Contact: Settings' shows 'Third party settings'.
    $this->drupalGet('/admin/structure/webform/manage/contact/settings');
    $assert_session->responseContains('Third party settings');

    // Check 'Webform: Settings' message.
    $this->drupalGet('/admin/structure/webform/config');
    $edit = ['third_party_settings[webform_test_third_party_settings][message]' => 'Message for all webforms'];
    $this->submitForm($edit, 'Save configuration');
    $this->drupalGet('/webform/contact');
    $assert_session->responseContains('Message for all webforms');

    // Check that webform.settings.yml contain message.
    $this->assertEquals(
      'Message for all webforms',
      $this->config('webform.settings')->get('third_party_settings.webform_test_third_party_settings.message')
    );

    // Check 'Contact: Settings: Third party' message.
    $this->drupalGet('/admin/structure/webform/manage/contact/settings');
    $edit = ['third_party_settings[webform_test_third_party_settings][message]' => 'Message for only this webform'];
    $this->submitForm($edit, 'Save');
    $this->drupalGet('/webform/contact');
    $assert_session->responseContains('Message for only this webform');

    // Check honeypot (custom) third party setting still exists.
    $webform = $this->reloadWebform('contact');
    $this->assertTrue($webform->getThirdPartySetting('honeypot', 'honeypot'));

    // Check 'Check 'Contact: Settings: Third party' is not null.
    $this->assertNotNull(
      $this->config('webform.webform.contact')->get('third_party_settings.webform_test_third_party_settings')
    );

    // Check clearing 'Check 'Contact: Settings: Third party' message
    // sets the value to null.
    $this->drupalGet('/admin/structure/webform/manage/contact/settings');
    $edit = ['third_party_settings[webform_test_third_party_settings][message]' => ''];
    $this->submitForm($edit, 'Save');
    $webform = $this->reloadWebform('contact');
    $this->assertEquals([], $webform->getThirdPartySettings('webform_test_third_party_settings'));
    $this->assertNull(
      $this->config('webform.webform.contact')->get('third_party_settings.webform_test_third_party_settings')
    );

    // Uninstall test third party settings module.
    $this->drupalGet('admin/modules/uninstall');
    $edit = ['uninstall[webform_test_third_party_settings]' => TRUE];
    $this->submitForm($edit, 'Uninstall');
    $this->submitForm([], 'Uninstall');

    // Check webform.
    $this->drupalGet('/webform/contact');
    $assert_session->responseNotContains('Message for only this webform');

    // Check that webform.settings.yml no longer contains message or
    // webform_test_third_party_settings.
    $this->assertNull(
      $this->config('webform.settings')->get('third_party_settings.webform_test_third_party_settings.message')
    );
    $this->assertNull(
      $this->config('webform.settings')->get('third_party_settings.webform_test_third_party_settings')
    );
  }

}
