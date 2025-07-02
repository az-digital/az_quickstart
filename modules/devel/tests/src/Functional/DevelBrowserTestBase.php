<?php

namespace Drupal\Tests\devel\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Browser test base class for Devel functional tests.
 *
 * DevelCommandsTest should not extend this class so that it can remain
 * independent and be used as a cut-and-paste example for other developers.
 */
abstract class DevelBrowserTestBase extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['devel', 'devel_test', 'block'];

  /**
   * Admin user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * User with Devel acces but not site admin permission.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $develUser;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->adminUser = $this->drupalCreateUser([
      'access devel information',
      'administer site configuration',
    ]);

    $this->develUser = $this->drupalCreateUser([
      'access devel information',
    ]);
  }

}
