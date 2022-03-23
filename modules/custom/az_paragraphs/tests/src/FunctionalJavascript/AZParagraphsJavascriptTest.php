<?php

namespace Drupal\Tests\az_paragraphs\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Run tests of paragraph bundles.
 *
 * @ingroup az_paragraphs_js
 *
 * @group az_paragraphs_js
 */
class AZParagraphsJavascriptTest extends WebDriverTestBase {

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
   * @var string
   */
  protected $defaultTheme = 'az_barrio';

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'az_paragraphs',
    'az_paragraphs_cards',
    'az_flexible_page',
    'node',
  ];

  /**
   * A node for paragraph tests.
   *
   * @var \Drupal\node\Entity\Node
   */
  protected $node;

  /**
   * A user with permission to work with pages.
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
      'create az_flexible_page content',
      'edit any az_flexible_page content',
      'edit behavior plugin settings',
      'edit own az_flexible_page content',
      'use text format full_html',
    ]);

    // Create a node for testing the paragraph bundles.
    $this->node = $this->createNode([
      'type' => 'az_flexible_page',
      'title' => 'A Flexible Page for Paragraph Testing',
    ]);

    $this->drupalLogin($this->user);
  }

  /**
   * Test the process of adding a flexible page text paragraph.
   */
  public function testParagraphs() {

    // Make sure that our node was created.
    $this->assertNotNull($this->node);

    $assert = $this->assertSession();
    $page = $this->getSession()->getPage();

    // Make sure we can successfully visit our test node.
    $this->drupalGet($this->node->toUrl());
    $assert->pageTextContains('A Flexible Page for Paragraph Testing');

    // Enter the node editor.
    $page->clickLink('Edit');

    // Add card paragraph bundle.
    $assert->waitForButton('Add Cards')->click();

    // Fill out the card title.
    $assert->waitForField('field_az_main_content[0][subform][field_az_cards][0][title]');
    $page->fillField('field_az_main_content[0][subform][field_az_cards][0][title]', 'Card One');

    // Add a second card.
    $assert->waitForButton('Add another item')->click();

    // Fill out the card title.
    $assert->waitForField('field_az_main_content[0][subform][field_az_cards][1][title]');
    $page->fillField('field_az_main_content[0][subform][field_az_cards][1][title]', 'Card Two');

    // Save the node.
    $page->pressButton('Save');

    // Check for our additions to the node.
    $assert->pageTextContains('Card One');
    $assert->pageTextContains('Card Two');
  }

}
