<?php

namespace Drupal\Tests\extlink\FunctionalJavascript;

/**
 * Testing the basic functionality of External Links.
 *
 * @group Extlink
 */
class ExtlinkTest extends ExtlinkTestBase {

  /**
   * Checks to see if external link gets extlink svg.
   */
  public function testExtlink(): void {
    // Login.
    $this->drupalLogin($this->adminUser);

    // Create a node with an external link.
    $settings = [
      'type' => 'page',
      'title' => 'test page',
      'body' => [
        [
          'value' => '<p><a href="http://google.com">Google!</a></p><p><a href="mailto:someone@example.com">Send Mail</a></p>',
          'format' => $this->emptyFormat->id(),
        ],
      ],
    ];
    $node = $this->drupalCreateNode($settings);

    // Get the page.
    $this->drupalGet($node->toUrl());
    $page = $this->getSession()->getPage();
    $this->assertTrue($page->hasLink('Google!'));
    $this->assertTrue($page->hasLink('Send Mail'));

    // Test that the page has the external link svg.
    $externalLink = $page->find('xpath', self::EXTLINK_EXT_XPATH);
    $this->assertTrue(!is_null($externalLink) && $externalLink->isVisible(), 'External Link Exists.');

    // Test that the page has the Mailto external link svg.
    $mailToLink = $page->find('xpath', self::EXTLINK_MAILTO_XPATH);
    $this->assertTrue(!is_null($mailToLink) && $mailToLink->isVisible(), 'External Link MailTo Exists.');
  }

  /**
   * Checks to see if telephone link gets extlink svg.
   */
  public function testExtlinkTel(): void {
    // Login.
    $this->drupalLogin($this->adminUser);

    // Create a node with an external link on a telephone.
    $settings = [
      'type' => 'page',
      'title' => 'test page',
      'body' => [
        [
          'value' => '<p><a href="tel:+4733378901">Google number</a></p>',
          'format' => $this->emptyFormat->id(),
        ],
      ],
    ];
    $node = $this->drupalCreateNode($settings);

    // Get page.
    $this->drupalGet($node->toUrl());
    $page = $this->getSession()->getPage();

    $this->assertTrue($page->hasLink('Google number'));

    $link = $page->findLink('Google number');
    // Link should have tel attribute.
    $this->assertTrue($link->hasClass('tel'));

    // Test that the page has the external link svg.
    $externalLink = $page->find('xpath', self::EXTLINK_TEL_XPATH);
    $this->assertTrue(!is_null($externalLink) && $externalLink->isVisible(), 'External Link Exists.');
  }

  /**
   * Checks to see if an image link gets extlink svg.
   */
  public function testExtlinkImg(): void {
    // Login.
    $this->drupalLogin($this->adminUser);

    $this->config('extlink.settings')->set('extlink_img_class', TRUE)->save();
    $test_image = current($this->drupalGetTestFiles('image'));
    $image_file_path = \Drupal::service('file_system')->realpath($test_image->uri);

    // Create a node with an external link on an image.
    $settings = [
      'type' => 'page',
      'title' => 'test page',
      'body' => [
        [
          'value' => '<p><a href="http://google.com"><img src="' . $image_file_path . '" alt="Google!" /></a></p>',
          'format' => $this->emptyFormat->id(),
        ],
      ],
    ];
    $node = $this->drupalCreateNode($settings);

    // Get page.
    $this->drupalGet($node->toUrl());
    $page = $this->getSession()->getPage();

    $this->assertTrue($page->hasLink('Google!'));

    // Test that the page has the external link svg.
    $externalLink = $page->find('xpath', self::EXTLINK_EXT_XPATH);
    $this->assertTrue(!is_null($externalLink) && $externalLink->isVisible(), 'External Link Exists.');
  }

