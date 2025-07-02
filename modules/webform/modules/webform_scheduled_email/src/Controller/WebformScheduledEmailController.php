<?php

namespace Drupal\webform_scheduled_email\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\webform\WebformInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Provides route responses for webform scheduled email.
 */
class WebformScheduledEmailController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The webform scheduled email manager.
   *
   * @var \Drupal\webform_scheduled_email\WebformScheduledEmailManagerInterface
   */
  protected $manager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->manager = $container->get('webform_scheduled_email.manager');
    return $instance;
  }

  /**
   * Runs cron task for webform scheduled email handler.
   *
   * @param \Drupal\webform\WebformInterface $webform
   *   The webform containing a scheduled email handler.
   * @param string|null $handler_id
   *   A webform handler id.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirection to the webform handlers page.
   */
  public function cron(WebformInterface $webform, $handler_id) {
    $stats = $this->manager->cron($webform, $handler_id);
    $this->messenger()->addStatus($this->t($stats['_message'], $stats['_context']));
    return new RedirectResponse($webform->toUrl('handlers')->toString());
  }

}
