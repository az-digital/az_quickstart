<?php

namespace Drupal\Tests\devel\Functional;

/**
 * Tests reinstall modules.
 *
 * @group devel
 */
class DevelModulesReinstallTest extends DevelBrowserTestBase {

  /**
   * The profile to install as a basis for testing.
   *
   * @var string
   */
  protected $profile = 'minimal';

  /**
   * Set up test.
   */
  protected function setUp(): void {
    parent::setUp();
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Reinstall modules.
   */
  public function testDevelReinstallModules(): void {
    // Minimal profile enables only dblog, block and node.
    $modules = ['dblog', 'block'];

    // Needed for compare correctly the message.
    sort($modules);

    $this->drupalGet('devel/reinstall');

    // Prepare field data in an associative array.
    $edit = [];
    foreach ($modules as $module) {
      $edit[sprintf('reinstall[%s]', $module)] = TRUE;
    }

    $this->drupalGet('devel/reinstall');
    $this->submitForm($edit, 'Reinstall');
    $this->assertSession()->pageTextContains('Uninstalled and installed: ' . implode(', ', $modules) . '.');

  }

}
