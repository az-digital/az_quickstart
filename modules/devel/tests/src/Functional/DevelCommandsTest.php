<?php

namespace Drupal\Tests\devel\Functional;

use Drupal\devel\Drush\Commands\DevelCommands;
use Drupal\Tests\BrowserTestBase;
use Drush\TestTraits\DrushTestTrait;

/**
 * Test class for the Devel drush commands.
 *
 * Note: Drush must be installed. Add it to your require-dev in composer.json.
 */

/**
 * @coversDefaultClass \Drupal\devel\Drush\Commands\DevelCommands
 * @group devel
 */
class DevelCommandsTest extends BrowserTestBase {

  use DrushTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['devel'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests drush commands.
   */
  public function testCommands(): void {
    $this->drush(DevelCommands::TOKEN, [], ['format' => 'json']);
    $output = $this->getOutputFromJSON();
    $tokens = array_column($output, 'token');
    $this->assertContains('account-name', $tokens);

    $this->drush(DevelCommands::SERVICES, [], ['format' => 'json']);
    $output = $this->getOutputFromJSON();
    $this->assertContains('current_user', $output);
  }

}
