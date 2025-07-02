<?php

namespace Drupal\Tests\metatag\Functional;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\field_ui\Traits\FieldUiTestTrait;

/**
 * Ensures that meta tags do not allow xss vulnerabilities.
 *
 * @group metatag
 */
class MetatagXssTest extends BrowserTestBase {

  use FieldUiTestTrait;
  use StringTranslationTrait;

  /**
   * String that causes an alert when page titles aren't filtered for xss.
   *
   * @var string
   */
  private $xssTitleString = '<script>alert("xss");</script>';

  /**
   * String that causes an alert when meta tags aren't filtered for xss.
   *
   * @var string
   */
  private $xssString = '"><script>alert("xss");</script><meta "';

  /**
   * Rendered xss tag that has escaped attribute to avoid xss injection.
   *
   * @var string
   */
  private $escapedXssTag = '<meta name="abstract" content="&quot;&gt;alert(&quot;xss&quot;);" />';

  /**
   * String that causes an alert when meta tags aren't filtered for xss.
   *
   * "Image" meta tags are processed differently to others, so this checks for a
   * different string.
   *
   * @var string
   */
  private $xssImageString = '"><script>alert("image xss");</script><meta "';

  /**
   * Rendered xss tag that has escaped attribute to avoid xss injection.
   *
   * @var string
   */
  private $escapedXssImageTag = '<link rel="image_src" href="&quot;&gt;alert(&quot;image xss&quot;);" />';

  /**
   * Administrator user for tests.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'views',
    'system',
    'field',
    'field_ui',
    'token',
    'metatag',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create a user that can manage content types and create content.
    $admin_permissions = [
      'administer content types',
      'administer nodes',
      'bypass node access',
      'administer meta tags',
      'administer site configuration',
      'access content',
      'administer content types',
      'administer nodes',
      'administer node fields',
    ];

    // Create and login a with the admin-ish permissions user.
    $this->adminUser = $this->drupalCreateUser($admin_permissions);
    $this->drupalLogin($this->adminUser);

    // Set up a content type.
    $this->drupalCreateContentType([
      'type' => 'metatag_node',
      'name' => 'Test Content Type',
    ]);

    // Add a metatag field to the content type.
    $this->fieldUIAddNewField('admin/structure/types/manage/metatag_node', 'metatag_field', 'Metatag', 'metatag');
  }

  /**
   * Verify XSS injected in global config is not rendered.
   */
  public function testXssMetatagConfig() {
    $this->drupalGet('admin/config/search/metatag/global');
    $session = $this->assertSession();
    $session->statusCodeEquals(200);
    $values = [
      'title' => $this->xssTitleString,
      'abstract' => $this->xssString,
      'image_src' => $this->xssImageString,
    ];
    $this->submitForm($values, 'Save');
    $session->pageTextContains('Saved the Global Metatag defaults.');
    $this->rebuildAll();

    // Load the Views-based front page.
    $this->drupalGet('node');
    $session->statusCodeEquals(200);
    $session->pageTextContains('No front page content has been created yet.');

    // Check for the title tag, which will have the HTML tags removed and then
    // be lightly HTML encoded.
    $session->assertEscaped(strip_tags($this->xssTitleString));
    $session->responseNotContains($this->xssTitleString);

    // Check for the basic meta tag.
    $session->responseContains($this->escapedXssTag);
    $session->responseNotContains($this->xssString);

    // Check for the image meta tag.
    $session->responseContains($this->escapedXssImageTag);
    $session->responseNotContains($this->xssImageString);
  }

  /**
   * Verify XSS injected in the entity metatag override field is not rendered.
   */
  public function testXssEntityOverride() {

    $this->drupalGet('node/add/metatag_node');
    $session = $this->assertSession();
    $session->statusCodeEquals(200);
    $edit = [
      'title[0][value]' => $this->randomString(32),
      'field_metatag_field[0][basic][title]' => $this->xssTitleString,
      'field_metatag_field[0][basic][abstract]' => $this->xssString,
      'field_metatag_field[0][advanced][image_src]' => $this->xssImageString,
    ];
    $this->submitForm($edit, 'Save');

    // Check for the title tag, which will have the HTML tags removed and then
    // be lightly HTML encoded.
    $session->assertEscaped(strip_tags($this->xssTitleString));
    $session->responseNotContains($this->xssTitleString);

    // Check for the basic meta tag.
    $session->responseContains($this->escapedXssTag);
    $session->responseNotContains($this->xssString);

    // Check for the image meta tag.
    $session->responseContains($this->escapedXssImageTag);
    $session->responseNotContains($this->xssImageString);
  }

  /**
   * Verify XSS injected in the entity titles are not rendered.
   */
  public function testXssEntityTitle() {

    $this->drupalGet('node/add/metatag_node');
    $session = $this->assertSession();
    $session->statusCodeEquals(200);
    $edit = [
      'title[0][value]' => $this->xssTitleString,
      'body[0][value]' => $this->randomString() . ' ' . $this->randomString(),
    ];
    $this->submitForm($edit, 'Save');

    // Check for the title tag, which will have the HTML tags removed and then
    // be lightly HTML encoded.
    $session->assertEscaped(strip_tags($this->xssTitleString));
    $session->responseNotContains($this->xssTitleString);
  }

  /**
   * Verify XSS injected in the entity fields are not rendered.
   */
  public function testXssEntityBody() {

    $this->drupalGet('node/add/metatag_node');
    $session = $this->assertSession();
    $session->statusCodeEquals(200);
    $edit = [
      'title[0][value]' => $this->randomString(),
      'body[0][value]' => $this->xssTitleString,
    ];
    $this->submitForm($edit, 'Save');

    // Check the body text.
    // @code
    // $this->assertNoTitle($this->xssTitleString);
    // @endcode
    $session->responseNotContains($this->xssTitleString);
  }

}
