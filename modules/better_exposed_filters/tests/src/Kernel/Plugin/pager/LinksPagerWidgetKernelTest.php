<?php

namespace Drupal\Tests\better_exposed_filters\Kernel\Plugin\pager;

use Drupal\Tests\better_exposed_filters\Kernel\BetterExposedFiltersKernelTestBase;
use Drupal\views\Views;

/**
 * Tests the links pager widget (i.e. "bef").
 *
 * @group better_exposed_filters
 *
 * @see \Drupal\better_exposed_filters\Plugin\better_exposed_filters\pager\RadioButtons
 */
class LinksPagerWidgetKernelTest extends BetterExposedFiltersKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $testViews = ['bef_test'];

  /**
   * Tests the exposed links pager widget.
   */
  public function testExposedLinks() {
    $view = Views::getView('bef_test');
    $display = &$view->storage->getDisplay('default');

    // Ensure our pager exposes all items (i.e. items_per_page and offset).
    $display['display_options']['pager']['options']['expose']['items_per_page'] = TRUE;
    $display['display_options']['pager']['options']['expose']['offset'] = TRUE;

    // Change exposed pager to radio buttons (i.e. 'bef').
    $this->setBetterExposedOptions($view, [
      'pager' => [
        'plugin_id' => 'bef_links',
      ],
    ]);

    // Render the exposed form.
    $this->renderExposedForm($view);

    // Check our pager item "items_per_page" is rendered as links.
    $actual = $this->xpath('//form//a[starts-with(@name, "items_per_page")]');
    $this->assertCount(4, $actual, 'Exposed pager "items_per_page" has correct number of exposed radio buttons.');

    $view->destroy();
  }

}
