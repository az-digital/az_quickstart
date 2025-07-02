<?php

namespace Drupal\Tests\webform\Functional;

/**
 * Tests for webform help.
 *
 * @group webform
 */
class WebformHelpTest extends WebformBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['block', 'help', 'webform_test_message_custom'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->drupalPlaceBlock('help_block');
  }

  /**
   * Tests webform help.
   */
  public function testHelp() {
    $assert_session = $this->assertSession();

    /* ********************************************************************** */
    // Help page.
    /* ********************************************************************** */

    // Check access denied to the webform help page.
    $this->drupalGet('/admin/structure/webform/help');
    $assert_session->statusCodeEquals(403);

    // Check access denied to the webform help video.
    $this->drupalGet('/admin/help/webform/video/introduction');
    $assert_session->statusCodeEquals(403);

    // Login with 'access content' permission.
    $this->drupalLogin($this->createUser(['access content']));

    // Check access allowed to the webform help video w/o watch more link.
    $this->drupalGet('/admin/help/webform/video/introduction', ['query' => ['_wrapper_format' => 'drupal_modal', 'more' => 1]]);
    $assert_session->statusCodeEquals(200);
    $assert_session->responseNotContains('Watch more videos');

    // Login with 'access webform help' permission.
    $this->drupalLogin($this->createUser(['access content', 'access webform help']));

    // Check access allowed to the webform help page.
    $this->drupalGet('/admin/structure/webform/help');
    $assert_session->statusCodeEquals(200);

    // Check access allowed to the webform help video with watch more link.
    $this->drupalGet('/admin/help/webform/video/introduction', ['query' => ['_wrapper_format' => 'drupal_modal', 'more' => 1]]);
    $assert_session->statusCodeEquals(200);
    $assert_session->responseContains('Watch more videos');

    /* ********************************************************************** */
    // Help block.
    /* ********************************************************************** */

    $this->drupalLogin($this->rootUser);

    // Check notifications, promotion, and welcome messages displayed.
    $this->drupalGet('/admin/structure/webform');
    $assert_session->responseContains('This is a warning notification.');
    $assert_session->responseContains('This is an info notification.');
    $assert_session->responseContains('If you enjoy and value Drupal and the Webform module consider');

    // Close all notifications, promotion, and welcome messages.
    $this->drupalGet('/admin/structure/webform');
    $this->clickLink('×', 0);
    $this->drupalGet('/admin/structure/webform');
    $this->clickLink('×', 0);
    $this->drupalGet('/admin/structure/webform');
    $this->clickLink('×', 0);

    // Check notifications, promotion, and welcome messages closed.
    $this->drupalGet('/admin/structure/webform');
    $assert_session->responseNotContains('This is a warning notification.');
    $assert_session->responseNotContains('This is an info notification.');
    $assert_session->responseNotContains('If you enjoy and value Drupal and the Webform module consider');

    // Check that help is enabled.
    $this->drupalGet('/admin/structure/webform/config/advanced');
    $assert_session->responseMatches('#<div id="block-[^"]+" role="complementary">#');
    $assert_session->responseContains('The <strong>Advanced configuration</strong> page allows an administrator to enable/disable UI behaviors, manage requirements and define data used for testing webforms.');

    // Disable help via the UI which will clear the cached help block.
    $this->drupalGet('/admin/structure/webform/config/advanced');
    $edit = ['ui[help_disabled]' => TRUE];
    $this->submitForm($edit, 'Save configuration');

    // Check that help is disabled.
    $this->drupalGet('/admin/structure/webform/config/advanced');
    $assert_session->responseNotMatches('#<div id="block-[^"]+" role="complementary">#');
    $assert_session->responseNotContains('The <strong>Advanced configuration</strong> page allows an administrator to enable/disable UI behaviors, manage requirements and define data used for testing webforms.');

  }

}
