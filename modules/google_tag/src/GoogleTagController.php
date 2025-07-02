<?php

namespace Drupal\google_tag;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\HtmlEntityFormController;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\google_tag\Entity\TagContainer;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for proper single-to-many configuration objects.
 */
class GoogleTagController extends HtmlEntityFormController implements ContainerInjectionInterface {

  /**
   * The Google Tag default entity.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  private EntityInterface $googleTagEntity;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = new self(
      $container->get('http_kernel.controller.argument_resolver'),
      $container->get('form_builder'),
      $container->get('entity_type.manager'),
    );
    $instance->configFactory = $container->get('config.factory');
    return $instance;
  }

  /**
   * Fetches the correct route based on single vs multiple config entities.
   */
  public function tagEditForm(Request $request, RouteMatchInterface $route_match) {
    // See if there is a default entity.
    // @todo Contemplate if dependency injection is needed here.
    $entity_type_id = 'google_tag_container';
    $tag_entity = $this->configFactory->get('google_tag.settings')->get('default_google_tag_entity');
    $tag_entities = $this->entityTypeManager->getStorage($entity_type_id)->loadMultipleOverrideFree();
    // Only one Google Tag exists, load it.
    // @todo There might be a better logic path here.
    if ($tag_entity && isset($tag_entities[$tag_entity])) {
      $this->googleTagEntity = $tag_entities[$tag_entity];
    }
    return $this->getContentResult($request, $route_match);
  }

  /**
   * Enables a tag container object.
   *
   * @param \Drupal\google_tag\Entity\TagContainer $google_tag_container
   *   The tag container object to enable.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect response to the google_tag_container listing page.
   */
  public function enable(TagContainer $google_tag_container) {
    $google_tag_container->enable()->save();
    return new RedirectResponse($google_tag_container->toUrl('collection', ['absolute' => TRUE])->toString());
  }

  /**
   * Disables a tag container object.
   *
   * @param \Drupal\google_tag\Entity\TagContainer $google_tag_container
   *   The tag container object to disable.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect response to the google_tag_container listing page.
   */
  public function disable(TagContainer $google_tag_container) {
    $google_tag_container->disable()->save();
    return new RedirectResponse($google_tag_container->toUrl('collection', ['absolute' => TRUE])->toString());
  }

  /**
   * {@inheritDoc}
   */
  protected function getFormObject(RouteMatchInterface $route_match, $form_arg) {
    // If no operation is provided, use 'default'.
    $form_arg .= '.default';
    [$entity_type_id, $operation] = explode('.', $form_arg);

    $form_object = $this->entityTypeManager->getFormObject($entity_type_id, $operation);
    if (isset($this->googleTagEntity)) {
      $form_object->setEntity($this->googleTagEntity);
    }
    else {
      // Allow the entity form to determine the entity object from a given route
      // match.
      $entity = $form_object->getEntityFromRouteMatch($route_match, $entity_type_id);
      $form_object->setEntity($entity);
    }
    return $form_object;
  }

  /**
   * Allows access to the default Entity Add Form, when using containers.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function addContainerAccess(AccountInterface $account) {
    // Check permissions and combine that with any custom access checking
    // needed. Pass forward parameters from the route and/or request as needed.
    // @phpstan-ignore-next-line
    $use_collection = \Drupal::config('google_tag.settings')->get('use_collection');
    if (!$use_collection) {
      return AccessResult::forbidden();
    }
    return AccessResult::allowedIfHasPermission(
      $account,
      $this->entityTypeManager->getDefinition('google_tag_container')->getAdminPermission()
    );
  }

  /**
   * Used for Accessing the tag container Listing page.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function containerListingAccess(AccountInterface $account) {
    // Check permissions and combine that with any custom access checking
    // needed. Pass forward parameters from the route and/or request as needed.
    // @phpstan-ignore-next-line
    $use_collection = \Drupal::config('google_tag.settings')->get('use_collection');
    if (!$use_collection) {
      return AccessResult::forbidden();
    }
    return AccessResult::allowedIfHasPermission(
      $account,
      $this->entityTypeManager->getDefinition('google_tag_container')->getAdminPermission()
    );
  }

}
