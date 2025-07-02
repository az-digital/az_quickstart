<?php

declare(strict_types=1);

namespace Drupal\Tests\flag\Functional;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Template\Attribute;
use Drupal\flag\FlagInterface;
use Drupal\node\Entity\Node;

/**
 * Tests the Flag link is output in various locations.
 *
 * This test does not cover the access to the link, or that the link works
 * correctly. It merely checks that the link is output when the various output
 * settings (e.g. 'show in entity links') call for it.
 *
 * @todo Parts of this test relating to entity links and contextual links are
 * not written, as that functionality is currently broken in Flag: see
 * https://www.drupal.org/node/2411977.
 *
 * @group flag
 */
class LinkOutputLocationTest extends FlagTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The flag.
   *
   * @var \Drupal\flag\FlagInterface
   */
  protected $flag;

  /**
   * The node.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $node;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create a flag.
    $this->flag = $this->createFlag('node', ['article'], 'reload');

    // Log in as the admin user so we don't have to worry about flag access.
    $this->grantFlagPermissions($this->flag);
    $this->drupalLogin($this->adminUser);

    // Create a node to flag.
    $this->node = Node::create([
      'body' => [
        [
          'value' => $this->randomMachineName(32),
          'format' => filter_default_format(),
        ],
      ],
      'type' => 'article',
      'title' => $this->randomMachineName(8),
      'uid' => $this->adminUser->id(),
      'status' => 1,
      // Promoted to front page to test teaser view mode.
      'promote' => 1,
      'sticky' => 0,
    ]);
    $this->node->save();
  }

  /**
   * Test the link output.
   */
  public function testLinkLocation() {
    // Turn off all link output for the flag.
    $flag_config = $this->flag->getFlagTypePlugin()->getConfiguration();
    $flag_config['show_as_field'] = FALSE;
    $flag_config['show_in_links'] = [];
    $this->flag->getFlagTypePlugin()->setConfiguration($flag_config);
    $this->flag->save();

    // Check the full node shows no flag link.
    $this->drupalGet('node/' . $this->node->id());
    $this->assertNoPseudofield($this->flag, $this->node);
    // @todo check no entity link.
    // Check the teaser view mode for the node shows no flag link.
    $this->drupalGet('node');
    $this->assertNoPseudofield($this->flag, $this->node);
    // @todo check no entity link.
    // Turn on 'show as field'.
    // By default, this will be visible on the field display configuration.
    $flag_config = $this->flag->getFlagTypePlugin()->getConfiguration();
    $flag_config['show_as_field'] = TRUE;
    $flag_config['show_in_links'] = [];
    $this->flag->getFlagTypePlugin()->setConfiguration($flag_config);
    $this->flag->save();

    // Check the full node shows the flag link as a field.
    $this->drupalGet('node/' . $this->node->id());
    $this->assertPseudofield($this->flag, $this->node);
    // @todo check no entity link.
    // Check the teaser view mode shows the flag link as a field.
    $this->drupalGet('node');
    $this->assertPseudofield($this->flag, $this->node);
    // @todo check no entity link.
    // Hide the flag field on teaser view mode.
    $edit = [
      'fields[flag_' . $this->flag->id() . '][region]' => 'hidden',
    ];
    $this->drupalGet('admin/structure/types/manage/article/display/teaser');
    $this->submitForm($edit, 'Save');
    // Check the form was saved successfully.
    $this->assertSession()->responseContains('Your settings have been saved.');

    // Check the full node still shows the flag link as a field.
    $this->drupalGet('node/' . $this->node->id());
    $this->assertPseudofield($this->flag, $this->node);
    // @todo check no entity link.
    // Check the teaser view mode does not show the flag link as a field.
    $this->drupalGet('node');
    $this->assertNoPseudofield($this->flag, $this->node);
    // @todo check no entity link.
    // @todo Turn on the entity link, and turn off the field.
    // @todo Check the full and teaser view modes.
    // @todo Turn off the entity link for one view mode.
    // @todo Check both view modes are as expected.
  }

  /**
   * Tests that when no display types are selected, no flag links appear.
   */
  public function testNoLinkLocation() {
    $flag_config = $this->flag->getFlagTypePlugin()->getConfiguration();
    $flag_config['show_as_field'] = FALSE;
    $flag_config['show_in_links'] = [];
    $flag_config['show_on_form'] = FALSE;
    $flag_config['show_contextual_link'] = FALSE;
    $this->flag->getFlagTypePlugin()->setConfiguration($flag_config);
    $this->flag->save();

    $contextual_links_id = 'node:node=' . $this->node->id() . ':changed=' . $this->node->getChangedTime() . '&flag_keys=' . $this->flag->id() . '-flag&langcode=en';
    $this->drupalGet('node');
    $this->assertNoPseudofield($this->flag, $this->node);
    $this->assertNoContextualLinkPlaceholder($contextual_links_id);

    $this->drupalGet('node/' . $this->node->id());
    $this->assertNoPseudofield($this->flag, $this->node);
    $this->assertNoContextualLinkPlaceHolder($contextual_links_id);
    // @todo check no entity field link.
    $this->drupalGet('node/' . $this->node->id() . '/edit');
    $this->assertSession()->fieldNotExists('flag[' . $this->flag->id() . ']');
    $this->assertNoContextualLinkPlaceholder($contextual_links_id);
  }

  /**
   * Pass if the flag link is shown as a field on the page.
   *
   * @param \Drupal\flag\FlagInterface $flag
   *   The flag to look for.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The flaggable entity the flag is on.
   * @param string $message
   *   (Optional) Message to display.
   */
  protected function assertPseudofield(FlagInterface $flag, EntityInterface $entity, $message = '') {
    $this->assertPseudofieldHelper($flag, $entity, $message ?: "The flag link is shown as a field.", TRUE);
  }

  /**
   * Pass if the flag link is not shown as a field on the page.
   *
   * @param \Drupal\flag\FlagInterface $flag
   *   The flag to look for.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The flaggable entity the flag is on.
   * @param string $message
   *   (Optional) Message to display.
   */
  protected function assertNoPseudofield(FlagInterface $flag, EntityInterface $entity, $message = '') {
    $this->assertPseudofieldHelper($flag, $entity, $message ?: "The flag link is not shown as a field.", FALSE);
  }

  /**
   * Helper for assertPseudofield() and assertNoPseudofield().
   *
   * It is not recommended to call this function directly.
   *
   * @param \Drupal\flag\FlagInterface $flag
   *   The flag to look for.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The flaggable entity the flag is on.
   * @param string $message
   *   Message to display.
   * @param bool $exists
   *   TRUE if the flag link should exist, FALSE if it should not exist.
   */
  protected function assertPseudofieldHelper(FlagInterface $flag, EntityInterface $entity, $message, $exists) {
    $xpath = $this->xpath("//*[contains(@class, 'layout-content')]//div[contains(@class, :id)]", [
      ':id' => 'flag-' . $flag->id() . '-' . $entity->id(),
    ]);
    $this->assertTrue(count($xpath) == ($exists ? 1 : 0), $message);
  }

  /**
   * Asserts that a contextual link placeholder with the given id exists.
   *
   * @param string $id
   *   A contextual link id.
   */
  protected function assertNoContextualLinkPlaceholder($id): void {
    $this->assertSession()->responseNotContains('<div' . new Attribute(['data-contextual-id' => $id]) . '></div>');
  }

  // @todo add assertions:
  // assertEntityLink
  // assertNoEntityLink.
}
