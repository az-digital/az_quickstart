<?php

namespace Drupal\Tests\quick_node_clone\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\group\Entity\Group;
use Drupal\group\Entity\GroupContent;
use Drupal\group\Entity\GroupRelationship;
use Drupal\group\Entity\GroupRole;
use Drupal\group\Entity\GroupType;
use Drupal\group\Entity\Storage\GroupContentTypeStorageInterface;
use Drupal\group\Entity\Storage\GroupRelationshipTypeStorageInterface;
use Drupal\group\PermissionScopeInterface;

/**
 * Tests node cloning with groups.
 *
 * @group group_integration
 */
class QuickNodeCloneGroupIntegrationTest extends BrowserTestBase {

  use NodeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected $profile = 'standard';

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'quick_node_clone',
    'gnode',
  ];

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->entityTypeManager = $this->container->get('entity_type.manager');
  }

  /**
   * Test node cloning.
   */
  public function testNodeClone() {
    if (class_exists(GroupContent::class)) {
      $this->checkGroupContent();
    }
    else {
      $this->checkGroupRelations();
    }
  }

  /**
   * Test groups v1.
   */
  public function checkGroupContent() {
    // Prepare a group type.
    $group_type = GroupType::create([
      'id' => $this->randomMachineName(),
      'label' => $this->randomString(),
      'creator_wizard' => FALSE,
    ]);
    $group_type->save();
    $node_type = 'page';
    $gnode_plugin_id = 'group_node:' . $node_type;
    $content_type_storage = $this->entityTypeManager->getStorage('group_content_type');
    /* @phpstan-ignore-next-line */
    assert($content_type_storage instanceof GroupContentTypeStorageInterface);
    $content_type_storage->save($content_type_storage->createFromPlugin($group_type, $gnode_plugin_id));

    // Create a node and add it to a group.
    $node = $this->createNode([
      'type' => $node_type,
      'title' => $this->randomString(200),
    ]);
    $group = Group::create([
      'type' => $group_type->id(),
      'label' => $this->randomString(),
    ]);
    $group->save();
    $group->addContent($node, $gnode_plugin_id);

    // Create a user with the required permissions.
    $group_role = GroupRole::create([
      'group_type' => $group_type->id(),
      'id' => $this->randomMachineName(),
      'label' => $this->randomString(),
      'permissions' => ['administer group'],
    ]);
    $group_role->save();
    $user = $this->drupalCreateUser(["clone $node_type content"]);
    $group->addMember($user, ['group_roles' => [$group_role->id()]]);

    // Clone the node and check that it is added to the group.
    $this->drupalLogin($user);
    $this->drupalGet("clone/{$node->id()}/quick_clone");
    $cloned_node_title = $this->randomMachineName(200);
    $edit = [
      'title[0][value]' => $cloned_node_title,
    ];
    $this->submitForm($edit, 'Save');
    $cloned_node = $this->getNodeByTitle($cloned_node_title);
    /* @phpstan-ignore-next-line */
    $cloned_relation = GroupContent::loadByEntity($cloned_node);
    $this->assertCount(1, $cloned_relation);
  }

  /**
   * Test groups v2 and higher.
   */
  protected function checkGroupRelations() {
    // Prepare a group type.
    $group_type = GroupType::create([
      'id' => $this->randomMachineName(),
      'label' => $this->randomString(),
      'creator_wizard' => FALSE,
    ]);
    $group_type->save();
    // Determine the relation type entity ID
    // for the installed version of the group module (v2 or v3).
    $relation_type_id = $this->entityTypeManager->getDefinition('group_content_type', FALSE)
    ? 'group_content_type'
    : 'group_relationship_type';
    $relation_type_storage = $this->entityTypeManager->getStorage($relation_type_id);
    assert($relation_type_storage instanceof GroupRelationshipTypeStorageInterface);
    $node_type = 'page';
    $gnode_plugin_id = 'group_node:' . $node_type;
    $relation_type_storage->save($relation_type_storage->createFromPlugin($group_type, $gnode_plugin_id));

    // Create a node and add it to a group.
    $node = $this->createNode([
      'type' => $node_type,
      'title' => $this->randomString(200),
    ]);
    $group = Group::create([
      'type' => $group_type->id(),
      'label' => $this->randomString(),
    ]);
    $group->save();
    $group->addRelationship($node, $gnode_plugin_id);

    // Create a user with the required permissions.
    $group_role = GroupRole::create([
      'group_type' => $group_type->id(),
      'id' => $this->randomMachineName(),
      'label' => $this->randomString(),
      'scope' => PermissionScopeInterface::INDIVIDUAL_ID,
      'admin' => TRUE,
    ]);
    $group_role->save();
    $user = $this->drupalCreateUser(["clone $node_type content"]);
    $group->addMember($user, ['group_roles' => [$group_role->id()]]);

    // Clone the node and check that it is added to the group.
    $this->drupalLogin($user);
    $this->drupalGet("clone/{$node->id()}/quick_clone");
    $cloned_node_title = $this->randomMachineName(200);
    $edit = [
      'title[0][value]' => $cloned_node_title,
    ];
    $this->submitForm($edit, 'Save');
    $cloned_node = $this->getNodeByTitle($cloned_node_title);
    $cloned_relation = GroupRelationship::loadByEntity($cloned_node);
    $this->assertCount(1, $cloned_relation);
  }

}
