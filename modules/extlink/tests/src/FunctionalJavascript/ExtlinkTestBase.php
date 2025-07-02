<?php

namespace Drupal\Tests\extlink\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\TestFileCreationTrait;
use Drupal\filter\Entity\FilterFormat;
use Drupal\filter\FilterFormatInterface;
use Drupal\user\UserInterface;

/**
 * Base class for External Link tests.
 *
 * Provides common setup stuff and various helper functions.
 */
abstract class ExtlinkTestBase extends WebDriverTestBase {

  /**
   * The default theme.
   *
   * @var string
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['extlink', 'node', 'filter', 'image'];

  /**
   * User with various administrative permissions.
   *
   * @var \Drupal\user\UserInterface
   */
  protected UserInterface $adminUser;

  /**
   * Normal visitor with limited permissions.
   *
   * @var \Drupal\user\UserInterface
   */
  protected UserInterface $normalUser;

  /**
   * Normal visitor with limited permissions.
   *
   * @var \Drupal\filter\FilterFormatInterface
   */
  protected FilterFormatInterface $emptyFormat;

  /**
   * Drupal path of the (general) External Links admin page.
   */
  const EXTLINK_ADMIN_PATH = 'admin/config/user-interface/extlink';

  /**
   * Xpath for External Links link class.
   */
  const EXTLINK_EXT_XPATH = '//*[local-name() = "svg" and @class="ext"]';

  /**
   * Xpath for External Links Mailto class.
   */
  const EXTLINK_MAILTO_XPATH = '//*[local-name() = "svg" and @class="mailto"]';

  /**
   * Xpath for External Links Tel class.
   */
  const EXTLINK_TEL_XPATH = '//*[local-name() = "svg" and @class="tel"]';

  // Set up file creation trait for image test.
  use TestFileCreationTrait {
    getTestFiles as drupalGetTestFiles;
  }

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    // Enable any module that you will need in your tests.
    parent::setUp();
    // Create a normal user.
    $permissions = [];
    $this->normalUser = $this->drupalCreateUser($permissions);

    // Create an admin user.
    $permissions[] = 'administer extlink';
    $permissions[] = 'administer permissions';
    $this->adminUser = $this->drupalCreateUser($permissions);
    $this->adminUser->roles[] = 'administrator';
    $this->adminUser->save();

    // Create page content type that we will use for testing.
    $this->drupalCreateContentType(['type' => 'page']);

    // Add a text format with minimum data only.
    $this->emptyFormat = FilterFormat::create([
      'format' => 'empty_format',
      'name' => 'Empty format',
    ]);
    $this->emptyFormat->save();
  }

}
