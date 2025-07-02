<?php

namespace Drupal\flag\Controller;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Url;
use Drupal\flag\FlagInterface;
use Drupal\flag\FlagServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Returns nojs responses to flag and unflag action links.
 *
 * "nojs" is when the user agent has javascript disabled the
 * behaviour reverts to that of a normal link.
 *
 * After an update the response to a valid request is a redirect to the entity
 * with drupal update message.
 */
class ActionLinkNoJsController implements ContainerInjectionInterface {
  /**
   * The flag service.
   *
   * @var \Drupal\flag\FlagServiceInterface
   */
  protected $flagService;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructor.
   *
   * @param \Drupal\flag\FlagServiceInterface $flag
   *   The flag service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   */
  public function __construct(FlagServiceInterface $flag, MessengerInterface $messenger) {
    $this->flagService = $flag;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('flag'),
      $container->get('messenger')
    );
  }

  /**
   * Performs a flagging when called via a route.
   *
   * @param \Drupal\flag\FlagInterface $flag
   *   The flag entity.
   * @param int $entity_id
   *   The flaggable entity ID.
   * @param string $view_mode
   *   The flaggable entity view mode. Note, that this parameter isn't actually
   *   used, but needed to match the route definition.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse|null
   *   The response object, only if successful.
   *
   * @see \Drupal\flag\Plugin\Reload
   */
  public function flag(FlagInterface $flag, $entity_id, ?string $view_mode = NULL) {
    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    $entity = $this->flagService->getFlaggableById($flag, $entity_id);

    if ($entity === NULL) {
      throw new NotFoundHttpException();
    }

    try {
      $this->flagService->flag($flag, $entity);
    }
    catch (\LogicException $e) {
      // Fail silently so we return to the entity, which will show an updated
      // link for the existing state of the flag.
    }

    return $this->generateResponse($entity, $flag->getMessage('flag'));
  }

  /**
   * Performs a unflagging when called via a route.
   *
   * @param \Drupal\flag\FlagInterface $flag
   *   The flag entity.
   * @param int $entity_id
   *   The flaggable entity ID.
   * @param string $view_mode
   *   The flaggable entity view mode. Note, that this parameter isn't actually
   *   used, but needed to match the route definition.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse|null
   *   The response object, only if successful.
   *
   * @see \Drupal\flag\Plugin\Reload
   */
  public function unflag(FlagInterface $flag, $entity_id, ?string $view_mode = NULL) {
    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    $entity = $this->flagService->getFlaggableById($flag, $entity_id);

    if ($entity === NULL) {
      throw new NotFoundHttpException();
    }

    try {
      $this->flagService->unflag($flag, $entity);
    }
    catch (\LogicException $e) {
      // Fail silently so we return to the entity, which will show an updated
      // link for the existing state of the flag.
    }

    return $this->generateResponse($entity, $flag->getMessage('unflag'));
  }

  /**
   * Generates a response after the flag has been updated.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity object.
   * @param string $message
   *   The message to display.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   The response object.
   */
  private function generateResponse(EntityInterface $entity, $message) {
    if (!empty($message)) {
      $this->messenger->addMessage($message);
    }
    if ($entity->hasLinkTemplate('canonical')) {
      // Redirect back to the entity. A passed in destination query parameter
      // will automatically override this.
      $url_info = $entity->toUrl();

      $options['absolute'] = TRUE;
      $url = Url::fromRoute($url_info->getRouteName(), $url_info->getRouteParameters(), $options);
      $response = new RedirectResponse($url->toString());
    }
    else {
      // For entities that don't have a canonical URL (like paragraphs),
      // redirect to the front page.
      $front = Url::fromRoute('<front>');
      $response = new RedirectResponse($front->toString());
    }

    return $response;
  }

}
