<?php

declare(strict_types=1);

namespace Drupal\Tests\flag\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\flag\Entity\Flag;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;

/**
 * Tests the entity form checkbox output respects flag access control.
 *
 * @group flag
 */
class OutputLocationEntityFormAccessTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'node',
    'flag',
    'flag_test_plugins',
  ];

  /**
   * The node whose edit form is shown.
   *
   * @var \Drupal\node\Entity\Node
   */
  protected $node;

  /**
   * A flag that grants access.
   *
   * @var \Drupal\flag\Entity\Flag
   */
  protected $flagGranted;

  /**
   * A flag that denies access.
   *
   * @var \Drupal\flag\Entity\Flag
   */
  protected $flagDenied;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    NodeType::create(['type' => 'page', 'name' => 'page'])->save();
    $this->node = Node::create(['type' => 'page', 'title' => 'test']);
    $this->node->save();

    $this->flagGranted = Flag::create([
      'id' => 'flag_granted',
      'label' => 'Flag allowed',
      'entity_type' => 'node',
      'bundles' => ['page'],
      // Use dummy flag type plugins that return a known access value so we're
      // not involving the actual access system.
      'flag_type' => 'test_access_granted',
      'link_type' => 'reload',
      'flagTypeConfig' => [
        'show_on_form' => TRUE,
      ],
      'linkTypeConfig' => [],
      'flag_short' => 'Flag this',
      'unflag_short' => 'Unflag this',
    ]);
    $this->flagGranted->save();

    $this->flagDenied = Flag::create([
      'id' => 'flag_denied',
      'label' => 'Flag denied',
      'entity_type' => 'node',
      'bundles' => ['page'],
      'flag_type' => 'test_access_denied',
      'link_type' => 'reload',
      'flagTypeConfig' => [
        'show_on_form' => TRUE,
      ],
      'linkTypeConfig' => [],
      'flag_short' => 'Flag this',
      'unflag_short' => 'Unflag this',
    ]);
    $this->flagDenied->save();

    // Create and login as an authenticated user.
    $auth_user = $this->drupalCreateUser([
      'access content',
      'edit any page content',
    ]);
    $this->drupalLogin($auth_user);
  }

  /**
   * Tests the access to the flag checkbox in the node edit form.
   */
  public function testCheckboxAccess() {
    // Get the node edit form.
    $this->drupalGet("node/" . $this->node->id() . "/edit");

    $this->assertSession()->pageTextContains('Flag allowed');
    $this->assertSession()->pageTextNotContains('Flag denied');
  }

}
