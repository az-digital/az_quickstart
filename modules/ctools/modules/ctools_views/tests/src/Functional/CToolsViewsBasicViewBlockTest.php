<?php

namespace Drupal\Tests\ctools_views\Functional;

use Drupal\Tests\views_ui\Functional\UITestBase;
use Drupal\views\Tests\ViewTestData;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Tests ctools_views block display plugin overrides settings from a basic View.
 *
 * @group ctools_views
 * @see \Drupal\ctools_views\Plugin\Display\Block
 */
class CToolsViewsBasicViewBlockTest extends UITestBase {

  use StringTranslationTrait;

  /**
   * Exempt from strict schema checking.
   *
   * @see \Drupal\Core\Config\Development\ConfigSchemaChecker
   *
   * @var bool
   */
  protected $strictConfigSchema = FALSE;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['ctools_views', 'ctools_views_test_views'];

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['ctools_views_test_view'];

  /**
   * The block storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storage;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'starterkit_theme';

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE, $modules = ['views_test_config']): void {
    parent::setUp($import_test_views);

    ViewTestData::createTestViews(get_class($this), ['ctools_views_test_views']);
    $this->storage = $this->container->get('entity_type.manager')->getStorage('block');
  }

  /**
   * Test basic view with ctools_views module enabled but no options set.
   */
  public function testBasic() {
    $default_theme = $this->config('system.theme')->get('default');

    // Get the "Configure block" form for our Views block.
    $this->drupalGet('admin/structure/block/add/views_block:ctools_views_test_view-block_basic/' . $default_theme);
    // Add block to sidebar_first region with default settings.
    $edit = [];
    $edit['region'] = 'sidebar_first';
    $this->drupalGet('admin/structure/block/add/views_block:ctools_views_test_view-block_basic/' . $default_theme);
    $this->submitForm($edit, 'Save block');

    // Assert items per page default settings.
    $this->drupalGet('<front>');
    $result = $this->xpath('//div[contains(@class, "region-sidebar-first")]/div[contains(@class, "block-views")]/h2');
    $this->assertSession()->fieldExists('status');
    $this->assertSession()->fieldExists('job');
    $this->assertSession()->buttonExists('Apply');
  }

  /**
   * Test ctools_views "items_per_page" configuration.
   */
  public function testItemsPerPage() {
    $default_theme = $this->config('system.theme')->get('default');

    // Get the "Configure block" form for our Views block.
    $this->drupalGet('admin/structure/block/add/views_block:ctools_views_test_view-block_pager/' . $default_theme);
    $this->assertNotEmpty($this->xpath('//input[@type="number" and @name="settings[override][items_per_page]"]'), 'items_per_page setting is a number field');
    // Add block to sidebar_first region with default settings.
    $edit = [];
    $edit['region'] = 'sidebar_first';
    $edit['id'] = 'views_block__ctools_views_test_view_block_pager';
    $edit['settings[override][items_per_page]'] = 0;
    $this->drupalGet('admin/structure/block/add/views_block:ctools_views_test_view-block_pager/' . $default_theme);
    $this->submitForm($edit, 'Save block');

    // Assert items per page default settings.
    $this->drupalGet('<front>');
    $result = $this->xpath('//div[contains(@class, "region-sidebar-first")]/div[contains(@class, "block-views")]/h2');
    $this->assertEquals('CTools Views Pager Block', $result[0]->getText());
    $this->assertSession()->pageTextContains('Showing 3 records on page 1');
    $this->assertEquals(3, count($this->xpath('//div[contains(@class, "view-display-id-block_pager")]//table/tbody/tr')));

    // Override items per page settings.
    $edit = [];
    $edit['region'] = 'sidebar_first';
    $edit['settings[override][items_per_page]'] = 2;
    $this->drupalGet('admin/structure/block/manage/views_block__ctools_views_test_view_block_pager');
    $this->submitForm($edit, 'Save block');

    $block = $this->storage->load('views_block__ctools_views_test_view_block_pager');
    $config = $block->getPlugin()->getConfiguration();
    $this->assertEquals(2, $config['items_per_page'], "'Items per page' is properly saved.");

    // Assert items per page overridden settings.
    $this->drupalGet('<front>');
    $result = $this->xpath('//div[contains(@class, "region-sidebar-first")]/div[contains(@class, "block-views")]/h2');
    $this->assertEquals('CTools Views Pager Block', $result[0]->getText());
    $this->assertSession()->pageTextContains('Showing 2 records on page 1');
    $this->assertEquals(2, count($this->xpath('//div[contains(@class, "view-display-id-block_pager")]//table/tbody/tr')));
    $elements = $this->xpath('//div[contains(@class, "view-display-id-block_pager")]//table//tr//td[contains(@class, "views-field-id")]');
    $results = array_map(function ($element) {
      return $element->getText();
    }, $elements);
    $this->assertEquals([1, 2], $results);
  }

