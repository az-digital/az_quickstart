<?php

declare(strict_types=1);

namespace Drupal\Tests\honeypot\FunctionalJavascript;

use Drupal\Tests\file\FunctionalJavascript\FileFieldWidgetTest;

/**
 * Tests the file field widget with the Honeypot module enabled.
 *
 * @group honeypot
 */
class HoneypotFileFieldTest extends FileFieldWidgetTest {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'honeypot',
    'node',
    'file',
    'file_module_test',
    'field_ui',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Set up required Honeypot configuration.
    $honeypot_config = \Drupal::configFactory()->getEditable('honeypot.settings');
    $honeypot_config->set('element_name', 'url');
    // Disable time_limit protection.
    $honeypot_config->set('time_limit', 0);
    // Test protecting all forms.
    $honeypot_config->set('protect_all_forms', TRUE);
    $honeypot_config->set('log', FALSE);
    $honeypot_config->save();
  }

}
