<?php

namespace Drupal\Tests\webform\Functional\Variant;

use Drupal\Tests\webform\Functional\WebformBrowserTestBase;

/**
 * Tests for webform variant plugin.
 *
 * @group webform
 */
class WebformVariantTest extends WebformBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['webform', 'webform_test_variant'];

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_variant'];

  /**
   * Tests webform variant plugin.
   */
  public function testWebformVariant() {
    $this->drupalLogin($this->rootUser);

    /* ********************************************************************** */
    // Off-canvas width.
    /* ********************************************************************** */

    // Check add off-canvas element width is 800.
    $this->drupalGet('/admin/structure/webform/manage/test_variant/variants/add');
    $this->assertCssSelect('[href$="/admin/structure/webform/manage/test_variant/variants/add/test_offcanvas_width"][data-dialog-options*="800"]');
    $this->assertNoCssSelect('[href$="/admin/structure/webform/manage/test_variant/variants/add/test_offcanvas_width"][data-dialog-options*="550"]');

    // Add variant.
    $this->drupalGet('/admin/structure/webform/manage/test_variant/variants/add/test_offcanvas_width');
    $edit = ['variant_id' => 'test_offcanvas_width', 'label' => 'test_offcanvas_width'];
    $this->submitForm($edit, 'Save');

    // Check edit off-canvas element width is 800.
    $this->drupalGet('/admin/structure/webform/manage/test_variant/variants/');
    $this->assertCssSelect('[href$="/admin/structure/webform/manage/test_variant/variants/test_offcanvas_width/edit"][data-dialog-options*="800"]');
    $this->assertNoCssSelect('[href$="/admin/structure/webform/manage/test_variant/variants/test_offcanvas_width/edit"][data-dialog-options*="550"]');
  }

}
