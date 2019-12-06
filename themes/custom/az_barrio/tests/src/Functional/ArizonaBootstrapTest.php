<?php

namespace Drupal\Tests\az_barrio\Functional;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests to verify function of arizona-bootstrap within az_barrio.
 *
 * @group az_barrio
 */
class ArizonaBootstrapTest extends BrowserTestBase {

  /**
   * The installation profile to use during the tests.
   *
   * @var string
   */
  protected $profile = 'az_quickstart';

  /**
   * @var bool
   */
  protected $strictConfigSchema = FALSE;

  /**
   * @var string
   */
  protected $defaultTheme = 'az_barrio';

  /**
   * Test inclusion of the arizona-bootstrap library.
   */
  public function testArizonaBootstrapLibrary() {

    // Set up our test user.
    $user = $this->drupalCreateUser(['access administration pages', 'administer site configuration']);
    $this->drupalLogin($user);
    $assert = $this->assertSession();

    // Try to navigate to performance settings and turn off aggregation.
    $this->drupalGet(Url::fromRoute('system.performance_settings'));
    $assert->statusCodeEquals(200);
    $this->submitForm(['edit-preprocess-css' => FALSE, 'edit-preprocess-js' => FALSE], 'Save configuration', 'system-performance-settings');

    // Clear out caches to allow a rebuild.
    drupal_flush_all_caches();

    // Try to check that the aggregation settings have been turned off.
    $this->drupalGet(Url::fromRoute('system.performance_settings'));
    $assert->statusCodeEquals(200);
    $assert->checkboxNotChecked('edit-preprocess-css');
    $assert->checkboxNotChecked('edit-preprocess-js');

    // Try to successfully navigate to the front page.
    $this->drupalGet(Url::fromRoute('<front>'));
    $assert->statusCodeEquals(200);

    // Try to fetch the library discovery service.
    $library_service = \Drupal::service('library.discovery');
    $assert->assert($library_service, 'Found the library.discovery service.');

    // Try to locate the library information of the arizona-bootstrap library.
    $library = $library_service->getLibraryByName('az_barrio', 'arizona-bootstrap');
    $assert->assert($library, 'Found information for the arizona-bootstrap library.');

    // Inspect library information for stylesheets or scripts.
    $components = ['css', 'js'];
    $link_count = 0;
    foreach ($components as $component) {
      if (!empty($library[$component])) {
        foreach ($library[$component] as $file) {
          // A library key of 'data' might contain a library URL to check.
          if (isset($file['data']) && filter_var($file['data'], FILTER_VALIDATE_URL)) {
            // A library URL was found. Check if it is included on the page.
            // Compute an array of XPath arguments.
            $xpath_arguments = [':url' => $file['data']];

            // Increment the number of found URLs.
            $link_count++;

            // The XPath selector to check for varies by type.
            $selectors = [
              'css' => '//link[@href=:url]',
              'js'  => '//script[@src=:url]',
            ];
            $selector = $assert->buildXPathQuery($selectors[$component], $xpath_arguments);

            // Check that the URL is being included on the front page.
            $assert->elementExists('xpath', $selector);
          }
        }
      }
    }

    // The arizona-bootstrap library should have files in it.
    $assert->assert($link_count > 0, 'The arizona-bootstrap library contained files.');
  }

}
