<?php

namespace Drupal\Tests\webform\Functional;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Test\AssertMailTrait;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\webform\Traits\WebformBrowserTestTrait;

/**
 * Defines an abstract test base for webform tests.
 */
abstract class WebformBrowserTestBase extends BrowserTestBase {

  use AssertMailTrait;
  use WebformBrowserTestTrait;
  use StringTranslationTrait;

  /**
   * Set default theme to stable.
   *
   * @var string
   */
  protected $defaultTheme = 'stable9';

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['webform'];

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = [];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->loadWebforms(static::$testWebforms);
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    $this->purgeSubmissions();
    parent::tearDown();
  }

}
