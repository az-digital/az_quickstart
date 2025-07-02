<?php

namespace Drupal\Tests\better_exposed_filters\Kernel\Plugin\filter;

use Drupal\Tests\better_exposed_filters\Kernel\BetterExposedFiltersKernelTestBase;
use Drupal\views\Views;

/**
 * Tests the links filter widget (i.e. "bef_links").
 *
 * @group better_exposed_filters
 *
 * @see \Drupal\better_exposed_filters\Plugin\better_exposed_filters\filter\Links
 */
class LinksFilterWidgetKernelTest extends BetterExposedFiltersKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $testViews = ['bef_test'];

  /**
   * Tests the exposed links filter widget.
   */
  public function testExposedLinks() {
    $view = Views::getView('bef_test');
    $display = &$view->storage->getDisplay('default');

    // Ensure our filter "term_node_tid_depth" has show hierarchy enabled.
    $display['display_options']['filters']['term_node_tid_depth']['hierarchy'] = TRUE;

    // Change exposed filter "field_bef_integer" and "term_node_tid_depth" to
    // links (i.e. 'bef_links').
    $this->setBetterExposedOptions($view, [
      'filter' => [
        'field_bef_integer_value' => [
          'plugin_id' => 'bef_links',
        ],
        'term_node_tid_depth' => [
          'plugin_id' => 'bef_links',
        ],
      ],
    ]);

    // Render the exposed form.
    $this->renderExposedForm($view);

    // Check our "FIELD_BEF_INTEGER" filter is rendered as links.
    $actual = $this->xpath('//form//a[starts-with(@name, "field_bef_integer_value")]');
    $this->assertCount(6, $actual, 'Exposed filter "FIELD_BEF_INTEGER" has correct number of exposed links.');

    // Check our "TERM_NODE_TID_DEPTH" filter is rendered as nested links.
    $actual = $this->xpath("//form//div[contains(concat(' ',normalize-space(@class),' '),' bef-nested ')]");
    $this->assertCount(1, $actual, 'Exposed filter "TERM_NODE_TID_DEPTH" has bef-nested class');

    $actual = $this->xpath('//form//div[@id="edit-term-node-tid-depth--2"]/ul/li/a[starts-with(@name, "term_node_tid_depth")]');
    $this->assertCount(4, $actual, 'Exposed filter "TERM_NODE_TID_DEPTH" has correct number of exposed top-level links.');

    $actual = $this->xpath('//form//div[@id="edit-term-node-tid-depth--2"]/ul/li/ul/li/a[starts-with(@name, "term_node_tid_depth")]');
    $this->assertCount(5, $actual, 'Exposed filter "TERM_NODE_TID_DEPTH" has correct number of exposed second-level links.');

    $actual = $this->xpath('//form//div[@id="edit-term-node-tid-depth--2"]/ul/li/ul/li/ul/li/a[starts-with(@name, "term_node_tid_depth")]');
    $this->assertCount(14, $actual, 'Exposed filter "TERM_NODE_TID_DEPTH" has correct number of exposed third-level links.');

    $view->destroy();
  }

}
