<?php

namespace Drupal\Tests\az_paragraphs\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Run tests of paragraph bundles.
 *
 * @ingroup az_paragraphs
 *
 * @group az_paragraphs
 */
class AZParagraphsTest extends BrowserTestBase {

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
    'az_paragraphs_html',
    'az_paragraphs_text',
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
    $assert->statusCodeEquals(200);
    $assert->pageTextContains('A Flexible Page for Paragraph Testing');

    // Enter the node editor.
    $page->clickLink('Edit');

    // Add text paragraph bundle.
    $page->pressButton('Add Text');

    // Fill out the text field.
    $page->fillField('field_az_main_content[0][subform][field_az_text_area][0][value]', 'Wilbur Wildcat was here.');

    // Fill out the bottom padding option with a test value.
    $page->fillField('field_az_main_content[0][behavior_plugins][az_default_paragraph_behavior][az_display_settings][bottom_spacing]', 'mb-8');

    // Add HTML paragraph bundle.
    $page->pressButton('Add HTML');

    // Fill out the HTML field with an iframe with custom class.
    $page->fillField('field_az_main_content[1][subform][field_az_full_html][0][value]', '<iframe class="iframe-video" src="https://www.youtube.com/embed/jRLIJkU3YaU"></iframe>');

    // Save the node.
    $page->pressButton('Save');

    // Check for our addition to the node.
    $assert->pageTextContains('Wilbur Wildcat was here.');

    // Check for applied bottom spacing.
    $assert->elementExists('css', '.paragraph.mb-8');

    // Check for iframe with custom class.
    $assert->elementExists('css', '.paragraph--type--az-html .iframe-video');
  }

}
