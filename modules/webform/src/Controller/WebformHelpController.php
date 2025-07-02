<?php

namespace Drupal\webform\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides route responses for Webform help.
 */
class WebformHelpController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The webform help manager.
   *
   * @var \Drupal\webform\WebformHelpManagerInterface
   */
  protected $help;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->help = $container->get('webform.help_manager');
    return $instance;
  }

  /**
   * Returns the Webform help page.
   *
   * @return array
   *   The webform submission webform.
   */
  public function index() {
    return $this->help->buildIndex();
  }

}
