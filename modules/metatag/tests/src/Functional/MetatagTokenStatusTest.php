<?php

namespace Drupal\Tests\metatag\Functional;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;

/**
 * Test the token status for metatag.
 *
 * @group metatag
 */
class MetatagTokenStatusTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['metatag'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Test the status report does not contain warnings about types.
   *
   * @see token_get_token_problems
   */
  public function testStatusReportTypesWarning() {
    $this->drupalLogin($this->rootUser);
    $this->drupalGet(Url::fromRoute('system.status'));

    $this->assertSession()->pageTextNotContains('$info[\'types\'][\'metatag');
  }

  /**
   * Test the status report does not contain warnings about tokens.
   *
   * @see token_get_token_problems
   */
  public function testStatusReportTokensWarning() {
    $this->drupalLogin($this->rootUser);
    $this->drupalGet(Url::fromRoute('system.status'));

    $this->assertSession()->pageTextNotContains('$info[\'tokens\'][\'metatag');
  }

}
