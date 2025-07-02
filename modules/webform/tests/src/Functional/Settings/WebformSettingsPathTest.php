<?php

namespace Drupal\Tests\webform\Functional\Settings;

use Drupal\Core\Serialization\Yaml;
use Drupal\Tests\webform\Functional\WebformBrowserTestBase;
use Drupal\webform\Entity\Webform;
use Drupal\webform\WebformInterface;

/**
 * Tests for webform path and page.
 *
 * @group webform
 */
class WebformSettingsPathTest extends WebformBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['path', 'webform', 'node'];

  /**
   * Tests YAML page and title.
   */
  public function testPaths() {
    $assert_session = $this->assertSession();

    /** @var \Drupal\path_alias\AliasRepositoryInterface $path_alias_repository */
    $path_alias_repository = $this->container->get('path_alias.repository');

    $node = $this->drupalCreateNode();

    /* ********************************************************************** */
    // With paths.
    /* ********************************************************************** */

    $webform = Webform::create([
      'langcode' => 'en',
      'status' => WebformInterface::STATUS_OPEN,
      'id' => 'test_paths',
      'title' => 'test_paths',
      'elements' => Yaml::encode([
        'test' => ['#markup' => 'test'],
      ]),
    ]);
    $webform->setSetting('draft', WebformInterface::DRAFT_ALL);
    $webform->save();
    $webform_path = '/webform/' . $webform->id();
    $form_path = '/form/' . str_replace('_', '-', $webform->id());

    // Check paths.
    $this->drupalLogin($this->rootUser);

    // Check that aliases exist.
    $this->assertIsArray($path_alias_repository->lookupByAlias($form_path, 'en'));
    $this->assertIsArray($path_alias_repository->lookupByAlias("$form_path/confirmation", 'en'));
    $this->assertIsArray($path_alias_repository->lookupByAlias("$form_path/drafts", 'en'));
    $this->assertIsArray($path_alias_repository->lookupByAlias("$form_path/submissions", 'en'));

    // Check default system submit path.
    $this->drupalGet($webform_path);
    $assert_session->statusCodeEquals(200);

    // Check default alias submit path.
    $this->drupalGet($form_path);
    $assert_session->statusCodeEquals(200);

    // Check default alias confirm path.
    $this->drupalGet("$form_path/confirmation");
    $assert_session->statusCodeEquals(200);

    // Check default alias drafts path.
    $this->drupalGet("$form_path/drafts");
    $assert_session->statusCodeEquals(200);

    // Check default alias submissions path.
    $this->drupalGet("$form_path/submissions");
    $assert_session->statusCodeEquals(200);

    $this->drupalLogout();

    // Disable paths for the webform.
    $webform->setSettings(['page' => FALSE])->save();

    // Check that aliases do not exist.
    $this->assertNull($path_alias_repository->lookupByAlias($form_path, 'en'));
    $this->assertNull($path_alias_repository->lookupByAlias("$form_path/confirmation", 'en'));
    $this->assertNull($path_alias_repository->lookupByAlias("$form_path/drafts", 'en'));
    $this->assertNull($path_alias_repository->lookupByAlias("$form_path/submissions", 'en'));

    // Check page hidden (i.e. access denied).
    $this->drupalGet($webform_path);
    $assert_session->statusCodeEquals(403);
    $assert_session->responseNotContains('Only webform administrators are allowed to access this page and create new submissions.');
    $this->drupalGet($form_path);
    $assert_session->statusCodeEquals(404);

    // Check page hidden with source entity.
    $this->drupalGet($webform_path, ['query' => ['source_entity_type' => 'node', 'source_entity_id' => $node->id()]]);
    $assert_session->statusCodeEquals(403);

    // Check page visible with source entity.
    $webform->setSettings(['form_prepopulate_source_entity' => TRUE])->save();
    $this->drupalGet($webform_path, ['query' => ['source_entity_type' => 'node', 'source_entity_id' => $node->id()]]);
    $assert_session->statusCodeEquals(200);

    // Check hidden page visible to admin.
    $this->drupalLogin($this->rootUser);
    $this->drupalGet($webform_path);
    $assert_session->statusCodeEquals(200);
    $assert_session->responseContains('Only webform administrators are allowed to access this page and create new submissions.');
    $this->drupalLogout();

    // Check custom submit and confirm path.
    $webform->setSettings(['page' => TRUE, 'page_submit_path' => '/page_submit_path', 'page_confirm_path' => '/page_confirm_path'])->save();
    $this->drupalGet('/page_submit_path');
    $assert_session->statusCodeEquals(200);
    $this->drupalGet('/page_confirm_path');
    $assert_session->statusCodeEquals(200);

    // Check custom base path.
    $webform->setSettings(['page_submit_path' => '', 'page_confirm_path' => ''])->save();
    $this->drupalLogin($this->rootUser);

    $this->drupalGet('/admin/structure/webform/config');
    $edit = ['page_settings[default_page_base_path]' => '/base/path'];
    $this->submitForm($edit, 'Save configuration');

    $this->drupalGet('/base/path/' . str_replace('_', '-', $webform->id()));
    $assert_session->statusCodeEquals(200);

    $this->drupalGet('/base/path/' . str_replace('_', '-', $webform->id()) . '/confirmation');
    $assert_session->statusCodeEquals(200);

    // Check custom base path delete if accessing webform as page is disabled.
    $webform->setSettings(['page' => FALSE])->save();
    $this->drupalGet('/base/path/' . str_replace('_', '-', $webform->id()));
    $assert_session->statusCodeEquals(404);
    $this->drupalGet('/base/path/' . str_replace('_', '-', $webform->id()) . '/confirmation');
    $assert_session->statusCodeEquals(404);

    // Disable automatic generation of paths.
    \Drupal::configFactory()->getEditable('webform.settings')
      ->set('settings.default_page_base_path', '')
      ->save();

    /* ********************************************************************** */
    // Without paths.
    /* ********************************************************************** */

    $webform = Webform::create([
      'langcode' => 'en',
      'status' => WebformInterface::STATUS_OPEN,
      'id' => 'test_no_paths',
      'title' => 'test_no_paths',
      'elements' => Yaml::encode([
        'test' => ['#markup' => 'test'],
      ]),
    ]);
    $webform->save();
    $webform_path = '/webform/' . $webform->id();
    $form_path = '/form/' . str_replace('_', '-', $webform->id());

    // Check default system submit path.
    $this->drupalGet($webform_path);
    $assert_session->statusCodeEquals(200);

    // Check no default alias submit path.
    $this->drupalGet($form_path);
    $assert_session->statusCodeEquals(404);

    /* ********************************************************************** */
    // Page theme.
    /* ********************************************************************** */

    $this->drupalLogin($this->rootUser);

    $webform = Webform::create([
      'langcode' => 'en',
      'status' => WebformInterface::STATUS_OPEN,
      'id' => 'test_admin_theme',
      'title' => 'test_admin_theme',
      'elements' => Yaml::encode([
        'test' => ['#markup' => 'test'],
      ]),
    ]);
    $webform->save();

    // Check that admin theme is not applied.
    $this->drupalGet('/webform/test_admin_theme');
    $assert_session->responseNotContains('claro');

    // Install Seven and set it as the default admin theme.
    \Drupal::service('theme_installer')->install(['claro']);

    $this->drupalGet('/admin/appearance');
    $edit = [
      'admin_theme' => 'claro',
      'use_admin_theme' => TRUE,
    ];
    $this->submitForm($edit, 'Save configuration');
    $webform->setSetting('page_theme_name', 'claro')->save();

    // Check that admin theme is applied.
    $this->drupalGet('/webform/test_admin_theme');
    $assert_session->responseContains('claro');
  }

}
