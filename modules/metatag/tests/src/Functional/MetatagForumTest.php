<?php

namespace Drupal\Tests\metatag\Functional;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Tests\BrowserTestBase;

/**
 * Ensures that meta tags are rendering correctly on forum pages.
 *
 * @group metatag
 */
class MetatagForumTest extends BrowserTestBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'token',
    'metatag',
    'node',
    'system',
    'forum',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Administrator user for tests.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * The nid of a node that is being tested.
   *
   * @var int
   */
  protected $nodeId;

  /**
   * Setup basic environment.
   */
  protected function setUp(): void {
    parent::setUp();

    $admin_permissions = [
      'administer nodes',
      'bypass node access',
      'administer meta tags',
      'administer site configuration',
      'access content',
    ];

    // Create and login user.
    $this->adminUser = $this->drupalCreateUser($admin_permissions);
    $this->drupalLogin($this->adminUser);

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

    $this->config('system.site')->set('page.front', '/node/' . $this->nodeId)->save();
  }

  /**
   * Verify that a forum post can be loaded when Metatag is enabled.
   */
  public function testForumPost() {
    $this->drupalGet('node/add/forum');
    $session = $this->assertSession();
    $session->statusCodeEquals(200);
    $edit = [
      'title[0][value]' => 'Testing forums',
      'taxonomy_forums' => 1,
      'body[0][value]' => 'Just testing.',
    ];
    $this->submitForm($edit, 'Save');
    $session->statusCodeEquals(200);
    $session->pageTextContains('Forum topic Testing forums has been created.');
  }

}
