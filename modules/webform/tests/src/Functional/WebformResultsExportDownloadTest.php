<?php

namespace Drupal\Tests\webform\Functional;

use Drupal\file\Entity\File;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;

/**
 * Tests for webform results export download.
 *
 * @group webform
 */
class WebformResultsExportDownloadTest extends WebformBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['node', 'locale', 'webform', 'token', 'webform_attachment'];

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_exporter_archive'];

  /**
   * Tests download files.
   */
  public function testDownloadFiles() {
    $this->drupalLogin($this->rootUser);

    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = Webform::load('test_exporter_archive');

    /** @var \Drupal\webform\WebformSubmissionExporterInterface $submission_exporter */
    $submission_exporter = \Drupal::service('webform_submission.exporter');
    $submission_exporter->setWebform($webform);
    $submission_exporter->setExporter();

    $sids = [];
    $sids[] = $this->postSubmissionTest($webform);
    $sids[] = $this->postSubmissionTest($webform);
    $sids[] = $this->postSubmissionTest($webform);

    $tests = [
      [
        'archive_type' => 'tar',
        'files' => TRUE,
        'attachments' => FALSE,
      ],
      [
        'archive_type' => 'zip',
        'files' => TRUE,
        'attachments' => FALSE,
      ],
      [
        'archive_type' => 'tar',
        'files' => FALSE,
        'attachments' => TRUE,
      ],
      [
        'archive_type' => 'zip',
        'files' => FALSE,
        'attachments' => TRUE,
      ],
    ];
    foreach ($tests as $test) {
      // Set exporter archive type.
      $submission_exporter->setExporter(['archive_type' => $test['archive_type']]);

      /* Download CSV */

      // Download archive with CSV (delimited).
      $this->drupalGet('/admin/structure/webform/manage/test_exporter_archive/results/download');
      $edit = [
        'exporter' => 'delimited',
        'archive_type' => $test['archive_type'],
        'files' => $test['files'],
        'attachments' => $test['attachments'],
      ];
      $this->submitForm($edit, 'Download');

      // Load the archive and get a list of files.
      $files = $this->getArchiveContents($submission_exporter->getArchiveFilePath());

      // Check that CSV file exists.
      $this->debug($files);
      $this->assertArrayHasKey('test_exporter_archive/test_exporter_archive.csv', $files);

      // Check submission file directories.
      /** @var \Drupal\webform\WebformSubmissionInterface[] $submissions */
      $submissions = WebformSubmission::loadMultiple($sids);
      foreach ($submissions as $submission) {
        $serial = $submission->serial();

        if ($test['files']) {
          $fid = $submission->getElementData('managed_file');
          $filename = File::load($fid)->getFilename();
          $this->assertArrayHasKey("submission-$serial/$filename", $files);
        }
      }

      /* Download YAML */

      // Download archive with YAML documents.
      $this->drupalGet('/admin/structure/webform/manage/test_exporter_archive/results/download');
      $edit = [
        'exporter' => 'yaml',
        'archive_type' => $test['archive_type'],
        'files' => $test['files'],
        'attachments' => $test['attachments'],
      ];
      $this->submitForm($edit, 'Download');

      // Load the archive and get a list of files.
      $files = $this->getArchiveContents($submission_exporter->getArchiveFilePath());

      // Check that CSV file does not exists.
      $this->assertArrayNotHasKey('test_exporter_archive/test_exporter_archive.csv', $files);

      // Check submission file directories.
      /** @var \Drupal\webform\WebformSubmissionInterface[] $submissions */
      $submissions = WebformSubmission::loadMultiple($sids);
      foreach ($submissions as $submission) {
        $serial = $submission->serial();

        $this->assertArrayHasKey("submission-$serial.yml", $files);

        if ($test['files']) {
          $fid = $submission->getElementData('managed_file');
          $filename = File::load($fid)->getFilename();
          $this->assertArrayHasKey("submission-$serial/$filename", $files);
        }
      }
    }
  }

  /**
   * Get archive contents.
   *
   * @param string $filepath
   *   Archive file path.
   *
   * @return array
   *   Array of archive contents.
   */
  protected function getArchiveContents($filepath) {
    if (strpos($filepath, '.zip') !== FALSE) {
      $archive = new \ZipArchive();
      $archive->open($filepath);
      $files = [];
      for ($i = 0; $i < $archive->numFiles; $i++) {
        $files[] = $archive->getNameIndex($i);
      }
    }
    else {
      $archive = new \Archive_Tar($filepath, 'gz');
      $files = [];
      foreach ($archive->listContent() as $file_data) {
        $files[] = $file_data['filename'];
      }
    }
    return array_combine($files, $files);
  }

}
