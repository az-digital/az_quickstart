<?php

namespace Drupal\Tests\extlink\FunctionalJavascript;

/**
 * Testing the basic functionality of External Links.
 *
 * @group Extlink
 */
class ExtlinkTestTarget extends ExtlinkTestBase {

  /**
   * Checks to see if extlink adds target and rel attributes.
   */
  public function testExtlinkTarget(): void {
    // Target Enabled.
    $this->config('extlink.settings')->set('extlink_target', TRUE)->save();

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

    // Test that the page has the external link svg.
    $externalLink = $page->find('xpath', self::EXTLINK_EXT_XPATH);
    $this->assertTrue(!is_null($externalLink) && $externalLink->isVisible(), 'External Link does not exist.');
    $link = $page->findLink('Google!');

    // Link should have target attribute.
    $this->assertTrue($link->getAttribute('target') === '_blank', 'ExtLink target attribute is not "_blank".');

    // Link should have rel attribute 'noopener noreferrer'.
    $this->assertTrue($link->getAttribute('rel') === 'noopener' || $link->getAttribute('rel') === 'noopener noreferrer' || $link->getAttribute('rel') === 'noreferrer noopener', 'ExtLink rel attribute is not "noopener".');
  }

  /**
   * Checks to see if extlink changes the target attribute.
   */
  public function testExtlinkTargetNoOverride(): void {
    // Target Enabled.
    $this->config('extlink.settings')->set('extlink_target', TRUE)->save();
    $this->config('extlink.settings')->set('extlink_target_no_override', TRUE)->save();

    // Login.
    $this->drupalLogin($this->adminUser);

    // Create a node with an external link.
    $settings = [
      'type' => 'page',
      'title' => 'test page',
      'body' => [
        [
          'value' => '<p><a href="http://google.com" target="_self">Google!</a></p>',
          'format' => $this->emptyFormat->id(),
        ],
      ],
    ];
    $node = $this->drupalCreateNode($settings);

    // Get the page.
    $this->drupalGet($node->toUrl());
    $page = $this->getSession()->getPage();
    $this->assertTrue($page->hasLink('Google!'));

    // Test that the page has the external link svg.
    $externalLink = $page->find('xpath', self::EXTLINK_EXT_XPATH);
    $this->assertTrue(!is_null($externalLink) && $externalLink->isVisible(), 'External Link does not exist.');
    $link = $page->findLink('Google!');

    // Link should have target attribute.
    $this->assertTrue($link->getAttribute('target') === '_self', 'ExtLink target attribute is not "_self".');

    // Link should have rel attribute 'noopener noreferrer'.
    $this->assertTrue($link->getAttribute('rel') === 'noopener' || $link->getAttribute('rel') === 'noopener noreferrer' || $link->getAttribute('rel') === 'noreferrer noopener', 'ExtLink rel attribute is not "noopener".');
  }

  /**
   * Checks to see if extlink adds (New Window) in the title.
   */
  public function testExtlinkTargetNewWindow(): void {
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

    // Test that the page has the external link svg.
    $externalLink = $page->find('xpath', self::EXTLINK_EXT_XPATH);
    $this->assertTrue(!is_null($externalLink) && $externalLink->isVisible(), 'External Link does not exist.');
    $link = $page->findLink('Google!');

    // Link should have target attribute.
    $this->assertTrue($link->getAttribute('target') === '_blank', 'Extlink target attribute is not "_blank".');

    // Link should have rel attribute 'noopener noreferrer'.
    $this->assertTrue($link->getAttribute('rel') === 'noopener' || $link->getAttribute('rel') === 'noopener noreferrer' || $link->getAttribute('rel') === 'noreferrer noopener', 'ExtLink rel attribute is not "noopener noreferrer".');

    // Link should have a title '(New window)'.
    $this->assertTrue($link->getAttribute('title') === '(opens in a new window)', 'ExtLink title attribute is not "(opens in a new window)".');

    $link = $page->findLink('Google with title!');
    // Link should have target attribute.
    $this->assertTrue($link->getAttribute('target') === '_blank', 'ExtLink target attribute is not "_blank".');

    // Link should have rel attribute 'noopener noreferrer'.
    $this->assertTrue($link->getAttribute('rel') === 'noopener' || $link->getAttribute('rel') === 'noopener noreferrer' || $link->getAttribute('rel') === 'noreferrer noopener', 'ExtLink rel attribute is not "noopener noreferrer".');

    // Link should have a title '(New window)'.
    $this->assertStringContainsString('(opens in a new window)', $link->getAttribute('title'), 'ExtLink title attribute is not "(opens in a new window)".');
  }

}
