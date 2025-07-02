<?php

declare(strict_types=1);

namespace Drupal\Tests\flag\Functional;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Xss;

/**
 * Tests use of HTML in the flag and unflag links.
 *
 * @group flag
 */
class LinkHtmlTest extends FlagTestBase {

  /**
   * The flag object.
   *
   * @var \Drupal\flag\FlagInterface
   */
  protected $flag;

  /**
   * Tests use of HTML in the flag and unflag links.
   */
  public function testHtmlLink() {
    // Create and log in our user.
    $this->adminUser = $this->drupalCreateUser([
      'administer flags',
    ]);

    $this->drupalLogin($this->adminUser);

    $this->doCreateHtmlFlag();
    $this->doFlagNode();
  }

  /**
   * Create a node type and a flag.
   */
  public function doCreateHtmlFlag() {
    $this->flag = $this->createFlag('node', [$this->nodeType], 'reload');
    // Generate new flag short text with 'odd' characters included, then wrap
    // the flag and unflag short text in italics to test using HTML in the text.
    // We use a long random string to increase the probability of randomly
    // generating something that looks like an HTML tag.
    $this->flag->setFlagShortText('<i>' . $this->randomString(32) . '</i>');
    $this->flag->setUnflagShortText('<i>' . $this->randomString(32) . '</i>');
    $this->flag->save();
  }

  /**
   * Flag a node and check flag text.
   */
  public function doFlagNode() {
    $node = $this->drupalCreateNode(['type' => $this->nodeType]);
    $node_id = $node->id();

    // Grant the flag permissions to the authenticated role, so that both
    // users have the same roles and share the render cache. ???? TODO.
    $this->grantFlagPermissions($this->flag);

    // Create and login a new user.
    $user_1 = $this->drupalCreateUser();
    $this->drupalLogin($user_1);

    // Click the flag link.
    $this->drupalGet('node/' . $node_id);
    // Find the marked-up flag short text in the raw HTML.
    $this->assertSession()->responseContains(Xss::filterAdmin($this->flag->getShortText('flag')));
    // Xss::filter() is used to strip all HTML tags from the short text
    // because clickLink() looks for text as it appears in the browser, and that
    // does not include the unescaped HTML tags. Note that the stripped tags
    // could either be at the ends (we added the italics tags above) OR they
    // could be in the middle as a result of a randomly-generated valid tag
    // in the flag text.
    $this->clickLink(Html::decodeEntities(Xss::filter($this->flag->getShortText('flag'))));

    // Check that the node is flagged.
    $this->drupalGet('node/' . $node_id);
    $this->assertSession()->responseContains(Xss::filterAdmin($this->flag->getShortText('unflag')));

    // Unflag the node.
    $this->drupalGet('node/' . $node_id);
    $this->clickLink(Html::decodeEntities(Xss::filter($this->flag->getShortText('unflag'))));

    // Check that the node is no longer flagged.
    $this->drupalGet('node/' . $node_id);
    $this->assertSession()->responseContains(Xss::filterAdmin($this->flag->getShortText('flag')));
  }

}
