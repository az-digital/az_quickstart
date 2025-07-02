<?php

namespace Drupal\Tests\metatag_extended_perms\Functional;

use Drupal\node\Entity\NodeType;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\field_ui\Traits\FieldUiTestTrait;
use Drupal\Tests\metatag\Functional\MetatagHelperTrait;

/**
 * Verify the new permissions are added.
 *
 * @group metatag
 */
class PermissionsTest extends BrowserTestBase {

  // Contains helper methods.
  use FieldUiTestTrait;
  use MetatagHelperTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    // Modules for core functionality.
    'node',

    // Needed for the field UI testing.
    'field_ui',

    // This custom module.
    'metatag_extended_perms',
  ];

  /**
   * Permissions to check for.
   *
   * @var string
   */
  protected $permissions = [
    'basic' => [
      'abstract' => 'Abstract',
      'description' => 'Description',
      'keywords' => 'Keywords',
      'title' => 'Page title',
    ],

    // Tags in the "Advanced" group.
    'advanced' => [
      'cache_control' => 'Cache control',
      'canonical_url' => 'Canonical URL',
      'expires' => 'Expires',
      'generator' => 'Generator',
      'geo_placename' => 'Geographical place name',
      'geo_position' => 'Geographical position',
      'geo_region' => 'Geographical region',
      'google' => 'Google',
      'icbm' => 'ICBM',
      'image_src' => 'Image',
      'next' => 'Next page URL',
      'original_source' => 'Original source',
      'pragma' => 'Pragma',
      'prev' => 'Previous page URL',
      'rating' => 'Rating',
      'referrer' => 'Referrer policy',
      'refresh' => 'Refresh',
      'revisit_after' => 'Revisit After',
      'rights' => 'Rights',
      // This one is more complicated, so skip it.
      // @code
      // 'robots' => 'Robots',
      // @endcode
      'set_cookie' => 'Set cookie',
      'shortlink' => 'Shortlink URL',
    ],
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Log in as the super admin.
    $this->loginUser1();

    // Create a content type with a Metatag field.
    $this->createContentType();
  }

  /**
   * Confirm each permission is listed.
   *
   * User 1 will be logged in from the setUp() method.
   */
  public function testPermissionsExist() {
    // Load the front page.
    $this->drupalGet('admin/people/permissions');

    // Confirm that the site didn't throw a server error or something else.
    $session = $this->assertSession();
    $session->statusCodeEquals(200);

    // Confirm that the page contains the standard text indicating this is the
    // permissions page.
    $session->pageTextContains('Administer modules');
    $session->pageTextContains('Administer site configuration');
    $session->pageTextContains('Administer themes');
    $session->pageTextContains('Administer software updates');

    // Look for each of the meta tags.
    foreach ($this->permissions as $group => $perms) {
      foreach ($perms as $tag_name => $tag_label) {
        // Look for the checkbox.
        $session->fieldExists('anonymous[access metatag ' . $group . '__' . $tag_name . ']');
      }
    }
  }

  /**
   * Confirm that the node form isn't affected for user 1.
   *
   * User 1 will be logged in from the setUp() method.
   */
  public function testUser1() {
    // Load the node form.
    $this->drupalGet('node/add/page');

    // Confirm that the site didn't throw a server error or something else.
    $session = $this->assertSession();
    $session->statusCodeEquals(200);

    // Look for each of the meta tags.
    foreach ($this->permissions as $group => $perms) {
      foreach ($perms as $tag_name => $tag_label) {
        // Look for the checkbox.
        $session->fieldExists("field_metatag[0][{$group}][{$tag_name}]");
      }
    }
  }

  /**
   * Confirm that the node form is affected for a limited-access user.
   */
  public function testUserPerms() {
    // Split up the permissions into ones that will be granted and ones that
    // will not.
    $group_yes = 'basic';
    $group_no = 'advanced';

    // Work out a list of permissions to grant the user. These are base perms.
    $perms_yes = [
      'access administration pages',
      'access content',
      'administer meta tags',
      'administer nodes',
      'create page content',
    ];
    // Grant each of the "yes" tag's permissions.
    foreach ($this->permissions[$group_yes] as $tag_name => $tag_label) {
      $perms_yes[] = "access metatag {$group_yes}__{$tag_name}";
    }

    // Create a user account with the above permissions.
    $user = $this->createUser($perms_yes);
    $this->drupalLogin($user);

    // Load the node form.
    $this->drupalGet('node/add/page');

    // Confirm that the site didn't throw a server error or something else.
    $session = $this->assertSession();
    $session->statusCodeEquals(200);

    // Look for each of the "yes" meta tags.
    foreach ($this->permissions[$group_yes] as $tag_name => $tag_label) {
      // Look for the checkbox.
      $session->fieldExists("field_metatag[0][{$group_yes}][{$tag_name}]");
    }

    // Make sure each of the "no" meta tags is not present.
    foreach ($this->permissions[$group_no] as $tag_name => $tag_label) {
      // Look for the checkbox.
      $session->fieldNotExists("field_metatag[0][{$group_no}][{$tag_name}]");
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function createContentType(array $values = []): NodeType {
    $type = parent::createContentType(['type' => 'page']);

    // Load a node form.
    $this->drupalGet('node/add/page');

    // Add a metatag field to the entity type test_entity.
    $this->fieldUIAddNewField('admin/structure/types/manage/page', 'metatag', 'Metatag', 'metatag');

    // Clear all settings.
    $this->container->get('entity_field.manager')->clearCachedFieldDefinitions();

    return $type;
  }

}
