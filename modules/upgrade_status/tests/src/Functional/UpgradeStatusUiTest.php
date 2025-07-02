<?php

namespace Drupal\Tests\upgrade_status\Functional;

use Drupal\Core\Url;
use Drupal\user\Entity\Role;

/**
 * Tests the UI before and after running scans.
 *
 * @group upgrade_status
 */
class UpgradeStatusUiTest extends UpgradeStatusTestBase {

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();
    $this->drupalLogin($this->drupalCreateUser(['administer software updates']));
  }

  /**
   * Test the user interface before running a scan.
   */
  public function testUiBeforeScan() {
    $this->drupalGet(Url::fromRoute('upgrade_status.report'));
    $assert_session = $this->assertSession();

    $assert_session->buttonExists('Scan selected');
    $assert_session->buttonExists('Export selected as HTML');

    // Scan result for every project should be 'N/A'.
    $status = $this->getSession()->getPage()->findAll('css', 'td.scan-result');
    $this->assertNotEmpty($status);
    foreach ($status as $project_status) {
      $this->assertSame('N/A', $project_status->getHtml());
    }
  }

  /**
   * Test the user interface after running a scan.
   */
  public function testUiAfterScan() {
    $this->runFullScan();

    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    $assert_session->buttonExists('Scan selected');
    $assert_session->buttonExists('Export selected as HTML');

    // Error and no-error test module results should show.
    $this->assertSame('7 problems', strip_tags($page->find('css', 'tr.project-upgrade_status_test_error td.scan-result')->getHtml()));
    $this->assertSame($this->getDrupalCoreMajorVersion() < 11 ? 'No problems found' : '1 problem', strip_tags($page->find('css', 'tr.project-upgrade_status_test_11_compatible td.scan-result')->getHtml()));
    $this->assertSame('No problems found', strip_tags($page->find('css', 'tr.project-upgrade_status_test_12_compatible td.scan-result')->getHtml()));

    // Parent module should show up without errors and submodule should not appear.
    $this->assertSame($this->getDrupalCoreMajorVersion() < 11 ? 'No problems found' : '2 problems', strip_tags($page->find('css', 'tr.project-upgrade_status_test_submodules td.scan-result')->getHtml()));
    $this->assertEmpty($page->find('css', 'tr.upgrade_status_test_submodules_a'));

    // Contrib test modules should show with results.
    $this->assertSame('6 problems', strip_tags($page->find('css', 'tr.project-upgrade_status_test_contrib_error td.scan-result')->getHtml()));
    $this->assertSame($this->getDrupalCoreMajorVersion() < 11 ? 'No problems found' : '1 problem', strip_tags($page->find('css', 'tr.project-upgrade_status_test_contrib_11_compatible td.scan-result')->getHtml()));
    // This contrib module has a different project name. Ensure the drupal.org link used that.
    $next_major = $this->getDrupalCoreMajorVersion() + 1;
    $this->assertSession()->linkByHrefExists('https://drupal.org/project/issues/upgrade_status_test_contributed_11_compatible?text=Drupal+' . $next_major . '&status=All');

    // Check UI of results for the custom project.
    $this->drupalGet('/admin/reports/upgrade-status/project/upgrade_status_test_error');
    $this->assertSession()->pageTextContains('Upgrade status test error');
    $this->assertSession()->pageTextContains('2 errors found. ' . ($this->getDrupalCoreMajorVersion() < 10 ? '4' : '5') . ' warnings found.');
    $this->assertSession()->pageTextContains('Call to deprecated function upgrade_status_test_contrib_error_function_9_to_10(). Deprecated in drupal:9.1.0 and is removed from drupal:10.0.0. Use the replacement instead.');

    // Go forward to the export page and assert that still contains the results
    // as well as an export specific title.
    $this->clickLink('Export as HTML');
    $this->assertSession()->pageTextContains('Upgrade Status report');
    $this->assertSession()->pageTextContains('Upgrade status test error');
    $this->assertSession()->pageTextContains('Custom projects');
    $this->assertSession()->pageTextNotContains('Contributed projects');
    $this->assertSession()->pageTextContains('2 errors found. ' . ($this->getDrupalCoreMajorVersion() < 10 ? '4' : '5') . ' warnings found.');
    $this->assertSession()->pageTextContains('Call to deprecated function upgrade_status_test_contrib_error_function_9_to_10(). Deprecated in drupal:9.1.0 and is removed from drupal:10.0.0. Use the replacement instead.');

    // Go back to the results page and click over to exporting in single ASCII.
    $this->drupalGet('/admin/reports/upgrade-status/project/upgrade_status_test_error');
    $this->clickLink('Export as text');
    $this->assertSession()->pageTextContains('Upgrade status test error');
    $this->assertSession()->pageTextContains('CUSTOM PROJECTS');
    $this->assertSession()->pageTextNotContains('CONTRIBUTED PROJECTS');
    $this->assertSession()->pageTextContains('2 errors found. ' . ($this->getDrupalCoreMajorVersion() < 10 ? '4' : '5') . ' warnings found.');
    $this->assertSession()->pageTextContains('Call to deprecated function upgrade_status_test_contrib_error_function_9_to_10(). Deprecated in drupal:9.1.0 and is removed from drupal:10.0.0. Use the replacement instead.');

    // Run partial export of multiple projects.
    $edit = [
      'manual[data][list][upgrade_status_test_error]' => TRUE,
      ($this->getDrupalCoreMajorVersion() < 11 ? 'relax' : 'manual') . '[data][list][upgrade_status_test_11_compatible]' => TRUE,
      'collaborate[data][list][upgrade_status_test_contrib_error]' => TRUE,
    ];
    $expected = [
      'Export selected as HTML' => ['Contributed projects', 'Custom projects'],
      'Export selected as text' => ['CONTRIBUTED PROJECTS', 'CUSTOM PROJECTS'],
    ];
    foreach ($expected as $button => $assert) {
      $this->drupalGet('admin/reports/upgrade-status');
      $this->submitForm($edit, $button);
      $this->assertSession()->pageTextContains($assert[0]);
      $this->assertSession()->pageTextContains($assert[1]);
      $this->assertSession()->pageTextContains('Upgrade status test contrib error');
      $this->assertSession()->pageTextContains('Upgrade status test 11 compatible');
      $this->assertSession()->pageTextContains('Upgrade status test error');
      $this->assertSession()->pageTextNotContains('Upgrade status test root module');
      $this->assertSession()->pageTextNotContains('Upgrade status test contrib 11 compatible');
      $this->assertSession()->pageTextContains('2 errors found. ' . ($this->getDrupalCoreMajorVersion() < 10 ? '4' : '5') . ' warnings found.');
      $this->assertSession()->pageTextContains('Call to deprecated function upgrade_status_test_contrib_error_function_9_to_10(). Deprecated in drupal:9.1.0 and is removed from drupal:10.0.0. Use the replacement instead.');
    }
  }

  /**
   * Test the user interface for role checking.
   */
  public function testRoleChecking() {
    if ($this->getDrupalCoreMajorVersion() == 9) {
      $authenticated = Role::load('authenticated');
      $authenticated->grantPermission('upgrade status invalid permission test');
      $authenticated->save();
      $this->drupalGet(Url::fromRoute('upgrade_status.report'));
      $this->assertSession()->pageTextContains('Permissions of user role: "Authenticated user":upgrade status invalid permission test');
    }
  }

}
