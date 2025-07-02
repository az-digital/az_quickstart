<?php

namespace Drupal\Tests\metatag\Functional;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\field_ui\Traits\FieldUiTestTrait;
use Drupal\Tests\token\Functional\TokenTestTrait;

/**
 * Verify that metatag token generation is working.
 *
 * @group metatag
 */
class MetatagTokenTest extends BrowserTestBase {

  use TokenTestTrait;
  use FieldUiTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block',
    'field_ui',
    'user',
    'token',
    'token_module_test',
    'metatag',
    'metatag_open_graph',
    'metatag_favicons',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $this->drupalPlaceBlock('system_breadcrumb_block');
    $this->drupalPlaceBlock('local_tasks_block');
    $this->drupalPlaceBlock('page_title_block');

    $this->drupalLogin($this->rootUser);
    $this->fieldUIAddNewField('/admin/config/people/accounts', 'metatags', 'Metatags', 'metatag');

    // This extra step is necessary due to changes in core that removed a cache
    // invalidation step.
    // @see https://www.drupal.org/project/drupal/issues/2189411
    $this->container->get('entity_field.manager')->clearCachedFieldDefinitions();
  }

  /**
   * Test current-page metatag token generation.
   */
  public function testMetatagCurrentPageTokens() {
    $user = $this->createUser([]);
    $this->drupalGet($user->toUrl('edit-form'));
    $this->submitForm([
      'field_metatags[0][basic][abstract]' => 'My abstract',
      'field_metatags[0][open_graph][og_title]' => 'My OG Title',
      'field_metatags[0][open_graph][og_image]' => 'Image 1,Image 2',
    ], 'Save');

    $tokens = [
      // Test globally configured metatags.
      '[current-page:metatag:title]' => sprintf('%s | %s', $user->getAccountName(), $this->config('system.site')
        ->get('name')),
      '[current-page:metatag:description]' => $this->config('system.site')
        ->get('name'),
      '[current-page:metatag:canonical-url]' => $user->toUrl('canonical', ['absolute' => TRUE])
        ->toString(),
      // Test entity overridden metatags.
      '[current-page:metatag:abstract]' => 'My abstract',
      // Test metatags provided by a submodule.
      '[current-page:metatag:og-title]' => 'My OG Title',
      // Test metatags that can contain multiple values.
      '[current-page:metatag:og_image]' => 'Image 1,Image 2',
      '[current-page:metatag:og_image:0]' => 'Image 1',
      '[current-page:metatag:og_image:1]' => 'Image 2',
    ];
    $this->assertPageTokens($user->toUrl(), $tokens);
  }

  /**
   * Test entity token generation.
   */
  public function testMetatagEntityTokens() {
    $user = $this->createUser();
    $this->drupalGet($user->toUrl('edit-form'));
    $this->submitForm([
      'field_metatags[0][basic][abstract]' => 'My abstract',
      'field_metatags[0][open_graph][og_title]' => 'My OG Title',
      'field_metatags[0][open_graph][og_image]' => 'Image 1,Image 2',
      // @todo Update this to use the full URL.
      'field_metatags[0][favicons][mask_icon][href]' => 'metatag-logo.svg',
    ], 'Save');

    $tokens = [
      // Test globally configured metatags.
      '[user:field_metatags:title]' => sprintf('%s | %s', $user->getAccountName(), $this->config('system.site')->get('name')),
      '[user:field_metatags:description]' => $this->config('system.site')->get('name'),
      '[user:field_metatags:canonical-url]' => $user->toUrl('canonical', ['absolute' => TRUE])->toString(),
      // Test entity overridden metatags.
      '[user:field_metatags:abstract]' => 'My abstract',
      // Test metatags provided by a submodule.
      '[user:field_metatags:og-title]' => 'My OG Title',
      // Test metatags that can contain multiple values.
      '[user:field_metatags:og_image]' => 'Image 1,Image 2',
      '[user:field_metatags:og_image:0]' => 'Image 1',
      '[user:field_metatags:og_image:1]' => 'Image 2',
      // Test metatags that store value as an array.
      '[user:field_metatags:mask_icon]' => 'metatag-logo.svg',
    ];

    $this->assertPageTokens($user->toUrl(), $tokens, ['user' => $user]);
  }

  /**
   * Test precedence overridden tags over defaults in tokens.
   */
  public function testTokenOverriddenMetatagPrecedence() {
    $user = $this->createUser();
    $this->drupalGet($user->toUrl('edit-form'));
    $this->submitForm([
      'field_metatags[0][basic][title]' => 'My Title',
      'field_metatags[0][basic][description]' => 'My Description',
    ], 'Save');

    $tokens = [
      '[current-page:metatag:title]' => 'My Title',
      '[current-page:metatag:description]' => 'My Description',
    ];

    $this->assertPageTokens($user->toUrl(), $tokens, ['user' => $user]);
  }

  /**
   * Test the status report does not contain warnings about types.
   *
   * @see token_get_token_problems
   */
  public function testStatusReportTypesWarning() {
    $this->drupalLogin($this->rootUser);
    $this->drupalGet(Url::fromRoute('system.status'));

    $this->assertSession()->pageTextNotContains('$info[\'types\'][\'metatag');
  }

  /**
   * Test the status report does not contain warnings about tokens.
   *
   * @see token_get_token_problems
   */
  public function testStatusReportTokensWarning() {
    $this->drupalLogin($this->rootUser);
    $this->drupalGet(Url::fromRoute('system.status'));

    $this->assertSession()->pageTextNotContains('$info[\'tokens\'][\'metatag');
  }

}
