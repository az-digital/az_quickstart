<?php

namespace Drupal\Tests\extlink\FunctionalJavascript;

/**
 * Testing the basic functionality of External Links.
 *
 * @group Extlink
 */
class ExtlinkTestNoTitleOverride extends ExtlinkTestBase {

  /**
   * Checks to see if extlink adds title and rel attributes.
   */
  public function testExtlinkTitle(): void {
    // Title override is disabled (so it should be shown).
    $this->config('extlink.settings')->set('extlink_title_no_override', FALSE)->save();

    // Login.
    $this->drupalLogin($this->adminUser);

    // Create a node with an external link.
    $settings = [
      'type' => 'page',
      'title' => 'test page',
      'body' => [
        [
          'value' => '<p><a href="http://google.com">Google!</a></p>',
          'format' => $this->emptyFormat->id(),
        ],
      ],
    ];
    $node = $this->drupalCreateNode($settings);

    // Get the page.
    $this->drupalGet($node->toUrl());
    $page = $this->getSession()->getPage();
    $this->assertTrue($page->hasLink('Google!'));

    // Test that the page has the external link.
    $externalLink = $page->find('xpath', self::EXTLINK_EXT_XPATH);
    $this->assertTrue(!is_null($externalLink) && $externalLink->isVisible(), 'External Link does not exist.');
    $link = $page->findLink('Google!');

    // Link should have a title attribute.
    $this->assertTrue($link->getAttribute('title') === '(opens in a new window)', 'ExtLink title attribute is not empty.');
  }

  /**
   * Checks to see if extlink changes the title attribute.
   */
  public function testExtlinkTitleNoOverride(): void {
    // Target Enabled.
    $this->config('extlink.settings')->set('extlink_title_no_override', TRUE)->save();

    // Login.
    $this->drupalLogin($this->adminUser);

    // Create a node with an external link.
    $settings = [
      'type' => 'page',
      'title' => 'test page',
      'body' => [
        [
          'value' => '<p><a href="http://google.com" title="">Google!</a></p>',
          'format' => $this->emptyFormat->id(),
        ],
      ],
    ];
    $node = $this->drupalCreateNode($settings);

    // Get the page.
    $this->drupalGet($node->toUrl());
    $page = $this->getSession()->getPage();
    $this->assertTrue($page->hasLink('Google!'));

    // Test that the page has the external link.
    $externalLink = $page->find('xpath', self::EXTLINK_EXT_XPATH);
    $this->assertTrue(!is_null($externalLink) && $externalLink->isVisible(), 'External Link does not exist.');
    $link = $page->findLink('Google!');

    // Link should have an empty title attribute.
    $this->assertTrue($link->getAttribute('title') === '', 'ExtLink title attribute is not empty.');
  }

  /**
   * Checks to see if extlink append (New Window) in the title.
   */
  public function testExtlinkTitleAppend(): void {
    // Target enabled.
    $this->config('extlink.settings')->set('extlink_target', TRUE)->save();

    // login.
    $this->drupalLogin($this->adminUser);

    // Create a node with an external link.
    $settings = [
      'type' => 'page',
      'title' => 'test page',
      'body' => [
        [
          'value' => '<p><a href="http://google.com">Google!</a><a href="http://google.com" title="My link title">Google with title!</a></p>',
          'format' => $this->emptyFormat->id(),
        ],
      ],
    ];
    $node = $this->drupalCreateNode($settings);

    // Get the page.
    $this->drupalGet($node->toUrl());
    $page = $this->getSession()->getPage();
    $this->assertTrue($page->hasLink('Google!'));
    $this->assertTrue($page->hasLink('Google with title!'));

    // Test that the page has the external link.
    $externalLink = $page->find('xpath', self::EXTLINK_EXT_XPATH);
    $this->assertTrue(!is_null($externalLink) && $externalLink->isVisible(), 'External Link does not exist.');
    $link = $page->findLink('Google!');

    // Link should have a title '(New window)'.
    $this->assertTrue($link->getAttribute('title') === '(opens in a new window)', 'ExtLink title attribute is not "(opens in a new window)".');

    $link = $page->findLink('Google with title!');
    // Link should have a title '(New window)' appended to its current title.
    $this->assertTrue($link->getAttribute('title') === 'My link title (opens in a new window)', 'ExtLink title attribute is not "(opens in a new window)".');
  }

}
