<?php

namespace Drupal\Tests\smart_date\Functional;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Tests the start date value in a date range.
 *
 * This test uses SmartDateTestBase to set up a fixture site so that we can test
 * for error messages when a user enters an invalid value for the start date in
 * a date range.
 *
 * @code
 * vendor/phpunit/phpunit/phpunit -c core/phpunit.xml --printer '\Drupal\Tests\Listeners\HtmlOutputPrinter' modules/start_date/tests/src/Functional
 * @endcode
 *
 * @group start_date
 */
class SmartDateStartValueTest extends SmartDateTestBase {

  /**
   * The content type name.
   *
   * @var string
   */
  protected $contentTypeName;

  /**
   * The field name.
   *
   * @var string
   */
  protected $fieldName;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    // Always call the parent setUp().
    parent::setUp();

    // Create a test content type.
    $this->contentTypeName = 'smart_date_content';
    $this->drupalCreateContentType(['type' => $this->contentTypeName]);

    $this->fieldName = 'field_date';
    $field_storage = FieldStorageConfig::create([
      'field_name' => $this->fieldName,
      'entity_type' => 'node',
      'type' => 'smartdate',
    ]);
    $field_storage->save();

    $field = FieldConfig::create([
      'field_name' => $this->fieldName,
      'entity_type' => 'node',
      'bundle' => $this->contentTypeName,
      'label' => 'Date',
    ]);
    $field->setDefaultValue([
      'default_duration' => 60,
      'default_duration_increments' => "30\r\n60|1 hour\r\n90\r\n120|2 hours\r\ncustom",
      'default_date_type' => '',
      'default_date' => '',
      'min' => '',
      'max' => '',
    ]);
    $field->save();

    // Assign widget settings for the default form mode.
    $settings = [
      'type' => 'smartdate_timezone',
      'weight' => 122,
      'region' => 'content',
      'settings' => [
        'modal' => FALSE,
        'default_tz' => '',
        'custom_tz' => '',
        'allowed_timezones' => [],
        'default_duration' => 60,
        'default_duration_increments' => "30\r\n60|1 hour\r\n90\r\n120|2 hours\r\ncustom",
        'show_extra' => FALSE,
        'hide_date' => TRUE,
        'separator' => 'to',
        'add_abbreviations' => '',
      ],
      'third_party_settings' => [],
    ];

    /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $display_repository */
    $display_repository = \Drupal::service('entity_display.repository');
    $display_repository->getFormDisplay('node', $this->contentTypeName)
      ->setComponent($this->fieldName, $settings)
      ->save();
  }

  /**
   * Tests for the existence of a default menu item on the home page.
   *
   * We'll open the home page and look for the Tools menu link called 'Add
   * content.'
   */
  public function testForStartDateAfterEndDate() {
    $assert = $this->assertSession();

    $this->drupalLogin(
      $this->createUser([
        'create ' . $this->contentTypeName . ' content',
      ])
    );

    $this->drupalGet('node/add/' . $this->contentTypeName);
    $title = $this->randomMachineName(20);
    $edit = [
      'title[0][value]' => $title,
      // Set the start value to be later than the end value,
      // 2024-01-01 13:00:00.
      $this->fieldName . '[0][time_wrapper][value][date]' => '2024-01-01',
      $this->fieldName . '[0][time_wrapper][value][time]' => '13:00:00',
      // Set the end date before the start date to trigger the error,
      // 2024-01-01 12:00:00.
      $this->fieldName . '[0][time_wrapper][end_value][date]' => '2024-01-01',
      $this->fieldName . '[0][time_wrapper][end_value][time]' => '12:00:00',
    ];

    $this->submitForm($edit, 'Save');

    $assert->pageTextContains('end date cannot be before the start date');
  }

}
