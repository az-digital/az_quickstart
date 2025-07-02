<?php

namespace Drupal\Tests\xmlsitemap\Functional;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\Entity\Role;

/**
 * Tests the generation of multilingual nodes.
 *
 * @group xmlsitemap
 */
class XmlSitemapMultilingualNodeTest extends XmlSitemapMultilingualTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['config_translation'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->admin_user = $this->drupalCreateUser([
      'administer nodes',
      'administer languages',
      'administer content types',
      'access administration pages',
      'create page content',
      'edit own page content',
    ]);
    $this->drupalLogin($this->admin_user);

    xmlsitemap_link_bundle_enable('node', 'article');

    xmlsitemap_link_bundle_enable('node', 'page');

    // Allow anonymous user to view user profiles.
    $user_role = Role::load(AccountInterface::ANONYMOUS_ROLE);
    $user_role->grantPermission('access content');
    $user_role->save();

    // Set "Basic page" content type to use multilingual support.
    $edit = [
      'language_configuration[language_alterable]' => TRUE,
    ];
    $this->drupalGet('admin/structure/types/manage/page');
    $this->submitForm($edit, 'Save content type');
    $this->assertSession()->responseContains((string) new FormattableMarkup('The content type %content_type has been updated.', [
      '%content_type' => 'Basic page',
    ]));
  }

  /**
   * Test language for sitemap node links.
   */
  public function testNodeLanguageData() {
    $this->drupalLogin($this->admin_user);
    $node = $this->drupalCreateNode([]);
    $this->drupalGet('node/' . $node->id() . '/edit');

    $this->submitForm([
      'langcode[0][value]' => 'en',
    ], t('Save'));
    $link = $this->assertSitemapLink('node', $node->id(), ['status' => 0, 'access' => 1]);
    $this->assertSame('en', $link['language']);
    $this->drupalGet('node/' . $node->id() . '/edit');

    $this->submitForm(['langcode[0][value]' => 'fr'], t('Save'));
    $link = $this->assertSitemapLink('node', $node->id(), ['status' => 0, 'access' => 1]);
    $this->assertSame('fr', $link['language']);
  }

}
