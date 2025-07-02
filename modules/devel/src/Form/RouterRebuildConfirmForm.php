<?php

namespace Drupal\devel\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteBuilderInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides confirmation form for rebuilding the routes.
 */
class RouterRebuildConfirmForm extends ConfirmFormBase {

  /**
   * The route builder service.
   */
  protected RouteBuilderInterface $routeBuilder;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    $instance = parent::create($container);
    $instance->routeBuilder = $container->get('router.builder');
    $instance->messenger = $container->get('messenger');
    $instance->stringTranslation = $container->get('string_translation');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'devel_menu_rebuild';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to rebuild the router?');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl(): Url {
    return new Url('<front>');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('Rebuilds the routes information gathering all routing data from .routing.yml files and from classes which subscribe to the route build events. This action cannot be undone.');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Rebuild');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->routeBuilder->rebuild();
    $this->messenger->addMessage($this->t('The router has been rebuilt.'));
    $form_state->setRedirect('<front>');
  }

}
