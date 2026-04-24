<?php

namespace Drupal\Tests\az_flexible_page\Functional;

use Drupal\Tests\az_core\Functional\QuickstartFunctionalTestBase;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Run tests of paragraph bundles.
 */
#[Group('az_flexible_page')]
#[RunTestsInSeparateProcesses]
class FlexiblePageTest extends QuickstartFunctionalTestBase {

  use ContentTypeCreationTrait;

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
   * @var string[]
   */
  protected static $modules = [
    'az_paragraphs',
    'az_paragraphs_html',
    'az_paragraphs_text',
    'az_flexible_page',
    'node',
  ];

  /**
   * A user with permission to work with pages.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Set up our initial permissions.
    $this->user = $this->drupalCreateUser([
      'create az_flexible_page content',
    ]);

    $this->drupalLogin($this->user);
  }

  /**
   * Test the process of creating a flexible page node.
   */
  public function testFlexiblePageCreation() {
    $this->drupalGet('node/add/az_flexible_page');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->addressEquals('node/add/az_flexible_page');
    // Create a Flexible page.
    $edit = [];
    $edit['title[0][value]'] = $this->randomMachineName(8);
    $this->drupalGet('node/add/az_flexible_page');
    $this->submitForm($edit, 'Save');

    // Check that the Flexible page has been created.
    $this->assertSession()->pageTextContains('Page ' . $edit['title[0][value]'] . ' has been created.');

    // Check that the Flexible page exists in the database.
    $node = $this->drupalGetNodeByTitle($edit['title[0][value]']);
    $this->assertNotEmpty($node, 'Flexible page found in database.');

    // Verify that Flexible page loads with the correct title.
    $this->drupalGet('node/' . $node->id());
    $this->assertSession()->pageTextContains($edit['title[0][value]']);
  }

}
