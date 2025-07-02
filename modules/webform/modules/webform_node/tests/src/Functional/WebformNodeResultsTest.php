<?php

namespace Drupal\Tests\webform_node\Functional;

use Drupal\Component\Utility\Html;
use Drupal\Core\Url;
use Drupal\webform\Entity\Webform;
use Drupal\webform\WebformInterface;

/**
 * Tests for webform node results.
 *
 * @group webform_node
 */
class WebformNodeResultsTest extends WebformNodeBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['block', 'webform', 'webform_node'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Place blocks.
    $this->placeBlocks();
  }

  /**
   * Tests webform node results.
   */
  public function testResults() {
    global $base_path;

    $assert_session = $this->assertSession();

    $normal_user = $this->drupalCreateUser();

    $admin_user = $this->drupalCreateUser([
      'administer webform',
    ]);

    $admin_submission_user = $this->drupalCreateUser([
      'administer webform submission',
    ]);

    /** @var \Drupal\webform\WebformSubmissionStorageInterface $submission_storage */
    $submission_storage = \Drupal::entityTypeManager()->getStorage('webform_submission');

    /* ********************************************************************** */

    $webform = Webform::load('contact');

    // Create node.
    $node = $this->drupalCreateNode(['type' => 'webform']);
    $nid = $node->id();

    /* Webform entity reference */

    // Check access denied to webform results.
    $this->drupalLogin($admin_submission_user);
    $this->drupalGet('/node/' . $node->id() . '/webform/results/submissions');
    $assert_session->statusCodeEquals(403);

    // Set Node webform to the contact webform.
    $node->webform->target_id = 'contact';
    $node->webform->status = WebformInterface::STATUS_OPEN;
    $node->save();

    /* Submission management */

    // Generate 3 node submissions and 3 webform submissions.
    $this->drupalLogin($normal_user);
    $node_sids = [];
    $webform_sids = [];
    for ($i = 1; $i <= 3; $i++) {
      $edit = [
        'name' => "node$i",
        'email' => "node$i@example.com",
        'subject' => "Node $i subject",
        'message' => "Node $i message",
      ];
      $node_sids[$i] = $this->postNodeSubmission($node, $edit);
      $edit = [
        'name' => "webform$i",
        'email' => "webform$i@example.com",
        'subject' => "Webform $i subject",
        'message' => "Webform $i message",
      ];
      $webform_sids[$i] = $this->postSubmission($webform, $edit);
    }

    // Check that 6 submission were created.
    $this->assertEquals($submission_storage->getTotal($webform, $node), 3);
    $this->assertEquals($submission_storage->getTotal($webform), 6);

    // Check webform node results.
    $this->drupalLogin($admin_submission_user);
    $node_route_parameters = ['node' => $node->id(), 'webform_submission' => $node_sids[1]];
    $node_submission_url = Url::fromRoute('entity.node.webform_submission.canonical', $node_route_parameters);
    $node_submission_title = $node->label() . ': Submission #' . $node_sids[1];
    $webform_submission_route_parameters = ['webform' => 'contact', 'webform_submission' => $node_sids[1]];
    $webform_submission_url = Url::fromRoute('entity.webform_submission.canonical', $webform_submission_route_parameters);

    $this->drupalGet('/node/' . $node->id() . '/webform/results/submissions');
    $assert_session->statusCodeEquals(200);
    $assert_session->responseContains('<h1>' . $node->label() . '</h1>');
    $assert_session->responseNotContains('<h1>' . $webform->label() . '</h1>');
    $assert_session->responseContains(('<a href="' . $node_submission_url->toString() . '" title="' . Html::escape($node_submission_title) . '" aria-label="' . Html::escape($node_submission_title) . '">' . $node_sids[1] . '</a>'));
    $assert_session->responseNotContains(('<a href="' . $webform_submission_url->toString() . '">' . $webform_sids[1] . '</a>'));

    // Check webform node title.
    $this->drupalGet('/node/' . $node->id() . '/webform/submission/' . $node_sids[1]);
    $assert_session->responseContains($node->label() . ': Submission #' . $node_sids[1]);
    $this->drupalGet('/node/' . $node->id() . '/webform/submission/' . $node_sids[2]);
    $assert_session->responseContains($node->label() . ': Submission #' . $node_sids[2]);

    // Check webform node navigation.
    $this->drupalGet('/node/' . $node->id() . '/webform/submission/' . $node_sids[1]);
    $node_route_parameters = ['node' => $node->id(), 'webform_submission' => $node_sids[2]];
    $node_submission_url = Url::fromRoute('entity.node.webform_submission.canonical', $node_route_parameters);
    $assert_session->responseContains('<a href="' . $node_submission_url->toString() . '" rel="next" title="Go to next page">Next submission <b>›</b></a>');

    // Check webform node saved draft.
    $webform->setSetting('draft', WebformInterface::DRAFT_AUTHENTICATED);
    $webform->save();

    // Check webform saved draft.
    $this->drupalLogin($normal_user);
    $this->drupalGet('/node/' . $node->id());
    $edit = [
      'name' => "nodeDraft",
      'email' => "nodeDraft@example.com",
      'subject' => "Node draft subject",
      'message' => "Node draft message",
    ];
    $this->submitForm($edit, 'Save Draft');
    $this->drupalGet('/node/' . $node->id());
    $assert_session->responseContains('A partially-completed form was found. Please complete the remaining portions.');
    $this->drupalGet('/webform/contact');
    $assert_session->responseNotContains('A partially-completed form was found. Please complete the remaining portions.');

    /* Table customization */

    // Check that access is denied to custom results table.
    $this->drupalLogin($admin_submission_user);
    $this->drupalGet('/admin/structure/webform/manage/' . $webform->id() . '/results/submissions/custom');
    $assert_session->statusCodeEquals(403);

    // Check that access is allowed to custom results table.
    $this->drupalLogin($admin_user);
    $this->drupalGet('/admin/structure/webform/manage/' . $webform->id() . '/results/submissions/custom');
    $assert_session->statusCodeEquals(200);

    // Check default node results table.
    $this->drupalGet('/node/' . $node->id() . '/webform/results/submissions');
    $assert_session->responseContains('<th specifier="created" class="priority-medium is-active" aria-sort="descending">');
    $assert_session->responseContains('sort by Created');
    $assert_session->responseNotContains('sort by Changed');

    // Customize to main webform's results table.
    $this->drupalGet('/admin/structure/webform/manage/' . $webform->id() . '/results/submissions/custom');
    $edit = [
      'columns[created][checkbox]' => FALSE,
      'columns[changed][checkbox]' => TRUE,
      'sort' => 'serial',
      'direction' => 'asc',
      'limit' => 20,
      'default' => TRUE,
    ];
    $this->submitForm($edit, 'Save');
    $assert_session->responseContains('The customized table has been saved.');

    // Check that the webform node's results table is now customized.
    $this->drupalGet('/node/' . $node->id() . '/webform/results/submissions');
    $assert_session->responseContains('<th specifier="serial" aria-sort="ascending" class="is-active">');
    $assert_session->responseNotContains('sort by Created');
    $assert_session->responseContains('sort by Changed');

    $this->drupalLogout();

    /* Access control */

    // Create any and own user accounts.
    $any_user = $this->drupalCreateUser([
      'access content',
      'view webform submissions any node',
      'edit webform submissions any node',
      'delete webform submissions any node',
    ]);
    $own_user = $this->drupalCreateUser([
      'access content',
      'view webform submissions own node',
      'edit webform submissions own node',
      'delete webform submissions own node',
    ]);

    // Check accessing results posted to any webform node.
    $this->drupalLogin($any_user);
    $this->drupalGet('/node/' . $node->id() . '/webform/results/submissions');
    $assert_session->statusCodeEquals(200);
    foreach ($node_sids as $node_sid) {
      $assert_session->linkByHrefExists("{$base_path}node/{$nid}/webform/submission/{$node_sid}");
    }

    // Check accessing results posted to own webform node.
    $this->drupalLogin($own_user);
    $this->drupalGet('/node/' . $node->id() . '/webform/results/submissions');
    $assert_session->statusCodeEquals(403);

    $node->setOwnerId($own_user->id())->save();
    $this->drupalGet('/node/' . $node->id() . '/webform/results/submissions');
    $assert_session->statusCodeEquals(200);
    foreach ($node_sids as $node_sid) {
      $assert_session->linkByHrefExists("{$base_path}node/{$nid}/webform/submission/{$node_sid}");
    }

    // Check deleting webform node results.
    $edit = ['confirm' => TRUE];
    $this->drupalGet('/node/' . $node->id() . '/webform/results/clear');
    $this->submitForm($edit, 'Clear');
    $this->assertEquals($submission_storage->getTotal($webform, $node), 0);
    $this->assertEquals($submission_storage->getTotal($webform), 3);
  }

}
