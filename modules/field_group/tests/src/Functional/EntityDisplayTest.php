<?php

namespace Drupal\Tests\field_group\Functional;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests for displaying entities.
 *
 * @group field_group
 */
class EntityDisplayTest extends BrowserTestBase {

  use FieldGroupTestTrait;
  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'field_test',
    'field_ui',
    'field_group',
    'field_group_test',
  ];

  /**
   * The node type id.
   *
   * @var string
   */
  protected $type;

  /**
   * A node to use for testing.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $node;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'starterkit_theme';

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    // Create test user.
    $admin_user = $this->drupalCreateUser([
      'access content',
      'administer content types',
      'administer node fields',
      'administer node form display',
      'administer node display',
      'bypass node access',
    ]);
    $this->drupalLogin($admin_user);

    // Create content type, with underscores.
    $type_name = strtolower($this->randomMachineName(8)) . '_test';
    $type = $this->drupalCreateContentType([
      'name' => $type_name,
      'type' => $type_name,
    ]);
    $this->type = $type->id();
    /** @var \Drupal\Core\Entity\Display\EntityViewDisplayInterface $display */
    $display = \Drupal::entityTypeManager()
      ->getStorage('entity_view_display')
      ->load('node.' . $type_name . '.default');

    // Create a node.
    $node_values = ['type' => $type_name];

    // Create test fields.
    foreach (['field_test', 'field_test_2', 'field_no_access'] as $field_name) {
      $field_storage = FieldStorageConfig::create([
        'field_name' => $field_name,
        'entity_type' => 'node',
        'type' => 'test_field',
      ]);
      $field_storage->save();

      $instance = FieldConfig::create([
        'field_storage' => $field_storage,
        'bundle' => $type_name,
        'label' => $this->randomMachineName(),
      ]);
      $instance->save();

      // Assign a test value for the field.
      $node_values[$field_name][0]['value'] = mt_rand(1, 127);

      // Set the field visible on the display object.
      $display_options = [
        'label' => 'above',
        'type' => 'field_test_default',
        'settings' => [
          'test_formatter_setting' => $this->randomMachineName(),
        ],
      ];
      $display->setComponent($field_name, $display_options);
    }

    // Save display + create node.
    $display->save();
    $this->node = $this->drupalCreateNode($node_values);
  }

  /**
   * Test field access for field groups.
   */
  public function testFieldAccess() {
    $data = [
      'label' => 'Wrapper',
      'children' => [
        0 => 'field_no_access',
      ],
      'format_type' => 'html_element',
      'format_settings' => [
        'element' => 'div',
        'id' => 'wrapper-id',
      ],
    ];

    $this->createGroup('node', $this->type, 'view', 'default', $data);
    $this->drupalGet('node/' . $this->node->id());

    // Test if group is not shown.
    $this->assertEmpty($this->xpath("//div[contains(@id, 'wrapper-id')]"), $this->t('Div that contains fields with no access is not shown.'));
  }

  /**
   * Test the html element formatter.
   */
  public function testHtmlElement() {
    $data = [
      'weight' => '1',
      'children' => [
        0 => 'field_test',
        1 => 'body',
      ],
      'label' => 'Link',
      'format_type' => 'html_element',
      'format_settings' => [
        'label' => 'Link',
        'element' => 'div',
        'id' => 'wrapper-id',
        'classes' => 'test-class',
      ],
    ];
    $group = $this->createGroup('node', $this->type, 'view', 'default', $data);

    // $groups =
    // field_group_info_groups('node', 'article', 'view', 'default', TRUE);.
    $this->drupalGet('node/' . $this->node->id());

    // Test group ids and classes.
    $this->assertCount(1, $this->xpath("//div[contains(@id, 'wrapper-id')]"), 'Wrapper id set on wrapper div');
    $this->assertCount(1, $this->xpath("//div[contains(@class, 'test-class')]"), 'Test class set on wrapper div, class="' . $group->group_name . ' test-class');

    // Test group label.
    $this->assertSession()->responseNotContains('<h3><span>' . $data['label'] . '</span></h3>');

    // Set show label to true.
    $group->format_settings['show_label'] = TRUE;
    $group->format_settings['label_element'] = 'h3';
    $group->format_settings['label_element_classes'] = 'my-label-class';
    field_group_group_save($group);

    $this->drupalGet('node/' . $this->node->id());
    $this->assertSession()->responseContains('<h3 class="my-label-class">' . $data['label'] . '</h3>');

    // Change to collapsible with blink effect.
    $group->format_settings['effect'] = 'blink';
    $group->format_settings['speed'] = 'fast';
    field_group_group_save($group);

    $this->drupalGet('node/' . $this->node->id());
    $this->assertCount(1, $this->xpath("//div[contains(@class, 'speed-fast')]"), 'Speed class is set');
    $this->assertCount(1, $this->xpath("//div[contains(@class, 'effect-blink')]"), 'Effect class is set');
  }

  /**
   * Test the html element formatter with label_as_html=TRUE.
   */
  public function testHtmlElementLabelHtml() {
    $session = $this->assertSession();
    $data = [
      'weight' => '1',
      'children' => [
        0 => 'field_test',
        1 => 'body',
      ],
      'label' => '<strong>Test HTML</strong>',
      'format_type' => 'html_element',
      'format_settings' => [
        'label' => 'Link',
        'element' => 'div',
        'id' => 'wrapper-id',
        'classes' => 'test-class',
        'label_as_html' => TRUE,
        'show_label' => TRUE,
      ],
    ];
    $this->createGroup('node', $this->type, 'view', 'default', $data);
    $this->drupalGet('node/' . $this->node->id());
    // See if the field group supports HTML elements in the label:
    // We expect the HTML to be not escaped:
    $session->elementContains('css', '#wrapper-id.test-class > h3', '<strong>Test HTML</strong>');
  }

  /**
   * Test the html element formatter with label_as_html=FALSE.
   */
  public function testHtmlElementLabelNoHtml() {
    $session = $this->assertSession();
    $data = [
      'weight' => '1',
      'children' => [
        0 => 'field_test',
        1 => 'body',
      ],
      'label' => '<strong>Test HTML</strong>',
      'format_type' => 'html_element',
      'format_settings' => [
        'label' => 'Link',
        'element' => 'div',
        'id' => 'wrapper-id',
        'classes' => 'test-class',
        'label_as_html' => FALSE,
        'show_label' => TRUE,
      ],
    ];
    $this->createGroup('node', $this->type, 'view', 'default', $data);
    $this->drupalGet('node/' . $this->node->id());
    // See if the field group supports HTML elements in the label:
    // We expect the HTML to be not escaped:
    $session->elementContains('css', '#wrapper-id.test-class > h3', '&lt;strong&gt;Test HTML&lt;/strong&gt;');
  }

  /**
   * Test the fieldset formatter.
   */
  public function testFieldset() {
    $data = [
      'weight' => '1',
      'children' => [
        0 => 'field_test',
        1 => 'body',
      ],
      'label' => 'Test Fieldset',
      'format_type' => 'fieldset',
      'format_settings' => [
        'id' => 'fieldset-id',
        'classes' => 'test-class',
        'description' => 'test description',
      ],
    ];
    $this->createGroup('node', $this->type, 'view', 'default', $data);
    $this->drupalGet('node/' . $this->node->id());

    // Test group ids and classes.
    $this->assertCount(1, $this->xpath("//fieldset[contains(@id, 'fieldset-id')]"), 'Correct id set on the fieldset');
    $this->assertCount(1, $this->xpath("//fieldset[contains(@class, 'test-class')]"), 'Test class set on the fieldset');
  }

  /**
   * Test the fieldset formatter with label_as_html=TRUE.
   */
  public function testFieldsetLabelHtml() {
    $session = $this->assertSession();
    $data = [
      'weight' => '1',
      'children' => [
        0 => 'field_test',
        1 => 'body',
      ],
      'label' => '<strong>Test Fieldset</strong>',
      'format_type' => 'fieldset',
      'format_settings' => [
        'id' => 'fieldset-id',
        'classes' => 'test-class',
        'description' => 'test description',
        'label_as_html' => TRUE,
      ],
    ];
    $this->createGroup('node', $this->type, 'view', 'default', $data);
    $this->drupalGet('node/' . $this->node->id());
    // See if the field group supports HTML elements in the label:
    // We expect the HTML to be not escaped:
    $session->elementContains('css', '#fieldset-id.test-class > legend > span', '<strong>Test Fieldset</strong>');
  }

  /**
   * Test the fieldset formatter with label_as_html=FALSE.
   */
  public function testFieldsetLabelNoHtml() {
    $session = $this->assertSession();
    $data = [
      'weight' => '1',
      'children' => [
        0 => 'field_test',
        1 => 'body',
      ],
      'label' => '<strong>Test Fieldset</strong>',
      'format_type' => 'fieldset',
      'format_settings' => [
        'id' => 'fieldset-id',
        'classes' => 'test-class',
        'description' => 'test description',
        'label_as_html' => FALSE,
      ],
    ];
    $this->createGroup('node', $this->type, 'view', 'default', $data);

    $this->drupalGet('node/' . $this->node->id());
    // See if the field group supports HTML elements in the label:
    // We expect the HTML to be escaped (plain):
    $session->elementContains('css', '#fieldset-id.test-class > legend > span', '&lt;strong&gt;Test Fieldset&lt;/strong&gt;');
  }

  /**
   * Test the tabs formatter.
   */
  public function testVerticalTabs() {
    $data = [
      'label' => 'Tab 1',
      'weight' => '1',
      'children' => [
        0 => 'field_test',
      ],
      'format_type' => 'tab',
      'format_settings' => [
        'label' => 'Tab 1',
        'classes' => 'test-class',
        'description' => '',
        'formatter' => 'open',
      ],
    ];
    $first_tab = $this->createGroup('node', $this->type, 'view', 'default', $data);

    $data = [
      'label' => 'Tab 2',
      'weight' => '1',
      'children' => [
        0 => 'field_test_2',
      ],
      'format_type' => 'tab',
      'format_settings' => [
        'label' => 'Tab 1',
        'classes' => 'test-class-2',
        'description' => 'description of second tab',
        'formatter' => 'closed',
      ],
    ];
    $second_tab = $this->createGroup('node', $this->type, 'view', 'default', $data);

    $data = [
      'label' => 'Tabs',
      'weight' => '1',
      'children' => [
        0 => $first_tab->group_name,
        1 => $second_tab->group_name,
      ],
      'format_type' => 'tabs',
      'format_settings' => [
        'direction' => 'vertical',
        'label' => 'Tab 1',
        'classes' => 'test-class-wrapper',
      ],
    ];
    $tabs_group = $this->createGroup('node', $this->type, 'view', 'default', $data);

    $this->drupalGet('node/' . $this->node->id());

    // Test properties.
    $this->assertCount(1, $this->xpath("//div[contains(@class, 'test-class-wrapper')]"), 'Test class set on tabs wrapper');
    $this->assertCount(1, $this->xpath("//details[contains(@class, 'test-class-2')]"), 'Test class set on second tab');
    $this->assertSession()->responseContains('<div class="details-description">description of second tab</div>');

    // Test if correctly nested.
    $this->assertCount(2, $this->xpath("//div[contains(@class, 'test-class-wrapper')]//details[contains(@class, 'test-class')]"), 'First tab is displayed as child of the wrapper.');
    $this->assertCount(1, $this->xpath("//div[contains(@class, 'test-class-wrapper')]//details[contains(@class, 'test-class-2')]"), 'Second tab is displayed as child of the wrapper.');

    // Test if it's a vertical tab.
    $this->assertCount(1, $this->xpath('//div[@data-vertical-tabs-panes=""]'), 'Tabs are shown vertical.');

    // Switch to horizontal.
    $tabs_group->format_settings['direction'] = 'horizontal';
    field_group_group_save($tabs_group);

    $this->drupalGet('node/' . $this->node->id());

    // Test if it's a horizontal tab.
    $this->assertCount(1, $this->xpath('//div[@data-horizontal-tabs-panes=""]'), 'Tabs are shown horizontal.');
  }

  /**
   * Test the vertical tab formatter inside tabs with label_as_html=TRUE.
   *
   * @todo The "label_as_html" is currently not working for vertical tabs,
   * as the HTML is escaped in the core definition of the vertical tab. For more
   * information see: https://www.drupal.org/project/field_group/issues/3363890.
   */
  public function todoTestVerticalTabsLabelHtml() {
    $session = $this->assertSession();
    $data = [
      'label' => '<em>Tab 1</em>',
      'weight' => '1',
      'children' => [
        0 => 'field_test',
      ],
      'format_type' => 'tab',
      'format_settings' => [
        'label' => '<em>Tab 1</em>',
        'classes' => 'test-class',
        'description' => '',
        'formatter' => 'open',
        'label_as_html' => TRUE,
      ],
    ];
    $first_tab = $this->createGroup('node', $this->type, 'view', 'default', $data);

    $data = [
      'label' => '<em>Tab 2</em>',
      'weight' => '1',
      'children' => [
        0 => 'field_test_2',
      ],
      'format_type' => 'tab',
      'format_settings' => [
        'label' => '<em>Tab 2</em>',
        'classes' => 'test-class-2',
        'description' => 'description of second tab',
        'formatter' => 'closed',
        'label_as_html' => TRUE,
      ],
    ];
    $second_tab = $this->createGroup('node', $this->type, 'view', 'default', $data);

    $data = [
      'label' => 'Tabs',
      'weight' => '1',
      'children' => [
        0 => $first_tab->group_name,
        1 => $second_tab->group_name,
      ],
      'format_type' => 'tabs',
      'format_settings' => [
        'direction' => 'vertical',
        'label' => 'Tab 1',
        'classes' => 'test-class-wrapper',
      ],
    ];
    $this->createGroup('node', $this->type, 'view', 'default', $data);

    $this->drupalGet('node/' . $this->node->id());
    $session->elementContains('css', 'div.test-class-wrapper li.vertical-tabs__menu-item.first > a > strong', '<em>Tab 1</em>');
    $session->elementContains('css', 'div.test-class-wrapper li.vertical-tabs__menu-item.last > a > strong', '<em>Tab 2</em>');
  }

  /**
   * Test the vertical tab formatter inside tabs with label_as_html=FALSE.
   *
   * @todo The "label_as_html" is currently not working for vertical tabs,
   * as the HTML is escaped in the core definition of the vertical tab. For more
   * information see: https://www.drupal.org/project/field_group/issues/3363890.
   */
  public function todoTestVerticalTabsLabelNoHtml() {
    $session = $this->assertSession();
    $data = [
      'label' => '<em>Tab 1</em>',
      'weight' => '1',
      'children' => [
        0 => 'field_test',
      ],
      'format_type' => 'tab',
      'format_settings' => [
        'label' => '<em>Tab 1</em>',
        'classes' => 'test-class',
        'description' => '',
        'formatter' => 'open',
        'label_as_html' => FALSE,
      ],
    ];
    $first_tab = $this->createGroup('node', $this->type, 'view', 'default', $data);

    $data = [
      'label' => '<em>Tab 2</em>',
      'weight' => '1',
      'children' => [
        0 => 'field_test_2',
      ],
      'format_type' => 'tab',
      'format_settings' => [
        'label' => '<em>Tab 2</em>',
        'classes' => 'test-class-2',
        'description' => 'description of second tab',
        'formatter' => 'closed',
        'label_as_html' => FALSE,
      ],
    ];
    $second_tab = $this->createGroup('node', $this->type, 'view', 'default', $data);

    $data = [
      'label' => 'Tabs',
      'weight' => '1',
      'children' => [
        0 => $first_tab->group_name,
        1 => $second_tab->group_name,
      ],
      'format_type' => 'tabs',
      'format_settings' => [
        'direction' => 'vertical',
        'label' => 'Tab 1',
        'classes' => 'test-class-wrapper',
      ],
    ];
    $this->createGroup('node', $this->type, 'view', 'default', $data);

    $this->drupalGet('node/' . $this->node->id());
    $session->elementContains('css', 'div.test-class-wrapper li.vertical-tabs__menu-item.first > a > strong', '&lt;em&gt;Tab 1&lt;/em&gt');
    $session->elementContains('css', 'div.test-class-wrapper li.vertical-tabs__menu-item.last > a > strong', '&lt;em&gt;Tab 2&lt;/em&gt');
  }

}
