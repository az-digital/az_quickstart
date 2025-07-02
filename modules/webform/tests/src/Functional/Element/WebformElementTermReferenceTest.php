<?php

namespace Drupal\Tests\webform\Functional\Element;

use Drupal\taxonomy\Entity\Term;
use Drupal\webform\Entity\Webform;

/**
 * Tests for term reference elements.
 *
 * @group webform
 */
class WebformElementTermReferenceTest extends WebformElementBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['taxonomy'];

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_term_reference'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create 'tags' vocabulary.
    $this->createTags();
  }

  /**
   * Test term reference element.
   */
  public function testTermReference() {
    $assert_session = $this->assertSession();

    $webform = Webform::load('test_element_term_reference');

    /* ********************************************************************** */
    // Term checkboxes.
    /* ********************************************************************** */

    $this->drupalGet('/webform/test_element_term_reference');

    // Check term checkboxes tree default.
    $assert_session->responseContains('<fieldset data-drupal-selector="edit-webform-term-checkboxes-tree-default" class="js-webform-term-checkboxes webform-term-checkboxes webform-term-checkboxes-scroll webform-term-checkboxes--wrapper fieldgroup form-composite webform-composite-visible-title js-webform-type-webform-term-checkboxes webform-type-webform-term-checkboxes js-form-item form-item js-form-wrapper form-wrapper" id="edit-webform-term-checkboxes-tree-default--wrapper">');
    $assert_session->responseContains('<span class="field-prefix">&nbsp;&nbsp;&nbsp;</span>');
    $assert_session->responseContains('<input data-drupal-selector="edit-webform-term-checkboxes-tree-default-2" type="checkbox" id="edit-webform-term-checkboxes-tree-default-2" name="webform_term_checkboxes_tree_default[2]" value="2" class="form-checkbox" />');
    $assert_session->responseContains('<label for="edit-webform-term-checkboxes-tree-default-2" class="option">Parent 1: Child 1</label>');

    // Check term checkboxes tree depth.
    $assert_session->responseContains('<label for="edit-webform-term-checkboxes-tree-depth-1" class="option">Parent 1</label>');
    $assert_session->responseNotContains('<label for="edit-webform-term-checkboxes-tree-depth-2" class="option">Parent 1: Child 1</label>');
    $assert_session->responseContains('<label for="edit-webform-term-checkboxes-tree-depth-5" class="option">Parent 2</label>');
    $assert_session->responseContains('<label for="edit-webform-term-checkboxes-tree-depth-9" class="option">Parent 3</label>');

    // Check term checkboxes tree advanced.
    $assert_session->responseContains('<fieldset data-drupal-selector="edit-webform-term-checkboxes-tree-advanced" class="js-webform-term-checkboxes webform-term-checkboxes webform-term-checkboxes--wrapper fieldgroup form-composite webform-composite-visible-title js-webform-type-webform-term-checkboxes webform-type-webform-term-checkboxes js-form-item form-item js-form-wrapper form-wrapper" data-options-all="all" data-options-none="none" id="edit-webform-term-checkboxes-tree-advanced--wrapper">');
    $assert_session->responseContains('<span class="field-prefix">..</span>');
    $assert_session->responseContains('<input data-drupal-selector="edit-webform-term-checkboxes-tree-advanced-2" type="checkbox" id="edit-webform-term-checkboxes-tree-advanced-2" name="webform_term_checkboxes_tree_advanced[2]" value="2" class="form-checkbox" />');
    $assert_session->responseContains('<label for="edit-webform-term-checkboxes-tree-advanced-2" class="option">Parent 1: Child 1</label>');
    $assert_session->responseContains('<input data-drupal-selector="edit-webform-term-checkboxes-tree-advanced-all" type="checkbox" id="edit-webform-term-checkboxes-tree-advanced-all" name="webform_term_checkboxes_tree_advanced[all]" value="all" class="form-checkbox" />');
    $assert_session->responseContains('<input data-drupal-selector="edit-webform-term-checkboxes-tree-advanced-none" type="checkbox" id="edit-webform-term-checkboxes-tree-advanced-none" name="webform_term_checkboxes_tree_advanced[none]" value="none" class="form-checkbox" />');

    // Check term checkboxes breadcrumb.
    $assert_session->responseContains('<label for="edit-webform-term-checkboxes-breadcrumb-default-2" class="option">Parent 1 › Parent 1: Child 1</label>');

    // Check term checkboxes breadcrumb advanced formatting.
    $edit = [
      'webform_term_checkboxes_breadcrumb_advanced[2]' => TRUE,
      'webform_term_checkboxes_breadcrumb_advanced[3]' => TRUE,
    ];
    $this->postSubmission($webform, $edit, 'Preview');
    $assert_session->responseContains('<label>webform_term_checkboxes_breadcrumb_advanced</label>');
    $assert_session->responseContains('<ul><li>Parent 1 › Parent 1: Child 1</li><li>Parent 1 › Parent 1: Child 2</li></ul>');

    // Unpublish term:2.
    Term::load(2)->setUnpublished()->save();

    $this->drupalGet('/webform/test_element_term_reference');

    // Check term select tree default.
    $assert_session->responseContains('<option value="1">Parent 1</option>');
    $assert_session->responseNotContains('<option value="2">-Parent 1: Child 1</option>');
    $assert_session->responseContains('<option value="3">-Parent 1: Child 2</option>');
    $assert_session->responseContains('<option value="4">-Parent 1: Child 3</option>');

    // Check term select breadcrumb default.
    $assert_session->responseContains('<option value="1">Parent 1</option>');
    $assert_session->responseNotContains('<option value="2">Parent 1 › Parent 1: Child 1</option>');
    $assert_session->responseContains('<option value="3">Parent 1 › Parent 1: Child 2</option>');
    $assert_session->responseContains('<option value="4">Parent 1 › Parent 1: Child 3</option>');

    // Publish term: 2.
    Term::load(2)->setPublished()->save();

    /* ********************************************************************** */
    // Term select.
    /* ********************************************************************** */

    $this->drupalGet('/webform/test_element_term_reference');

    // Check term select tree default.
    $assert_session->responseContains('<option value="1">Parent 1</option>');
    $assert_session->responseContains('<option value="2">-Parent 1: Child 1</option>');
    $assert_session->responseContains('<option value="3">-Parent 1: Child 2</option>');
    $assert_session->responseContains('<option value="4">-Parent 1: Child 3</option>');

    // Check term select tree advanced.
    $assert_session->responseContains('<option value="1">Parent 1</option>');
    $assert_session->responseContains('<option value="2">..Parent 1: Child 1</option>');
    $assert_session->responseContains('<option value="3">..Parent 1: Child 2</option>');
    $assert_session->responseContains('<option value="4">..Parent 1: Child 3</option>');

    // Check term select breadcrumb default.
    $assert_session->responseContains('<option value="1">Parent 1</option>');
    $assert_session->responseContains('<option value="2">Parent 1 › Parent 1: Child 1</option>');
    $assert_session->responseContains('<option value="3">Parent 1 › Parent 1: Child 2</option>');
    $assert_session->responseContains('<option value="4">Parent 1 › Parent 1: Child 3</option>');

    // Check term select breadcrumb advanced.
    $assert_session->responseContains('<option value="1">Parent 1</option>');
    $assert_session->responseContains('<option value="2">Parent 1 » Parent 1: Child 1</option>');
    $assert_session->responseContains('<option value="3">Parent 1 » Parent 1: Child 2</option>');
    $assert_session->responseContains('<option value="4">Parent 1 » Parent 1: Child 3</option>');

    // Check term select breadcrumb advanced formatting.
    $edit = [
      'webform_term_select_breadcrumb_advanced[]' => [2, 3],
    ];
    $this->postSubmission($webform, $edit, 'Preview');
    $assert_session->responseContains('<label>webform_term_select_breadcrumb_advanced</label>');
    $assert_session->responseContains('<ul><li>Parent 1 › Parent 1: Child 1</li><li>Parent 1 › Parent 1: Child 2</li></ul>');

    // Unpublish term:2.
    Term::load(2)->setUnpublished()->save();

    $this->drupalGet('/webform/test_element_term_reference');

    // Check term select tree default.
    $assert_session->responseContains('<option value="1">Parent 1</option>');
    $assert_session->responseNotContains('<option value="2">-Parent 1: Child 1</option>');
    $assert_session->responseContains('<option value="3">-Parent 1: Child 2</option>');
    $assert_session->responseContains('<option value="4">-Parent 1: Child 3</option>');

    // Check term select breadcrumb default.
    $assert_session->responseContains('<option value="1">Parent 1</option>');
    $assert_session->responseNotContains('<option value="2">Parent 1 › Parent 1: Child 1</option>');
    $assert_session->responseContains('<option value="3">Parent 1 › Parent 1: Child 2</option>');
    $assert_session->responseContains('<option value="4">Parent 1 › Parent 1: Child 3</option>');
  }

}
