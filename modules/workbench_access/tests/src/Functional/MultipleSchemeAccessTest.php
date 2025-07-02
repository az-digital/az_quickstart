<?php

namespace Drupal\Tests\workbench_access\Functional;

use Drupal\menu_link_content\Entity\MenuLinkContent;
use Drupal\taxonomy\Entity\Term;
use Drupal\Tests\BrowserTestBase;
use Drupal\workbench_access\WorkbenchAccessManagerInterface;
use Drupal\Tests\workbench_access\Traits\WorkbenchAccessTestTrait;

/**
 * Tests cases where two schemes apply to a node.
 *
 * @group workbench_access
 */
class MultipleSchemeAccessTest extends BrowserTestBase {

  use WorkbenchAccessTestTrait;

  /**
   * The default theme.
   *
   * @var string
   */
  protected $defaultTheme = 'stark';

  /**
   * Simple array.
   */
  protected $terms = [];

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'workbench_access',
    'node',
    'taxonomy',
    'menu_ui',
    'link',
    'menu_link_content',
    'options',
    'user',
    'system',
  ];

  /**
   * Tests that the user can edit the node based on settings.
   */
  public function testNodeForm() {
    // Set up a content type and menu scheme.
    $node_type_values = [
      'type' => 'page',
      'third_party_settings' => [
        'menu_ui' => [
          'available_menus' => [
            'main',
            'account',
          ],
        ],
      ],
    ];

    $node_type = $this->createContentType($node_type_values);
    $menu_scheme = $this->setUpMenuScheme(['page'], ['main', 'account'], 'menu_scheme');

    $vocab = $this->setUpVocabulary();
    $field = $this->setUpTaxonomyFieldForEntityType('node', $node_type->id(), $vocab->id());
    $this->assertEquals($field->getDefaultValueLiteral(), []);
    $taxonomy_scheme = $this->setUpTaxonomyScheme($node_type, $vocab);
    $user_storage = \Drupal::service('workbench_access.user_section_storage');

    // Set up an editor and log in as them.
    $editor = $this->setUpEditorUser();
    $this->drupalLogin($editor);

    // Set up some roles and terms for this test.
    // Create terms and roles.
    $staff_term = Term::create([
      'vid' => $vocab->id(),
      'name' => 'Staff',
    ]);
    $staff_term->save();
    $super_staff_term = Term::create([
      'vid' => $vocab->id(),
      'name' => 'Super staff',
    ]);
    $super_staff_term->save();
    $base_term = Term::create([
      'vid' => $vocab->id(),
      'name' => 'Editor',
    ]);
    $base_term->save();

    // Set up some roles and menu links for this test.
    $staff_link = MenuLinkContent::create([
      'title' => 'Link 1',
      'link' => [['uri' => 'route:<front>']],
      'menu_name' => 'main',
    ]);
    $staff_link->save();
    $super_staff_link = MenuLinkContent::create([
      'title' => 'Link 2',
      'link' => [['uri' => 'route:<front>']],
      'menu_name' => 'main',
    ]);
    $super_staff_link->save();
    $base_link = MenuLinkContent::create([
      'title' => 'Link 3',
      'link' => [['uri' => 'route:<front>']],
      'menu_name' => 'main',
    ]);
    $base_link->save();

    // Add the user to the base section.
    $user_storage->addUser($taxonomy_scheme, $editor, [$staff_term->id()]);
    $expected = [$editor->id()];
    $existing_users = $user_storage->getEditors($taxonomy_scheme, $staff_term->id());
    $this->assertEquals($expected, array_keys($existing_users));

    $this->drupalGet('node/add/page');
    $this->assertSession()->statusCodeEquals(200);

    // Strict checking does not affect creation.
    $config = $this->config('workbench_access.settings');
    $config->set('deny_strict', TRUE)->save();

    $this->drupalGet('node/add/page');
    $this->assertSession()->statusCodeEquals(200);

    // Create a node and try to edit it.
    $node_values = [
      'type' => 'page',
      'title' => 'foo',
      WorkbenchAccessManagerInterface::FIELD_NAME => $staff_term->id(),
    ];
    $node = $this->createNode($node_values);
    _menu_ui_node_save($node, [
      'title' => 'baz',
      'menu_name' => 'main',
      'description' => 'view baz',
      'parent' => $staff_link->getPluginId(),
    ]);

    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->assertSession()->statusCodeEquals(403);

    $config = $this->config('workbench_access.settings');
    $config->set('deny_strict', FALSE)->save();

    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->assertSession()->statusCodeEquals(200);

    // Create a node and try to edit it.
    $node_values = [
      'type' => 'page',
      'title' => 'foo',
      WorkbenchAccessManagerInterface::FIELD_NAME => $staff_term->id(),
    ];
    $node2 = $this->createNode($node_values);
    _menu_ui_node_save($node2, [
      'title' => 'bar',
      'menu_name' => 'main',
      'description' => 'view bar',
      'parent' => $base_link->getPluginId(),
    ]);

    $this->drupalGet('node/' . $node2->id() . '/edit');
    $this->assertSession()->statusCodeEquals(200);

    $config = $this->config('workbench_access.settings');
    $config->set('deny_strict', TRUE)->save();

    $this->drupalGet('node/' . $node2->id() . '/edit');
    $this->assertSession()->statusCodeEquals(403);

    // Add the user to the base menu section.
    $user_storage->addUser($menu_scheme, $editor, [$base_link->getPluginId()]);
    $expected = [$editor->id()];
    $existing_users = $user_storage->getEditors($menu_scheme, $base_link->getPluginId());
    $this->assertEquals($expected, array_keys($existing_users));

    $this->drupalGet('node/' . $node2->id() . '/edit');
    $this->assertSession()->statusCodeEquals(200);

    // In cases where one scheme is empty, access should be allowed unless
    // deny on empty is enforced.
    // Create a node and try to edit it.
    $node_values = [
      'type' => 'page',
      'title' => 'foo',
      WorkbenchAccessManagerInterface::FIELD_NAME => $staff_term->id(),
    ];
    $node3 = $this->createNode($node_values);
    $this->drupalGet('node/' . $node3->id() . '/edit');
    $this->assertSession()->statusCodeEquals(200);

    $config = $this->config('workbench_access.settings');
    $config->set('deny_on_empty', TRUE)->save();

    $this->drupalGet('node/' . $node3->id() . '/edit');
    $this->assertSession()->statusCodeEquals(403);

  }

}
