<?php

namespace Drupal\webform_test_element\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides route responses for webform test element.
 */
class WebformTestElementController extends ControllerBase {

  /**
   * Returns the webform test element page.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return array
   *   A render array containing a webform.
   */
  public function index(Request $request) {
    $build = [];

    // Render the contact form.
    $build['webform'] = [
      '#type' => 'webform',
      '#webform' => 'contact',
    ];

    // Populate webform properties using query string parameters.
    $properties = ['sid', 'default_data', 'information', 'action', 'lazy'];
    foreach ($properties as $property) {
      if ($value = $request->query->get($property)) {
        switch ($value) {
          case 'false':
            $value = FALSE;
            break;
        }
        $build['webform']["#$property"] = $value;
      }
    }

    // Add query args to cache context.
    $build['#cache']['contexts'][] = 'url.query_args';

    return $build;
  }

}
