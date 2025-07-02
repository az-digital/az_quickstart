<?php

namespace Drupal\Tests\inline_entity_form\FunctionalJavascript;

use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * IEF complex field widget containing an IEF simple field widget tests.
 *
 * @group inline_entity_form
 */
class ComplexSimpleWidgetTest extends InlineEntityFormTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'inline_entity_form_test',
    'field',
    'field_ui',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->user = $this->createUser([
      'create ief_complex_simple content',
      'create ief_simple_single content',
      'create ief_test_custom content',
      'view own unpublished content',
    ]);
    $this->drupalLogin($this->user);
    $this->fieldConfigStorage = $this->container
      ->get('entity_type.manager')
      ->getStorage('field_config');
  }

  /**
   * Test a Simple IEF widget inside of Complex IEF widget.
   */
  public function testSimpleInComplex() {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();
    $outer_required_options = [
      TRUE,
      FALSE,
    ];
    $cardinality_options = [
      1 => 1,
      2 => 2,
      FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED => 3,
    ];
    $first_title_field_xpath = $this->getXpathForNthInputByLabelText('Title', 1);
    $outer_title_field_xpath = $this->getXpathForNthInputByLabelText('Title', 2);
    $inner_title_field_xpath = $this->getXpathForNthInputByLabelText('Title', 3);
    /** @var \Drupal\field\FieldStorageConfigInterface $field_storage */
    $field_storage = $this->fieldStorageConfigStorage->load('node.ief_complex_outer');
    /** @var \Drupal\Core\Field\FieldConfigInterface $field_config */
    $field_config = $this->fieldConfigStorage->load('node.ief_complex_simple.ief_complex_outer');
    foreach ($outer_required_options as $outer_required_option) {
      $field_config->setRequired($outer_required_option);
      $field_config->save();
      foreach ($cardinality_options as $cardinality => $limit) {
        $field_storage->setCardinality($cardinality);
        $field_storage->save();

        $this->drupalGet('node/add/ief_complex_simple');
        if (!$outer_required_option) {
          $assert_session->pageTextContains('Complex Outer');

          // Field should not be available before ajax submit.
          $assert_session->elementNotExists('xpath', $outer_title_field_xpath);
          $assert_session
            ->elementExists('xpath', '//input[@type="submit" and @value="Add new node"]')
            ->press();
          $this->assertNotEmpty($assert_session->waitForElement('xpath', $outer_title_field_xpath));
        }
        $outer_title = $this->randomMachineName();
        $inner_title = $this->randomMachineName();
        $assert_session->elementExists('xpath', $outer_title_field_xpath)->setValue($outer_title);

        // Simple widget is required so should always show up. No need to add
        // submit.
        $assert_session->elementExists('xpath', $inner_title_field_xpath)->setValue($inner_title);
        $create_outer_button_selector = '//input[@type="submit" and @value="Create node"]';
        $assert_session->elementExists('xpath', $create_outer_button_selector)->press();

        // After Ajax submit the ief title fields should be gone.
        $this->assertNotEmpty($assert_session->waitForButton('Edit'));
        $assert_session->elementNotExists('xpath', $outer_title_field_xpath);
        $assert_session->elementNotExists('xpath', $inner_title_field_xpath);
        $assert_session->elementNotExists('xpath', $create_outer_button_selector);

        // The nodes should not actually be saved at this point.
        $this->assertNoNodeByTitle($outer_title, 'Outer node was not created when widget submitted.');
        $this->assertNoNodeByTitle($inner_title, 'Inner node was not created when widget submitted.');

        $host_title = $this->randomMachineName();
        $assert_session->elementExists('xpath', $first_title_field_xpath)->setValue($host_title);
        $page->pressButton('Save');
        $assert_session->pageTextContains("$host_title has been created.");
        $assert_session->pageTextContains($outer_title);

        // Check the nodes were created correctly.
        $host_node = $this->drupalGetNodeByTitle($host_title);
        $this->assertNotNull($host_node->ief_complex_outer->entity, 'Outer node was created.');
        if (isset($host_node->ief_complex_outer->entity)) {
          $outer_node = $host_node->ief_complex_outer->entity;
          $this->assertEquals($outer_title, $outer_node->label(), "Outer node's title looks correct.");
          $this->assertEquals('ief_simple_single', $outer_node->bundle(), "Outer node's type looks correct.");
          $this->assertNotNull($outer_node->single->entity, 'Inner node was created');
          if (isset($outer_node->single->entity)) {
            $inner_node = $outer_node->single->entity;
            $this->assertEquals($inner_title, $inner_node->label(), "Inner node's title looks correct.");
            $this->assertEquals('ief_test_custom', $inner_node->bundle(), "Inner node's type looks correct.");
          }
        }
      }
    }
  }

}
