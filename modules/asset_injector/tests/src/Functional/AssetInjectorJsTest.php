<?php

namespace Drupal\Tests\asset_injector\Functional;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Tests\BrowserTestBase;

/**
 * Test JS Asset Injector.
 *
 * @package Drupal\Tests\asset_injector\Functional
 *
 * @group asset_injector
 */
class AssetInjectorJsTest extends BrowserTestBase {

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
   * The account to be used to test access to both workflows.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $administrator;

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
  public function testJsPermissionDenied() {
    $account = $this->drupalCreateUser();
    $this->drupalLogin($account);
    $this->drupalGet('admin/config/development/asset-injector/js');
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Tests a user WITH permission has access.
   *
   * @throws \Exception
   */
  public function testJsPermissionGranted() {
    $account = $this->drupalCreateUser(['administer js assets injector']);
    $this->drupalLogin($account);
    $this->drupalGet('admin/config/development/asset-injector/js');
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Test a created JS injector is added to the page and the JS file exists.
   *
   * @throws \Exception
   */
  public function testJsInjector() {
    $this->testJsPermissionGranted();
    $this->drupalGet('admin/config/development/asset-injector/js/add');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains($this->t('Code'));
    $this->submitForm([
      'label' => $this->t('Blocks'),
      'id' => $this->t('blocks'),
      'code' => '.block {border:1px solid black;}',
    ], $this->t('Save'));

    $this->getSession()->getPage()->hasContent('asset_injector/js/blocks');

    /** @var \Drupal\asset_injector\Entity\AssetInjectorJs $asset */
    foreach (asset_injector_get_assets(NULL, ['asset_injector_js']) as $asset) {
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
    $this->testJsPermissionGranted();
    $this->drupalGet('admin/config/development/asset-injector/js/add');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains($this->t('Code'));
    $page->fillField('Label', 'test save continue');
    $page->fillField('Machine-readable name', 'test_save_continue');
    $page->fillField('Code', 'var a;');
    $page->pressButton('Save and Continue Editing');
    $this->assertSession()
      ->pageTextContains('Created the test save continue Asset Injector');
    $this->assertSession()
      ->addressEquals('admin/config/development/asset-injector/js/test_save_continue');
  }

  /**
   * Tests if the Form functions correctly.
   *
   * @throws \Exception
   */
  public function testForm() {
    $session = $this->assertSession();
    $page = $this->getSession()->getPage();
    $this->testJsPermissionGranted();
    // Go to the settings page and check the default values.
    $this->drupalGet('admin/config/development/asset-injector/js/add');
    $session->statusCodeEquals(200);
    $session->fieldValueEquals('label', '');
    $session->checkboxChecked('status');
    $session->fieldValueEquals('code', '');
    $session->checkboxNotChecked('jquery');
    $session->checkboxChecked('preprocess');
    $session->checkboxNotChecked('header');
    $session->checkboxNotChecked('use_noscript');
    // Change all values and save the form.
    $page->fillField('label', 'test_label');
    $page->uncheckField('status');
    $page->fillField('code', 'test_code');
    $page->checkField('jquery');
    $page->uncheckField('preprocess');
    $page->checkField('header');
    $page->checkField('use_noscript');
    $page->pressButton('save_continue');
    $session->statusCodeEquals(200);
    // Check if the changed settings still apply.
    $session->fieldValueEquals('label', 'test_label');
    $session->checkboxNotChecked('status');
    $session->fieldValueEquals('code', 'test_code');
    $session->checkboxChecked('jquery');
    $session->checkboxNotChecked('preprocess');
    $session->checkboxChecked('header');
    $session->checkboxChecked('use_noscript');
  }

}
