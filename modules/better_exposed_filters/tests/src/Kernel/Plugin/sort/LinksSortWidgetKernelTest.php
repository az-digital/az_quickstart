<?php

namespace Drupal\Tests\better_exposed_filters\Kernel\Plugin\sort;

use Drupal\Tests\better_exposed_filters\Kernel\BetterExposedFiltersKernelTestBase;
use Drupal\views\Views;

/**
 * Tests the links sort widget (i.e. "bef_links").
 *
 * @group better_exposed_filters
 *
 * @see \Drupal\better_exposed_filters\Plugin\better_exposed_filters\sort\Links
 */
class LinksSortWidgetKernelTest extends BetterExposedFiltersKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $testViews = ['bef_test'];

  /**
   * Tests the exposed links sort widget.
   */
  public function testExposedLinks() {
    $view = Views::getView('bef_test');
    $display = &$view->storage->getDisplay('default');

    // Change exposed sort to links (i.e. 'bef_links').
    $this->setBetterExposedOptions($view, [
      'sort' => [
        'plugin_id' => 'bef_links',
      ],
    ]);

    // Render the exposed form.
    $this->renderExposedForm($view);

    // Check our sort item "sort_by" is rendered as links.
    $actual = $this->xpath('//form//a[starts-with(@id, "edit-sort-by")]');
    $this->assertCount(1, $actual, 'Exposed sort "sort_by" has correct number of exposed links.');

    // Check our sort item "sort_order" is rendered as links.
    $actual = $this->xpath('//form//a[starts-with(@id, "edit-sort-order")]');
    $this->assertCount(2, $actual, 'Exposed sort "sort_order" has correct number of exposed links.');

    $view->destroy();
  }

}
