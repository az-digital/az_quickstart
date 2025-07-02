<?php

declare(strict_types=1);

namespace Drupal\Tests\flag\FunctionalJavascript;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\flag\Traits\FlagCreateTrait;
use Drupal\Tests\flag\Traits\FlagPermissionsTrait;

/**
 * Provides common methods for Flag tests.
 */
abstract class FlagJsTestBase extends WebDriverTestBase {

  use FlagCreateTrait;
  use FlagPermissionsTrait;
  use StringTranslationTrait;

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

}
