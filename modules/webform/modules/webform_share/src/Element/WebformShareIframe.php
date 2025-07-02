<?php

namespace Drupal\webform_share\Element;

use Drupal\Core\Render\Element\RenderElement;
use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\Core\Url;
use Drupal\webform\Plugin\WebformSourceEntity\QueryStringWebformSourceEntity;

/**
 * Provides a render element that createa an iframe to share a webform.
 *
 * @RenderElement("webform_share_iframe")
 */
class WebformShareIframe extends RenderElement implements TrustedCallbackInterface {

  /**
   * The JavaScript iframe library.
   */
  const LIBRARY = 'iframe-resizer';

  /**
   * The JavaScript iframe library version.
   */
  const VERSION = '4.2.10';

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#webform' => NULL,
      '#source_entity' => NULL,
      '#javascript' => FALSE,
      '#script' => '//cdn.jsdelivr.net/gh/davidjbradshaw/iframe-resizer@' . static::VERSION . '/js/iframeResizer.min.js',
      '#query' => [],
      '#options' => [],
      '#test' => [],
      '#theme' => 'webform_share_iframe',
      '#pre_render' => [
        [$class, 'preRenderWebformShareIframe'],
      ],
    ];
  }

  /**
   * Webform share iframe element pre render callback.
   */
  public static function preRenderWebformShareIframe($element) {
    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = $element['#webform'];

    /** @var \Drupal\Core\Entity\EntityInterface $source_entity */
    $source_entity = $element['#source_entity'];

    // Get iframe src route information and attributes.
    if ($element['#javascript']) {
      // Set JavaScript iframe.
      // @see https://github.com/davidjbradshaw/iframe-resizer
      $route_name = 'entity.webform.share_page.javascript';
      $route_parameters = [
        'webform' => $webform->id(),
        'library' => static::LIBRARY,
        'version' => static::VERSION,
      ];
      $attributes = [
        'style' => 'width:1px;min-width:100%',
      ];
    }
    else {
      // Set static iframe.
      $route_name = 'entity.webform.share_page';
      $route_parameters = [
        'webform' => $webform->id(),
      ];
      $attributes = [
        'style' => 'width:100%;height:600px;border:none',
      ];
    }

    $route_options = QueryStringWebformSourceEntity::getRouteOptionsQuery($source_entity);
    $route_options += ['query' => []];
    // Append prepopulate and variant query to route options.
    if ($element['#query']) {
      $route_options['query'] += $element['#query'];
    }
    // Append ?_webform_test={webform} to route options.
    if ($element['#test']) {
      $route_options['query']['_webform_test'] = $webform->id();
    }
    if (empty($route_options['query'])) {
      unset($route_options['query']);
    }

    // Get iframe URL.
    $url = Url::fromRoute($route_name, $route_parameters, $route_options);

    // Get iframe src and title.
    $src = preg_replace('#^https?:#', '', $url->setAbsolute()->toString());
    $title = $webform->label() . ' | ' . \Drupal::config('system.site')->get('name');

    $element += ['#attributes' => []];
    $element['#attributes'] += [
      'src' => $src,
      'title' => $title,
      'class' => [],
      'frameborder' => '0',
      'allow' => 'geolocation; microphone; camera',
      'allowtransparency' => 'true',
      'allowfullscreen' => 'true',
    ] + $attributes;

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return [
      'preRenderWebformShareIframe',
    ];
  }

}
