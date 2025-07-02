<?php

namespace Drupal\viewsreference;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\UrlHelper;
use Drupal\views\ViewExecutable;

/**
 * Provides the views reference compression service.
 *
 * This service compresses the Views Reference settings prior to render, and
 * decompresses them again prior to reloading the View.
 */
class ViewsReferenceCompression implements ViewsReferenceCompressionInterface {

  /**
   * {@inheritdoc}
   */
  public function compress(array $viewsreference, ViewExecutable $view): array {
    // Compress the Views Reference Field settings as a JSON String to help
    // avoid 414 errors with the request URI being too long.
    $json = Json::encode($viewsreference);
    return [
      'compressed' => UrlHelper::compressQueryParameter($json),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function uncompress(array $viewsreference, ViewExecutable $view): array {
    if (isset($viewsreference['compressed'])) {
      // Un-compress the Views Reference Field settings if they are still
      // compressed.
      $json = UrlHelper::uncompressQueryParameter($viewsreference['compressed']);
      $viewsreference = Json::decode($json);
    }
    return $viewsreference;
  }

}
