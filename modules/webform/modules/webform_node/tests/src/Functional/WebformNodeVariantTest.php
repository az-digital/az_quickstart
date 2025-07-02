<?php

namespace Drupal\Tests\webform_node\Functional;

use Drupal\webform\Entity\WebformSubmission;

/**
 * Tests for webform node variants.
 *
 * @group webform_node
 */
class WebformNodeVariantTest extends WebformNodeBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['webform', 'webform_node'];

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_variant_multiple'];

  /**
   * Tests webform node variant.
   */
  public function testNodeVariant() {
    $assert_session = $this->assertSession();

    // Create a webform node with a variant.
    $node = $this->createWebformNode('test_variant_multiple');
    $node->webform->default_data = "letter: a
number: '1'";
    $node->save();

    /* ********************************************************************** */

    $this->drupalLogin($this->rootUser);

    // Check node variants render.
    $this->drupalGet('/node/' . $node->id());
    $assert_session->responseContains('[A]');
    $assert_session->responseContains('[1]');

    // Check node variants processed.
    $sid = $this->postNodeSubmission($node);
    $webform_submission = WebformSubmission::load($sid);
    $this->assertEquals(['letter' => 'a', 'number' => '1'], $webform_submission->getData());

    // Check node variants test processed.
    $sid = $this->postNodeSubmissionTest($node);
    $webform_submission = WebformSubmission::load($sid);
    $this->assertEquals(['letter' => 'a', 'number' => '1'], $webform_submission->getData());
  }

}
