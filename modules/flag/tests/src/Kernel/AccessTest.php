<?php

declare(strict_types=1);

namespace Drupal\Tests\flag\Kernel;

use Drupal\flag\Entity\Flag;
use Drupal\node\Entity\Node;

/**
 * Tests related to access to flags.
 *
 * Three distinct areas:
 *   Default hook_flag_action_access().
 *   Users flagging only content they own.
 *   UserFlagType optional self flagging tests.
 *
 * @group flag
 */
class AccessTest extends FlagKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installSchema('user', ['users_data']);

    // The first user is uid 1, create that to avoid that our test users
    // implicitly have all permissions even those that don't exist.
    $this->createUser();
  }

  /**
   * Tests default hook_flag_action_access() mechanism.
   */
  public function testDefault() {
    // Create a flag.
    $flag = Flag::create([
      'id' => 'example',
      'label' => $this->randomString(),
      'entity_type' => 'node',
      'bundles' => ['article'],
      'flag_type' => 'entity:node',
      'link_type' => 'reload',
      'flagTypeConfig' => [],
      'linkTypeConfig' => [],
    ]);
    $flag->save();

    // Create a user who may flag and unflag.
    $user_alice = $this->createUser([
      'administer flags',
      'flag example',
      'unflag example',
    ]);

    // Create a user who may only flag.
    $user_jill = $this->createUser([
      'administer flags',
      'flag example',
    ]);

    // Create a user who may not flag or unflag.
    $user_bob = $this->createUser();

    $article = Node::create([
      'type' => 'article',
      'title' => 'Article node',
    ]);
    $article->save();

    // Test with both permissions.
    $this->assertTrue($flag->actionAccess('flag', $user_alice, $article)->isAllowed(), 'Alice can flag.');
    $this->assertTrue($flag->actionAccess('unflag', $user_alice, $article)->isAllowed(), 'Alice can unflag.');

    // Test with only flag permission.
    $this->assertTrue($flag->actionAccess('flag', $user_jill, $article)->isAllowed(), 'Jill can flag.');
    $this->assertTrue($flag->actionAccess('unflag', $user_jill, $article)->isNeutral(), 'Jill cannot unflag.');

    // Test without permissions.
    $this->assertTrue($flag->actionAccess('flag', $user_bob, $article)->isNeutral(), 'Bob cannot flag.');
    $this->assertTrue($flag->actionAccess('unflag', $user_bob, $article)->isNeutral(), 'Bob cannot unflag..');
  }

  /**
   * Tests owners access to flaggables.
   *
   * Authors own articles - and can only flag their own work.
   * Editors own articles - but can only flag the work of others.
   */
  public function testOwnersAccess() {
    // A review flag with extra permissions set.
    $flag = Flag::create([
      'id' => 'me_myself_and_I',
      'label' => 'Self Review Flag',
      'entity_type' => 'node',
      'bundles' => ['article'],
      'flag_type' => 'entity:node',
      'link_type' => 'reload',
      'flagTypeConfig' => [
        'extra_permissions' => ['owner'],
      ],
      'linkTypeConfig' => [],
    ]);
    $flag->save();

    $flag_id = $flag->id();

    // Give authors permission to flag their own work.
    $user_author = $this->createUser([
      "flag $flag_id own items",
      "unflag $flag_id own items",
    ]);

    // Editors get permission.
    $user_editor = $this->createUser([
      "flag $flag_id other items",
      "unflag $flag_id other items",
    ]);

    // Article is owned by Author.
    $article_by_author = Node::create([
      'type' => 'article',
      'title' => 'Article node',
    ]);
    $article_by_author->setOwner($user_author);
    $article_by_author->save();

    // Article owned by editor (which NO one can flag or unflag).
    $article_by_editor = Node::create([
      'type' => 'article',
      'title' => 'Article node',
    ]);
    $article_by_editor->setOwner($user_editor);
    $article_by_editor->save();

    // Author can self review own work.
    $this->assertTrue($flag->actionAccess('flag', $user_author, $article_by_author)->isAllowed());
    $this->assertTrue($flag->actionAccess('unflag', $user_author, $article_by_author)->isAllowed());

    // Author can review others work.
    $this->assertTrue($flag->actionAccess('flag', $user_author, $article_by_editor)->isNeutral());
    $this->assertTrue($flag->actionAccess('unflag', $user_author, $article_by_editor)->isNeutral());

    // Editors should be able to access work that is not their own.
    $this->assertTrue($flag->actionAccess('flag', $user_editor, $article_by_author)->isAllowed());
    $this->assertTrue($flag->actionAccess('unflag', $user_editor, $article_by_author)->isAllowed());

    // Editors should not get access to the self review flag.
    $this->assertTrue($flag->actionAccess('flag', $user_editor, $article_by_editor)->isNeutral());
    $this->assertTrue($flag->actionAccess('unflag', $user_editor, $article_by_editor)->isNeutral());

    // When no flaggable is supplied EntityFlagType::actionAccess() tests are
    // bypassed.
    $this->assertTrue($flag->actionAccess('flag', $user_author)->isNeutral());
    $this->assertTrue($flag->actionAccess('flag', $user_editor)->isNeutral());
    $this->assertTrue($flag->actionAccess('unflag', $user_author)->isNeutral());
    $this->assertTrue($flag->actionAccess('unflag', $user_editor)->isNeutral());
  }

  /**
   * Tests specific UserFlagType permissions.
   */
  public function testUserFlag() {
    // A flag that shows on users profiles.
    $flag = Flag::create([
      'id' => 'A flag about users',
      'label' => $this->randomString(),
      'entity_type' => 'user',
      'flag_type' => 'entity:user',
      'link_type' => 'reload',
      'flagTypeConfig' => [
        'extra_permissions' => ['owner'],
      ],
      'linkTypeConfig' => [],
    ]);
    $flag->save();

    $flag_id = $flag->id();

    // Create a user who may flag own user account.
    $user_alice = $this->createUser([
      "flag $flag_id own user account",
      "unflag $flag_id own user account",
    ]);

    // Create a user who may flag the work of others.
    $user_bob = $this->createUser([
      "flag $flag_id other user accounts",
      "unflag $flag_id other user accounts",
    ]);

    // For Alice selfies are permitted.
    $this->assertTrue($flag->actionAccess('flag', $user_alice, $user_alice)->isAllowed());
    $this->assertTrue($flag->actionAccess('unflag', $user_alice, $user_alice)->isAllowed());

    // For Bob selfies are banned.
    $this->assertTrue($flag->actionAccess('flag', $user_bob, $user_bob)->isNeutral());
    $this->assertTrue($flag->actionAccess('unflag', $user_bob, $user_bob)->isNeutral());

    // For alice flagging other people's profiles is banned.
    $this->assertTrue($flag->actionAccess('flag', $user_alice, $user_bob)->isNeutral());
    $this->assertTrue($flag->actionAccess('flag', $user_alice, $user_bob)->isNeutral());

    // For Bob flagging other people's profiles is permitted.
    $this->assertTrue($flag->actionAccess('unflag', $user_bob, $user_alice)->isAllowed());
    $this->assertTrue($flag->actionAccess('unflag', $user_bob, $user_alice)->isAllowed());

    // When no flaggable is supplied UserFlagType::actionAccess() tests are
    // bypassed.
    $this->assertTrue($flag->actionAccess('flag', $user_alice)->isNeutral());
    $this->assertTrue($flag->actionAccess('flag', $user_bob)->isNeutral());
    $this->assertTrue($flag->actionAccess('unflag', $user_alice)->isNeutral());
    $this->assertTrue($flag->actionAccess('unflag', $user_bob)->isNeutral());
  }

}
