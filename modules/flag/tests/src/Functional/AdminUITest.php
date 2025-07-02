<?php

declare(strict_types=1);

namespace Drupal\Tests\flag\Functional;

/**
 * Tests the Flag admin UI.
 *
 * @group flag
 */
class AdminUITest extends FlagTestBase {


  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * The label of the flag to create for the test.
   *
   * @var string
   */
  protected $label = 'Test label 123';

  /**
   * The ID of the flag created for the test.
   *
   * @var string
   */
  protected $flagId = 'test_label_123';

  /**
   * The flag used for the test.
   *
   * @var \Drupal\flag\FlagInterface
   */
  protected $flag;

  /**
   * The node for test flagging.
   *
   * @var \Drupal\node\Entity\Node
   */
  protected $node;

  /**
   * The ID of the entity to created for the test.
   *
   * @var int
   */
  protected $nodeId;

  /**
   * Text used in construction of the flag.
   *
   * @var string
   */
  protected $flagShortText = 'Flag this stuff';

  /**
   * Text used in construction of the flag.
   *
   * @var string
   */
  protected $unflagShortText = 'Unflag this stuff';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->container->get('entity_type.manager');

    $this->drupalLogin($this->adminUser);

    // Create a node to flag.
    $this->node = $this->drupalCreateNode(['type' => $this->nodeType]);
    $this->nodeId = $this->node->id();
  }

  /**
   * Test basic flag admin.
   */
  public function testFlagAdmin() {
    $this->doFlagAdd();
    $this->doFlagEdit();

    $this->doFlagDisable();
    $this->doFlagEnable();

    $this->doFlagReset();

    $this->doFlagChangeWeights();

    $this->doFlagDelete();
  }

  /**
   * Flag creation.
   */
  public function doFlagAdd() {
    // Test with minimal value requirement.
    $this->drupalGet('admin/structure/flags/add');
    $this->submitForm([], 'Continue');
    // Check for fieldset titles.
    $this->assertSession()->pageTextContains('Messages');
    $this->assertSession()->pageTextContains('Flag access');
    $this->assertSession()->pageTextContains('Display options');

    $edit = [
      'label' => $this->label,
      'id' => $this->flagId,
      'bundles[' . $this->nodeType . ']' => $this->nodeType,
      'flag_short' => $this->flagShortText,
      'unflag_short' => $this->unflagShortText,
    ];
    $this->submitForm($edit, 'Create Flag');

    $this->assertSession()->pageTextContains("Flag $this->label has been added.");

    $this->flag = $this->flagService->getFlagById($this->flagId);

    $this->assertNotNull($this->flag, 'The flag was created.');

    $this->grantFlagPermissions($this->flag);
  }

  /**
   * Check the flag edit form.
   */
  public function doFlagEdit() {
    $this->drupalGet('admin/structure/flags/manage/' . $this->flagId);
    // Assert the global form element is disabled when editing the flag.
    $this->assertSession()->elementAttributeExists('css', '#edit-global-0', 'disabled');
  }

  /**
   * Disable the flag and ensure the link does not appear on entities.
   */
  public function doFlagDisable() {
    $this->drupalGet('admin/structure/flags');
    $this->assertSession()->pageTextContains('Enabled');

    $this->drupalGet('admin/structure/flags/manage/' . $this->flagId . '/disable');
    $this->submitForm([], 'Disable');
    $this->assertSession()->statusCodeEquals(200);

    $this->drupalGet('admin/structure/flags');
    $this->assertSession()->pageTextContains('Disabled');

    $this->drupalGet('node/' . $this->nodeId);
    $this->assertSession()->pageTextNotContains($this->flagShortText);
  }

  /**
   * Enable the flag and ensure it appears on target entities.
   */
  public function doFlagEnable() {
    $this->drupalGet('admin/structure/flags');
    $this->assertSession()->pageTextContains('Disabled');

    $this->drupalGet('admin/structure/flags/manage/' . $this->flagId . '/enable');
    $this->submitForm([], 'Enable');
    $this->assertSession()->statusCodeEquals(200);

    $this->drupalGet('admin/structure/flags');
    $this->assertSession()->pageTextContains('Enabled');

    $this->drupalGet('node/' . $this->nodeId);
    $this->assertSession()->pageTextContains($this->flagShortText);
  }

  /**
   * Reset the flag and ensure the flaggings are deleted.
   */
  public function doFlagReset() {
    // Flag the node.
    $this->flagService->flag($this->flag, $this->node, $this->adminUser);

    $ids_before = $this->entityTypeManager->getStorage('flagging')->getQuery()
      ->accessCheck()
      ->condition('flag_id', $this->flag->id())
      ->condition('entity_type', 'node')
      ->condition('entity_id', $this->node->id())
      ->execute();

    $this->assertCount(1, $ids_before, "The flag has one flagging.");

    // Go to the reset form for the flag.
    $this->drupalGet('admin/structure/flags/manage/' . $this->flag->id() . '/reset');

    $this->assertSession()->pageTextContains('Are you sure you want to reset the Flag');

    $this->submitForm([], 'Reset');

    $ids_after = $this->entityTypeManager->getStorage('flagging')->getQuery()
      ->accessCheck()
      ->condition('flag_id', $this->flag->id())
      ->condition('entity_type', 'node')
      ->condition('entity_id', $this->node->id())
      ->execute();

    $this->assertCount(0, $ids_after, "The flag has no flaggings after being reset.");
  }

  /**
   * Create further flags and change the weights using the draggable list.
   */
  public function doFlagChangeWeights() {
    $flag_weights_to_set = [];

    // We have one flag already.
    $flag_weights_to_set[$this->flagId] = 0;

    foreach (range(1, 10) as $i) {
      $flag = $this->createFlag();

      $flag_weights_to_set[$flag->id()] = -$i;
    }

    $edit = [];
    foreach ($flag_weights_to_set as $id => $weight) {
      $edit['flags[' . $id . '][weight]'] = $weight;
    }
    // Saving the new weights via the interface.
    $this->drupalGet('admin/structure/flags');
    $this->submitForm($edit, 'Save');

    // Load the all the flags.
    /** @var \Drupal\flag\FlagInterface[] $all_flags */
    $all_flags = $this->container
      ->get('entity_type.manager')
      ->getStorage('flag')
      ->loadMultiple();

    // Check that the weights for each flag are saved in the database correctly.
    foreach ($all_flags as $id => $flag) {
      $this->assertEquals($flag_weights_to_set[$id], $all_flags[$id]->getWeight(), 'The flag weight was changed.');
    }
  }

  /**
   * Delete the flag.
   */
  public function doFlagDelete() {
    // Flag node.
    $this->drupalGet('node/' . $this->nodeId);
    $this->assertSession()->linkExists($this->flagShortText);
    // Go to the delete form for the flag.
    $this->drupalGet('admin/structure/flags/manage/' . $this->flag->id() . '/delete');

    $this->assertSession()->pageTextContains("Are you sure you want to delete the flag $this->label?");

    $this->submitForm([], 'Delete');

    // Check the flag has been deleted.
    $result = $this->flagService->getFlagById($this->flagId);

    $this->assertNull($result, 'The flag was deleted.');
    $this->drupalGet('node/' . $this->nodeId);
    $this->assertSession()->pageTextContains($this->node->label());
    $this->assertSession()->linkNotExists($this->flagShortText);
  }

}
