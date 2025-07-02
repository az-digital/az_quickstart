<?php

namespace Drupal\Tests\inline_entity_form\FunctionalJavascript;

/**
 * Tests the IEF element on a custom form.
 *
 * @group inline_entity_form
 */
class ElementWebTest extends InlineEntityFormTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['inline_entity_form_test'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->user = $this->createUser([
      'create ief_simple_single content',
      'edit any ief_test_custom content',
      'view own unpublished content',
      'administer nodes',
    ]);

    $this->drupalLogin($this->user);

    $this->fieldStorageConfigStorage = $this->container
      ->get('entity_type.manager')
      ->getStorage('field_storage_config');
  }

  /**
   * Tests IEF on a custom form.
   */
  public function testCustomForm() {
    // Get the xpath selectors for the fields in this test.
    $title_field_xpath = $this->getXpathForNthInputByLabelText('Title', 1);
    $positive_int_field_xpath = $this->getXpathForNthInputByLabelText('Positive int', 1);

    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    foreach (['default', 'inline'] as $form_mode_possibility) {
      $title = $this->randomMachineName();
      $this->drupalGet("ief-test/$form_mode_possibility");
      $assert_session->pageTextContains('Title');
      $assert_session->pageTextContains('Positive int');
      $this->checkFormDisplayFields("node.ief_test_custom.$form_mode_possibility", 'inline_entity_form');

      $page->pressButton('Save');
      $assert_session->pageTextNotContains("Created Content $title");

      // @todo How do we test Chrome's HTML 5 validation?
      // $assert_session->pageTextContains('Please fill out this field.');
      // Fix in https://www.drupal.org/project/inline_entity_form/issues/3100883
      $this->assertNoNodeByTitle($title);

      $assert_session->elementExists('xpath', $title_field_xpath)->setValue($title);
      $assert_session->elementExists('xpath', $positive_int_field_xpath)->setValue('-1');

      $page->pressButton('Save');
      $assert_session->pageTextNotContains("Created Content $title");
      $this->assertNoNodeByTitle($title);

      $assert_session->elementExists('xpath', $positive_int_field_xpath)->setValue('11');
      $page->pressButton('Save');
      $assert_session->pageTextContains("Created Content $title");
      $this->assertNodeByTitle($title, 'ief_test_custom');
      $node = $this->getNodeByTitle($title);

      $this->drupalGet("ief-test/$form_mode_possibility/{$node->id()}");

      // Assert node title appears in form.
      $assert_session->elementExists('xpath', $title_field_xpath);
      $this->checkFormDisplayFields("node.ief_test_custom.$form_mode_possibility", 'inline_entity_form');
      $this->assertSame('11', $assert_session->elementExists('xpath', $positive_int_field_xpath)->getValue());
      $assert_session->elementExists('xpath', $title_field_xpath)->setValue($title . ' - updated');
      $page->pressButton('Update');
      $this->assertNodeByTitle($title . ' - updated', 'ief_test_custom');
    }
  }

}
