<?php

declare(strict_types=1);

namespace Drupal\Tests\flag\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\flag\Traits\FlagCreateTrait;
use Drupal\Tests\flag\Traits\FlagPermissionsTrait;
use Drupal\flag\Entity\Flag;

/**
 * Provides common methods for Flag tests.
 */
abstract class FlagTestBase extends BrowserTestBase {

  use FlagCreateTrait;
  use FlagPermissionsTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The flag service.
   *
   * @var \Drupal\flag\FlagServiceInterface
   */
  protected $flagService;

  /**
   * A user with Flag admin rights.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $adminUser;

  /**
   * The node type to use in the test.
   *
   * @var string
   */
  protected $nodeType = 'article';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Get the Flag Service.
    $this->flagService = \Drupal::service('flag');

    // Place the title block, otherwise some tests fail.
    $this->drupalPlaceBlock('page_title_block', ['region' => 'content']);

    // Create content type.
    $this->drupalCreateContentType(['type' => $this->nodeType]);

    // Create the admin user.
    $this->adminUser = $this->createUser([
      'administer flags',
      'administer flagging display',
      'administer flagging fields',
      'administer node display',
      'administer modules',
      'administer nodes',
      'create ' . $this->nodeType . ' content',
      'edit any ' . $this->nodeType . ' content',
      'delete any ' . $this->nodeType . ' content',
    ]);
  }

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'views',
    'node',
    'user',
    'flag',
    'node',
    'field_ui',
    'text',
    'block',
    'contextual',
    'flag_event_test',
  ];

  /**
   * Creates a flag entity using the admin UI.
   *
   * If you do not provide any bundles in $edit, all bundles for $entity_type
   * are assumed.
   *
   * @param string|null $entity_type
   *   (optional) A string containing the flaggable entity type, by default
   *   'node'.
   * @param array $edit
   *   (optional) An array of form field names and values. If omitted, random
   *   strings will be used for the flag ID, label, short and long text.
   * @param string|null $link_type
   *   (optional) A string containing the link type ID. Is omitted, assumes
   *   'reload'.
   *
   * @return \Drupal\flag\FlagInterface|null
   *   The created flag entity.
   */
  protected function createFlagWithForm($entity_type = 'node', $edit = [], $link_type = 'reload') {
    // Submit the flag add page.
    $this->drupalGet('admin/structure/flags/add');
    $this->submitForm([
      'flag_entity_type' => $this->getFlagType($entity_type),
    ], 'Continue');

    // Set the link type.
    $this->submitForm(['link_type' => $link_type], 'Create Flag');

    // Create an array of defaults.
    $default_edit = [
      'id' => strtolower($this->randomMachineName()),
      'label' => $this->randomHTMLString(),
      'flag_short' => $this->randomHTMLString(),
      'unflag_short' => $this->randomHTMLString(),
      'flag_long' => $this->randomHTMLString(16),
      'unflag_long' => $this->randomHTMLString(16),
      'flag_message' => $this->randomHTMLString(32),
      'unflag_message' => $this->randomHTMLString(32),
      'unflag_denied_text' => $this->randomHTMLString(),
      'link_type' => $link_type,
    ];

    // Merge the default values with the edit array.
    $final_edit = array_merge($default_edit, $edit);

    // Submit the flag details form.
    $this->submitForm($final_edit, 'Create Flag');

    // Load the new flag we created.
    $flag = Flag::load($final_edit['id']);

    // Make sure that we actually did get a flag entity.
    $this->assertTrue($flag instanceof Flag);

    // Return the flag.
    return $flag;
  }

}