  /**
   * Checks to see if external link works correctly when disabled.
   */
  public function testExtlinkDisabled(): void {
    // Disable Extlink.
    $this->config('extlink.settings')->set('extlink_class', '0')->save();
    $this->config('extlink.settings')->set('extlink_mailto_class', '0')->save();

    // Login.
    $this->drupalLogin($this->adminUser);

    // Create a node with an external link.
    $settings = [
      'type' => 'page',
      'title' => 'test page',
      'body' => [
        [
          'value' => '<p><a href="http://google.com">Google!</a></p><p><a href="mailto:someone@example.com">Send Mail</a></p>',
          'format' => $this->emptyFormat->id(),
        ],
      ],
    ];
    $node = $this->drupalCreateNode($settings);

    // Get the page.
    $this->drupalGet($node->toUrl());
    $page = $this->getSession()->getPage();
    $this->assertTrue($page->hasLink('Google!'));
    $this->assertTrue($page->hasLink('Send Mail'));

    // Test that the page has the external link svg.
    $externalLink = $page->find('xpath', self::EXTLINK_EXT_XPATH);
    $this->assertTrue(is_null($externalLink), 'External Link does not exist.');

    // Test that the page has the Mailto external link svg.
    $mailToLink = $page->find('xpath', self::EXTLINK_MAILTO_XPATH);
    $this->assertTrue(is_null($mailToLink), 'External Link MailTo does not exist.');
  }

  /**
   * Checks to see if external link works with an extended set of links.
   */
  public function testExtlinkDomainMatching(): void {
    // Login.
    $this->drupalLogin($this->adminUser);

    $domains = [
      'http://www.example.com',
      'http://www.example.com:8080',
      'http://www.example.co.uk',
      'http://test.example.com',
      'http://example.com',
      'http://www.whatever.com',
      'http://www.domain.org',
      'http://www.domain.nl',
      'http://www.domain.de',
      'http://www.auspigs.com',
      'http://www.usapigs.com',
      'http://user:password@example.com',
    ];

    // Build the html for the page.
    $node_html = '';
    foreach ($domains as $item) {
      $node_html .= '<p><a href="' . $item . '">' . $item . '</a></p><p>';
    }

    // Create the node.
    $settings = [
      'type' => 'page',
      'title' => 'test page',
      'body' => [
        [
          'value' => $node_html,
          'format' => $this->emptyFormat->id(),
        ],
      ],
    ];
    $node = $this->drupalCreateNode($settings);

    // Get the page.
    $this->drupalGet($node->toUrl());
    $page = $this->getSession()->getPage();

    // Test that the page has an external link on each link.
    foreach ($domains as $item) {
      $externalLink = $page->findLink($item);
      $this->assertTrue($externalLink->hasAttribute('data-extlink'), 'External Link failed for "' . $item . '"');
    }

  }

  /**
   * Checks to see if external link works with an extended set of links.
   */
  public function testExtlinkDomainMatchingExcludeSubDomainsEnabled(): void {
    $this->config('extlink.settings')->set('extlink_subdomains', TRUE)->save();
    $this->testExtlinkDomainMatching();
  }

  /**
   * Checks to see if external link font awesome works.
   */
  public function testExtlinkUseFontAwesome(): void {
    // Enable Use Font Awesome.
    $this->config('extlink.settings')->set('extlink_use_font_awesome', TRUE)->save();

    // Login.
    $this->drupalLogin($this->adminUser);

    // Create a node with an external link.
    $settings = [
      'type' => 'page',
      'title' => 'test page',
      'body' => [
        [
          'value' => '<p><a href="http://google.com">Google!</a></p><p><a href="mailto:someone@example.com">Send Mail</a></p>',
          'format' => $this->emptyFormat->id(),
        ],
      ],
    ];
    $node = $this->drupalCreateNode($settings);

    // Get the page.
    $this->drupalGet($node->toUrl());
    $page = $this->getSession()->getPage();
    $this->assertTrue($page->hasLink('Google!'));
    $this->assertTrue($page->hasLink('Send Mail'));

    // Test that the page has the external link span.
    $this->assertSession()->elementExists('css', 'span.fa-external-link');

    // Test that the page has the Mailto external link span.
    $this->assertSession()->elementExists('css', 'span.fa-envelope-o');
  }

