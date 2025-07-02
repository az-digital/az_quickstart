<?php

namespace Drupal\Tests\webform_share\Functional;

use Drupal\Tests\webform_node\Functional\WebformNodeBrowserTestBase;
use Drupal\webform\Entity\Webform;

/**
 * Webform share node test.
 *
 * @group webform_share
 */
class WebformShareNodeTest extends WebformNodeBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'webform',
    'webform_node',
    'webform_share',
  ];

  /**
   * Test share.
   */
  public function testShare() {
    global $base_url;

    $assert_session = $this->assertSession();

    $config = \Drupal::configFactory()->getEditable('webform.settings');

    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = Webform::load('contact');
    $node = $this->createWebformNode('contact');
    $nid = $node->id();

    /** @var \Drupal\Core\Render\RendererInterface $render */
    $renderer = \Drupal::service('renderer');

    $this->drupalLogin($this->rootUser);

    /* ********************************************************************** */

    // Check share page access denied.
    $this->drupalGet('/webform/contact/share');
    $assert_session->statusCodeEquals(403);

    // Check webform node share page access denied.
    $this->drupalGet("/node/$nid/webform/share");
    $assert_session->statusCodeEquals(403);

    // Check webform node preview access denied.
    $this->drupalGet("/node/$nid/webform/share/preview");
    $assert_session->statusCodeEquals(403);

    // Enable enable share for all webform node.
    $config->set('settings.default_share_node', TRUE)->save();

    // Check share enabled for all webform nodes.
    $this->drupalGet('/webform/contact/share');
    $assert_session->statusCodeEquals(200);
    $this->drupalGet("/node/$nid/webform/share");
    $assert_session->statusCodeEquals(200);
    $this->drupalGet("/node/$nid/webform/share/preview");
    $assert_session->statusCodeEquals(200);

    // Enable disable share for all webform nodes.
    $config->set('settings.default_share_node', FALSE)->save();

    // Enable share for contact webform node.
    $webform->setSetting('share_node', TRUE)->save();

    // Check share enabled for a single webform.
    $this->drupalGet('/webform/contact/share');
    $assert_session->statusCodeEquals(200);
    $this->drupalGet("/node/$nid/webform/share");
    $assert_session->statusCodeEquals(200);
    $this->drupalGet("/node/$nid/webform/share/preview");
    $assert_session->statusCodeEquals(200);

    // Check webform node script tag.
    $build = [
      '#type' => 'webform_share_script',
      '#webform' => $webform,
      '#source_entity' => $node,
    ];
    $actual_script_tag = $renderer->renderPlain($build);

    $src = $base_url . "/webform/contact/share.js?source_entity_type=node&amp;source_entity_id=$nid";
    $src = preg_replace('#^https?:#', '', $src);
    $expected_script_tag = '<script src="' . $src . '"></script>' . PHP_EOL;

    $this->assertEquals($expected_script_tag, $actual_script_tag);
  }

}