  /**
   * Test ctools_views "offset" configuration.
   */
  public function testOffset() {
    $default_theme = $this->config('system.theme')->get('default');

    // Get the "Configure block" form for our Views block.
    $this->drupalGet('admin/structure/block/add/views_block:ctools_views_test_view-block_pager/' . $default_theme);
    $this->assertNotEmpty($this->xpath('//input[@type="number" and @name="settings[override][pager_offset]"]'), 'items_per_page setting is a number field');
    // Add block to sidebar_first region with default settings.
    $edit = [];
    $edit['region'] = 'sidebar_first';
    $edit['id'] = 'views_block__ctools_views_test_view_block_pager';
    $edit['settings[override][items_per_page]'] = 0;
    $this->drupalGet('admin/structure/block/add/views_block:ctools_views_test_view-block_pager/' . $default_theme);
    $this->submitForm($edit, 'Save block');

    // Assert pager offset default settings.
    $this->drupalGet('<front>');
    $elements = $this->xpath('//div[contains(@class, "view-display-id-block_pager")]//table//tr//td[contains(@class, "views-field-id")]');
    $results = array_map(function ($element) {
      return $element->getText();
    }, $elements);
    $this->assertEquals([1, 2, 3], $results);

    // Override pager offset settings.
    $edit = [];
    $edit['region'] = 'sidebar_first';
    $edit['settings[override][items_per_page]'] = 0;
    $edit['settings[override][pager_offset]'] = 1;
    $this->drupalGet('admin/structure/block/manage/views_block__ctools_views_test_view_block_pager');
    $this->submitForm($edit, 'Save block');

    $block = $this->storage->load('views_block__ctools_views_test_view_block_pager');
    $config = $block->getPlugin()->getConfiguration();
    $this->assertEquals(1, $config['pager_offset'], "'Pager offset' is properly saved.");

    // Assert pager offset overridden settings.
    $this->drupalGet('<front>');
    $elements = $this->xpath('//div[contains(@class, "view-display-id-block_pager")]//table//tr//td[contains(@class, "views-field-id")]');
    $results = array_map(function ($element) {
      return $element->getText();
    }, $elements);
    $this->assertEquals([2, 3, 4], $results);
  }

