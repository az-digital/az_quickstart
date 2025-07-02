<?php

namespace Drupal\Tests\devel\Functional;

/**
 * Tests pluggable dumper feature.
 *
 * @group devel
 */
class DevelDumperTest extends DevelBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var string[]
   */
  protected static $modules = ['devel', 'devel_dumper_test'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Test dumpers configuration page.
   */
  public function testDumpersConfiguration(): void {
    $this->drupalGet('admin/config/development/devel');

    // Ensures that the dumper input is present on the config page.
    $this->assertSession()->fieldExists('dumper');

    // No need to ensure that the 'default' dumper is enabled by default via
    // "checkboxChecked('edit-dumper-default')" since devel_install does dynamic
    // default.
    // Ensures that all dumpers (both those declared by devel and by other
    // modules) are present on the config page and that only the available
    // dumpers are selectable.
    $dumpers = [
      'var_dumper' => 'Symfony var-dumper',
      'available_test_dumper' => 'Available test dumper',
      'not_available_test_dumper' => 'Not available test dumper',
    ];
    $available_dumpers = ['default', 'var_dumper', 'available_test_dumper'];

    foreach ($dumpers as $dumper => $label) {
      // Check that a radio option exists for the specified dumper.
      $this->assertSession()->elementExists('xpath', '//input[@type="radio" and @name="dumper" and @value="' . $dumper . '"]');
      $this->assertSession()->pageTextContains($label);

      // Check that the available dumpers are enabled and the non-available
      // dumpers are not enabled.
      if (in_array($dumper, $available_dumpers)) {
        $this->assertSession()->elementExists('xpath', '//input[@name="dumper" and not(@disabled="disabled") and @value="' . $dumper . '"]');
      }
      else {
        $this->assertSession()->elementExists('xpath', '//input[@name="dumper" and @disabled="disabled" and @value="' . $dumper . '"]');
      }
    }

    // Ensures that saving of the dumpers configuration works as expected.
    $edit = [
      'dumper' => 'var_dumper',
    ];
    $this->submitForm($edit, 'Save configuration');
    $this->assertSession()->pageTextContains('The configuration options have been saved.');
    $this->assertSession()->checkboxChecked('Symfony var-dumper');

    $config = \Drupal::config('devel.settings')->get('devel_dumper');
    $this->assertEquals('var_dumper', $config, 'The configuration options have been properly saved');
  }

  /**
   * Test variable is dumped in page.
   */
  public function testDumpersOutput(): void {
    $edit = [
      'dumper' => 'available_test_dumper',
    ];
    $this->drupalGet('admin/config/development/devel');
    $this->submitForm($edit, 'Save configuration');
    $this->assertSession()->pageTextContains('The configuration options have been saved.');

    $this->drupalGet('devel_dumper_test/dump');
    $elements = $this->xpath('//body/pre[contains(text(), :message)]', [':message' => 'AvailableTestDumper::dump() Test output']);
    $this->assertNotEmpty($elements, 'Dumped message #1 is present.');

    $this->drupalGet('devel_dumper_test/message');
    $elements = $this->xpath('//div[@aria-label="Status message"]/pre[contains(text(), :message)]', [':message' => 'AvailableTestDumper::export() Test output']);
    $this->assertNotEmpty($elements, 'Dumped message #2 is present.');

    $this->drupalGet('devel_dumper_test/export');
    $elements = $this->xpath('//div[@class="layout-content"]//pre[contains(text(), :message)]', [':message' => 'AvailableTestDumper::export() Test output']);
    $this->assertNotEmpty($elements, 'Dumped message #3 is present.');

    $this->drupalGet('devel_dumper_test/export_renderable');
    $elements = $this->xpath('//div[@class="layout-content"]//pre[contains(text(), :message)]', [':message' => 'AvailableTestDumper::exportAsRenderable() Test output']);
    $this->assertNotEmpty($elements, 'Dumped message #4 is present.');
    // Ensures that plugins can add libraries to the page when the
    // ::exportAsRenderable() method is used.
    $this->assertSession()->responseContains('devel_dumper_test/css/devel_dumper_test.css');
    $this->assertSession()->responseContains('devel_dumper_test/js/devel_dumper_test.js');

    $debug_filename = \Drupal::service('file_system')->getTempDirectory() . '/' . 'drupal_debug.txt';
    $this->drupalGet('devel_dumper_test/debug');
    $file_content = file_get_contents($debug_filename);
    $expected = <<<EOF
<pre>AvailableTestDumper::export() Test output</pre>

EOF;
    $this->assertEquals($file_content, $expected, 'Dumped message #5 is present.');

    // Ensures that the DevelDumperManager::debug() is not access checked and
    // that the dump is written in the debug file even if the user has not the
    // 'access devel information' permission.
    file_put_contents($debug_filename, '');
    $this->drupalLogout();
    $this->drupalGet('devel_dumper_test/debug');
    $file_content = file_get_contents($debug_filename);
    $expected = <<<EOF
<pre>AvailableTestDumper::export() Test output</pre>

EOF;
    $this->assertEquals($file_content, $expected, 'Dumped message #6 is present.');
  }

}
