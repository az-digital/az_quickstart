<?php

namespace Drupal\Tests\smart_date\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Provides a base class for functional testing of smart_date fields.
 */
class SmartDateTestBase extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['node', 'smart_date', 'field_ui'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setup();
    $web_user = $this->drupalCreateUser([
      'access content',
      'administer content types',
      'bypass node access',
      'administer node fields',
      'administer node form display',
      'administer node display',
    ]);
    $this->drupalLogin($web_user);
  }

}
