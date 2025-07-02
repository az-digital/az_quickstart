<?php

namespace Drupal\webform_share\Element;

use Drupal\Core\Render\Element\RenderElement;
use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\Core\Url;
use Drupal\webform\Plugin\WebformSourceEntity\QueryStringWebformSourceEntity;

/**
 * Provides a render element that creates a <script> tag to share a webform.
 *
 * @RenderElement("webform_share_script")
 */
class WebformShareScript extends RenderElement implements TrustedCallbackInterface {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#webform' => NULL,
      '#source_entity' => NULL,
      '#query' => [],
      '#theme' => 'webform_share_script',
      '#pre_render' => [
        [$class, 'preRenderWebformShareScript'],
      ],
    ];
  }

  /**
   * Webform share iframe element pre render callback.
   */
  public static function preRenderWebformShareScript($element) {
    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = $element['#webform'];

    /** @var \Drupal\Core\Entity\EntityInterface $source_entity */
    $source_entity = $element['#source_entity'];

    $route_name = 'entity.webform.share_script';
    $route_parameters = ['webform' => $webform->id()];
    $route_options = QueryStringWebformSourceEntity::getRouteOptionsQuery($source_entity);
    // Append prepopulate and variant query to route options.
    if ($element['#query']) {
      $route_options += ['query' => []];
      $route_options['query'] += $element['#query'];
    }
    $url = Url::fromRoute($route_name, $route_parameters, $route_options);
    $script = preg_replace('#^https?:#', '', $url->setAbsolute()->toString());
    $element['#script'] = $script;

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return [
      'preRenderWebformShareScript',
    ];
  }

}
