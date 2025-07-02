<?php

declare(strict_types=1);

namespace Drupal\Tests\imagemagick\Kernel;

use Drupal\imagemagick\ImagemagickExecManagerInterface;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests for ImagemagickExecManager.
 *
 * @group imagemagick
 */
class ExecManagerTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['imagemagick', 'file_mdm', 'sophron'];

  /**
   * Test missing command on ExecManager.
   */
  public function testProcessCommandNotFound(): void {
    $execManager = \Drupal::service(ImagemagickExecManagerInterface::class);
    $output = '';
    $error = '';
    $ret = $execManager->runProcess(['pinkpanther', '-inspector', 'Clouseau'], 'blake', $output, $error);
    $this->assertTrue(in_array($ret, [1, 127], TRUE), $error);
  }

  /**
   * Test timeout on ExecManager.
   */
  public function testProcessTimeout(): void {
    $execManager = \Drupal::service(ImagemagickExecManagerInterface::class);
    $output = '';
    $error = '';
    $expected = substr(PHP_OS, 0, 3) !== 'WIN' ? [143, -1] : [1];
    // Set a short timeout (1 sec.) and run a process that is expected to last
    // longer (10 secs.). Should return a 'terminate' exit code.
    $execManager->setTimeout(1);
    $ret = $execManager->runProcess(['sleep', '10'], 'sleep', $output, $error);
    $this->assertTrue(in_array($ret, $expected, TRUE), $error);
  }

}
