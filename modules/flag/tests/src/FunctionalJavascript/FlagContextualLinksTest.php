<?php

declare(strict_types=1);

namespace Drupal\Tests\flag\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\flag\Traits\FlagCreateTrait;
use Drupal\Tests\flag\Traits\FlagPermissionsTrait;
use Drupal\flag\Entity\Flag;

/**
 * Test the contextual links with Reload link type.
 *
 * PHPUnit serialises all the $GLOBALS in the system for ::assertLinkMatches
 * to work the backing up of globals need to be disabled.
 *
 * @backupGlobals disabled
 *
 * @group flag
 */
class FlagContextualLinksTest extends WebDriverTestBase {

  use FlagCreateTrait;
  use FlagPermissionsTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'contextual',
    'field_ui',
    'flag',
    'node',
    'system',
    'toolbar',
    'user',
  ];

  /**
   * The flag.
   *
   * @var \Drupal\flag\FlagInterface
   */
  protected $flag;

  /**
   * A user with Flag admin rights.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $adminUser;

  /**
   * An authenticated user to test flagging.
   *
   * @var \Drupal\user\Entity\User|false
   */
  protected $alice;

  /**
   * An authenticated user to test flagging.
   *
   * Used to test cache contexts.
   *
   * @var \Drupal\user\Entity\User|false
   */
  protected $bob;

  /**
   * The node type to use in the test.
   *
   * @var string
   */
  protected $nodeType = 'article';

  /**
   * The flag service.
   *
   * @var \Drupal\flag\FlagServiceInterface
   */
  protected $flagService;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    // Get the Flag Service.
    $this->flagService = $this->container->get('flag');

    // Create content type.
    $this->drupalCreateContentType(['type' => $this->nodeType]);

    // Create the admin user.
    $this->adminUser = $this->createUser([
      'administer flags',
      'administer flagging display',
      'administer flagging fields',
      'administer node display',
      'administer modules',
      'administer nodes',
      'create ' . $this->nodeType . ' content',
      'edit any ' . $this->nodeType . ' content',
      'delete any ' . $this->nodeType . ' content',
      'access contextual links',
      'access content',
      'access toolbar',
    ]);

    // Create a regular user who will be flagging content.
    $this->alice = $this->drupalCreateUser([
      'create ' . $this->nodeType . ' content',
      'edit any ' . $this->nodeType . ' content',
      'delete any ' . $this->nodeType . ' content',
      'access contextual links',
      'access content',
      'access toolbar',
    ]);

    // Create a second regular user who will be flagging content.
    $this->bob = $this->drupalCreateUser([
      'create ' . $this->nodeType . ' content',
      'edit any ' . $this->nodeType . ' content',
      'delete any ' . $this->nodeType . ' content',
      'access contextual links',
      'access content',
      'access toolbar',
    ]);

    $this->drupalLogin($this->adminUser);

    // Create flag with a Reload link type and enable contextual links display.
    $this->flag = Flag::create([
      'id' => 'test_label_123',
      'label' => $this->randomHTMLString(),
      'entity_type' => 'node',
      'bundles' => array_keys(\Drupal::service('entity_type.bundle.info')->getBundleInfo('node')),
      'flag_short' => $this->randomHTMLString(),
      'unflag_short' => $this->randomHTMLString(),
      'unflag_denied_text' => $this->randomHTMLString(),
      'flag_long' => $this->randomHTMLString(16),
      'unflag_long' => $this->randomHTMLString(16),
      'flag_message' => $this->randomHTMLString(32),
      'unflag_message' => $this->randomHTMLString(32),
      'flag_type' => $this->getFlagType('node'),
      'link_type' => 'reload',
      'flagTypeConfig' => [
        'show_as_field' => FALSE,
        'show_on_form' => FALSE,
        'show_contextual_link' => TRUE,
      ],
      'linkTypeConfig' => [],
      'global' => FALSE,
    ]);
    $this->flag->save();

    // Grant the flag permissions to the authenticated role.
    $this->grantFlagPermissions($this->flag);

  }

  /**
   * Verify the behaviour and rendering of Flag links.
   *
   * @runInSeparateProcess
   */
  public function testFlagLinks() {
    /** @var \Drupal\FunctionalJavascriptTests\JSWebAssert $assert_session */
    $assert_session = $this->assertSession();

    // Create an article, to which our contextual link will be attached.
    $node = $this->drupalCreateNode(['type' => $this->nodeType]);

    // Login as normal user.
    $this->drupalLogin($this->alice);

    // Open node view page that renders Full view mode.
    $this->drupalGet('node/' . $node->id());

    // Click on the edit button to become active.
    $main_contextual_button = $assert_session
      ->waitForElementVisible('css', 'button:contains("Edit")');

    $this->assertNotNull($main_contextual_button);
    $main_contextual_button->click();

    // Expect the contextual links identifier for this node to contain flag_keys
    // metadata related to the flag action.
    $flag_contextual_links_id = 'node:node=' . $node->id() . '&view_mode=full:changed=' . $node->getChangedTime() . '&flag_keys=' . $this->flag->id() . '-flag&langcode=en';

    // Wait for the article contextual link button to appear.
    $contextual_edit = $assert_session
      ->waitForElementVisible('css', 'div[data-contextual-id="' . $flag_contextual_links_id . '"] button');
    $this->assertNotNull($contextual_edit, "Contextual link placeholder with id $flag_contextual_links_id exists (flag).");

    $contextual_edit->click();

    // The contextual link dialog will appear .. containing a flag link.
    $flag_link1 = $assert_session->waitForLink($this->flag->getShortText('flag'));
    $this->assertNotNull($flag_link1);

    $flag_link1->click();

    // Verify the contextual links data are updated to contain unflag links.
    $unflag_contextual_links_id = 'node:node=' . $node->id() . '&view_mode=full:changed=' . $node->getChangedTime() . '&flag_keys=' . $this->flag->id() . '-unflag&langcode=en';
    $contextual_edit2 = $assert_session
      ->waitForElementVisible('css', 'div[data-contextual-id="' . $unflag_contextual_links_id . '"]  button');
    $this->assertNotNull($contextual_edit2, "Contextual link placeholder with id $unflag_contextual_links_id exists (unflag).");

    $contextual_edit2->click();

    // The contextual link dialog will appear .. containing a unflag link.
    $unflag_link1 = $assert_session->waitForLink($this->flag->getShortText('unflag'));
    $this->assertNotNull($unflag_link1);

    // Login as alternate regular user.
    // to verify that the cache context displayed to bob is different.
    $this->drupalLogin($this->bob);

    // Open node view page that renders Full view mode.
    $this->drupalGet('node/' . $node->id());

    // Expect the contextual links identifier for this node to contain flag_keys
    // metadata related to the flag action.
    $contextual_edit3 = $assert_session
      ->waitForElementVisible('css', 'div[data-contextual-id="' . $flag_contextual_links_id . '"] button');
    $this->assertNotNull($contextual_edit3, "Contextual link placeholder with id $flag_contextual_links_id exists.");
    $contextual_edit3->click();

    // The contextual link dialog will appear .. containing a flag link.
    $flag_link2 = $assert_session->waitForLink($this->flag->getShortText('flag'));
    $this->assertNotNull($flag_link2);
  }

}
