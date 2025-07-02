<?php

namespace Drupal\Tests\webform_node\Functional;

use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\webform\WebformInterface;

/**
 * Tests for webform node.
 *
 * @group webform_node
 */
class WebformNodeTest extends WebformNodeBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['block', 'webform', 'webform_node'];

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_form_limit', 'test_confirmation_inline'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Place webform test blocks.
    $this->placeWebformBlocks('webform_test_block_submission_limit');
  }

  /**
   * Tests webform node.
   */
  public function testNode() {
    global $base_path;

    $assert_session = $this->assertSession();

    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = Webform::load('contact');
    $node = $this->createWebformNode('contact');

    /** @var \Drupal\webform\WebformEntityReferenceManagerInterface $entity_reference_manager */
    $entity_reference_manager = \Drupal::service('webform.entity_reference_manager');

    $normal_user = $this->drupalCreateUser();

    /* ********************************************************************** */

    // Check table names.
    $this->assertEquals($entity_reference_manager->getTableNames(), [
      "node__webform" => 'webform',
      "node_revision__webform" => 'webform',
    ]);

    /* ********************************************************************** */
    // Webform node basic.
    /* ********************************************************************** */

    // Check contact webform.
    $this->drupalGet('/node/' . $node->id());
    $assert_session->responseContains('id="webform-submission-contact-node-' . $node->id() . '-add-form"');
    $assert_session->fieldValueNotEquals('name', 'John Smith');

    // Check contact webform with default data.
    $node->webform->default_data = "name: 'John Smith'";
    $node->save();
    $this->drupalGet('/node/' . $node->id());
    $assert_session->fieldValueEquals('name', 'John Smith');

    /* ********************************************************************** */
    // Webform closed.
    /* ********************************************************************** */

    $webform->setStatus(WebformInterface::STATUS_CLOSED);
    $webform->save();

    // Check page closed message.
    $this->drupalGet('/node/' . $node->id());
    $assert_session->responseContains('Sorry… This form is closed to new submissions.');

    $this->drupalLogin($this->rootUser);

    // Check webform closed warning.
    $this->drupalGet('/node/' . $node->id() . '/edit');
    $assert_session->responseContains('The <em class="placeholder">Contact</em> webform is <a href="' . $base_path . 'admin/structure/webform/manage/contact/settings/form">closed</a>. The below status will be ignored.');

    $webform->setStatus(WebformInterface::STATUS_OPEN);
    $webform->save();
    $this->drupalLogout();

    /* ********************************************************************** */
    // Webform node open and closed.
    /* ********************************************************************** */

    // Check contact webform closed.
    $node->webform->status = WebformInterface::STATUS_CLOSED;
    $node->save();
    $this->drupalGet('/node/' . $node->id());
    $assert_session->fieldNotExists('name');
    $assert_session->responseContains('Sorry… This form is closed to new submissions.');

    /* Confirmation inline (test_confirmation_inline) */

    // Check confirmation inline webform.
    $node->webform->target_id = 'test_confirmation_inline';
    $node->webform->default_data = '';
    $node->webform->status = WebformInterface::STATUS_OPEN;
    $node->webform->open = '';
    $node->webform->close = '';
    $node->save();
    $this->postNodeSubmission($node);
    $assert_session->responseContains('This is a custom inline confirmation message.');

    /* ********************************************************************** */
    // Webform node scheduled.
    /* ********************************************************************** */

    // Check scheduled to open.
    $node->webform->target_id = 'contact';
    $node->webform->status = WebformInterface::STATUS_SCHEDULED;
    $node->webform->open = date('Y-m-d\TH:i:s', strtotime('today +1 day'));
    $node->webform->close = '';
    $node->save();
    $this->drupalGet('/node/' . $node->id());
    $assert_session->responseContains('This form has not yet been opened to submissions.');
    $assert_session->fieldNotExists('name');

    // Check scheduled and opened.
    $node->webform->target_id = 'contact';
    $node->webform->status = WebformInterface::STATUS_SCHEDULED;
    $node->webform->open = date('Y-m-d\TH:i:s', strtotime('today -1 day'));
    $node->webform->close = '';
    $node->save();
    $this->drupalGet('/node/' . $node->id());
    $assert_session->responseNotContains('This form has not yet been opened to submissions.');
    $assert_session->fieldExists('name');

    // Check scheduled and closed.
    $node->webform->target_id = 'contact';
    $node->webform->status = WebformInterface::STATUS_SCHEDULED;
    $node->webform->open = '';
    $node->webform->close = date('Y-m-d\TH:i:s', strtotime('today -1 day'));
    $node->save();
    $this->drupalGet('/node/' . $node->id());
    $assert_session->responseContains('Sorry… This form is closed to new submissions.');
    $assert_session->fieldNotExists('name');

    // Check scheduled and is open because open or close data was not set.
    $node->webform->target_id = 'contact';
    $node->webform->status = WebformInterface::STATUS_SCHEDULED;
    $node->webform->open = '';
    $node->webform->close = '';
    $node->save();
    $this->drupalGet('/node/' . $node->id());
    $assert_session->responseNotContains('Sorry… This form is closed to new submissions.');
    $assert_session->fieldExists('name');

    // Check that changes to global message clear the cache.
    $node->webform->target_id = 'contact';
    $node->webform->status = WebformInterface::STATUS_SCHEDULED;
    $node->webform->open = '';
    $node->webform->close = date('Y-m-d\TH:i:s', strtotime('today -1 day'));
    $node->save();
    $this->drupalGet('/node/' . $node->id());

    \Drupal::configFactory()
      ->getEditable('webform.settings')
      ->set('settings.default_form_close_message', '{Custom closed message}')
      ->save();
    $this->drupalGet('/node/' . $node->id());
    $assert_session->responseContains('{Custom closed message}');

    /* ********************************************************************** */
    // Submission limit (test_form_limit).
    /* ********************************************************************** */

    // Set per source entity total and user limit.
    // @see \Drupal\webform\Tests\WebformSubmissionFormSettingsTest::testSettings
    $node->webform->target_id = 'test_form_limit';
    $node->webform->default_data = '';
    $node->webform->open = '';
    $node->webform->close = '';
    $node->save();

    $limit_form = Webform::load('test_form_limit');
    $limit_form->setSettings([
      'limit_total' => NULL,
      'limit_user' => NULL,
      'entity_limit_total' => 3,
      'entity_limit_user' => 1,
      'limit_total_message' => 'Only 3 submissions are allowed.',
      'limit_user_message' => 'You are only allowed to have 1 submission for this webform.',
    ]);
    $limit_form->save();

    $this->drupalGet('/node/' . $node->id());

    // Check submission limit tokens.
    $assert_session->responseContains('limit:webform:source_entity: 3');
    $assert_session->responseContains('remaining:webform:source_entity: 3');
    $assert_session->responseContains('limit:user:source_entity: 1');
    $assert_session->responseContains('remaining:user:source_entity: 1');

    // Check submission limit blocks.
    $assert_session->responseContains('0 user + source entity submission(s)');
    $assert_session->responseContains('1 user + source entity limit');
    $assert_session->responseContains('0 webform + source entity submission(s)');
    $assert_session->responseContains('3 webform + source entity limit');

    // Login as normal user.
    $this->drupalLogin($normal_user);

    // Create submission as authenticated user.
    $this->postNodeSubmission($node);

    $this->drupalGet('/node/' . $node->id());

    // Check per source entity user limit.
    $assert_session->buttonNotExists('Submit');
    $assert_session->responseContains('You are only allowed to have 1 submission for this webform.');

    // Check submission limit blocks.
    $assert_session->responseContains('1 user + source entity submission(s)');
    $assert_session->responseContains('1 user + source entity limit');
    $assert_session->responseContains('1 webform + source entity submission(s)');
    $assert_session->responseContains('3 webform + source entity limit');

    // Login as root user.
    $this->drupalLogin($this->rootUser);

    // Check submission limit tokens.
    $this->drupalGet('/node/' . $node->id());
    $assert_session->responseContains('remaining:webform:source_entity: 2');
    $assert_session->responseContains('remaining:user:source_entity: 1');

    // Create 2 submissions as root user, who can ignore submission limits.
    $this->postNodeSubmission($node);
    $this->postNodeSubmission($node);

    // Check submission limit tokens.
    $this->drupalGet('/node/' . $node->id());
    $assert_session->responseContains('remaining:webform:source_entity: 0');
    $assert_session->responseContains('remaining:user:source_entity: 0');

    // Login as normal user.
    $this->drupalLogin($normal_user);

    $this->drupalGet('/node/' . $node->id());

    // Check per source entity total limit.
    $assert_session->buttonNotExists('Submit');
    $assert_session->responseContains('Only 3 submissions are allowed.');
    $assert_session->responseNotContains('You are only allowed to have 1 submission for this webform.');

    // Check submission limit blocks.
    $assert_session->responseContains('1 user + source entity submission(s)');
    $assert_session->responseContains('1 user + source entity limit');
    $assert_session->responseContains('3 webform + source entity submission(s)');
    $assert_session->responseContains('3 webform + source entity limit');

    /* ********************************************************************** */
    // Prepopulate source entity.
    /* ********************************************************************** */

    $webform_contact = Webform::load('contact');

    $node->webform->target_id = 'contact';
    $node->webform->status = WebformInterface::STATUS_OPEN;
    $node->webform->default_data = "name: '{name}'";
    $node->webform->open = '';
    $node->webform->close = '';
    $node->save();

    $source_entity_options = ['query' => ['source_entity_type' => 'node', 'source_entity_id' => $node->id()]];

    // Check default data from source entity using query string.
    $this->drupalGet('/webform/contact', $source_entity_options);
    $assert_session->fieldValueEquals('name', '{name}');

    // Check prepopulating source entity using query string.
    $this->drupalGet('/webform/contact', $source_entity_options);
    $edit = [
      'name' => 'name',
      'email' => 'example@example.com',
      'subject' => 'subject',
      'message' => 'message',
    ];
    $this->submitForm($edit, 'Send message');
    $sid = $this->getLastSubmissionId($webform_contact);
    $submission = WebformSubmission::load($sid);
    $this->assertNotNull($submission->getSourceEntity());
    if ($submission->getSourceEntity()) {
      $this->assertEquals($submission->getSourceEntity()
        ->getEntityTypeId(), 'node');
      $this->assertEquals($submission->getSourceEntity()->id(), $node->id());
    }

    /* ********************************************************************** */
    // Check displaying link to webform.
    /* ********************************************************************** */

    // Set webform reference to be displayed as a link.
    $display_options = [
      'type' => 'webform_entity_reference_link',
      'settings' => [
        'label' => 'Register',
      ],
    ];
    $view_display = EntityViewDisplay::load('node.webform.default');
    $view_display->setComponent('webform', $display_options)->save();

    // Set default data.
    $node->webform->target_id = 'contact';
    $node->webform->status = WebformInterface::STATUS_OPEN;
    $node->webform->default_data = "name: '{name}'";
    $node->webform->open = '';
    $node->webform->close = '';
    $node->save();

    // Check 'Register' link.
    $this->drupalGet('/node/' . $node->id());
    $assert_session->linkExists('Register');

    // Check that link include source_entity_type and source_entity_id.
    $assert_session->linkByHrefExists($webform_contact->toUrl('canonical', $source_entity_options)->toString());
  }

}