  /**
   * Checks to see if external additional custom css classes work.
   */
  public function testExtlinkAdditionalCssClasses(): void {
    // Set custom css classes for external and mailto links.
    $this->config('extlink.settings')
      ->set('extlink_additional_link_classes', 'ext-link-css')
      ->set('extlink_additional_mailto_classes', 'ext-mailto-css')
      ->set('extlink_additional_tel_classes', 'ext-tel-css')
      ->save();

    // Login.
    $this->drupalLogin($this->adminUser);

    // Create a node with an external link.
    $settings = [
      'type' => 'page',
      'title' => 'test page',
      'body' => [
        [
          'value' => '<p><a href="http://google.com">Google!</a></p><p><a href="mailto:someone@example.com">Send Mail</a></p><p><a href="tel:+4733378901">Google number</a></p>',
          'format' => $this->emptyFormat->id(),
        ],
      ],
    ];
    $node = $this->drupalCreateNode($settings);

    // Get the page.
    $this->drupalGet($node->toUrl());
    $page = $this->getSession()->getPage();
    $this->assertTrue($page->hasLink('Google!'));
    $this->assertTrue($page->hasLink('Send Mail'));
    $this->assertTrue($page->hasLink('Google number'));

    // Test that the external link element has the css class applied.
    $this->assertSession()->elementExists('css', 'a.ext-link-css');

    // Test that the external mailto link element has the css class applied.
    $this->assertSession()->elementExists('css', 'a.ext-mailto-css');

    // Test that the external tel link element has the css class applied.
    $this->assertSession()->elementExists('css', 'a.ext-tel-css');
  }

  /**
   * Checks to see if external additional custom css classes work.
   */
  public function testExtlinkAdditionalCssClassesWithExistingClasses(): void {
    // Set custom css classes for external and mailto links.
    $this->config('extlink.settings')
      ->set('extlink_additional_link_classes', 'ext-link-css')
      ->set('extlink_additional_mailto_classes', 'ext-mailto-css')
      ->set('extlink_additional_tel_classes', 'ext-tel-css')
      ->save();

    // Login.
    $this->drupalLogin($this->adminUser);

    // Create a node with an external link.
    $settings = [
      'type' => 'page',
      'title' => 'test page',
      'body' => [
        [
          'value' => '<p><a href="http://google.com" class="existing-class-ext-link">Google!</a></p><p><a href="mailto:someone@example.com" class="existing-class-ext-mailto">Send Mail</a></p><p><a href="tel:+4733378901" class="existing-class-ext-tel">Google number</a></p>',
          'format' => $this->emptyFormat->id(),
        ],
      ],
    ];
    $node = $this->drupalCreateNode($settings);

    // Get the page.
    $this->drupalGet($node->toUrl());
    $page = $this->getSession()->getPage();
    $this->assertTrue($page->hasLink('Google!'));
    $this->assertTrue($page->hasLink('Send Mail'));
    $this->assertTrue($page->hasLink('Google number'));

    // Test that the external link element has the css class applied.
    $this->assertSession()->elementExists('css', 'a.existing-class-ext-link.ext-link-css');

    // Test that the external mailto link element has the css class applied.
    $this->assertSession()->elementExists('css', 'a.existing-class-ext-mailto.ext-mailto-css');

    // Test that the external tel link element has the css class applied.
    $this->assertSession()->elementExists('css', 'a.existing-class-ext-tel.ext-tel-css');
  }

  /**
   * Checks the ability to update the labels.
   */
  public function testExtlinkLabels(): void {
    // Login.
    $this->drupalLogin($this->adminUser);

    // Create a node with an external link, telephone link, and mailto link.
    $settings = [
      'type' => 'page',
      'title' => 'test page',
      'body' => [
        [
          'value' => '<p><a href="http://google.com">Google!</a></p><p><a href="mailto:someone@example.com">Send Mail</a></p><p><a href="tel:+4733378901">Google number</a></p>',
          'format' => $this->emptyFormat->id(),
        ],
      ],
    ];
    $node = $this->drupalCreateNode($settings);

    // Get the page.
    $this->drupalGet($node->toUrl());
    $page = $this->getSession()->getPage();
    $this->assertTrue($page->hasLink('Google!'));
    $this->assertTrue($page->hasLink('Send Mail'));
    $this->assertTrue($page->hasLink('Google number'));

    // Test the default labels first.
    $externalLink = $page->find('xpath', self::EXTLINK_EXT_XPATH);
    $this->assertSame($externalLink->getAttribute('aria-label'), '(link is external)');

    $mailToLink = $page->find('xpath', self::EXTLINK_MAILTO_XPATH);
    $this->assertSame($mailToLink->getAttribute('aria-label'), '(link sends email)');

    $telLink = $page->find('xpath', self::EXTLINK_TEL_XPATH);
    $this->assertSame($telLink->getAttribute('aria-label'), '(link is a phone number)');

    // Now update the labels.
    $this->config('extlink.settings')
      ->set('extlink_label', 'New ext link')
      ->set('extlink_mailto_label', 'New mail link')
      ->set('extlink_tel_label', 'Hello world')
      ->save();

    // Get the page again.
    $this->drupalGet($node->toUrl());
    $page = $this->getSession()->getPage();

    // Test the new labels.
    $externalLink = $page->find('xpath', self::EXTLINK_EXT_XPATH);
    $this->assertSame($externalLink->getAttribute('aria-label'), 'New ext link');

    $mailToLink = $page->find('xpath', self::EXTLINK_MAILTO_XPATH);
    $this->assertSame($mailToLink->getAttribute('aria-label'), 'New mail link');

    $telLink = $page->find('xpath', self::EXTLINK_TEL_XPATH);
    $this->assertSame($telLink->getAttribute('aria-label'), 'Hello world');
  }

