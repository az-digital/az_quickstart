<?php

namespace Drupal\Tests\workbench_access\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\taxonomy\Entity\Term;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\Tests\UiHelperTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\Tests\workbench_access\Traits\WorkbenchAccessTestTrait;
use Drupal\workbench_access\WorkbenchAccessManagerInterface;

/**
 * Tests workbench_access integration with node access APIs.
 *
 * @group workbench_access
 */
class NodeAccessTest extends KernelTestBase {

  use ContentTypeCreationTrait;
  use NodeCreationTrait;
  use UiHelperTrait;
  use UserCreationTrait;
  use WorkbenchAccessTestTrait;

  /**
   * Access vocabulary.
   *
   * @var \Drupal\taxonomy\VocabularyInterface
   */
  protected $vocabulary;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'text',
    'system',
    'user',
    'workbench_access',
    'field',
    'filter',
    'taxonomy',
    'options',
  ];

  /**
   * Access handler.
   *
   * @var \Drupal\Core\Entity\EntityAccessControlHandlerInterface
   */
  protected $accessHandler;

  /**
   * Access control scheme.
   *
   * @var \Drupal\workbench_access\Entity\AccessSchemeInterface
   */
  protected $scheme;

  /**
   * User section storage.
   *
   * @var \Drupal\workbench_access\UserSectionStorage
   */
  protected $userStorage;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('node');
    $this->installConfig(['filter', 'node', 'workbench_access']);
    $this->installEntitySchema('user');
    $this->installEntitySchema('taxonomy_term');
    $this->installEntitySchema('section_association');
    $this->installSchema('system', ['sequences']);
    $node_type = $this->createContentType(['type' => 'page']);
    $this->createContentType(['type' => 'article']);
    $this->vocabulary = $this->setUpVocabulary();
    $this->accessHandler = $this->container->get('entity_type.manager')
      ->getAccessControlHandler('node');
    $this->setUpTaxonomyFieldForEntityType('node', $node_type->id(), $this->vocabulary->id());
    $this->scheme = $this->setUpTaxonomyScheme($node_type, $this->vocabulary);
    $this->userStorage = \Drupal::service('workbench_access.user_section_storage');
  }

  /**
   * Test create access integration.
   */
  public function testCreateAccess() {
    // The first user in a kernel test gets UID 1, so we need to make sure we're
    // not testing with that user.
    $this->createUser();
    // Create a section.
    $term = Term::create([
      'vid' => $this->vocabulary->id(),
      'name' => 'Some section',
    ]);
    $term->save();
    // Create two users with equal permissions but assign one of them to the
    // section.
    $permissions = [
      'create page content',
      'edit any page content',
      'access content',
      'delete any page content',
      'administer nodes',
    ];
    $allowed_editor = $this->createUser($permissions);
    $allowed_editor->save();
    $this->userStorage->addUser($this->scheme, $allowed_editor, [$term->id()]);

    $editor_with_no_access = $this->createUser($permissions);
    $permissions[] = 'bypass workbench access';
    $editor_with_bypass_access = $this->createUser($permissions);

    $this->assertTrue($this->accessHandler->createAccess('page', $allowed_editor));
    $this->assertFalse($this->accessHandler->createAccess('page', $editor_with_no_access));
    $this->assertTrue($this->accessHandler->createAccess('page', $editor_with_bypass_access));
  }

  /**
   * Test edit access integration.
   */
  public function testEditAccess() {
    // The first user in a kernel test gets UID 1, so we need to make sure we're
    // not testing with that user.
    $this->createUser();
    // Create a section.
    $term = Term::create([
      'vid' => $this->vocabulary->id(),
      'name' => 'Some section',
    ]);
    $term->save();
    // Create two users with equal permissions but assign one of them to the
    // section.
    $permissions = [
      'create page content',
      'edit any page content',
      'access content',
      'delete any page content',
    ];
    $allowed_editor = $this->createUser($permissions);
    $allowed_editor->save();
    $this->userStorage->addUser($this->scheme, $allowed_editor, [$term->id()]);

    $editor_with_no_access = $this->createUser($permissions);

    // Test a node that is not assigned to a section. Both should be allowed
    // because we do not assert access control by default.
    $node1 = $this->createNode(['type' => 'page', 'title' => 'foo']);
    $this->assertTrue($this->accessHandler->access($node1, 'update', $allowed_editor));
    $this->assertTrue($this->accessHandler->access($node1, 'update', $editor_with_no_access));
    $this->assertTrue($this->accessHandler->access($node1, 'delete', $allowed_editor));
    $this->assertTrue($this->accessHandler->access($node1, 'delete', $editor_with_no_access));

    // Create a node that is assigned to a section.
    $node2 = $this->createNode([
      'type' => 'page',
      'title' => 'bar',
      WorkbenchAccessManagerInterface::FIELD_NAME => $term->id(),
    ]);
    $this->assertTrue($this->accessHandler->access($node2, 'update', $allowed_editor));
    $this->assertFalse($this->accessHandler->access($node2, 'update', $editor_with_no_access));
    $this->assertTrue($this->accessHandler->access($node2, 'delete', $allowed_editor));
    $this->assertFalse($this->accessHandler->access($node2, 'delete', $editor_with_no_access));

    // With strict checking, nodes that are not assigned to a section return
    // false.
    $this->config('workbench_access.settings')
      ->set('deny_on_empty', 1)
      ->save();

    // Test a new node because the results for $node1 are cached.
    $node3 = $this->createNode(['type' => 'page', 'title' => 'baz']);
    $this->assertFalse($this->accessHandler->access($node3, 'update', $allowed_editor));
    $this->assertFalse($this->accessHandler->access($node3, 'update', $editor_with_no_access));
    $this->assertFalse($this->accessHandler->access($node3, 'delete', $allowed_editor));
    $this->assertFalse($this->accessHandler->access($node3, 'delete', $editor_with_no_access));
  }

}
