<?php

namespace Drupal\Tests\asset_injector\Functional;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests CSS Asset Injector.
 *
 * @package Drupal\Tests\asset_injector\Functional
 *
 * @group asset_injector
 */
class AssetInjectorCssTest extends BrowserTestBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Modules to install.
   *
   * @var array
   */
  protected static $modules = ['asset_injector', 'toolbar', 'block'];

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $this->drupalPlaceBlock('local_tasks_block');
    $this->drupalPlaceBlock('page_title_block');
    $this->drupalPlaceBlock('system_messages_block');
  }

  /**
   * Tests a user without permissions gets access denied.
   *
   * @throws \Exception
   */
  public function testCssPermissionDenied() {
    $account = $this->drupalCreateUser();
    $this->drupalLogin($account);
    $this->drupalGet('admin/config/development/asset-injector/css');
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Tests a user WITH permission has access.
   *
   * @throws \Exception
   */
  public function testCssPermissionGranted() {
    $account = $this->drupalCreateUser(['administer css assets injector']);
    $this->drupalLogin($account);
    $this->drupalGet('admin/config/development/asset-injector/css');
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Test a created CSS injector is added to the page and the CSS file exists.
   *
   * @throws \Exception
   */
  public function testCssInjector() {
    $this->testCssPermissionGranted();
    $this->drupalGet('admin/config/development/asset-injector/css/add');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains($this->t('Code'));
    $this->submitForm([
      'label' => 'Blocks',
      'id' => 'blocks',
      'code' => '.block {border:1px solid black;}',
    ], 'Save');

    $this->getSession()->getPage()->hasContent('asset_injector/css/blocks');
    /** @var \Drupal\asset_injector\Entity\AssetInjectorCss $asset */
    foreach (asset_injector_get_assets(NULL, ['asset_injector_css']) as $asset) {
      $path = parse_url(\Drupal::service('file_url_generator')
        ->generateAbsoluteString($asset->internalFileUri()), PHP_URL_PATH);
      $path = str_replace(base_path(), '/', $path);

      $this->drupalGet($path);
      $this->assertSession()->statusCodeEquals(200);
    }
  }

  /**
   * Test a created CSS injector is added to the Maintenance Mode page.
   *
   * @throws \Exception
   */
  public function testMaintenanceMode() {
    $this->testCssPermissionGranted();
    $this->drupalGet('admin/config/development/asset-injector/css/add');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains($this->t('Code'));
    $this->submitForm([
      'label' => 'Blocks',
      'id' => 'blocks',
      'code' => '.block {border:1px solid black;}',
    ], 'Save');

    $this->drupalLogout();
    $this->container->get('state')->set('system.maintenance_mode', TRUE);

    $this->drupalGet('<front>');
    $this->getSession()->getPage()->hasContent('asset_injector/css/blocks');
    /** @var \Drupal\asset_injector\Entity\AssetInjectorCss $asset */
    foreach (asset_injector_get_assets(NULL, ['asset_injector_css']) as $asset) {
      $path = parse_url(\Drupal::service('file_url_generator')
        ->generateAbsoluteString($asset->internalFileUri()), PHP_URL_PATH);
      $path = str_replace(base_path(), '/', $path);

      $this->drupalGet($path);
      $this->assertSession()->statusCodeEquals(200);
    }
  }

  /**
   * Tests if the save and continue button works accurately.
   *
   * @throws \Exception
   */
  public function testSaveContinue() {
    $page = $this->getSession()->getPage();
    $this->testCssPermissionGranted();
    $this->drupalGet('admin/config/development/asset-injector/css/add');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains($this->t('Code'));
    $page->fillField('Label', 'test save continue');
    $page->fillField('Machine-readable name', 'test_save_continue');
    $page->fillField('Code', '.block{}');
    $page->pressButton('Save and Continue Editing');
    $this->assertSession()
      ->pageTextContains('Created the test save continue Asset Injector');
    $this->assertSession()
      ->addressEquals('admin/config/development/asset-injector/css/test_save_continue');
  }

  /**
   * Tests if the Form functions correctly.
   *
   * @throws \Exception
   */
  public function testForm() {
    $session = $this->assertSession();
    $page = $this->getSession()->getPage();
    $this->testCssPermissionGranted();
    // Go to the settings page and check the default values.
    $this->drupalGet('admin/config/development/asset-injector/css/add');
    $session->statusCodeEquals(200);
    $session->fieldValueEquals('label', '');
    $session->checkboxChecked('status');
    $session->fieldValueEquals('code', '');
    $session->fieldValueEquals('media', 'all');
    $session->checkboxChecked('preprocess');
    // Change all values and save the form.
    $page->fillField('label', 'test_label');
    $page->uncheckField('status');
    $page->fillField('code', 'test_code');
    $page->fillField('media', 'print');
    $page->uncheckField('preprocess');
    $page->pressButton('save_continue');
    $session->statusCodeEquals(200);
    // Check if the changed settings still apply.
    $session->fieldValueEquals('label', 'test_label');
    $session->checkboxNotChecked('status');
    $session->fieldValueEquals('code', 'test_code');
    $session->fieldValueEquals('media', 'print');
    $session->checkboxNotChecked('preprocess');
  }

}
