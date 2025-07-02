<?php

namespace Drupal\flag\Plugin\ActionLink;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\flag\FlagInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides the AJAX link type.
 *
 * This class is an extension of the Reload link type, but modified to
 * provide AJAX links.
 *
 * @ActionLinkType(
 *   id = "ajax_link",
 *   label = @Translation("AJAX link"),
 *   description = @Translation("An AJAX JavaScript request will be made without reloading the page.")
 * )
 */
class AJAXactionLink extends Reload {

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * Build a new link type instance and sets the configuration.
   *
   * @param array $configuration
   *   The configuration array with which to initialize this plugin.
   * @param string $plugin_id
   *   The ID with which to initialize this plugin.
   * @param array $plugin_definition
   *   The plugin definition array.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request from the request stack.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, AccountInterface $current_user, Request $request) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $current_user);
    $this->request = $request;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_user'),
      $container->get('request_stack')->getCurrentRequest()
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getDestination() {
    if ($destination = $this->request->query->get('destination')) {
      // Workaround the default behavior so we keep the GET[destination] value
      // no matter how many times the flag is clicked.
      return $destination;
    }
    return parent::getDestination();
  }

  /**
   * {@inheritdoc}
   */
  public function getAsFlagLink(FlagInterface $flag, EntityInterface $entity, ?string $view_mode = NULL): array {
    $build = parent::getAsFlagLink($flag, $entity, $view_mode);
    $build['#attached']['library'][] = 'flag/flag.link_ajax';
    $build['#attributes']['class'][] = 'use-ajax';
    return $build;

  }

}
