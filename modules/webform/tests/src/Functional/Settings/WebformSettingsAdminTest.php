<?php

namespace Drupal\Tests\webform\Functional\Settings;

use Drupal\Tests\webform\Functional\WebformBrowserTestBase;
use Drupal\webform\Utility\WebformYaml;

/**
 * Tests for webform entity.
 *
 * @group webform
 */
class WebformSettingsAdminTest extends WebformBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['block', 'captcha', 'node', 'toolbar', 'views', 'webform', 'webform_ui', 'webform_node'];

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->drupalPlaceBlock('local_actions_block');
  }

  /**
   * Tests webform admin settings.
   */
  public function testAdminSettings() {
    global $base_path;

    $assert_session = $this->assertSession();

    $this->drupalLogin($this->rootUser);

    /* Settings Webform */

    // Get 'webform.settings'.
    $original_data = \Drupal::configFactory()->getEditable('webform.settings')->getRawData();

    // Update 'settings.default_form_close_message'.
    $types = [
      'forms' => 'admin/structure/webform/config',
      'elements' => 'admin/structure/webform/config/elements',
      'submissions' => 'admin/structure/webform/config/submissions',
      'handlers' => 'admin/structure/webform/config/handlers',
      'exporters' => 'admin/structure/webform/config/exporters',
      'libraries' => 'admin/structure/webform/config/libraries',
      'advanced' => 'admin/structure/webform/config/advanced',
    ];
    foreach ($types as $path) {
      $this->drupalGet($path);
      $this->submitForm([], 'Save configuration');
      \Drupal::configFactory()->reset('webform.settings');
      $updated_data = \Drupal::configFactory()->getEditable('webform.settings')->getRawData();
      $this->ksort($updated_data);

      // Check the updating 'Settings' via the UI did not lose or change any data.
      $this->assertEquals($updated_data, $original_data, 'Updated admin settings via the UI did not lose or change any data');

      // DEBUG:
      $original_yaml = WebformYaml::encode($original_data);
      $updated_yaml = WebformYaml::encode($updated_data);
      $this->verbose('<pre>' . $original_yaml . '</pre>');
      $this->verbose('<pre>' . $updated_yaml . '</pre>');
      $this->debug(array_diff(explode(PHP_EOL, $original_yaml), explode(PHP_EOL, $updated_yaml)));
    }

    /* Elements */

    // Check that description is 'after' the element.
    $this->drupalGet('/webform/test_element');
    $assert_session->responseMatches('#\{item title\}.+\{item markup\}.+\{item description\}#ms');

    // Set the default description display to 'before'.
    $this->drupalGet('/admin/structure/webform/config/elements');
    $edit = ['element[default_description_display]' => 'before'];
    $this->submitForm($edit, 'Save configuration');

    // Check that description is 'before' the element.
    $this->drupalGet('/webform/test_element');
    $assert_session->responseNotMatches('#\{item title\}.+\{item markup\}.+\{item description\}#ms');
    $assert_session->responseMatches('#\{item title\}.+\{item description\}.+\{item markup\}#ms');

    /* UI disable dialog */

    // Check that dialogs are enabled.
    $this->drupalGet('/admin/structure/webform');
    $assert_session->responseContains('<a href="' . $base_path . 'admin/structure/webform/add" class="webform-ajax-link button button-action" data-dialog-type="modal" data-dialog-options="{&quot;width&quot;:700,&quot;dialogClass&quot;:&quot;webform-ui-dialog&quot;}" data-drupal-link-system-path="admin/structure/webform/add">Add webform</a>');

    // Disable dialogs.
    $this->drupalGet('/admin/structure/webform/config/advanced');
    $edit = ['ui[dialog_disabled]' => TRUE];
    $this->submitForm($edit, 'Save configuration');

    // Check that dialogs are disabled. (i.e. use-ajax is not included)
    $this->drupalGet('/admin/structure/webform');
    $assert_session->responseNotContains('<a href="' . $base_path . 'admin/structure/webform/add" class="webform-ajax-link button button-action" data-dialog-type="modal" data-dialog-options="{&quot;width&quot;:700,&quot;dialogClass&quot;:&quot;webform-ui-dialog&quot;}" data-drupal-link-system-path="admin/structure/webform/add">Add webform</a>');
    $assert_session->responseContains('<a href="' . $base_path . 'admin/structure/webform/add" class="button button-action" data-drupal-link-system-path="admin/structure/webform/add">Add webform</a>');

    /* UI description help */

    // Check moving #description to #help for webform admin routes.
    $this->drupalGet('/admin/structure/webform/config/advanced');
    $edit = ['ui[description_help]' => TRUE];
    $this->submitForm($edit, 'Save configuration');
    $assert_session->responseContains('<span class="webform-element-help js-webform-element-help" role="tooltip" tabindex="0" aria-label="Display element description as help text (tooltip)" data-webform-help="&lt;div class=&quot;webform-element-help--title&quot;&gt;Display element description as help text (tooltip)&lt;/div&gt;&lt;div class=&quot;webform-element-help--content&quot;&gt;If checked, all element descriptions will be moved to help text (tooltip).&lt;/div&gt;"><span aria-hidden="true">?</span></span>');

    // Check moving #description to #help for webform admin routes.
    $this->drupalGet('/admin/structure/webform/config/advanced');
    $edit = ['ui[description_help]' => FALSE];
    $this->submitForm($edit, 'Save configuration');
    $assert_session->responseNotContains('<span class="webform-element-help js-webform-element-help" role="tooltip" tabindex="0" aria-label="Display element description as help text (tooltip)" data-webform-help="&lt;div class=&quot;webform-element-help--title&quot;&gt;Display element description as help text (tooltip)&lt;/div&gt;&lt;div class=&quot;webform-element-help--content&quot;&gt;If checked, all element descriptions will be moved to help text (tooltip).&lt;/div&gt;"><span aria-hidden="true">?</span></span>');

    /* Toolbar */

    // Check that Webforms are NOT displayed as a top-level item in the toolbar.
    $this->drupalGet('/admin/structure/webform/config/advanced');
    $assert_session->statusCodeEquals(200);
    $this->assertNoCssSelect('.menu-item a.toolbar-icon-entity-webform-collection');

    // Check that Webforms are displayed as a top-level item in the toolbar.
    $this->drupalGet('/admin/structure/webform/config/advanced');
    $edit = ['ui[toolbar_item]' => TRUE];
    $this->submitForm($edit, 'Save configuration');
    $this->assertCssSelect('.menu-item a.toolbar-icon-entity-webform-collection');

    // Check that /structure/ is removed from webform paths.
    $this->drupalGet('/admin/structure/webform/config/advanced');
    $assert_session->statusCodeEquals(404);
    $this->drupalGet('/admin/webform/config/advanced');
    $assert_session->statusCodeEquals(200);
  }

  /**
   * Sort a nested associative array by key.
   *
   * @param array $array
   *   A nested associative array.
   */
  protected function ksort(array &$array) {
    ksort($array);
    foreach ($array as &$value) {
      if (is_array($value)) {
        ksort($value);
      }
    }
  }

}
