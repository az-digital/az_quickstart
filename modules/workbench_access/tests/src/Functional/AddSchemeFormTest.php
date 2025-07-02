<?php

namespace Drupal\Tests\workbench_access\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\workbench_access\Traits\WorkbenchAccessTestTrait;

/**
 * Tests for the add scheme form.
 *
 * @group workbench_access
 */
class AddSchemeFormTest extends BrowserTestBase {

  use WorkbenchAccessTestTrait;

  /**
   * The default theme.
   *
   * @var string
   */
  protected $defaultTheme = 'stark';

  /**
   * Admin user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $admin;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'workbench_access',
    'workbench_access_test',
    'menu_link_content',
    'link',
    'options',
    'user',
    'system',
    'views',
    'taxonomy',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->setUpVocabulary();
    $this->admin = $this->setUpAdminUser(['administer workbench access']);
  }

  /**
   * Ensures the add scheme options match what we expect, including derivatives.
   */
  public function testAddSchemeOptions() {
    $this->drupalLogin($this->admin);
    $this->drupalGet('/admin/config/workflow/workbench_access/access_scheme/add');
    $expected_options = [
      'menu',
      'taxonomy',
      'workbench_access_test_derived:foo',
      'workbench_access_test_derived:bar',
    ];
    foreach ($expected_options as $option) {
      $this->assertSession()->optionExists('Access scheme', $option);
    }
  }

}
