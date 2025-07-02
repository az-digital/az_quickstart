<?php

namespace Drupal\Tests\better_exposed_filters\Kernel;

use Drupal\Tests\better_exposed_filters\Traits\BetterExposedFiltersTrait;
use Drupal\Tests\views\Kernel\ViewsKernelTestBase;
use Drupal\views\ViewExecutable;

/**
 * Defines a base class for Better Exposed Filters kernel testing.
 */
abstract class BetterExposedFiltersKernelTestBase extends ViewsKernelTestBase {

  use BetterExposedFiltersTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'field',
    'views',
    'node',
    'filter',
    'options',
    'text',
    'taxonomy',
    'better_exposed_filters',
    'bef_test',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE): void {
    parent::setUp();

    $this->installSchema('node', ['node_access']);

    $this->installEntitySchema('node');
    $this->installEntitySchema('taxonomy_vocabulary');
    $this->installEntitySchema('taxonomy_term');

    \Drupal::moduleHandler()->loadInclude('bef_test', 'install');
    bef_test_install();

    $this->installConfig(['system', 'field', 'node', 'taxonomy', 'bef_test']);
  }

  /**
   * Gets the render array for the views exposed form.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   The view object.
   *
   * @return array
   *   The render array.
   */
  public function getExposedFormRenderArray(ViewExecutable $view) {
    $this->executeView($view);
    $exposed_form = $view->display_handler->getPlugin('exposed_form');
    return $exposed_form->renderExposedForm();
  }

  /**
   * Renders the views exposed form.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   The view object.
   */
  protected function renderExposedForm(ViewExecutable $view) {
    $output = $this->getExposedFormRenderArray($view);
    $this->setRawContent(\Drupal::service('renderer')->renderRoot($output));
  }

}
