<?php

namespace Drupal\token_module_test\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\token\TokenInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for token module test.
 */
class TokenTreeBrowseController extends ControllerBase {

  /**
   * Service to retrieve token information.
   *
   * @var \Drupal\token\TokenInterface
   */
  protected $token;

  /**
   * The construct method.
   *
   * @param \Drupal\token\TokenInterface $token
   *   The token.
   */
  public function __construct(TokenInterface $token) {
    $this->token = $token;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('token')
    );
  }

  /**
   * Page callback to output a link.
   */
  public function outputLink(Request $request) {
    $build['tree']['#theme'] = 'token_tree_link';
    $build['tokenarea'] = [
      '#markup' => $this->token->replace('[current-page:title]'),
      '#type' => 'markup',
    ];
    return $build;
  }

  /**
   * Title callback for the page outputting a link.
   *
   * We are using a title callback instead of directly defining the title in the
   * routing YML file. This is so that we could return an array instead of a
   * simple string. This allows us to test if [current-page:title] works with
   * render arrays and other objects as titles.
   */
  public function getTitle() {
    return [
      '#type' => 'markup',
      '#markup' => 'Available Tokens',
    ];
  }

}
