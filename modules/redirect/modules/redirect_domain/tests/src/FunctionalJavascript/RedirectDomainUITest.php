<?php

declare(strict_types=1);

namespace Drupal\Tests\redirect_domain\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests the UI for domain redirect.
 *
 * @group redirect_domain
 */
class RedirectDomainUITest extends WebDriverTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'redirect_domain',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests domain redirect.
   */
  public function testDomainRedirect() {
    $user = $this->drupalCreateUser([
      'administer site configuration',
      'access administration pages',
      'administer redirects',
    ]);
    $this->drupalLogin($user);
    $this->drupalGet('/admin/config/search/redirect/domain');

    // Assert that there are 2 domain redirect fields.
    $this->assertSession()->fieldExists('redirects[0][from]');
    $this->assertSession()->fieldExists('redirects[0][sub_path]');
    $this->assertSession()->fieldExists('redirects[0][destination]');

    // Add another field for new domain redirect.
    $page = $this->getSession()->getPage();
    $page->pressButton('Add another');
    $this->assertSession()->assertWaitOnAjaxRequest();

    // Add two new domain redirects.
    $page->fillField('redirects[0][from]', 'foo.example.org');
    $page->fillField('redirects[0][sub_path]', '//sub-path');
    $page->fillField('redirects[0][destination]', 'www.example.org/foo');
    $page->fillField('redirects[1][from]', 'bar.example.org');
    $page->fillField('redirects[1][sub_path]', '');
    $page->fillField('redirects[1][destination]', 'www.example.org/bar');
    $page->pressButton('Save');

    // Check the new domain redirects.
    $this->assertSession()->fieldValueEquals('redirects[0][from]', 'foo.example.org');
    $this->assertSession()->fieldValueEquals('redirects[0][destination]', 'www.example.org/foo');
    $this->assertSession()->fieldValueEquals('redirects[1][from]', 'bar.example.org');
    $this->assertSession()->fieldValueEquals('redirects[1][destination]', 'www.example.org/bar');

    // Ensure that the sub paths are correct.
    $this->assertSession()->fieldValueEquals('redirects[0][sub_path]', '/sub-path');
    $this->assertSession()->fieldValueEquals('redirects[1][sub_path]', '/');
  }

}
