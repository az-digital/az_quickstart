<?php

namespace Drupal\Tests\webform\Functional\Variant;

use Drupal\Core\Url;
use Drupal\Tests\webform\Functional\WebformBrowserTestBase;
use Drupal\webform\Entity\Webform;

/**
 * Tests for the webform variant operations.
 *
 * @group webform
 */
class WebformVariantOperationsTest extends WebformBrowserTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_variant_multiple'];

  /**
   * Test variant operation.
   */
  public function testVariantOperations() {
    $assert_session = $this->assertSession();

    $webform = Webform::load('test_variant_multiple');
    $this->drupalLogin($this->rootUser);

    // Check that default markup {X} and {0} are displayed.
    $this->drupalGet('/webform/test_variant_multiple');
    $assert_session->responseContains('{X}');
    $assert_session->responseContains('{0}');

    // Check that variant markup {A} and {1} are displayed.
    $this->drupalGet('/webform/test_variant_multiple', ['query' => ['letter' => 'a', 'number' => '1']]);
    $assert_session->responseContains('[A]');
    $assert_session->responseContains('[1]');

    $prepopulate_options = ['query' => ['letter' => 'a']];
    $route_parameters = ['webform' => $webform->id()];
    $route_options = ['query' => ['variant_id' => 'a']];

    // Check the view, test, and apply operation links for the 'a' variant.
    $this->drupalGet('/admin/structure/webform/manage/test_variant_multiple/variants');
    $assert_session->linkByHrefExists($webform->toUrl('canonical', $prepopulate_options)->toString());
    $assert_session->linkByHrefExists($webform->toUrl('test-form', $prepopulate_options)->toString());
    $assert_session->linkByHrefExists(Url::fromRoute('entity.webform.variant.apply_form', $route_parameters, $route_options)->toString());

    // Check that the 'a' variant is available on the view multiple form.
    $this->drupalGet('/admin/structure/webform/manage/test_variant_multiple/variants/view');
    $assert_session->optionExists('edit-variants-letter', 'a');

    // Check that the 'a' variant is selected on the apply multiple form.
    $this->drupalGet('/admin/structure/webform/manage/test_variant_multiple/variants/apply', $route_options);
    $assert_session->optionExists('edit-variants-letter', 'a');
    $this->assertTrue($assert_session->optionExists('edit-variants-letter', 'a')->hasAttribute('selected'));

    // Disable the 'a' variant.
    /** @var \Drupal\webform\Plugin\WebformVariantInterface $letter_a_variant_plugin */
    $letter_a_variant_plugin = $webform->getVariant('a');
    $letter_a_variant_plugin->disable();
    $webform->save();

    // Check that the 'a' variant's markup {A} is not displayed.
    $this->drupalGet('/webform/test_variant_multiple', ['query' => ['letter' => 'a', 'number' => '1']]);
    $assert_session->responseNotContains('[A]');
    $assert_session->responseContains('{X}');
    $assert_session->responseContains('[1]');

    // Check that the view and test operation links for the 'a' variant
    // are removed.
    $this->drupalGet('/admin/structure/webform/manage/test_variant_multiple/variants');
    $assert_session->linkByHrefNotExists($webform->toUrl('canonical', $prepopulate_options)->toString());
    $assert_session->linkByHrefNotExists($webform->toUrl('test-form', $prepopulate_options)->toString());
    // Check that apply operation link is still available for the 'a' variant.
    $assert_session->linkByHrefExists(Url::fromRoute('entity.webform.variant.apply_form', $route_parameters, $route_options)->toString());

    // Check that the 'a' variant is not available ont the view multiple form.
    $this->drupalGet('/admin/structure/webform/manage/test_variant_multiple/variants/view');
    $assert_session->optionNotExists('edit-variants-letter', 'a');

    // Check that the 'a' variant is still selected on the apply multiple form.
    $this->drupalGet('/admin/structure/webform/manage/test_variant_multiple/variants/apply', $route_options);
    $assert_session->optionExists('edit-variants-letter', 'a');
    $this->assertTrue($assert_session->optionExists('edit-variants-letter', 'a')->hasAttribute('selected'));

    // Reenable the 'a' variant and now disable '#prepopulate' for the
    // 'letter' element.
    $letter_a_variant_plugin->enable();
    $letter_element = $webform->getElementDecoded('letter');
    $letter_element['#prepopulate'] = FALSE;
    $webform->setElementProperties('letter', $letter_element);
    $webform->save();

    // Check that the 'letter' element can't be prepopulated.
    $this->drupalGet('/webform/test_variant_multiple', ['query' => ['letter' => 'a', 'number' => '1']]);
    $assert_session->responseNotContains('[A]');
    $assert_session->responseContains('{X}');

    // Check that the 'letter' element can be prepopulated using the
    // '_webform_variant' query string parameter.
    $this->drupalGet('/webform/test_variant_multiple', ['query' => ['_webform_variant[letter]' => 'a', 'number' => '1']]);
    $assert_session->responseContains('[A]');
    $assert_session->responseNotContains('{X}');
    $assert_session->responseContains('[1]');

    // Check that the 'a' and 'b' letter variants exist.
    $this->drupalGet('/admin/structure/webform/manage/test_variant_multiple/variants');
    $assert_session->responseContains('>A<');
    $assert_session->responseContains('>B<');

    // Delete 'letter' variant element and its related variants.
    $webform->deleteElement('letter');
    $webform->save();

    // Check that all 'letter' variants were deleted.
    $this->drupalGet('/admin/structure/webform/manage/test_variant_multiple/variants');
    $assert_session->responseNotContains('>A<');
    $assert_session->responseNotContains('>B<');
  }

}
