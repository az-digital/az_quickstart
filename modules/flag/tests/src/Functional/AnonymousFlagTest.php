<?php

declare(strict_types=1);

namespace Drupal\Tests\flag\Functional;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;
use Drupal\flag\Entity\Flag;
use Drupal\flag\Entity\Flagging;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\user\Entity\Role;

/**
 * Anonymous Flag Test.
 *
 * @group flag
 */
class AnonymousFlagTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'user', 'node', 'flag'];

  /**
   * Node.
   *
   * @var \Drupal\node\Entity\Node
   */
  protected $node;

  /**
   * Flag.
   *
   * @var \Drupal\flag\Entity\Flag
   */
  protected $flag;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    NodeType::create(['type' => 'page', 'name' => 'page'])->save();
    $this->node = Node::create(['type' => 'page', 'title' => 'test']);
    $this->node->save();
    $flag_id = strtolower($this->randomMachineName());
    $this->flag = Flag::create([
      'id' => $flag_id,
      'label' => $this->randomString(),
      'entity_type' => 'node',
      'bundles' => ['page'],
      'flag_type' => 'entity:node',
      'link_type' => 'reload',
      'flagTypeConfig' => [],
      'linkTypeConfig' => [],
      'flag_short' => 'switch_this_on',
      'unflag_short' => 'switch_this_off',
    ]);
    $this->flag->save();

    Role::load(Role::ANONYMOUS_ID)
      ->grantPermission('flag ' . $flag_id)
      ->grantPermission('unflag ' . $flag_id)
      ->save();
  }

  /**
   * Tests flagging as an anonymous user.
   */
  public function testAnonymousFlagging() {
    $this->drupalGet(Url::fromRoute('entity.node.canonical', ['node' => $this->node->id()]));

    // Assert that just visiting a page as anonymous user does not initialize
    // the session.
    $this->assertEmpty($this->getSession()->getCookie($this->getSessionName()));

    $this->getSession()->getPage()->clickLink('switch_this_on');
    $this->assertNotEmpty($this->getSession()->getPage()->findLink('switch_this_off'));
    // Warning: $this->getDatabaseConnection() is the original database
    // connection, not the current one.
    $flagging_id = \Drupal::database()->query('SELECT id FROM {flagging}')->fetchField();
    $this->assertNotEmpty($flagging_id);

    $flagging = Flagging::load($flagging_id);
    // Check that the session of the user contains the generated flag session
    // id and that matches the flagging.
    $session_id = $this->getFlagSessionIdFromSession();

    $this->assertNotEmpty($session_id);
    $this->assertEquals($session_id, $flagging->get('session_id')->value, "The flagging entity has the session ID set.");

    // Try another anonymous user.
    $old_mink = $this->mink;
    $this->initMink();
    $this->drupalGet(Url::fromRoute('entity.node.canonical', ['node' => $this->node->id()]));
    $this->assertNotEmpty($this->getSession()->getPage()->findLink('switch_this_on'));

    // Switch back to the original.
    $this->mink = $old_mink;
    // Unflag the node.
    $this->getSession()->getPage()->clickLink('switch_this_off');
    $this->assertNotEmpty($this->getSession()->getPage()->findLink('switch_this_on'));

    // Clear the storage cache so we load fresh entities.
    $this->container->get('entity_type.manager')->getStorage('flagging')->resetCache();

    $flagging = Flagging::load($flagging_id);
    $this->assertEmpty($flagging, "The first user's flagging was deleted.");
  }

  /**
   * Returns the flag session ID based on the current session cookie.
   *
   * @return string|null
   *   The flag session ID.
   */
  public function getFlagSessionIdFromSession() {
    $session_id = $this->getSession()->getCookie($this->getSessionName());
    if (!$session_id) {
      return NULL;
    }

    $session_data = \Drupal::database()
      ->query('SELECT session FROM {sessions} WHERE sid = :sid', [':sid' => Crypt::hashBase64($session_id)])
      ->fetchField();

    // PHP uses a custom serialize function for session data, parse out the
    // flag session id with a regular expression.
    if (preg_match('/"flag\.session_id";s:\d+:"(.[^"]+)"/', $session_data, $match)) {
      return $match[1];
    }
    return NULL;
  }

}
