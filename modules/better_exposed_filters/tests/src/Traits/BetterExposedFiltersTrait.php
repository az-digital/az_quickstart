<?php

namespace Drupal\Tests\better_exposed_filters\Traits;

use Drupal\Component\Utility\NestedArray;
use Drupal\views\ViewExecutable;

/**
 * Makes Drupal's test API forward compatible with multiple versions of PHPUnit.
 */
trait BetterExposedFiltersTrait {

  /**
   * Returns the configured BEF options.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   The view object.
   * @param string $display_id
   *   The display ID.
   *
   * @return array
   *   Array of BEF options.
   */
  protected function &getBetterExposedOptions(ViewExecutable $view, string $display_id): array {
    return $view->storage->getDisplay($display_id)['display_options']['exposed_form']['options']['bef'];
  }

  /**
   * Merges options into existing BEF configuration.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   The view object.
   * @param array $options
   *   The list of options (e.g. ['sort' => ['plugin_id' => 'default']]).
   * @param string $display_id
   *   Display ID to use.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   In case of failures an exception is thrown.
   */
  protected function setBetterExposedOptions(ViewExecutable $view, array $options, string $display_id = 'default'): void {
    $bef_options = &$this->getBetterExposedOptions($view, $display_id);
    $bef_options = NestedArray::mergeDeep($bef_options, $options);

    $view->storage->save();
  }

}
