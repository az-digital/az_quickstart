<?php

namespace Drupal\Tests\az_publication\FunctionalJavascript;

use Drupal\Tests\az_core\FunctionalJavascript\QuickstartFunctionalJavascriptTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\IgnorePhpunitDeprecations;

/**
 * Run tests of publication contributor role functionality.
 */
#[Group('az_publication')]
#[IgnoreDeprecations]
#[IgnorePhpunitDeprecations]
#[RunTestsInSeparateProcesses]
class AZPublicationInlineContributorTest extends QuickstartFunctionalJavascriptTestBase {

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
    'az_publication',
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
  protected function setUp(): void {
    parent::setUp();

    // Set up our initial permissions.
    $this->user = $this->drupalCreateUser([
      'add author entities',
      'administer site configuration',
      'create az_publication content',
      'edit any az_publication content',
      'edit author entities',
      'edit own az_publication content',
    ]);

    // Create a node for testing the paragraph bundles.
    $this->node = $this->createNode([
      'type' => 'az_publication',
      'title' => 'A Publication for Testing',
    ]);

    $this->drupalLogin($this->user);
  }

  /**
   * Test the process of adding a flexible page text paragraph.
   */
  public function testInlineContributor() {

    // Make sure that our node was created.
    $this->assertNotNull($this->node);

    /** @var \Drupal\FunctionalJavascriptTests\WebDriverWebAssert $assert */
    $assert = $this->assertSession();
    $page = $this->getSession()->getPage();

    // Make sure we can successfully visit our test node.
    $this->drupalGet($this->node->toUrl());
    $assert->pageTextContains('A Publication for Testing');

    // Enter the node editor.
    $page->clickLink('Edit');

    // Add new contributor to pub.
    $assert->waitForButton('Add new contributor')->click();

    // Fill out the first name.
    $assert->waitForField('field_az_contributors[form][0][field_az_author_fname][0][value]');
    $page->fillField('field_az_contributors[form][0][field_az_author_fname][0][value]', 'Wilbur');

    // Fill out the last name.
    $assert->waitForField('field_az_contributors[form][0][field_az_author_lname][0][value]');
    $page->fillField('field_az_contributors[form][0][field_az_author_lname][0][value]', 'Wildcat');

    // Save contributor.
    $assert->waitForButton('Create contributor')->click();

    // Fill out the role field.
    $assert->waitForField('field_az_contributors[entities][0][role]');
    $page->fillField('field_az_contributors[entities][0][role]', 'translator');

    // Save the node.
    $page->pressButton('Save');

    // Check our publication exists and has the contributor.
    $assert->pageTextContains('Publication for Testing');
    $assert->pageTextContains('Wildcat, Wilbur, translator.');
  }

}
