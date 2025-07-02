<?php

namespace Drupal\webform_ui\PathProcessor;

use Drupal\Core\PathProcessor\OutboundPathProcessorInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Symfony\Component\HttpFoundation\Request;

/**
 * Path processor for webform UI.
 */
class WebformUiPathProcessor implements OutboundPathProcessorInterface {

  /**
   * {@inheritdoc}
   */
  public function processOutbound($path, &$options = [], Request $request = NULL, BubbleableMetadata $bubbleable_metadata = NULL) {

    $is_webform_path = (!empty($path) && str_contains($path, '/webform/'));
    $has_query_string = (!empty($request) && !empty($request->getQueryString()));

    if (!$is_webform_path || !$has_query_string) {
      return $path;
    }

    if (!str_contains($request->getQueryString(), '_wrapper_format=')) {
      return $path;
    }

    $query = [];
    parse_str($request->getQueryString(), $query);
    if (empty($query['destination'])) {
      return $path;
    }

    $destination = $query['destination'];
    $options['query']['destination'] = $destination;
    return $path;
  }

}
