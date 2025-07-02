<?php

namespace Drupal\role_delegation\Plugin\views\field;

use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Routing\ResettableStackedRouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\Plugin\views\field\UserBulkForm;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ViewExecutable;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a user operations bulk form element.
 *
 * @ViewsField("role_delegation_user_bulk_form")
 */
class RoleDelegationUserBulkForm extends UserBulkForm {

  /**
   * The currently logged in user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, LanguageManagerInterface $language_manager, MessengerInterface $messenger, EntityRepositoryInterface $entity_repository, ResettableStackedRouteMatchInterface $route_match, AccountInterface $currentUser) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $language_manager, $messenger, $entity_repository, $route_match);

    $this->currentUser = $currentUser;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('language_manager'),
      $container->get('messenger'),
      $container->get('entity.repository'),
      $container->get('current_route_match'),
      $container->get('current_user'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);

    $entity_type = $this->getEntityType();
    // Filter the actions to only include those for this entity type.
    /** @var \Drupal\system\ActionConfigEntityInterface[] $actions */
    $actions = $this->actionStorage->loadMultiple();
    $this->actions = array_filter($actions, function ($action) use ($entity_type) {
      $plugin_definition = $action->getPluginDefinition();

      if ('user' === $action->getType() && in_array($plugin_definition['id'], [
        'user_add_role_action',
        'user_remove_role_action',
      ])) {
        $collections = $action->getPluginCollections();
        $collection = reset($collections);
        $configuration = $collection->getConfiguration();

        return $this->currentUser->hasPermission('assign all roles') || $this->currentUser->hasPermission(sprintf('assign %s role', $configuration['rid']));
      }
      else {
        return $action->getType() == $entity_type;
      }
    });
  }

}
