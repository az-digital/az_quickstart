<?php

namespace Drupal\Tests\devel\Kernel;

/**
 * Provides a class for checking dumper output.
 */
trait DevelDumperTestTrait {

  /**
   * Assertion for ensure dump content.
   *
   * Asserts that the string passed in input is equals to the string
   * representation of a variable obtained exporting the data.
   *
   * Use \Drupal\devel\DevelDumperManager::export().
   *
   * @param string $dump
   *   The string that contains the dump output to test.
   * @param mixed $data
   *   The variable to dump.
   * @param string $name
   *   (optional) The label to output before variable, defaults to NULL.
   * @param string $message
   *   (optional) A message to display with the assertion.
   */
  public function assertDumpExportEquals($dump, mixed $data, $name = NULL, $message = ''): void {
    $output = $this->getDumperExportDump($data, $name);
    $this->assertEquals(rtrim($dump), $output, $message);
  }

  /**
   * Asserts that a haystack contains the dump export output.
   *
   * Use \Drupal\devel\DevelDumperManager::export().
   *
   * @param string $haystack
   *   The string that contains the dump output to test.
   * @param mixed $data
   *   The variable to dump.
   * @param string $name
   *   (optional) The label to output before variable, defaults to NULL.
   * @param string $message
   *   (optional) A message to display with the assertion.
   */
  public function assertContainsDumpExport($haystack, mixed $data, $name = NULL, $message = ''): void {
    // As at 18.04.2020 assertContainsDumpExport() is not actually used in any
    // devel tests in any current code branch.
    $output = $this->getDumperExportDump($data, $name);
    $this->assertStringContainsString($output, (string) $haystack, $message);
  }

  /**
   * Assertion for ensure dump content.
   *
   * Asserts that the string passed in input is equals to the string
   * representation of a variable obtained dumping the data.
   *
   * Use \Drupal\devel\DevelDumperManager::dump().
   *
   * @param string $dump
   *   The string that contains the dump output to test.
   * @param mixed $data
   *   The variable to dump.
   * @param string $name
   *   (optional) The label to output before variable, defaults to NULL.
   * @param string $message
   *   (optional) A message to display with the assertion.
   */
  public function assertDumpEquals($dump, mixed $data, $name = NULL, $message = ''): void {
    $output = $this->getDumperDump($data, $name);
    $this->assertEquals(rtrim($dump), $output, $message);
  }

  /**
   * Asserts that a haystack contains the dump output.
   *
   * Use \Drupal\devel\DevelDumperManager::dump().
   *
   * @param string $haystack
   *   The string that contains the dump output to test.
   * @param mixed $data
   *   The variable to dump.
   * @param string $name
   *   (optional) The label to output before variable, defaults to NULL.
   * @param string $message
   *   (optional) A message to display with the assertion.
   */
  public function assertContainsDump($haystack, mixed $data, $name = NULL, $message = ''): void {
    $output = $this->getDumperDump($data, $name);
    $this->assertStringContainsString($output, (string) $haystack, $message);
  }

  /**
   * Returns a string representation of a variable.
   *
   * @param mixed $input
   *   The variable to dump.
   * @param string $name
   *   (optional) The label to output before variable, defaults to NULL.
   *
   * @return string
   *   String representation of a variable.
   *
   * @see \Drupal\devel\DevelDumperManager::export()
   */
  private function getDumperExportDump(mixed $input, $name = NULL): string {
    $output = \Drupal::service('devel.dumper')->export($input, $name);
    return rtrim($output);
  }

  /**
   * Returns a string representation of a variable.
   *
   * @param mixed $input
   *   The variable to dump.
   * @param string $name
   *   (optional) The label to output before variable, defaults to NULL.
   *
   * @return string
   *   String representation of a variable.
   *
   * @see \Drupal\devel\DevelDumperManager::dump()
   */
  private function getDumperDump(mixed $input, $name = NULL): string {
    ob_start();
    \Drupal::service('devel.dumper')->dump($input, $name);
    $output = ob_get_contents();
    ob_end_clean();
    return rtrim($output);
  }

}
