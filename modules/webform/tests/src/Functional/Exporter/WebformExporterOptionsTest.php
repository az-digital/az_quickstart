<?php

namespace Drupal\Tests\webform\Functional\Exporter;

use Drupal\Tests\webform\Functional\WebformBrowserTestBase;
use Drupal\webform\Entity\Webform;

/**
 * Tests for the webform exporter options.
 *
 * @group webform
 */
class WebformExporterOptionsTest extends WebformBrowserTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_exporter_options'];

  /**
   * Test exporter options.
   */
  public function testExporterOptions() {
    $assert_session = $this->assertSession();

    $this->drupalLogin($this->rootUser);

    $webform = Webform::load('test_exporter_options');

    // Create one submission.
    $sid = $this->postSubmission($webform);

    // Set default edit export settings.
    $edit = [
      // Exclude all columns except sid.
      'excluded_columns' => 'serial,uuid,token,uri,created,completed,changed,in_draft,current_page,remote_addr,uid,langcode,webform_id,entity_type,entity_id,sticky,locked,notes',
    ];

    // Check default options.
    $this->getExport($webform, $edit);
    $assert_session->responseContains('"Submission ID","Select single","Select multiple","Select other"');
    $assert_session->responseContains($sid . ',One,One;Two;Three,Four');

    // Check manually setting default options.
    $edit += [
      'options_single_format' => 'compact',
      'options_multiple_format' => 'compact',
      'options_item_format' => 'label',
    ];
    $this->getExport($webform, $edit);
    $assert_session->responseContains('"Submission ID","Select single","Select multiple","Select other"');
    $assert_session->responseContains($sid . ',One,One;Two;Three,Four');

    // Check item format key.
    $edit['options_item_format'] = 'key';
    $this->getExport($webform, $edit);
    $assert_session->responseContains($sid . ',one,one;two;three,Four');

    // Check options single separate format.
    $edit['options_single_format'] = 'separate';
    $edit['options_multiple_format'] = 'compact';
    $this->getExport($webform, $edit);
    $assert_session->responseContains('"Submission ID","Select single: one","Select single: two","Select single: three","Select multiple","Select other: one","Select other: two","Select other: three","Select other: other"');
    $assert_session->responseContains($sid . ',X,,,one;two;three,,,,Four');

    // Check options multiple separate format.
    $edit['options_single_format'] = 'compact';
    $edit['options_multiple_format'] = 'separate';
    $this->getExport($webform, $edit);
    $assert_session->responseContains('"Submission ID","Select single","Select multiple: one","Select multiple: two","Select multiple: three","Select other"');
    $assert_session->responseContains($sid . ',one,X,X,X,Four');

    // Check options single and multiple separate format.
    $edit['options_single_format'] = 'separate';
    $edit['options_multiple_format'] = 'separate';
    $this->getExport($webform, $edit);
    $assert_session->responseContains('"Submission ID","Select single: one","Select single: two","Select single: three","Select multiple: one","Select multiple: two","Select multiple: three","Select other: one","Select other: two","Select other: three","Select other: other"');
    $assert_session->responseContains($sid . ',X,,,X,X,X,,,,Four');
  }

}
