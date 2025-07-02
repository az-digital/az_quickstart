<?php

declare(strict_types=1);

namespace Drupal\Tests\honeypot\Functional;

use Drupal\Component\Serialization\Yaml;
use Drupal\Tests\BrowserTestBase;
use Drupal\webform\Entity\Webform;
use Drupal\webform\WebformInterface;

/**
 * Test Honeypot protection functionality on Webforms.
 *
 * @group honeypot
 */
class HoneypotWebformTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['honeypot', 'webform'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Set up required Honeypot configuration.
    $honeypot_config = \Drupal::configFactory()->getEditable('honeypot.settings');
    $honeypot_config->set('element_name', 'non_unique_field_name');
    // Disable time_limit protection.
    $honeypot_config->set('time_limit', 0);
    // Test protecting all forms.
    $honeypot_config->set('protect_all_forms', TRUE);
    $honeypot_config->set('log', FALSE);
    $honeypot_config->save();
  }

  /**
   * Test if honeypot field name is altered (single conflict).
   */
  public function testHoneypotFieldNameAlteration(): void {
    // Create new webform with a conflicting field name.
    $webform = Webform::create([
      'langcode' => 'en',
      'status' => WebformInterface::STATUS_OPEN,
      'id' => 'test_webform_single_conflict',
      'title' => 'Test webform with single conflict',
      'elements' => Yaml::encode([
        'non_unique_field_name' => [
          '#type' => 'textfield',
          '#title' => 'Non unique field name',
        ],
      ]),
    ]);
    $webform->save();

    // Visit the webform page.
    $this->drupalGet('/webform/' . $webform->id());

    // Honeypot field with the name "non_unique_field_name_"
    // should exist.
    $this->assertSession()->fieldExists('non_unique_field_name_');
  }

  /**
   * Test if honeypot field name is altered (multiple conflicts).
   */
  public function testHoneypotFieldNameAlterationMultipleConflicts(): void {
    // Create a webform with 2 conflicting field names.
    $webform = Webform::create([
      'langcode' => 'en',
      'status' => WebformInterface::STATUS_OPEN,
      'id' => 'test_webform_multiple_conflicts',
      'title' => 'Test webform with multiple conflicts',
      'elements' => Yaml::encode([
        'non_unique_field_name' => [
          '#type' => 'textfield',
          '#title' => 'Non unique field name 1',
        ],
        'non_unique_field_name_' => [
          '#type' => 'textfield',
          '#title' => 'Non unique field name 2',
        ],
      ]),
    ]);
    $webform->save();

    // Visit the webform page.
    $this->drupalGet('/webform/' . $webform->id());

    // Honeypot field with the name "non_unique_field_name__"
    // should exist.
    $this->assertSession()->fieldExists('non_unique_field_name__');
  }

  /**
   * Test if honeypot field name is not altered (No conflict).
   */
  public function testHoneypotFieldNameNoAlteration(): void {
    // Create a webform without any conflicting field names.
    $webform = Webform::create([
      'langcode' => 'en',
      'status' => WebformInterface::STATUS_OPEN,
      'id' => 'test_webform_no_conflict',
      'title' => 'Test webform without conflict',
      'elements' => Yaml::encode([
        'unique_field_name' => [
          '#type' => 'textfield',
          '#title' => 'Unique field name',
        ],
      ]),
    ]);
    $webform->save();

    // Visit the webform page.
    $this->drupalGet('/webform/' . $webform->id());

    // Check if the honeypot field with the name
    // "unique_field_name" exists (no renaming).
    $this->assertSession()->fieldExists('unique_field_name');
  }

  /**
   * Test if honeypot field name is not altered (Webform closed).
   */
  public function testHoneypotFieldWithClosedWebform(): void {
    // Create new webform with a conflicting field name.
    $webform = Webform::create([
      'langcode' => 'en',
      'status' => WebformInterface::STATUS_CLOSED,
      'id' => 'test_closed_webform',
      'title' => 'Test closed webform',
      'elements' => Yaml::encode([
        'non_unique_field_name' => [
          '#type' => 'textfield',
          '#title' => 'Unique field name',
        ],
        'unique_field_name' => [
          '#type' => 'textfield',
          '#title' => 'Unique field name',
        ],
      ]),
    ]);
    $webform->save();

    // Visit the webform page.
    $this->drupalGet('/webform/' . $webform->id());

    // Honeypot field with the name "non_unique_field_name"
    // should exist.
    $this->assertSession()->fieldExists('non_unique_field_name');
    // Honeypot field with the name "non_unique_field_name_"
    // should NOT exist.
    $this->assertSession()->fieldNotExists('non_unique_field_name_');
    // Webform field with the name "unique_field_name"
    // should NOT exist.
    $this->assertSession()->fieldNotExists('unique_field_name');
  }

}
