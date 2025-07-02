<?php

namespace Drupal\Tests\coffee\Kernel;

use Drupal\Core\Url;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;

/**
 * Tests hook_coffee_commands().
 *
 * @group coffee
 */
class CoffeeCommandsTest extends KernelTestBase {

  use UserCreationTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['coffee', 'coffee_test', 'system', 'node', 'user'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installSchema('system', ['sequences']);
    $this->installEntitySchema('user');
    $this->installConfig('coffee');

    // Create the node bundles required for testing.
    $node_type = NodeType::create([
      'type' => 'page',
      'name' => 'Basic page',
    ]);
    $node_type->save();

    // Create user that can create a node for our bundle.
    $role_id = $this->createRole(['create page content'], 'page_creator');

    $user = User::create([
      'name' => $this->randomMachineName(),
      'roles' => [$role_id],
    ]);
    $user->save();

    // Set current user.
    \Drupal::currentUser()->setAccount($user);
  }

  /**
   * Tests hook_coffee_commands().
   */
  public function testHookCoffeeCommands() {
    $expected_hook = [
      'value' => Url::fromRoute('<front>')->toString(),
      'label' => 'Coffee hook fired!',
      'command' => ':test',
    ];

    $expected_system = [
      'value' => Url::fromRoute('<front>')->toString(),
      'label' => 'Go to front page',
      'command' => ':front',
    ];

    $expected_node = [
      'value' => Url::fromRoute('node.add', ['node_type' => 'page'])->toString(),
      'label' => 'Basic page',
      'command' => ':add Basic page',
    ];

    $commands = \Drupal::moduleHandler()->invokeAll('coffee_commands');
    // Convert the command labels to strings for the comparison.
    array_walk($commands, function (array &$command): void {
      $command['label'] = (string) $command['label'];
    });
    $this->assertContains($expected_hook, $commands);
    $this->assertContains($expected_system, $commands);
    $this->assertContains($expected_node, $commands);
  }

}