  /**
   * Test ctools_views "pager" configuration.
   */
  public function testPager() {
    $default_theme = $this->config('system.theme')->get('default');

    // Get the "Configure block" form for our Views block.
    $this->drupalGet('admin/structure/block/add/views_block:ctools_views_test_view-block_pager/' . $default_theme);
    $this->assertSession()->fieldValueEquals('edit-settings-override-pager-view', 'view');
    $this->assertSession()->fieldExists('edit-settings-override-pager-some');
    $this->assertSession()->fieldExists('edit-settings-override-pager-none');

    // Add block to sidebar_first region with default settings.
    $edit = [];
    $edit['region'] = 'sidebar_first';
    $edit['id'] = 'views_block__ctools_views_test_view_block_pager';
    $edit['settings[override][items_per_page]'] = 0;
    $this->drupalGet('admin/structure/block/add/views_block:ctools_views_test_view-block_pager/' . $default_theme);
    $this->submitForm($edit, 'Save block');

    // Assert pager default settings.
    $this->drupalGet('<front>');
    $this->assertSession()->pageTextContains('Page 1');
    $this->assertSession()->pageTextContains('Next ›');

    // Override pager settings to 'some'.
    $edit = [];
    $edit['region'] = 'sidebar_first';
    $edit['settings[override][items_per_page]'] = 0;
    $edit['settings[override][pager]'] = 'some';
    $this->drupalGet('admin/structure/block/manage/views_block__ctools_views_test_view_block_pager');
    $this->submitForm($edit, 'Save block');

    $block = $this->storage->load('views_block__ctools_views_test_view_block_pager');
    $config = $block->getPlugin()->getConfiguration();
    $this->assertEquals('some', $config['pager'], "'Pager' setting is properly saved.");

    // Assert pager overridden settings to 'some', showing no pager.
    $this->drupalGet('<front>');
    $this->assertEquals(3, count($this->xpath('//div[contains(@class, "view-display-id-block_pager")]//table/tbody/tr')));
    $this->assertSession()->elementNotExists('css', '#block-views-block-ctools-views-test-view-block-pager .pager');

    // Override pager settings to 'none'.
    $edit = [];
    $edit['region'] = 'sidebar_first';
    $edit['settings[override][items_per_page]'] = 0;
    $edit['settings[override][pager]'] = 'none';
    $this->drupalGet('admin/structure/block/manage/views_block__ctools_views_test_view_block_pager');
    $this->submitForm($edit, 'Save block');

    $block = $this->storage->load('views_block__ctools_views_test_view_block_pager');
    $config = $block->getPlugin()->getConfiguration();
    $this->assertEquals('none', $config['pager'], "'Pager' setting is properly saved.");

    // Assert pager overridden settings to 'some', showing no pager.
    $this->drupalGet('<front>');
    $this->assertEquals(5, count($this->xpath('//div[contains(@class, "view-display-id-block_pager")]//table/tbody/tr')));
    $this->assertSession()->elementNotExists('css', '#block-views-block-ctools-views-test-view-block-pager .pager');
  }

  /**
   * Test ctools_views 'hide_fields' configuration.
   */
  public function testHideFields() {
    $default_theme = $this->config('system.theme')->get('default');

    // Get the "Configure block" form for our Views block.
    $this->drupalGet('admin/structure/block/add/views_block:ctools_views_test_view-block_fields/' . $default_theme);
    $this->assertSession()->fieldExists('edit-settings-override-order-fields-id-hide');

    // Add block to sidebar_first region with default settings.
    $edit = [];
    $edit['region'] = 'sidebar_first';
    $edit['id'] = 'views_block__ctools_views_test_view_block_fields';
    $this->drupalGet('admin/structure/block/add/views_block:ctools_views_test_view-block_fields/' . $default_theme);
    $this->submitForm($edit, 'Save block');

    // Assert hide_fields default settings.
    $this->drupalGet('<front>');
    $this->assertEquals(5, count($this->xpath('//div[contains(@class, "view-display-id-block_fields")]//table//td[contains(@class, "views-field-id")]')));

    // Override hide_fields settings.
    $edit = [];
    $edit['region'] = 'sidebar_first';
    $edit['settings[override][order_fields][id][hide]'] = 1;
    $this->drupalGet('admin/structure/block/manage/views_block__ctools_views_test_view_block_fields');
    $this->submitForm($edit, 'Save block');

    $block = $this->storage->load('views_block__ctools_views_test_view_block_fields');
    $config = $block->getPlugin()->getConfiguration();
    $this->assertEquals(1, $config['fields']['id']['hide'], "'hide_fields' setting is properly saved.");
    $this->assertEquals(0, $config['fields']['name']['hide'], "'hide_fields' setting is properly saved.");

    // Assert hide_fields overridden settings.
    $this->drupalGet('<front>');
    $this->assertEquals(0, count($this->xpath('//div[contains(@class, "view-display-id-block_fields")]//table//td[contains(@class, "views-field-id")]')));
  }

