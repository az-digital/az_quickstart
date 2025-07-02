<?php

namespace Drupal\Tests\webform_templates\Functional;

use Drupal\Tests\webform\Functional\WebformBrowserTestBase;
use Drupal\webform\Entity\Webform;
use Drupal\webform\WebformInterface;

/**
 * Tests for webform submission webform settings.
 *
 * @group webform_templates
 */
class WebformTemplatesTest extends WebformBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['webform', 'webform_templates'];

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_form_template'];

  /**
   * Tests webform templates.
   */
  public function testTemplates() {
    $assert_session = $this->assertSession();

    $user_account = $this->drupalCreateUser([
      'access webform overview',
      'administer webform',
    ]);

    $admin_account = $this->drupalCreateUser([
      'access webform overview',
      'administer webform',
      'administer webform templates',
    ]);

    // Login the user.
    $this->drupalLogin($user_account);

    $template_webform = Webform::load('test_form_template');

    // Check the templates always will remain closed.
    $this->assertTrue($template_webform->isClosed());
    $template_webform->setStatus(WebformInterface::STATUS_OPEN)->save();
    $this->assertTrue($template_webform->isClosed());

    // Check template is included in the 'Templates' list display.
    $this->drupalGet('/admin/structure/webform/templates');
    $assert_session->responseContains('Test: Webform: Template');
    $assert_session->responseContains('Test using a webform as a template.');

    // Check template filtering by key and category.
    $this->drupalGet('/admin/structure/webform/templates', ['query' => ['category' => 'Not a category']]);
    $assert_session->responseNotContains('Test: Webform: Template');
    $this->drupalGet('/admin/structure/webform/templates', ['query' => ['category' => 'Test: Webform']]);
    $assert_session->responseContains('Test: Webform: Template');
    $this->drupalGet('/admin/structure/webform/templates', ['query' => ['search' => 'Not a search']]);
    $assert_session->responseNotContains('Test: Webform: Template');
    $this->drupalGet('/admin/structure/webform/templates', ['query' => ['category' => 'Test: Webform: Template']]);
    $assert_session->responseContains('Test: Webform: Template');

    // Check template is accessible to user with create webform access.
    $this->drupalGet('/webform/test_form_template');
    $assert_session->statusCodeEquals(200);
    $assert_session->responseContains('You are previewing the below template,');

    // Check select template clears the description.
    $this->drupalGet('/admin/structure/webform/manage/test_form_template/duplicate');
    $assert_session->fieldValueEquals('description[value][value]', '');

    // Check that admin can not access manage templates.
    $this->drupalGet('/admin/structure/webform/templates/manage');
    $assert_session->statusCodeEquals(403);

    // Login the admin.
    $this->drupalLogin($admin_account);

    // Check that admin can access manage templates.
    $this->drupalGet('/admin/structure/webform/templates/manage');
    $assert_session->statusCodeEquals(200);

    // Check select template clears the description.
    $this->drupalGet('/admin/structure/webform/manage/test_form_template/duplicate', ['query' => ['template' => 1]]);
    $assert_session->fieldValueEquals('description[value][value]', 'Test using a webform as a template.');
  }

}
