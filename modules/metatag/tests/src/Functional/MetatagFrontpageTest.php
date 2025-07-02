<?php

namespace Drupal\Tests\metatag\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Ensures that meta tags are rendering correctly on home page.
 *
 * @group metatag
 */
class MetatagFrontpageTest extends BrowserTestBase {

  use MetatagHelperTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'token',
    'metatag',
    'node',
    'system',
    'test_page_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The path to a node that is created for testing.
   *
   * @var string
   */
  protected $nodeId;

  /**
   * Setup basic environment.
   */
  protected function setUp(): void {
    parent::setUp();

    // Login user 1.
    $this->loginUser1();

    // Create content type.
    $this->drupalCreateContentType([
      'type' => 'page',
      'display_submitted' => FALSE,
    ]);
    $this->nodeId = $this->drupalCreateNode(
      [
        'title' => $this->randomMachineName(8),
        'promote' => 1,
      ])->id();

    $this->config('system.site')
      ->set('page.front', '/node/' . $this->nodeId)
      ->save();
  }

  /**
   * The front page config is enabled, its meta tags should be used.
   */
  public function testFrontPageMetatagsEnabledConfig() {
    // Add something to the front page config.
    $this->drupalGet('admin/config/search/metatag/front');
    $session = $this->assertSession();
    $session->statusCodeEquals(200);
    $edit = [
      'title' => 'Test title',
      'description' => 'Test description',
      'keywords' => 'testing,keywords',
    ];
    $this->submitForm($edit, 'Save');
    $session->statusCodeEquals(200);
    $session->pageTextContains('Saved the Front page Metatag defaults.');

    // Testing front page metatags.
    $this->drupalGet('<front>');
    foreach ($edit as $metatag => $metatag_value) {
      $xpath = $this->xpath("//meta[@name='" . $metatag . "']");
      if ($metatag == 'title') {
        $this->assertCount(0, $xpath, 'Title meta tag not found.');
        $xpath = $this->xpath("//title");
        $this->assertCount(1, $xpath, 'Head title tag found.');
        $value = $xpath[0]->getText();
      }
      else {
        $this->assertCount(1, $xpath, 'Exactly one ' . $metatag . ' meta tag found.');
        $value = $xpath[0]->getAttribute('content');
      }
      $this->assertEquals($value, $metatag_value);
    }

    $node_path = '/node/' . $this->nodeId;
    // Testing front page metatags.
    $this->drupalGet($node_path);
    foreach ($edit as $metatag => $metatag_value) {
      $xpath = $this->xpath("//meta[@name='" . $metatag . "']");
      if ($metatag == 'title') {
        $this->assertCount(0, $xpath, 'Title meta tag not found.');
        $xpath = $this->xpath("//title");
        $this->assertCount(1, $xpath, 'Head title tag found.');
        $value = $xpath[0]->getText();
      }
      else {
        $this->assertCount(1, $xpath, 'Exactly one ' . $metatag . ' meta tag found.');
        $value = $xpath[0]->getAttribute('content');
      }
      $this->assertEquals($value, $metatag_value);
    }

    // Change the front page to a valid custom route.
    $site_edit = [
      'site_frontpage' => '/test-page',
    ];
    $this->drupalGet('admin/config/system/site-information');
    $session->statusCodeEquals(200);
    $this->submitForm($site_edit, 'Save configuration');
    $session->pageTextContains('The configuration options have been saved.');

    // @todo Finish this?
    // @code
    // $this->drupalGet('test-page');
    // $session->statusCodeEquals(200);
    // foreach ($edit as $metatag => $metatag_value) {
    //   $xpath = $this->xpath("//meta[@name='" . $metatag . "']");
    //   $assert_message = 'Exactly one ' . $metatag . ' meta tag found.';
    //   $this->assertCount(1, $xpath, $assert_message);
    //   $value = $xpath[0]->getAttribute('content');
    //   $this->assertEquals($value, $metatag_value);
    // }
    // @endcode
  }

  /**
   * Test front page meta tags when front page config is disabled.
   */
  public function testFrontPageMetatagDisabledConfig() {
    // Disable front page metatag, enable node metatag & check.
    $this->drupalGet('admin/config/search/metatag/front/delete');
    $session = $this->assertSession();
    $session->statusCodeEquals(200);
    $this->submitForm([], 'Delete');
    $session->statusCodeEquals(200);
    $session->pageTextContains('Deleted Front page defaults.');

    // Update the Metatag Node defaults.
    $this->drupalGet('admin/config/search/metatag/node');
    $session->statusCodeEquals(200);
    $edit = [
      'title' => 'Test title for a node.',
      'description' => 'Test description for a node.',
    ];
    $this->submitForm($edit, 'Save');
    $session->pageTextContains('Saved the Content Metatag defaults.');
    $this->drupalGet('<front>');
    foreach ($edit as $metatag => $metatag_value) {
      $xpath = $this->xpath("//meta[@name='" . $metatag . "']");
      if ($metatag == 'title') {
        $this->assertCount(0, $xpath, 'Title meta tag not found.');
        $xpath = $this->xpath("//title");
        $this->assertCount(1, $xpath, 'Head title tag found.');
        $value = $xpath[0]->getText();
      }
      else {
        $this->assertCount(1, $xpath, 'Exactly one ' . $metatag . ' meta tag found.');
        $value = $xpath[0]->getAttribute('content');
      }
      $this->assertEquals($value, $metatag_value);
    }

    // Change the front page to a valid path.
    $this->drupalGet('admin/config/system/site-information');
    $session->statusCodeEquals(200);
    $edit = [
      'site_frontpage' => '/test-page',
    ];
    $this->submitForm($edit, 'Save configuration');
    $session->pageTextContains('The configuration options have been saved.');

    // Front page is custom route.
    // Update the Metatag Node global.
    $this->drupalGet('admin/config/search/metatag/global');
    $session->statusCodeEquals(200);
    $edit = [
      'title' => 'Test title.',
      'description' => 'Test description.',
    ];
    $this->submitForm($edit, 'Save');
    $session->pageTextContains('Saved the Global Metatag defaults.');

    // Test Metatags.
    $this->drupalGet('test-page');
    $session->statusCodeEquals(200);
    foreach ($edit as $metatag => $metatag_value) {
      $xpath = $this->xpath("//meta[@name='" . $metatag . "']");
      if ($metatag == 'title') {
        $this->assertCount(0, $xpath, 'Title meta tag not found.');
        $xpath = $this->xpath("//title");
        $this->assertCount(1, $xpath, 'Head title tag found.');
        $value = $xpath[0]->getText();
      }
      else {
        $this->assertCount(1, $xpath, 'Exactly one ' . $metatag . ' meta tag found.');
        $value = $xpath[0]->getAttribute('content');
      }
      $this->assertEquals($value, $metatag_value);
    }
  }

}