  /**
   * Checks to see if noreferrer exclusion for external links work.
   */
  public function testExtlinkNoreferrerExclusion(): void {
    // Enable target for external links.
    $this->config('extlink.settings')->set('extlink_target', TRUE)->save();
    // Add pattern to exclude 'noreferrer' tag from external links.
    $this->config('extlink.settings')->set('extlink_exclude_noreferrer', '(example\.com)')->save();

    // Admin login.
    $this->drupalLogin($this->adminUser);

    // Create a node with two external links.
    $settings = [
      'type' => 'page',
      'title' => 'test page',
      'body' => [
        [
          'value' => '<p><a href="http://google.com">Google!</a><a href="http://example.com">Example link!</a></p>',
          'format' => $this->emptyFormat->id(),
        ],
      ],
    ];
    $node = $this->drupalCreateNode($settings);

    // Get the test page.
    $this->drupalGet($node->toUrl());
    $page = $this->getSession()->getPage();

    $this->assertTrue($page->hasLink('Google!'));
    $this->assertTrue($page->hasLink('Example link!'));

    $link = $page->findLink('Google!');
    // Link should have rel attribute 'noopener noreferrer'.
    $this->assertTrue($link->getAttribute('rel') === 'noopener noreferrer' || $link->getAttribute('rel') === 'noreferrer noopener', 'ExtLink rel attribute is not "noopener noreferrer".');

    $link = $page->findLink('Example link!');
    // Link should have rel attribute as 'noopener' only.
    $this->assertTrue($link->getAttribute('rel') === 'noopener', 'ExtLink rel attribute is not "noopener".');
  }

  /**
   * Checks that extlink have the "ext" class when an ext icon is not placed.
   */
  public function testExtlinkHasExtClassWhenNoIconIsPlaced(): void {
    // Icon disabled.
    $this->config('extlink.settings')->set('extlink_class', FALSE)->save();

    // Login.
    $this->drupalLogin($this->adminUser);

    // Create a node with an external link.
    $settings = [
      'type' => 'page',
      'title' => 'test page',
      'body' => [
        [
          'value' => '<p><a id="the-link" href="http://google.com">Google!</a></p>',
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
    $externalLink = $page->find('css', '#the-link');
    $this->assertTrue(!is_null($externalLink) && $externalLink->isVisible(), 'External Link does not exist.');

    // Does the anchor tag have the "ext" class?
    $this->assertTrue($externalLink->hasClass('ext'), 'External link does not have the ext class.');

  }

  /**
   * Checks that extlink have the "ext" class even when an ext icon is placed.
   */
  public function testExtlinkHasExtClassWhenIconIsPlaced(): void {
    // Icon disabled.
    $this->config('extlink.settings')->set('extlink_class', TRUE)->save();

    // Login.
    $this->drupalLogin($this->adminUser);

    // Create a node with an external link.
    $settings = [
      'type' => 'page',
      'title' => 'test page',
      'body' => [
        [
          'value' => '<p><a id="the-link" href="http://google.com">Google!</a></p>',
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
    $externalLink = $page->find('css', '#the-link');
    $this->assertTrue(!is_null($externalLink) && $externalLink->isVisible(), 'External Link does not exist.');

    // Does the anchor tag have the "ext" class?
    $this->assertTrue($externalLink->hasClass('ext'), 'External link does not have the ext class.');

  }

}
