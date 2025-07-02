<?php

namespace Drupal\Tests\webform\Functional\Variant;

use Drupal\Tests\webform\Functional\WebformBrowserTestBase;

/**
 * Tests for the webform variant apply.
 *
 * @group webform
 */
class WebformVariantApplyTest extends WebformBrowserTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = [
    'test_variant_randomize',
    'test_variant_multiple',
  ];

  /**
   * Test variant apply.
   */
  public function testVariantApply() {
    $assert_session = $this->assertSession();

    $webform = $this->loadWebform('test_variant_randomize');

    $this->drupalLogin($this->rootUser);

    // Check apply single variant page title.
    $this->drupalGet('/admin/structure/webform/manage/test_variant_randomize/variants/apply');
    $assert_session->responseContains('Apply variant to the <em class="placeholder">Test: Variant randomize</em> webform?');

    // Check apply multiple variants page title.
    $this->drupalGet('/admin/structure/webform/manage/test_variant_multiple/variants/apply');
    $assert_session->responseContains('>Apply the selected variants to the <em class="placeholder">Test: Variant multiple</em> webform?');

    // Check that no variant has not been applied.
    $this->assertEquals(2, $webform->getVariants()->count());
    $this->drupalGet('/webform/test_variant_randomize');
    $assert_session->responseContains('{X}');
    $this->drupalGet('/webform/test_variant_randomize', ['query' => ['letter' => 'a']]);
    $assert_session->responseNotContains('{X}');
    $assert_session->responseContains('[A]');

    // Check access denied error when trying to apply non-existent variant.
    $this->drupalGet('/admin/structure/webform/manage/test_variant_randomize/variants/apply', ['query' => ['variant_id' => 'c']]);
    $assert_session->statusCodeEquals(403);

    // Check access allowed when trying to apply existing 'a' variant.
    $this->drupalGet('/admin/structure/webform/manage/test_variant_randomize/variants/apply', ['query' => ['variant_id' => 'a']]);
    $assert_session->statusCodeEquals(200);

    // Check variant select menu is not visible when variant is specified.
    $this->drupalGet('/admin/structure/webform/manage/test_variant_randomize/variants/apply', ['query' => ['variant_id' => 'a']]);
    $this->assertNoCssSelect('#edit-variants-letter');

    // Check variant select menu is visible when no variant is specified.
    $this->drupalGet('/admin/structure/webform/manage/test_variant_randomize/variants/apply');
    $this->assertCssSelect('#edit-variants-letter');

    // Apply 'a' variant.
    $options = ['query' => ['variant_id' => 'a']];
    $this->drupalGet('/admin/structure/webform/manage/test_variant_randomize/variants/apply', $options);
    $edit = ['delete' => 'none'];
    $this->submitForm($edit, 'Apply');
    $webform = $this->reloadWebform('test_variant_randomize');

    // Check that the 'a' variant has been applied and no variants have been deleted.
    $this->drupalGet('/webform/test_variant_randomize');
    $assert_session->responseNotContains('{X}');
    $assert_session->responseContains('[A]');
    $this->assertTrue($webform->getVariants()->has('a'));
    $this->assertEquals(2, $webform->getVariants()->count());

    // Disable the 'b' variant.
    $variant = $webform->getVariant('b');
    $variant->disable();
    $webform->save();

    // Apply the 'b' variant which is disabled.
    $options = ['query' => ['variant_id' => 'b']];
    $this->drupalGet('/admin/structure/webform/manage/test_variant_randomize/variants/apply', $options);
    $edit = ['delete' => 'none'];
    $this->submitForm($edit, 'Apply');
    $webform = $this->reloadWebform('test_variant_randomize');
    $assert_session->responseNotContains('{X}');
    $assert_session->responseContains('[B]');
    $this->assertTrue($webform->getVariants()->has('b'));
    $this->assertEquals(2, $webform->getVariants()->count());

    // Apply and delete the 'a' variant.
    $options = ['query' => ['variant_id' => 'a']];
    $this->drupalGet('/admin/structure/webform/manage/test_variant_randomize/variants/apply', $options);
    $edit = ['delete' => 'selected'];
    $this->submitForm($edit, 'Apply');
    $webform = $this->reloadWebform('test_variant_randomize');

    // Check that the 'a' variant has been applied and no variants have been deleted.
    $this->drupalGet('/webform/test_variant_randomize');
    $assert_session->responseNotContains('{X}');
    $assert_session->responseContains('[A]');
    $this->assertFalse($webform->getVariants()->has('a'));
    $this->assertEquals(1, $webform->getVariants()->count());

    // Apply the 'b' variant and delete all variants.
    $options = ['query' => ['variant_id' => 'b']];
    $this->drupalGet('/admin/structure/webform/manage/test_variant_randomize/variants/apply', $options);
    $edit = ['delete' => 'all'];
    $this->submitForm($edit, 'Apply');
    $webform = $this->reloadWebform('test_variant_randomize');

    // Check that the 'b' variant has been applied and all variants have been deleted.
    $this->drupalGet('/webform/test_variant_randomize');
    $assert_session->responseNotContains('{X}');
    $assert_session->responseContains('[B]');
    $this->assertFalse($webform->getVariants()->has('b'));
    $this->assertEquals(0, $webform->getVariants()->count());
  }

}
