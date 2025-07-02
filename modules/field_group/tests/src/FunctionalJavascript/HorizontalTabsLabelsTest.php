<?php

namespace Drupal\Tests\field_group\FunctionalJavascript;

use Drupal\Core\Extension\Exception\UnknownExtensionException;
use Drupal\Core\Extension\ThemeInstallerInterface;
use Drupal\Core\Url;
use Drupal\field\FieldStorageConfigInterface;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\field_group\Functional\FieldGroupTestTrait;

/**
 * Tests horizontal tabs labels.
 *
 * @group field_group
 */
class HorizontalTabsLabelsTest extends WebDriverTestBase {

  use FieldGroupTestTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'starterkit_theme';

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'block',
    'field_group',
    'node',
    'user',
  ];

  /**
   * The themes to test with.
   *
   * @var string[]
   */
  protected $themeList = [
    'claro',
    'olivero',
    'stable9',
    'stark',
  ];

  /**
   * The themes that are shipped with block configurations.
   *
   * @var string[]
   */
  protected $themesWithBlocks = [
    'claro',
  ];

  /**
   * The webassert session.
   *
   * @var \Drupal\Tests\WebAssert
   */
  protected $assertSession;

  /**
   * The page element.
   *
   * @var \Behat\Mink\Element\DocumentElement
   */
  protected $page;

  /**
   * The node type used for testing.
   *
   * @var \Drupal\node\NodeTypeInterface
   */
  protected $testNodeType;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $this->assertSession = $this->assertSession();
    $this->page = $this->getSession()->getPage();
    $this->testNodeType = $this->drupalCreateContentType([
      'type' => 'test_node_bundle',
      'name' => 'Test Node Type',
    ]);

    // Add an extra field to the test content type.
    $entity_type_manager = $this->container->get('entity_type.manager');
    $field_storage = $entity_type_manager
      ->getStorage('field_storage_config')
      ->create([
        'type' => 'string',
        'field_name' => 'test_label',
        'entity_type' => 'node',
      ]);
    assert($field_storage instanceof FieldStorageConfigInterface);
    $field_storage->save();

    $entity_type_manager->getStorage('field_config')
      ->create([
        'label' => 'Test label',
        'field_storage' => $field_storage,
        'bundle' => $this->testNodeType->id(),
      ])
      ->save();
  }

  /**
   * Tests horizontal tabs labels.
   *
   * @dataProvider providerTestHorizontalTabsLabels
   */
  public function testHorizontalTabsLabels(string $theme_name) {
    $entity_type_manager = $this->container->get('entity_type.manager');
    $tab1 = [
      'label' => 'Tab1',
      'group_name' => 'group_tab1',
      'weight' => '1',
      'children' => [
        0 => 'test_label',
      ],
      'format_type' => 'tab',
      'format_settings' => [
        'label' => 'Tab1',
        'formatter' => 'open',
      ],
    ];
    $this->createGroup('node', $this->testNodeType->id(), 'form', 'default', $tab1);
    $this->createGroup('node', $this->testNodeType->id(), 'view', 'default', $tab1);

    $tab2 = [
      'label' => 'Tab2',
      'group_name' => 'group_tab2',
      'weight' => '2',
      'children' => [
        0 => 'body',
      ],
      'format_type' => 'tab',
      'format_settings' => [
        'label' => 'Tab2',
        'formatter' => 'closed',
      ],
    ];
    $this->createGroup('node', $this->testNodeType->id(), 'form', 'default', $tab2);
    $this->createGroup('node', $this->testNodeType->id(), 'view', 'default', $tab2);

    $horizontal_tabs = [
      'label' => 'Horizontal tabs',
      'group_name' => 'group_horizontal_tabs',
      'weight' => '-5',
      'children' => [
        'group_tab1',
        'group_tab2',
      ],
      'format_type' => 'tabs',
      'format_settings' => [
        'direction' => 'horizontal',
        'label' => 'Horizontal tabs',
      ],
    ];
    $this->createGroup('node', $this->testNodeType->id(), 'form', 'default', $horizontal_tabs);
    $this->createGroup('node', $this->testNodeType->id(), 'view', 'default', $horizontal_tabs);

    $entity_type_manager->getStorage('entity_form_display')
      ->load(implode('.', [
        'node',
        $this->testNodeType->id(),
        'default',
      ]))
      ->setComponent('test_label', ['weight' => '1'])
      ->save();

    $entity_type_manager->getStorage('entity_view_display')
      ->load(implode('.', [
        'node',
        $this->testNodeType->id(),
        'default',
      ]))
      ->setComponent('test_label', ['weight' => '1'])
      ->save();

    if ($theme_name !== $this->defaultTheme) {
      $theme_installer = \Drupal::service('theme_installer');
      assert($theme_installer instanceof ThemeInstallerInterface);
      try {
        $theme_installer->install([$theme_name]);
      }
      catch (UnknownExtensionException $ex) {
        // Themes might be missing, e.g Drupal 10 does not have stable theme.
        $this->markTestSkipped("The $theme_name theme does not exist in the current test environment.");
        return;
      }
      \Drupal::configFactory()
        ->getEditable('system.theme')
        ->set('default', $theme_name)
        ->set('admin', $theme_name)
        ->save();
    }

    if (!in_array($theme_name, $this->themesWithBlocks, TRUE)) {
      $this->drupalPlaceBlock('page_title_block', [
        'region' => 'content',
      ]);
      $this->drupalPlaceBlock('local_tasks_block', [
        'region' => 'content',
        'weight' => 1,
      ]);
      $this->drupalPlaceBlock('local_actions_block', [
        'region' => 'content',
        'weight' => 2,
      ]);
      $this->drupalPlaceBlock('system_main_block', [
        'region' => 'content',
        'weight' => 3,
      ]);
    }

    $this->drupalLogin($this->rootUser);

    // Actual test: check the node edit page. Tab1 and Tab2 should be present.
    $this->drupalGet(Url::fromRoute('node.add', [
      'node_type' => $this->testNodeType->id(),
    ]));
    $this->assertHorizontalTabsLabels();

    // Create a node.
    $this->page->fillField('title[0][value]', 'Field Group Horizontal Tabs Test Node');
    $this->page->fillField('Test label', 'Test label');
    $this->assertNotNull($tab2 = $this->page->find('css', '.field-group-tabs-wrapper a[href="#edit-group-tab2"]'));
    $tab2->click();
    $this->assertSession->waitForElementVisible('css', '[name="body[0][value]"]');
    // cspell:disable-next-line
    $this->page->fillField('body[0][value]', 'Donec laoreet imperdiet.');
    $this->page->findButton('edit-submit')->click();
    $this->assertSession->waitForElement('css', 'html.js [data-drupal-messages]');
    $status_message = $this->page->find('css', 'html.js [data-drupal-messages]');
    $this->assertStringContainsString("{$this->testNodeType->label()} Field Group Horizontal Tabs Test Node has been created.", $status_message->getText());

    // Check the node.
    $this->drupalGet(Url::fromRoute('entity.node.canonical', [
      'node' => '1',
    ]));
    $this->assertHorizontalTabsLabels();

    $this->drupalLogout();

    // Retest the node with anonymous user.
    $this->drupalGet(Url::fromRoute('entity.node.canonical', [
      'node' => '1',
    ]));
    $this->assertHorizontalTabsLabels();
  }

  /**
   * Asserts the horizontal tabs labels.
   */
  protected function assertHorizontalTabsLabels() {
    $this->assertSession->waitForElement('css', '.field-group-tabs-wrapper a[href="#edit-group-tab1"]');
    $this->assertSession->waitForElement('css', '.field-group-tabs-wrapper a[href="#edit-group-tab2"]');
    $this->assertNotNull($tab1 = $this->page->find('css', '.field-group-tabs-wrapper a[href="#edit-group-tab1"]'));
    $this->assertStringContainsString('Tab1', $tab1->getText());
    $this->assertNotNull($tab2 = $this->page->find('css', '.field-group-tabs-wrapper a[href="#edit-group-tab2"]'));
    $this->assertStringContainsString('Tab2', $tab2->getText());
  }

  /**
   * Data provider for testHorizontalTabsLabels.
   *
   * @return string[][][]
   *   The test cases with the theme machine names.
   */
  public function providerTestHorizontalTabsLabels() {
    return array_reduce($this->themeList, function (array $carry, string $theme_name) {
      $carry[$theme_name] = [
        'theme_name' => $theme_name,
      ];
      return $carry;
    }, []);
  }

  /**
   * Test horizontal tab formatter inside tabs with label_as_html=TRUE.
   */
  public function testHorizontalTabsLabelHtml() {
    $session = $this->assertSession();

    $data = [
      'label' => '<em>Tab 1</em>',
      'group_name' => 'group_tab1',
      'weight' => '1',
      'children' => [
        0 => 'test_label',
      ],
      'format_type' => 'tab',
      'format_settings' => [
        'label' => '<em>Tab 1</em>',
        'formatter' => 'open',
        'label_as_html' => TRUE,
      ],
    ];
    $tab1 = $this->createGroup('node', $this->testNodeType->id(), 'view', 'default', $data);

    $data = [
      'label' => '<em>Tab 2</em>',
      'group_name' => 'group_tab2',
      'weight' => '2',
      'children' => [
        0 => 'body',
      ],
      'format_type' => 'tab',
      'format_settings' => [
        'label' => '<em>Tab 2</em>',
        'formatter' => 'closed',
        'label_as_html' => TRUE,
      ],
    ];
    $tab2 = $this->createGroup('node', $this->testNodeType->id(), 'view', 'default', $data);

    $data = [
      'label' => 'Horizontal tabs',
      'group_name' => 'group_horizontal_tabs',
      'weight' => '1',
      'children' => [
        0 => $tab1->group_name,
        1 => $tab2->group_name,
      ],
      'format_type' => 'tabs',
      'format_settings' => [
        'direction' => 'horizontal',
        'label' => 'Horizontal tabs',
        'classes' => 'test-class-wrapper',
      ],
    ];
    $this->createGroup('node', $this->testNodeType->id(), 'view', 'default', $data);

    $node = $this->createNode([
      'type' => $this->testNodeType->id(),
      'title' => 'Test',
    ]);
    $this->drupalGet('node/' . $node->id());
    // See if the field group supports HTML elements in the label:
    // Note, for some reason only Tab 2 gets rendered on the page:
    // We expect the HTML to be not escaped:
    $session->elementContains('css', 'div.test-class-wrapper li.horizontal-tab-button.first > a > strong', '<em>Tab 2</em>');
  }

  /**
   * Test horizontal tab formatter inside tabs with label_as_html=FALSE.
   */
  public function testHorizontalTabsLabelNoHtml() {
    $session = $this->assertSession();

    $data = [
      'label' => '<em>Tab 1</em>',
      'group_name' => 'group_tab1',
      'weight' => '1',
      'children' => [
        0 => 'test_label',
      ],
      'format_type' => 'tab',
      'format_settings' => [
        'label' => '<em>Tab 1</em>',
        'formatter' => 'open',
        'label_as_html' => FALSE,
      ],
    ];
    $tab1 = $this->createGroup('node', $this->testNodeType->id(), 'view', 'default', $data);

    $data = [
      'label' => '<em>Tab 2</em>',
      'group_name' => 'group_tab2',
      'weight' => '2',
      'children' => [
        0 => 'body',
      ],
      'format_type' => 'tab',
      'format_settings' => [
        'label' => '<em>Tab 2</em>',
        'formatter' => 'closed',
        'label_as_html' => FALSE,
      ],
    ];
    $tab2 = $this->createGroup('node', $this->testNodeType->id(), 'view', 'default', $data);

    $data = [
      'label' => 'Horizontal tabs',
      'group_name' => 'group_horizontal_tabs',
      'weight' => '1',
      'children' => [
        0 => $tab1->group_name,
        1 => $tab2->group_name,
      ],
      'format_type' => 'tabs',
      'format_settings' => [
        'direction' => 'horizontal',
        'label' => 'Horizontal tabs',
        'classes' => 'test-class-wrapper',
      ],
    ];
    $this->createGroup('node', $this->testNodeType->id(), 'view', 'default', $data);

    $node = $this->createNode([
      'type' => $this->testNodeType->id(),
      'title' => 'Test',
    ]);
    $this->drupalGet('node/' . $node->id());
    // See if the field group supports HTML elements in the label:
    // Note, for some reason only Tab 2 gets rendered on the page:
    // We expect the HTML to be not escaped:
    $session->elementContains('css', 'div.test-class-wrapper li.horizontal-tab-button.first > a > strong', '&lt;em&gt;Tab 2&lt;/em&gt');
  }

}
