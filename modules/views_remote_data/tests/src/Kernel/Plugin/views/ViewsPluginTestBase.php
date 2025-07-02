<?php

declare(strict_types=1);

namespace Drupal\Tests\views_remote_data\Kernel\Plugin\views;

use Prophecy\PhpUnit\ProphecyTrait;
use Drupal\Tests\views_remote_data\Kernel\ViewsRemoteDataTestBase;
use Drupal\views\Plugin\views\display\DisplayPluginInterface;
use Drupal\views\Plugin\views\display\Embed;
use Drupal\views\Plugin\views\pager\Mini;
use Drupal\views\Plugin\views\style\DefaultStyle;
use Drupal\views\ViewExecutable;

/**
 * Base test class for views plugin testing.
 */
abstract class ViewsPluginTestBase extends ViewsRemoteDataTestBase {

  use ProphecyTrait;
  /**
   * Creates a mocked view executable.
   *
   * @return \Drupal\views\ViewExecutable
   *   The view.
   */
  protected function createViewExecutable(): ViewExecutable {
    $pager = $this->container
      ->get('plugin.manager.views.pager')
      ->createInstance('mini');
    self::assertInstanceOf(Mini::class, $pager);

    $display_handler = $this->prophesize(DisplayPluginInterface::class);

    $view = $this->prophesize(ViewExecutable::class);
    $view->id()->willReturn('test_view_id');
    $view->display_handler = $display_handler->reveal();
    $view->initPager()->willReturn();
    $view->getStyle()->willReturn($this->prophesize(DefaultStyle::class)->reveal());
    $view->pager = $pager;
    $revealed = $view->reveal();
    $pager->init($revealed, $this->prophesize(Embed::class)->reveal());
    return $revealed;
  }

}
