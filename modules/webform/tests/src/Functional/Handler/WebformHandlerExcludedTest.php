<?php

namespace Drupal\Tests\webform\Functional\Handler;

use Drupal\Tests\webform\Functional\WebformBrowserTestBase;

/**
 * Tests for the webform handler excluded.
 *
 * @group webform
 */
class WebformHandlerExcludedTest extends WebformBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['block', 'webform'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->drupalPlaceBlock('local_actions_block');
  }

  /**
   * Test excluded handlers.
   */
  public function testExcludeHandlers() {
    $assert_session = $this->assertSession();

    $this->drupalLogin($this->rootUser);

    /** @var \Drupal\webform\Plugin\WebformHandlerManagerInterface $handler_manager */
    $handler_manager = $this->container->get('plugin.manager.webform.handler');

    // Check add mail and handler plugin.
    $this->drupalGet('/admin/structure/webform/manage/contact/handlers');
    $assert_session->linkExists('Add email');
    $assert_session->linkExists('Add handler');

    // Check add mail accessible.
    $this->drupalGet('/admin/structure/webform/manage/contact/handlers/add/email');
    $assert_session->statusCodeEquals(200);

    // Exclude the email handler.
    \Drupal::configFactory()->getEditable('webform.settings')->set('handler.excluded_handlers', ['email' => 'email'])->save();

    // Check add mail hidden.
    $this->drupalGet('/admin/structure/webform/manage/contact/handlers');
    $assert_session->linkNotExists('Add email');
    $assert_session->linkExists('Add handler');

    // Check add mail access denied.
    $this->drupalGet('/admin/structure/webform/manage/contact/handlers/add/email');
    $assert_session->statusCodeEquals(403);

    // Exclude the email handler.
    \Drupal::configFactory()->getEditable('webform.settings')->set('handler.excluded_handlers', ['action' => 'action', 'broken' => 'broken', 'debug' => 'debug', 'email' => 'email', 'remote_post' => 'remote_post', 'settings' => 'settings'])->save();

    // Check add mail and handler hidden.
    $this->drupalGet('/admin/structure/webform/manage/contact/handlers');
    $assert_session->linkNotExists('Add email');
    $assert_session->linkNotExists('Add handler');

    // Check handler definitions.
    $definitions = $handler_manager->getDefinitions();
    $definitions = $handler_manager->removeExcludeDefinitions($definitions);
    $this->assertEquals(array_keys($definitions), []);
  }

}
