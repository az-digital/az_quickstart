<?php

namespace Drupal\webform_options_limit\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\webform_options_limit\Plugin\WebformOptionsLimitHandlerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides route responses for webform options limit.
 */
class WebformOptionsLimitController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The webform request handler.
   *
   * @var \Drupal\webform\WebformRequestInterface
   */
  protected $requestHandler;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->requestHandler = $container->get('webform.request');
    return $instance;
  }

  /**
   * Returns the Webform submission export example CSV view.
   */
  public function index() {
    $webform = $this->requestHandler->getCurrentWebform();
    $source_entity = $this->requestHandler->getCurrentSourceEntity(['webform']);

    $build = [];

    $handlers = $webform->getHandlers();
    foreach ($handlers as $handler) {
      if ($handler instanceof WebformOptionsLimitHandlerInterface) {
        $handler->setSourceEntity($source_entity);
        $build[$handler->getHandlerId()] = $handler->buildSummaryTable();
        $build[$handler->getHandlerId()]['#suffix'] = '<br/><br/>';
      }
    }

    return $build;
  }

}
