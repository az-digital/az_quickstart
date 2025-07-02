<?php

namespace Drupal\Tests\config_readonly\Functional;

/**
 * Tests read-only module config whitelist functionality.
 *
 * @group ConfigReadOnly
 */
class ReadOnlyConfigWhitelistTest extends ReadOnlyConfigTest {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'config',
    'config_readonly',
    'node',
    'config_readonly_whitelist_test',
    'block',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Ensure that the whitelist allows a read-only form to become saveable.
   */
  public function testWhitelist() {
    $assert_session = $this->assertSession();
    $this->createContentType([
      'type' => 'article1',
      'name' => 'Article1',
    ]);
    $this->createContentType([
      'type' => 'article2',
      'name' => 'Article2',
    ]);

    $this->turnOnReadOnlySetting();

    $this->drupalGet('admin/structure/types/manage/article1');
    // Warning shown on edit node type page.
    $assert_session->pageTextContains('This form will not be saved because the configuration active store is read-only.');

    $this->drupalGet('admin/structure/types/manage/article2');
    // Warning not show on edit node type page.
    $assert_session->pageTextNotContains('This form will not be saved because the configuration active store is read-only.');
  }

  /**
   * Test simple config with whitelist.
   */
  public function testSimpleConfig() {
    $assert_session = $this->assertSession();
    $this->drupalGet('admin/config/development/configuration/single/import');
    // Warning not shown on single config import page.
    $assert_session->pageTextNotContains('This form will not be saved because the configuration active store is read-only.');

    $this->drupalGet('admin/config/development/performance');
    // Warning not shown on performance config page.
    $assert_session->pageTextNotContains('This form will not be saved because the configuration active store is read-only.');

    $this->turnOnReadOnlySetting();
    $this->drupalGet('admin/config/development/configuration/single/import');
    // Warning shown on single config import page.
    $assert_session->pageTextContains('This form will not be saved because the configuration active store is read-only.');

    $this->drupalGet('admin/config/development/performance');
    // Warning not shown on performance config page.
    $assert_session->pageTextNotContains('This form will not be saved because the configuration active store is read-only.');
  }

  /**
   * Test ConfigEntityListBuilder form with whitelisted entity type.
   */
  public function testConfigEntityListBuilder() {
    $assert_session = $this->assertSession();
    $this->turnOnReadOnlySetting();
    $this->drupalGet('admin/structure/block');
    // Warning not shown on admin block listing form.
    $assert_session->pageTextNotContains('This form will not be saved because the configuration active store is read-only.');
  }

}
