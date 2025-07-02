<?php

namespace Drupal\Tests\webform\Functional\Settings;

use Drupal\Tests\webform\Functional\WebformBrowserTestBase;
use Drupal\webform\WebformInterface;

/**
 * Tests for webform default status.
 *
 * @group webform
 */
class WebformSettingsStatusTest extends WebformBrowserTestBase {

  /**
   * Tests default status.
   */
  public function testStatus() {
    $assert_session = $this->assertSession();

    $this->drupalLogin($this->rootUser);

    // Check add form status = open.
    $this->drupalGet('/admin/structure/webform/add');
    $assert_session->checkboxChecked('edit-status-open');
    $assert_session->checkboxNotChecked('edit-status-closed');

    // Check duplicate form status = open.
    $this->drupalGet('/admin/structure/webform/manage/contact/duplicate');
    $assert_session->checkboxChecked('edit-status-open');
    $assert_session->checkboxNotChecked('edit-status-closed');

    // Set default status to closed.
    $this->config('webform.settings')
      ->set('settings.default_status', WebformInterface::STATUS_CLOSED)
      ->save();

    // Check add form status = closed.
    $this->drupalGet('/admin/structure/webform/add');
    $assert_session->checkboxNotChecked('edit-status-open');
    $assert_session->checkboxChecked('edit-status-closed');

    // Check duplicate form status = closed.
    $this->drupalGet('/admin/structure/webform/manage/contact/duplicate');
    $assert_session->checkboxNotChecked('edit-status-open');
    $assert_session->checkboxChecked('edit-status-closed');
  }

}
