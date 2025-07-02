<?php

declare(strict_types=1);

namespace Drupal\views_remote_data\Plugin\views\cache;

use Drupal\Component\Utility\Crypt;

/**
 * Provides a trait to use in Views cache plugins for remote data queries.
 *
 * Inspired by the implementation in search_api.
 *
 * @see https://git.drupalcode.org/project/search_api/-/blob/8.x-1.x/src/Plugin/views/cache/SearchApiCachePluginTrait.php
 */
trait RemoteDataCachePluginTrait {

  /**
   * {@inheritdoc}
   */
  public function generateResultsKey(): string {
    if (!isset($this->resultsKey)) {
      $build_info = $this->view->build_info;

      $key_data = [
        'build_info' => $build_info,
        'pager' => [
          'page' => $this->view->getCurrentPage(),
          'items_per_page' => $this->view->getItemsPerPage(),
          'offset' => $this->view->getOffset(),
        ],
      ];
      $key_data += \Drupal::service('cache_contexts_manager')->convertTokensToKeys($this->displayHandler->getCacheMetadata()->getCacheContexts())->getKeys();
      $this->resultsKey = $this->view->storage->id() . ':' . $this->displayHandler->display['id'] . ':results:' . Crypt::hashBase64(serialize($key_data));
    }

    return $this->resultsKey;
  }

}
