<?php

namespace Drupal\Tests\webform\Functional\Exporter;

use Drupal\Tests\webform\Functional\WebformBrowserTestBase;

/**
 * Tests for the webform exporter excluded.
 *
 * @group webform
 */
class WebformExporterExcludedTest extends WebformBrowserTestBase {

  /**
   * Test excluded exporters.
   */
  public function testExcludeExporters() {
    $assert_session = $this->assertSession();

    $this->drupalLogin($this->rootUser);

    // Check exporter options.
    $this->drupalGet('/admin/structure/webform/manage/contact/results/download');
    $assert_session->responseContains('<option value="delimited"');
    $assert_session->responseContains('<option value="table"');
    $assert_session->responseContains('<option value="json"');
    $assert_session->responseContains('<option value="yaml"');

    // Exclude the delimited exporter.
    \Drupal::configFactory()->getEditable('webform.settings')->set('export.excluded_exporters', ['delimited' => 'delimited'])->save();

    // Check delimited exporter excluded.
    $this->drupalGet('/admin/structure/webform/manage/contact/results/download');
    $assert_session->responseNotContains('<option value="delimited"');
    $assert_session->responseContains('<option value="table"');
    $assert_session->responseContains('<option value="json"');
    $assert_session->responseContains('<option value="yaml"');
  }

}
