<?php

namespace Drupal\Tests\paragraphs_admin\Functional;

use Drupal\paragraphs\Entity\Paragraph;
use Drupal\Tests\paragraphs\Functional\WidgetStable\ParagraphsTestBase;
use Drupal\Tests\paragraphs\FunctionalJavascript\ParagraphsTestBaseTrait;

/**
 * Tests file listing page functionality.
 *
 * @group file
 */
class ParagraphListingTest extends ParagraphsTestBase {

  use ParagraphsTestBaseTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'paragraphs',
    'paragraphs_admin',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests file overview with different user permissions.
   */
  public function testParagraphListingPages() {
    $this->addParagraphsType('paragraphed_test');

    // Users without sufficient permissions should not see paragraph listing.
    $basicUser = $this->drupalCreateUser();
    $admin = $this->drupalCreateUser(['administer paragraphs']);

    $this->drupalLogin($basicUser);
    $this->drupalGet('admin/content/paragraphs');
    $this->assertSession()->statusCodeEquals(403);

    // Log in with user with right permissions and test listing.
    $this->loginAsAdmin($admin);
    $this->drupalGet('admin/content/paragraphs');
    $this->assertSession()->pageTextContains('Paragraphs');

    // Create a paragraph.
    $paragraph = $this->createParagraph();
    $pid = $paragraph->id();

    // Check paragraph exists.
    $delete_link = 'paragraph/' . $pid . '/delete';
    $this->drupalGet('admin/content/paragraphs');
    $this->assertSession()->linkByHrefExists($delete_link);

    // Delete paragraph.
    $this->drupalGet($delete_link);

    // Check that paragraph was deleted.
    $this->assertSession()->pageTextContains('Paragraph ' . $pid . ' deleted.');
    $this->assertSession()->linkByHrefNotExists($delete_link);
  }

  /**
   * Creates and saves a test paragraph.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   A paragraph entity.
   */
  protected function createParagraph() {
    // Create a new paragraph entity.
    $paragraph = Paragraph::create([
      'type' => 'paragraphed_test',
      'langcode' => 'en',
    ]);
    $paragraph->save();

    return $paragraph;
  }

}