  /**
   * Test ctools_views 'sort_fields' configuration.
   */
  public function testOrderFields() {
    $default_theme = $this->config('system.theme')->get('default');

    // Get the "Configure block" form for our Views block.
    $this->drupalGet('admin/structure/block/add/views_block:ctools_views_test_view-block_fields/' . $default_theme);
    $this->assertSession()->fieldValueEquals('edit-settings-override-order-fields-id-weight', 0);

    // Add block to sidebar_first region with default settings.
    $edit = [];
    $edit['region'] = 'sidebar_first';
    $edit['id'] = 'views_block__ctools_views_test_view_block_fields';
    $this->drupalGet('admin/structure/block/add/views_block:ctools_views_test_view-block_fields/' . $default_theme);
    $this->submitForm($edit, 'Save block');

    // Assert sort_fields default settings.
    $this->drupalGet('<front>');
    // Check that the td with class "views-field-id" is the first td in the
    // first tr element.
    $this->assertEquals(0, count($this->xpath('//div[contains(@class, "view-display-id-block_fields")]//table//tr[1]//td[contains(@class, "views-field-id")]/preceding-sibling::td')));

    // Override sort_fields settings.
    $edit = [];
    $edit['region'] = 'sidebar_first';
    $edit['settings[override][order_fields][name][weight]'] = -50;
    $edit['settings[override][order_fields][age][weight]'] = -49;
    $edit['settings[override][order_fields][job][weight]'] = -48;
    $edit['settings[override][order_fields][created][weight]'] = -47;
    $edit['settings[override][order_fields][id][weight]'] = -46;
    $edit['settings[override][order_fields][name_1][weight]'] = -45;
    $this->drupalGet('admin/structure/block/manage/views_block__ctools_views_test_view_block_fields');
    $this->submitForm($edit, 'Save block');

    $block = $this->storage->load('views_block__ctools_views_test_view_block_fields');
    $config = $block->getPlugin()->getConfiguration();
    $this->assertEquals(-46, $config['fields']['id']['weight'], "'sort_fields' setting is properly saved.");
    $this->assertEquals(-50, $config['fields']['name']['weight'], "'sort_fields' setting is properly saved.");

    // Assert sort_fields overridden settings.
    $this->drupalGet('<front>');

    // Check that the td with class "views-field-id" is the 5th td in the first
    // tr element.
    $this->assertEquals(4, count($this->xpath('//div[contains(@class, "view-display-id-block_fields")]//table//tr[1]//td[contains(@class, "views-field-id")]/preceding-sibling::td')));

    // Check that duplicate fields in the View produce expected output.
    $name1_element = $this->xpath('//div[contains(@class, "view-display-id-block_fields")]//table//tr[1]/td[contains(@class, "views-field-name")]');
    $name1 = $name1_element[0]->getText();
    $this->assertEquals('John', trim($name1));
    $name2_element = $this->xpath('//div[contains(@class, "view-display-id-block_fields")]//table//tr[1]/td[contains(@class, "views-field-name-1")]');
    $name2 = $name2_element[0]->getText();
    $this->assertEquals('John', trim($name2));
  }

