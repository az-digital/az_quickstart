<?php

declare(strict_types=1);

namespace Drupal\Tests\flag\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\flag\Traits\FlagCreateTrait;

/**
 * Tests Flag module permissions.
 *
 * @group flag
 */
class FlagPermissionsTest extends BrowserTestBase {

  use FlagCreateTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['flag', 'node', 'user'];

  /**
   * The flag under test.
   *
   * @var \Drupal\flag\Entity\Flag
   */
  protected $flag;

  /**
   * The node to flag.
   *
   * @var \Drupal\node\Entity\Node
   */
  protected $node;

  /**
   * A user who can flag and unflag.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $fullFlagUser;

  /**
   * A user who can only flag.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $flagOnlyUser;

  /**
   * A user with no flag permissions.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $authUser;

  /**
   * The node type to use in the test.
   *
   * @var string
   */
  protected $nodeType = 'article';

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    // Create content type.
    $this->drupalCreateContentType(['type' => $this->nodeType]);

    // Create the flag.
    $this->flag = $this->createFlag();

    // Create the full permission flag user.
    $this->fullFlagUser = $this->drupalCreateUser([
      'flag ' . $this->flag->id(),
      'unflag ' . $this->flag->id(),
    ]);

    // Create the flag only user.
    $this->flagOnlyUser = $this->drupalCreateUser([
      'flag ' . $this->flag->id(),
    ]);

    // Create a user with no flag permissions.
    $this->authUser = $this->drupalCreateUser();

    // Create a node to test.
    $this->node = $this->drupalCreateNode(['type' => $this->nodeType]);
  }

  /**
   * Test permissions.
   */
  public function testPermissions() {
    $assert_session = $this->assertSession();

    // Check the full flag permission user can flag...
    $this->drupalLogin($this->fullFlagUser);
    $this->drupalGet('node/' . $this->node->id());
    $assert_session->linkExists($this->flag->getShortText('flag'));
    $this->clickLink($this->flag->getShortText('flag'));
    $assert_session->statusCodeEquals(200);

    // ...and also unflag.
    $this->drupalGet('node/' . $this->node->id());
    $assert_session->statusCodeEquals(200);
    $assert_session->linkExists($this->flag->getShortText('unflag'));

    // Check the flag only user can flag...
    $this->drupalLogin($this->flagOnlyUser);
    $this->drupalGet('node/' . $this->node->id());
    $assert_session->linkExists($this->flag->getShortText('flag'));
    $this->clickLink($this->flag->getShortText('flag'));
    $assert_session->statusCodeEquals(200);

    // ...but not unflag.
    $this->drupalGet('node/' . $this->node->id());
    $assert_session->statusCodeEquals(200);
    $assert_session->linkNotExists($this->flag->getShortText('flag'));
    $assert_session->linkNotExists($this->flag->getShortText('unflag'));

    // Check an unprivileged authenticated user.
    $this->drupalLogin($this->authUser);
    $this->drupalGet('node/' . $this->node->id());
    $assert_session->linkNotExists($this->flag->getShortText('flag'));

    // Check the anonymous user.
    $this->drupalLogout();
    $this->drupalGet('node/' . $this->node->id());
    $assert_session->linkNotExists($this->flag->getShortText('flag'));
  }

}
