<?php

declare(strict_types=1);

namespace Drupal\Tests\easy_breadcrumb\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests configuring easy_breadcrumb.
 *
 * @group easy_breadcrumb
 */
class EasyBreadcrumbConfigureTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['easy_breadcrumb'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests configuring easy_breadcrumb.
   */
  public function testAdministration() {
    $assert = $this->assertSession();

    $config_after_install = $this->config('easy_breadcrumb.settings')->get();
    $this->drupalGet('admin/config/user-interface/easy-breadcrumb');
    $assert->statusCodeEquals(403);

    $this->drupalLogin($this->createUser(['administer easy breadcrumb']));
    $this->drupalGet('admin/config/user-interface/easy-breadcrumb');
    $assert->statusCodeEquals(200);
    $this->submitForm([], 'Save configuration');
    $assert->statusCodeEquals(200);
    $assert->pageTextContainsOnce('The configuration options have been saved.');
    $this->assertSame($config_after_install, $this->config('easy_breadcrumb.settings')->get());
  }

}
