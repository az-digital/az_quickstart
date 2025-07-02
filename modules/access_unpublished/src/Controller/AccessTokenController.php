<?php

namespace Drupal\access_unpublished\Controller;

use Drupal\access_unpublished\Entity\AccessToken;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for the access token handling.
 */
class AccessTokenController extends ControllerBase {

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $controller = parent::create($container);
    $controller->setTime($container->get('datetime.time'));
    return $controller;
  }

  /**
   * Set the time service.
   *
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   */
  protected function setTime(TimeInterface $time) {
    $this->time = $time;
  }

  /**
   * Renews an access token.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   * @param \Drupal\access_unpublished\Entity\AccessToken $access_token
   *   The access token to renew.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   A replace command to replace the token table.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function renew(Request $request, AccessToken $access_token) {
    // Calculate lifetime of expired token.
    $expire = $access_token->get('expire')->value - $access_token->getChangedTime();
    if ($expire > 0) {
      $expire += $this->time->getRequestTime();
    }
    else {
      $expire = AccessToken::defaultExpiration();
    }
    $access_token->set('expire', $expire);
    $access_token->save();

    return $this->buildResponse($request, $access_token);
  }

  /**
   * Deletes an access token.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   * @param \Drupal\access_unpublished\Entity\AccessToken $access_token
   *   The access token to delete.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   A replace command to replace the token table.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function delete(Request $request, AccessToken $access_token) {
    $access_token->delete();

    return $this->buildResponse($request, $access_token);
  }

  /**
   * Builds a response object.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   * @param \Drupal\access_unpublished\Entity\AccessToken $access_token
   *   The access token to delete.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   A replace command to replace the token table.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  protected function buildResponse(Request $request, AccessToken $access_token) {
    $handler_name = $request->query->has('handler') ? $request->query->get('handler') : 'list_builder';

    /** @var \Drupal\Core\Entity\EntityListBuilder $list_builder */
    $list_builder = $this->entityTypeManager()->getHandler('access_token', $handler_name);
    $form = $list_builder->render($access_token->getHost());

    $response = new AjaxResponse();
    $response->addCommand(new ReplaceCommand('[data-drupal-selector="access-token-list"]', $form['table']));

    return $response;
  }

}
