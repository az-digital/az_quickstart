<?php

declare(strict_types=1);

namespace Drupal\Tests\flag\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\flag\Traits\FlagCreateTrait;

/**
 * Javascript test for ajax links.
 *
 * @group flag
 */
class LinkTypeAjaxTest extends WebDriverTestBase {

  use FlagCreateTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The flag service.
   *
   * @var \Drupal\flag\FlagServiceInterface
   */
  protected $flagService;

  /**
   * A user with Flag admin rights.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * The node type to use in the test.
   *
   * @var string
   */
  protected $nodeType = 'article';

  /**
   * The flag under test.
   *
   * @var \Drupal\flag\FlagInterface
   */
  protected $flag;

  /**
   * The node to be flagged and unflagged.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $node;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['flag', 'flag_event_test', 'node', 'user'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Get the Flag Service.
    $this->flagService = $this->container->get('flag');

    // Create content type.
    $this->drupalCreateContentType(['type' => $this->nodeType]);

    // Create the admin user.
    $this->adminUser = $this->createUser([], NULL, TRUE);

    $this->flag = $this->createFlag('node', [], 'ajax_link');
    $this->node = $this->drupalCreateNode(['type' => $this->nodeType]);
  }

  /**
   * Test the ajax link type.
   */
  public function testAjaxLink() {
    // Create and login as an authenticated user.
    $auth_user = $this->drupalCreateUser([
      'flag ' . $this->flag->id(),
      'unflag ' . $this->flag->id(),
    ]);
    $this->drupalLogin($auth_user);

    // Navigate to the node page.
    $this->drupalGet($this->node->toUrl());

    /** @var \Drupal\FunctionalJavascriptTests\JSWebAssert $assert_session */
    $assert_session = $this->assertSession();

    // Confirm the flag link exists.
    $assert_session->linkExists($this->flag->getShortText('flag'));

    // Click the flag link.
    $this->clickLink($this->flag->getShortText('flag'));
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->addressEquals($this->node->toUrl());
    $assert_session->linkExists($this->flag->getShortText('unflag'));
    $this->assertNotNull($this->flagService->getFlagging($this->flag, $this->node, $auth_user));

    // Click the unflag link, repeat the check.
    $this->clickLink($this->flag->getShortText('unflag'));
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->addressEquals($this->node->toUrl());
    $assert_session->linkExists($this->flag->getShortText('flag'));
    $this->assertNull($this->flagService->getFlagging($this->flag, $this->node, $auth_user));

    // And flag again.
    $this->clickLink($this->flag->getShortText('flag'));
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->addressEquals($this->node->toUrl());
    $assert_session->linkExists($this->flag->getShortText('unflag'));
    $this->assertNotNull($this->flagService->getFlagging($this->flag, $this->node, $auth_user));

    // Add an unrelated flag, and enable flag events.
    // @see \Drupal\flag_test\EventSubscriber\FlagEvents
    $this->flagService->unflag($this->flag, $this->node, $auth_user);
    $flag_b = $this->createFlag();
    $this->container->get('flag')->flag($flag_b, $this->node, $auth_user);
    $this->container->get('state')
      ->set('flag_test.react_flag_event', $flag_b->id());
    $this->container->get('state')
      ->set('flag_test.react_unflag_event', $flag_b->id());

    // Navigate to the node page.
    $this->drupalGet($this->node->toUrl());

    // Confirm the flag link exists.
    $assert_session->linkExists($this->flag->getShortText('flag'));

    // Click the flag link.
    $this->clickLink($this->flag->getShortText('flag'));
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->addressEquals($this->node->toUrl());
    $assert_session->linkExists($this->flag->getShortText('unflag'));
    $this->assertNotNull($this->flagService->getFlagging($this->flag, $this->node, $auth_user));

    // Verifies that the event subscriber was called.
    $this->assertTrue($this->container->get('state')->get('flag_test.is_flagged', FALSE));

    // Click the unflag link, repeat the check.
    $this->clickLink($this->flag->getShortText('unflag'));
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->addressEquals($this->node->toUrl());
    $assert_session->linkExists($this->flag->getShortText('flag'));
    $this->assertNull($this->flagService->getFlagging($this->flag, $this->node, $auth_user));

    // Verifies that the event subscriber was called.
    $this->assertTrue($this->container->get('state')->get('flag_test.is_unflagged', FALSE));

    // And flag again.
    $this->clickLink($this->flag->getShortText('flag'));
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->addressEquals($this->node->toUrl());
    $assert_session->linkExists($this->flag->getShortText('unflag'));
    $this->assertNotNull($this->flagService->getFlagging($this->flag, $this->node, $auth_user));
  }

}
