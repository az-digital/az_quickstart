<?php

namespace Drupal\Tests\webform\Functional\Variant;

use Drupal\Tests\webform\Functional\WebformBrowserTestBase;
use Drupal\webform\Entity\Webform;

/**
 * Tests for the webform variant element.
 *
 * @group webform
 */
class WebformVariantElementTest extends WebformBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['block', 'webform', 'webform_ui', 'webform_test_variant'];

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_variant_multiple'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->placeBlocks();
  }

  /**
   * Test variant element.
   */
  public function testVariantElement() {
    $assert_session = $this->assertSession();

    $variant_user = $this->drupalCreateUser(['administer webform', 'edit webform variants']);
    $admin_user = $this->drupalCreateUser(['administer webform']);

    /* *********************************************************************** */

    // Check that the variant element is visible to users with
    // 'edit webform variants' permission.
    $this->drupalLogin($variant_user);
    $this->drupalGet('/admin/structure/webform/manage/contact/element/add');
    $assert_session->linkExists('Variant');

    // Check that the variant element is hidden to users without
    // 'edit webform variants' permission.
    $this->drupalLogin($admin_user);
    $this->drupalGet('/admin/structure/webform/manage/contact/element/add');
    $assert_session->linkNotExists('Variant');

    // Check that hidden variant element is still available.
    $this->drupalGet('/admin/structure/webform/manage/contact/element/add/webform_variant');
    $assert_session->statusCodeEquals(200);

    // Check that only the override variant plugins is available to all webforms.
    $this->drupalGet('/admin/structure/webform/manage/contact/element/add/webform_variant');
    $assert_session->responseContains('<option value="override">Override</option>');
    $assert_session->responseNotContains('<option value="test">Test</option>');

    // Check that only the test variant plugins is available to test_variant_*.
    // @see \Drupal\webform_test_variant\Plugin\WebformVariant\TestWebformVariant::isApplicable
    $this->drupalGet('/admin/structure/webform/manage/test_variant_multiple/element/add/webform_variant');
    $assert_session->responseContains('<option value="override">Override</option>');
    $assert_session->responseContains('<option value="test">Test</option>');

    // Login as variant user to display 'Variants' tab info messages.
    $this->drupalLogin($variant_user);

    // Check 'Variants' tab message is displayed.
    $this->drupalGet('/admin/structure/webform/manage/contact/element/add/webform_variant');
    $assert_session->responseContains("After clicking 'Save', the 'Variants' manage tab will be displayed. Use the 'Variants' manage tab to add and remove variants.");
    $assert_session->pageTextNotContains('Add and remove variants using the Variants manage tab.');

    // Check that 'Variants' tab is not visible.
    $this->drupalGet('/admin/structure/webform/manage/contact');
    $assert_session->linkNotExists('Variants');

    // Add a variant element to contact form.
    $this->drupalGet('/admin/structure/webform/manage/contact/element/add/webform_variant');
    $edit = [
      'key' => 'variant',
      'properties[title]' => '{variant_title}',
      'properties[variant]' => 'override',
    ];
    $this->submitForm($edit, 'Save');

    // Check that the 'Variants' tab is visible.
    $this->drupalGet('/admin/structure/webform/manage/contact');
    $assert_session->linkExists('Variants');

    // Check that the 'Variants' tab message is displayed.
    $this->drupalGet('/admin/structure/webform/manage/contact/element/add/webform_variant');
    $assert_session->responseNotContains("After clicking 'Save', the 'Variants' manage tab will be displayed. Use the 'Variants' manage tab to add and remove variants.");
    $assert_session->pageTextContains('Add and remove variants using the Variants manage tab.');

    // Check that users missing the 'edit webform variants' permission
    // don't see any messages.
    $this->drupalLogin($admin_user);
    $this->drupalGet('/admin/structure/webform/manage/contact/element/add/webform_variant');
    $assert_session->pageTextNotContains('Add and remove variants using the Variants manage tab.');

    // Check that the 'Variants' tab is also not visible.
    $this->drupalGet('/admin/structure/webform/manage/contact');
    $assert_session->linkNotExists('Variants');

    // Check that the 'Variant type' can not be changed once variants have created.
    $this->drupalGet('/admin/structure/webform/manage/test_variant_multiple/element/letter/edit');
    $assert_session->responseNotContains('<option value="override">Override</option>');
    $assert_session->responseContains('Override');
    $assert_session->responseContains('This variant is currently in-use. The variant type cannot be changed.');

    // Check that the letter element has 2 related variants.
    $webform = Webform::load('test_variant_multiple');
    $this->assertEquals(2, $webform->getVariants(NULL, NULL, 'letter')->count());

    // Delete the letter element and its related variants.
    $webform->deleteElement('letter');
    $webform->save();

    // Check that letter element now has 0 related variants.
    $this->assertEquals(0, $webform->getVariants(NULL, NULL, 'letter')->count());
  }

}
