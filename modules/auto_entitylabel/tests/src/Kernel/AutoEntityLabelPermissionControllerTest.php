<?php

namespace Drupal\Tests\auto_entitylabel\Kernel;

use Drupal\auto_entitylabel\AutoEntityLabelPermissionController;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;

/**
 * Tests AutoEntityLabelPermissionController methods.
 *
 * @group auto_entitylabel
 */
class AutoEntityLabelPermissionControllerTest extends EntityKernelTestBase {

  use ContentTypeCreationTrait;
  use StringTranslationTrait;

  /**
   * Node type.
   *
   * @var \Drupal\node\Entity\NodeType
   */
  protected $nodeType;

  /**
   * AutoEntityLabelPermissionController variable.
   *
   * @var mixed
   */
  protected $autoEntityLabelPermissionController;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'system',
    'user',
    'node',
    'auto_entitylabel',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('node');

    $this->installSchema('user', 'users_data');
    $this->installSchema('node', ['node_access']);

    $this->installConfig(self::$modules);

    $this->nodeType = $this->createContentType(['type' => 'page']);
    $this->autoEntityLabelPermissionController = new AutoEntityLabelPermissionController(
      $this->container->get('entity_type.manager')
    );
  }

  /**
   * Tests autoEntityLabelPermissions() method.
   */
  public function testAutoEntityLabelPermissions() {
    $this->assertNotEmpty($this->autoEntityLabelPermissionController->autoEntityLabelPermissions());
    $this->assertEquals([
      'administer node_type labels' => [
        'title' => $this->t('<em class="placeholder">Content type</em>: Administer automatic entity labels'),
        'restrict access' => TRUE,
      ],
    ], $this->autoEntityLabelPermissionController->autoEntityLabelPermissions());
  }

}
