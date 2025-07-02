<?php

namespace Drupal\Tests\webform\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\webform\WebformSubmissionStorageInterface;

/**
 * Defines a class for testing webform submission purge APIs.
 *
 * @group webform
 */
class WebformSubmissionPurgeApiTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'system',
    'user',
    'path',
    'path_alias',
    'webform_test_handler',
    'field',
    'webform',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('path_alias');
    $this->installSchema('webform', ['webform']);
    $this->installConfig('webform');
    $this->installEntitySchema('webform_submission');
    $this->installEntitySchema('user');
  }

  /**
   * Tests webform handler prePurge and postPurge methods and associated hooks.
   */
  public function testPurgeApis() {
    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = Webform::create([
      'id' => $this->randomMachineName(),
    ]);
    $webform->setSetting('purge', WebformSubmissionStorageInterface::PURGE_ALL);
    $webform->setSetting('purge_days', 14);
    $webform->addWebformHandler(\Drupal::service('plugin.manager.webform.handler')->createInstance('test_purge'));
    $webform->save();

    $submission_ids = [];
    for ($i = 0; $i < 10; $i++) {
      $webform_submission = WebformSubmission::create([
        'webform_id' => $webform->id(),
      ]);
      $webform_submission->in_draft = FALSE;
      // 15 days ago.
      $webform_submission->setCreatedTime(time() - (15 * 86400));
      $webform_submission->save();
      $submission_ids[$webform_submission->id()] = $webform_submission->id();
    }
    \Drupal::entityTypeManager()->getStorage('webform_submission')->purge(10);
    // At this point the submissions array will be shifted by a handler.
    array_shift($submission_ids);
    $this->assertEquals($submission_ids, \Drupal::state()->get('webform_test_purge_handler_pre'));
    // At this point the submissions array will be shifted by a hook.
    array_shift($submission_ids);
    $this->assertEquals($submission_ids, \Drupal::state()->get('webform_test_purge_hook_pre'));
    $this->assertEquals($submission_ids, \Drupal::state()->get('webform_test_purge_handler_post'));
    $this->assertEquals($submission_ids, \Drupal::state()->get('webform_test_purge_hook_post'));
  }

}