  /**
   * Test ctools_views 'disable_filters' configuration.
   */
  public function testDisableFilters() {
    $default_theme = $this->config('system.theme')->get('default');

    // Get the "Configure block" form for our Views block.
    $this->drupalGet('admin/structure/block/add/views_block:ctools_views_test_view-block_filter/' . $default_theme);
    $this->assertSession()->fieldExists('edit-settings-override-filters-status-disable');
    $this->assertSession()->fieldExists('edit-settings-override-filters-job-disable');

    // Add block to sidebar_first region with default settings.
    $edit = [];
    $edit['region'] = 'sidebar_first';
    $edit['id'] = 'views_block__ctools_views_test_view_block_filter';
    $edit['settings[exposed][filter-status][exposed]'] = 1;
    $edit['settings[exposed][filter-job][exposed]'] = 1;
    $this->drupalGet('admin/structure/block/add/views_block:ctools_views_test_view-block_filter/' . $default_theme);
    $this->submitForm($edit, 'Save block');

    // Assert disable_filters default settings.
    $this->drupalGet('<front>');
    // Check that the default settings show both filters.
    $this->assertSession()->fieldExists('status');
    $this->assertSession()->fieldExists('job');

    // Override disable_filters settings.
    $edit = [];
    $edit['region'] = 'sidebar_first';
    $edit['settings[exposed][filter-status][exposed]'] = 1;
    $edit['settings[exposed][filter-job][exposed]'] = 1;
    $edit['settings[override][filters][status][disable]'] = 1;
    $edit['settings[override][filters][job][disable]'] = 1;
    $this->drupalGet('admin/structure/block/manage/views_block__ctools_views_test_view_block_filter');
    $this->submitForm($edit, 'Save block');

    $block = $this->storage->load('views_block__ctools_views_test_view_block_filter');
    $config = $block->getPlugin()->getConfiguration();
    $this->assertEquals(1, $config['filter']['status']['disable'], "'disable_filters' setting is properly saved.");
    $this->assertEquals(1, $config['filter']['job']['disable'], "'disable_filters' setting is properly saved.");

    // Assert disable_filters overridden settings.
    $this->drupalGet('<front>');
    $this->assertSession()->fieldNotExists('status');
    $this->assertSession()->fieldNotExists('job');
  }

  /**
   * Test ctools_views 'configure_sorts' configuration.
   */
  public function testConfigureSorts() {
    $default_theme = $this->config('system.theme')->get('default');

    // Get the "Configure block" form for our Views block.
    $this->drupalGet('admin/structure/block/add/views_block:ctools_views_test_view-block_sort/' . $default_theme);
    $this->assertSession()->fieldExists('settings[override][sort][id][order]');

    // Add block to sidebar_first region with default settings.
    $edit = [];
    $edit['region'] = 'sidebar_first';
    $edit['id'] = 'views_block__ctools_views_test_view_block_sort';
    $this->drupalGet('admin/structure/block/add/views_block:ctools_views_test_view-block_sort/' . $default_theme);
    $this->submitForm($edit, 'Save block');

    // Assert configure_sorts default settings.
    $this->drupalGet('<front>');
    // Check that the results are sorted ASC.
    $element = $this->xpath('//div[contains(@class, "view-display-id-block_sort")]//table//tr[1]/td[1]');
    $value = $element[0]->getText();
    $this->assertEquals('1', trim($value));

    // Override configure_sorts settings.
    $edit = [];
    $edit['region'] = 'sidebar_first';
    $edit['settings[override][sort][id][order]'] = 'DESC';
    $this->drupalGet('admin/structure/block/manage/views_block__ctools_views_test_view_block_sort');
    $this->submitForm($edit, 'Save block');

    $block = $this->storage->load('views_block__ctools_views_test_view_block_sort');
    $config = $block->getPlugin()->getConfiguration();
    $this->assertEquals('DESC', $config['sort']['id'], "'configure_sorts' setting is properly saved.");

    // Assert configure_sorts overridden settings.
    // Check that the results are sorted DESC.
    $this->drupalGet('<front>');
    $element = $this->xpath('//div[contains(@class, "view-display-id-block_sort")]//table//tr[1]/td[1]');
    $value = $element[0]->getText();
    $this->assertEquals('5', trim($value));
  }

}
