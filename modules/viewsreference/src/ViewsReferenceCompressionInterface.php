<?php

namespace Drupal\viewsreference;

use Drupal\views\ViewExecutable;

/**
 * Defines an interface for the views reference compression service.
 */
interface ViewsReferenceCompressionInterface {

  /**
   * Compress the views reference configuration.
   *
   * @param array $viewsreference
   *   The Views Reference configuration.
   * @param \Drupal\views\ViewExecutable $view
   *   The View being pre-rendered.
   *
   * @return array
   *   The updated Views Reference configuration.
   */
  public function compress(array $viewsreference, ViewExecutable $view): array;

  /**
   * Un-compress the views reference configuration.
   *
   * @param array $viewsreference
   *   The Views Reference configuration.
   * @param \Drupal\views\ViewExecutable $view
   *   The View being pre-viewed.
   *
   * @return array
   *   The updated Views Reference configuration.
   */
  public function uncompress(array $viewsreference, ViewExecutable $view): array;

}
