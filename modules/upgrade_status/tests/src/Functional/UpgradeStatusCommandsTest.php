<?php

namespace Drupal\Tests\upgrade_status\Functional;

use Drush\TestTraits\DrushTestTrait;

/**
 * @coversDefaultClass \Drupal\upgrade_status\Drush\Commands\UpgradeStatusCommands
 *
 * @group upgrade_status
 */
class UpgradeStatusCommandsTest extends UpgradeStatusTestBase {

  use DrushTestTrait;

  /**
   * Tests drush commands.
   */
  public function testCommands() {
    // Test a Drupal 10 and 11 compatible module.
    if ($this->getDrupalCoreMajorVersion() < 11) {
      $this->drush('us-a', ['upgrade_status_test_11_compatible'], [], null, null, 0);
      $output = $this->getOutput();
      $this->assertStringContainsString('No known issues found.', $output);
    }
    else {
      $this->drush('upgrade_status:analyze', ['upgrade_status_test_11_compatible'], [], null, null, 3);
      $output = $this->getOutput();
      $this->assertStringContainsString('Value of core_version_requirement:', $output);
    }

    // Test a Drupal 12 compatible module.
    $this->drush('upgrade_status:analyze', ['upgrade_status_test_12_compatible'], [], null, null, 0);
    $output = $this->getOutput();
    $this->assertStringContainsString('No known issues found.', $output);

    // Test checkstyle output.
    $this->drush('upgrade_status:analyze', ['upgrade_status_test_error'], ['format' => 'checkstyle'], null, null, 3);
    $output = $this->getOutput();
    $this->assertStringContainsString('<checkstyle', $output);
    $this->assertStringContainsString('<file', $output);
    $this->assertStringContainsString('<error', $output);

    // Test codeclimate output.
    $this->drush('upgrade_status:analyze', ['upgrade_status_test_error'], ['format' => 'codeclimate'], null, null, 3);
    $output = $this->getOutput();
    $this->assertStringContainsString('"type": "issue"', $output);
    $this->assertStringContainsString('check_name', $output);
    $this->assertStringContainsString('description', $output);
    $this->assertStringContainsString('categories', $output);
    $this->assertStringContainsString('Compatibility', $output);
    $this->assertStringContainsString('location', $output);
    $this->assertStringContainsString('fingerprint', $output);
    $this->assertStringContainsString('severity', $output);

    // Test deprecated checkstyle output.
    $this->drush('upgrade_status:checkstyle', ['upgrade_status_test_error'], [], null, null, 3);
    $output = $this->getOutput();
    $this->assertStringContainsString('<checkstyle', $output);
    $this->assertStringContainsString('<file', $output);
    $this->assertStringContainsString('<error', $output);
    $output = $this->getErrorOutput();
    $this->assertStringContainsString('The checkstyle (us-cs) drush command is deprecated and will be removed.', $output);
}

}
