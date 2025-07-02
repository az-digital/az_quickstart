<?php

namespace Drupal\Tests\extlink\FunctionalJavascript;

/**
 * Testing the CSS class exclusion/inclusion functionality of External Links.
 *
 * @group Extlink
 */
class ExtlinkTestCssExcludeInclude extends ExtlinkTestBase {

  /**
   * Checks to see if an external link behaves as internal when it is forced.
   */
  public function testExtlinkCssExclusion(): void {
    // Add the CSS class to config.
    $this->config('extlink.settings')->set('extlink_css_exclude', '.my-exclusion-class')->save();

    // Login.
    $this->drupalLogin($this->adminUser);

    // Create a node with an excluded external link inside.
    $settings = [
      'type' => 'page',
      'title' => 'test page',
      'body' => [
        [
          'value' => '<p class="my-exclusion-class"><a href="http://google.com">Google!</a></p>',
          'format' => $this->emptyFormat->id(),
        ],
      ],
    ];
    $node = $this->drupalCreateNode($settings);

    // Get the page.
    $this->drupalGet($node->toUrl());
    $page = $this->getSession()->getPage();
    $this->assertTrue($page->hasLink('Google!'));

    // Test that the link is not external.
    $this->assertEmpty($page->find('css', '.my-exclusion-class a[class="ext"]'));

  }

  /**
   * Checks to see if an external link behaves as internal when it is forced.
   */
  public function testExtlinkCssInclusion(): void {
    // Add the CSS class to config.
    $this->config('extlink.settings')->set('extlink_css_include', '.my-inclusion-class')->save();

    // Login.
    $this->drupalLogin($this->adminUser);

    // Create a node with an included internal link inside.
    $settings = [
      'type' => 'page',
      'title' => 'test page',
      'body' => [
        [
          'value' => '<p class="my-inclusion-class"><a href="' . $this->buildUrl('/node/1') . '">Internal!</a></p>',
          'format' => $this->emptyFormat->id(),
        ],
      ],
    ];
    $node = $this->drupalCreateNode($settings);

    // Get the page.
    $this->drupalGet($node->toUrl());
    $page = $this->getSession()->getPage();
    $this->assertTrue($page->hasLink('Internal!'));

    // Test that the link behaves as external.
    $this->assertNotEmpty($page->find('css', '.my-inclusion-class a[class="ext"]'));

  }

}
