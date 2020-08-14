<?php

namespace Drupal\Tests\az_layouts\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Run tests of layout buidler modifications.
 *
 * @ingroup az_layouts
 *
 * @group az_layouts
 */
class AZLayoutsUITest extends BrowserTestBase {

  /**
   * The profile to install as a basis for testing.
   *
   * @var string
   */
  protected $profile = 'az_quickstart';

  /**
   * @var bool
   */
  protected $strictConfigSchema = FALSE;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'az_layouts',
    'block_content',
    'layout_builder',
    'block',
    'node',
  ];

  /**
   * A node for layout tests.
   *
   * @var \Drupal\node\Entity\Node
   */
  protected $node;

  /**
   * A user with permission to work with layouts.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Set up our initial permissions.
    $this->user = $this->drupalCreateUser([
      'administer site configuration',
      'access contextual links',
      'configure any layout',
      'administer node display',
      'administer node fields',
      'create and edit custom blocks',
    ]);

    // Create a node for testing the layout UI.
    $this->node = $this->createNode([
      'type' => 'az_flexible_page',
      'title' => 'A Flexible Page for Layout Testing',
    ]);

    $this->drupalLogin($this->user);
  }

  /**
   * Test the process of adding a flexible page block.
   */
  public function testAddCustomInlineBlock() {

    // Make sure that our node was created.
    $this->assertNotNull($this->node);

    $assert = $this->assertSession();
    $page = $this->getSession()->getPage();

    // Make sure we can successfully visit our test node.
    $this->drupalGet($this->node->toUrl());
    $assert->statusCodeEquals(200);
    $assert->pageTextContains('A Flexible Page for Layout Testing');

    // Enter the layout editor.
    $page->clickLink('Layout');

    // Enter the add section interface.
    $page->clickLink('Add section');

    // Choose a known layout option.
    $page->clickLink('One column layout');

    // Add the section.
    $page->pressButton('Add section');

    // Check for proper title. If our route override worked correctly,
    // we should have skipped directly to the add custom block screen.
    $assert->pageTextContains('Add Flexible Content');

    // Choose a known flexible page custom block.
    $page->clickLink('Text Area');

    // Fill out the text field.
    $page->fillField('settings[block_form][field_az_adv_content][0][value]', 'Wilbur Wildcat was here.');

    // Add the custom block.
    $page->pressButton('Add block');

    // Save the layout modification.
    $page->pressButton('Save layout');

    // Check for our addition to the node.
    $assert->pageTextContains('Wilbur Wildcat was here.');
  }

}
