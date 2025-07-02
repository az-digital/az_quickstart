<?php

namespace Drupal\Tests\date_ap_style\Kernel;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\KernelTests\KernelTestBase;

/**
 * Kernel tests for the twig filter.
 *
 * @group date_ap_style
 */
class ApStyleFilterTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'date_ap_style'];

  /**
   * Tests the ap_style filter.
   */
  public function testBasicFilter() {
    // Get the Twig service.
    $twig = $this->container->get('twig');

    // Create a mock timestamp.
    $timestamp = 1630454400;

    // Render a Twig template using the ap_style filter.
    $template = $twig->createTemplate('{{ timestamp|ap_style }}');
    $output = $template->render(['timestamp' => $timestamp]);

    // Assert the expected output.
    $this->assertEquals('Sept. 1, 2021', $output);
  }

  /**
   * Tests the ap_style filter.
   */
  public function testOptionsFilter() {
    // Get the Twig service.
    $twig = $this->container->get('twig');

    $today = new DrupalDateTime('today');
    $today->setTime(13, 30);
    $today = $today->getTimestamp();

    // Render a Twig template using the ap_style filter.
    $template = $twig->createTemplate('{{ timestamp|ap_style({"use_today": true, "cap_today": true}) }}');
    $output = $template->render(['timestamp' => $today]);

    // Assert the expected output.
    $this->assertEquals('Today', $output);
  }

}
