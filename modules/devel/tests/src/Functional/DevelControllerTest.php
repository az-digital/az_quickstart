<?php

namespace Drupal\Tests\devel\Functional;

use Drupal\Core\Entity\EntityInterface;
use Drupal\devel_entity_test\Entity\DevelEntityTestCanonical;
use Drupal\devel_entity_test\Entity\DevelEntityTestEdit;
use Drupal\devel_entity_test\Entity\DevelEntityTestNoLinks;
use Drupal\entity_test\Entity\EntityTest;

/**
 * Tests Devel controller.
 *
 * @group devel
 */
class DevelControllerTest extends DevelBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var string[]
   */
  protected static $modules = [
    'devel',
    'node',
    'entity_test',
    'devel_entity_test',
    'block',
  ];

  /**
   * Test entity provided by Core.
   */
  protected EntityTest|EntityInterface $entity;

  /**
   * Devel test entity with canonical link.
   */
  protected DevelEntityTestCanonical|EntityInterface $entityCanonical;

  /**
   * Devel test entity with edit form link.
   */
  protected DevelEntityTestEdit|EntityInterface $entityEdit;

  /**
   * Devel test entity with no links.
   */
  protected DevelEntityTestNoLinks|EntityInterface $entityNoLinks;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $entity_type_manager = $this->container->get('entity_type.manager');

    // Create a test entity.
    $random_label = $this->randomMachineName();
    $data = ['type' => 'entity_test', 'name' => $random_label];
    $this->entity = $entity_type_manager->getStorage('entity_test')->create($data);
    $this->entity->save();

    // Create a test entity with only canonical route.
    $random_label = $this->randomMachineName();
    $data = ['type' => 'devel_entity_test_canonical', 'name' => $random_label];
    $this->entityCanonical = $entity_type_manager->getStorage('devel_entity_test_canonical')->create($data);
    $this->entityCanonical->save();

    // Create a test entity with only edit route.
    $random_label = $this->randomMachineName();
    $data = ['type' => 'devel_entity_test_edit', 'name' => $random_label];
    $this->entityEdit = $entity_type_manager->getStorage('devel_entity_test_edit')->create($data);
    $this->entityEdit->save();

    // Create a test entity with no routes.
    $random_label = $this->randomMachineName();
    $data = ['type' => 'devel_entity_test_no_links', 'name' => $random_label];
    $this->entityNoLinks = $entity_type_manager->getStorage('devel_entity_test_no_links')->create($data);
    $this->entityNoLinks->save();

    $this->drupalPlaceBlock('local_tasks_block');

    $web_user = $this->drupalCreateUser([
      'view test entity',
      'administer entity_test content',
      'access devel information',
    ]);
    $this->drupalLogin($web_user);
  }

  /**
   * Tests route generation.
   */
  public function testRouteGeneration(): void {
    // Test Devel load and render routes for entities with both route
    // definitions.
    $this->drupalGet('entity_test/' . $this->entity->id());
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->LinkExists('View');
    $this->assertSession()->LinkExists('Edit');
    $this->assertSession()->LinkExists('Devel');
    $this->drupalGet('devel/entity_test/' . $this->entity->id());
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->LinkExists('Definition');
    $this->assertSession()->LinkExists('Render');
    $this->assertSession()->LinkExists('Load');
    $this->assertSession()->LinkExists('Load (with references)');
    $this->assertSession()->LinkExists('Path alias');
    $this->assertSession()->linkByHrefExists('devel/render/entity_test/' . $this->entity->id());
    $this->drupalGet('devel/render/entity_test/' . $this->entity->id());
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->linkByHrefExists('devel/definition/entity_test/' . $this->entity->id());
    $this->drupalGet('devel/definition/entity_test/' . $this->entity->id());
    $this->assertSession()->statusCodeEquals(200);

    // Test Devel load and render routes for entities with only canonical route
    // definitions.
    $this->drupalGet('devel_entity_test_canonical/' . $this->entityCanonical->id());
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->LinkExists('View');
    $this->assertSession()->LinkNotExists('Edit');
    $this->assertSession()->LinkExists('Devel');
    // Use xpath with equality check on @data-drupal-link-system-path because
    // assertNoLinkByHref matches on partial values and finds the other link.
    $this->assertSession()->elementNotExists('xpath',
      '//a[@data-drupal-link-system-path = "devel/devel_entity_test_canonical/' . $this->entityCanonical->id() . '"]');
    $this->assertSession()->elementExists('xpath',
      '//a[@data-drupal-link-system-path = "devel/render/devel_entity_test_canonical/' . $this->entityCanonical->id() . '"]');
    $this->drupalGet('devel/devel_entity_test_canonical/' . $this->entityCanonical->id());
    // This url used to be '404 not found', but is now '200 OK' following the
    // generating of devel load links for all entity types.
    // @see https://gitlab.com/drupalspoons/devel/-/issues/377
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet('devel/render/devel_entity_test_canonical/' . $this->entityCanonical->id());
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->LinkExists('Definition');
    $this->assertSession()->LinkExists('Render');
    $this->assertSession()->LinkNotExists('Load');
    $this->assertSession()->LinkNotExists('Load (with references)');
    $this->assertSession()->LinkExists('Path alias');
    $this->assertSession()->linkByHrefExists('devel/definition/devel_entity_test_canonical/' . $this->entityCanonical->id());
    $this->drupalGet('devel/definition/devel_entity_test_canonical/' . $this->entityCanonical->id());
    $this->assertSession()->statusCodeEquals(200);

    // Test Devel load and render routes for entities with only edit route
    // definitions.
    $this->drupalGet('devel_entity_test_edit/manage/' . $this->entityEdit->id());
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->LinkNotExists('View');
    $this->assertSession()->LinkExists('Edit');
    $this->assertSession()->LinkExists('Devel');
    $this->assertSession()->linkByHrefExists('devel/devel_entity_test_edit/' . $this->entityEdit->id());
    $this->drupalGet('devel/devel_entity_test_edit/' . $this->entityEdit->id());
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->LinkExists('Definition');
    $this->assertSession()->LinkNotExists('Render');
    $this->assertSession()->LinkExists('Load');
    $this->assertSession()->LinkExists('Load (with references)');
    $this->assertSession()->LinkExists('Path alias');
    $this->assertSession()->linkByHrefExists('devel/definition/devel_entity_test_edit/' . $this->entityEdit->id());
    $this->assertSession()->linkByHrefNotExists('devel/render/devel_entity_test_edit/' . $this->entityEdit->id());
    $this->drupalGet('devel/definition/devel_entity_test_edit/' . $this->entityEdit->id());
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet('devel/render/devel_entity_test_edit/' . $this->entityEdit->id());
    $this->assertSession()->statusCodeEquals(404);

    // Test Devel load and render routes for entities with no route
    // definitions.
    $this->drupalGet('devel_entity_test_no_links/' . $this->entityEdit->id());
    $this->assertSession()->statusCodeEquals(404);
    $this->drupalGet('devel/devel_entity_test_no_links/' . $this->entityNoLinks->id());
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet('devel/render/devel_entity_test_no_links/' . $this->entityNoLinks->id());
    $this->assertSession()->statusCodeEquals(404);
    $this->drupalGet('devel/definition/devel_entity_test_no_links/' . $this->entityNoLinks->id());
    $this->assertSession()->statusCodeEquals(404);
  }

  /**
   * Tests the field info page.
   */
  public function testFieldInfoPage(): void {
    $this->drupalGet('/devel/field/info');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Field types');
  }

}
