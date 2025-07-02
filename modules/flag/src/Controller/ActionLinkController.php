<?php

namespace Drupal\flag\Controller;

use Drupal\Component\Utility\Html;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\flag\Ajax\ActionLinkFlashCommand;
use Drupal\flag\FlagInterface;
use Drupal\flag\FlagServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Controller responses to flag and unflag action links.
 *
 * The response is a set of AJAX commands to update the
 * link in the page.
 */
class ActionLinkController implements ContainerInjectionInterface {
  /**
   * The flag service.
   *
   * @var \Drupal\flag\FlagServiceInterface
   */
  protected $flagService;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructor.
   *
   * @param \Drupal\flag\FlagServiceInterface $flag
   *   The flag service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   */
  public function __construct(FlagServiceInterface $flag, RendererInterface $renderer) {
    $this->flagService = $flag;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('flag'),
      $container->get('renderer')
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
   *   The flaggable entity view mode.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse|null
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

    return $this->generateResponse($flag, $entity, $flag->getMessage('flag'), $view_mode);
  }

  /**
   * Performs a unflagging when called via a route.
   *
   * @param \Drupal\flag\FlagInterface $flag
   *   The flag entity.
   * @param int $entity_id
   *   The flaggable entity ID.
   * @param string $view_mode
   *   The flaggable entity view mode.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse|null
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

    return $this->generateResponse($flag, $entity, $flag->getMessage('unflag'), $view_mode);
  }

  /**
   * Generates a response after the flag has been updated.
   *
   * @param \Drupal\flag\FlagInterface $flag
   *   The flag entity.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity object.
   * @param string $message
   *   (optional) The message to flash.
   * @param string $view_mode
   *   The flaggable entity view mode.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The response object.
   */
  private function generateResponse(FlagInterface $flag, EntityInterface $entity, $message, ?string $view_mode = NULL) {
    // Create a new AJAX response.
    $response = new AjaxResponse();

    // Get the link type plugin.
    $link_type = $flag->getLinkTypePlugin();

    // Generate the link render array.
    $link = $link_type->getAsFlagLink($flag, $entity, $view_mode);

    // Generate a CSS selector to use in a JQuery Replace command.
    $selector = '.js-flag-' . Html::cleanCssIdentifier($flag->id()) . '-' . $entity->id();

    // Create a new JQuery Replace command to update the link display.
    $replace = new ReplaceCommand($selector, $this->renderer->renderInIsolation($link));
    $response->addCommand($replace);

    // Put the focus back on the link.
    $focus = new InvokeCommand($selector . '>a', 'focus');
    $response->addCommand($focus);

    // Push a message pulsing command onto the stack.
    $pulse = new ActionLinkFlashCommand($selector, $message);
    $response->addCommand($pulse);

    return $response;
  }

}
