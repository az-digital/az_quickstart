<?php

namespace Drupal\asset_injector;

use Drupal\Component\Plugin\Exception\ContextException;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Condition\ConditionAccessResolverTrait;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Plugin\Context\ContextHandlerInterface;
use Drupal\Core\Plugin\Context\ContextRepositoryInterface;
use Drupal\Core\Plugin\ContextAwarePluginInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the access control handler for the asset_injector entity types.
 *
 * @see \Drupal\asset_injector\Entity\AssetInjectorCss
 * @see \Drupal\asset_injector\Entity\AssetInjectorJs
 */
class AssetInjectorAccessControlHandler extends EntityAccessControlHandler implements EntityHandlerInterface {

  use ConditionAccessResolverTrait;

  /**
   * The plugin context handler.
   *
   * @var \Drupal\Core\Plugin\Context\ContextHandlerInterface
   */
  protected $contextHandler;

  /**
   * The context manager service.
   *
   * @var \Drupal\Core\Plugin\Context\ContextRepositoryInterface
   */
  protected $contextRepository;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('context.handler'),
      $container->get('context.repository')
    );
  }

  /**
   * Constructs the asset_injector access control handler instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Plugin\Context\ContextHandlerInterface $context_handler
   *   The ContextHandler for applying contexts to conditions properly.
   * @param \Drupal\Core\Plugin\Context\ContextRepositoryInterface $context_repository
   *   The lazy context repository service.
   */
  public function __construct(EntityTypeInterface $entity_type, ContextHandlerInterface $context_handler, ContextRepositoryInterface $context_repository) {
    parent::__construct($entity_type);
    $this->contextHandler = $context_handler;
    $this->contextRepository = $context_repository;
  }

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    // "View" operation is used as an indicator that the asset will be added
    // to the page. This doesn't restrict access to view the asset via the url.
    if ($operation != 'view') {
      return parent::checkAccess($entity, $operation, $account);
    }

    /** @var \Drupal\asset_injector\AssetInjectorInterface $entity */
    // Don't grant access to disabled assets.
    if (!$entity->status()) {
      return AccessResult::forbidden()->addCacheableDependency($entity);
    }
    else {
      $conditions = [];

      foreach ($entity->getConditionsCollection() as $condition_id => $condition) {
        // We'll resolve current_theme conditions in another method.
        // @see resolveThemeConditions().
        if ($condition_id == 'current_theme') {
          continue;
        }

        if ($condition instanceof ContextAwarePluginInterface) {
          try {
            $contexts = $this->contextRepository->getRuntimeContexts(array_values($condition->getContextMapping()));
            $this->contextHandler->applyContextMapping($condition, $contexts);
          }
          catch (ContextException $e) {
          }
        }
        $conditions[$condition_id] = $condition;
      }

      $logic = $entity->conditions_require_all || empty($conditions) ? 'and' : 'or';

      $conditions_allowed = $this->resolveConditions($conditions, $logic);
      $themes_allowed = $this->resolveThemeConditions($entity);

      // Since we split the themes out into their own method, we have to do
      // logic to check if it satisfies both options.
      if ($entity->getConditionsCollection()->has('current_theme')) {

        $access = AccessResult::forbidden();

        if (
          ($logic == 'and' && ($conditions_allowed && $themes_allowed)) ||
          ($logic == 'or' && ($conditions_allowed || $themes_allowed))
        ) {
          $access = AccessResult::allowed();
        }

      }
      else {
        // No current theme so we can just check normal conditions.
        $access = $conditions_allowed ? AccessResult::allowed() : AccessResult::forbidden();
      }

      $this->mergeCacheabilityFromConditions($access, $conditions);

      // Ensure that access is evaluated again when the asset changes.
      return $access->addCacheableDependency($entity);
    }
  }

  /**
   * Resolve only current_theme condition plugins.
   *
   * @param \Drupal\asset_injector\AssetInjectorInterface $entity
   *   The asset with theme conditions.
   *
   * @return bool
   *   If the theme condition resolves true or not.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  protected function resolveThemeConditions(AssetInjectorInterface $entity) {

    $conditions = $entity->getConditionsCollection();

    $theme_conditions = [];

    // If no current theme condition, return true.
    if (!$conditions->has('current_theme')) {
      return TRUE;
    }

    /** @var \Drupal\system\Plugin\Condition\CurrentThemeCondition $theme_condition */
    $theme_condition = $conditions->get('current_theme');
    $config = $theme_condition->getConfig();

    $themes = is_array($config['theme']) ? $config['theme'] : [$config['theme']];
    // If no themes were selected in the UI, the value of `theme`
    // is an empty string.
    // Change it to an array.
    foreach ($themes as $theme) {
      $new_theme_conditions = clone $theme_condition;
      $new_theme_conditions->setConfig('theme', $theme);
      $conditions->set("current_theme_$theme", $new_theme_conditions);
      $theme_conditions[] = $new_theme_conditions;
    }

    $logic = $config['negate'] ? 'and' : 'or';
    return $this->resolveConditions($theme_conditions, $logic);
  }

  /**
   * Merges cacheable metadata from conditions onto the access result object.
   *
   * @param \Drupal\Core\Access\AccessResult $access
   *   The access result object.
   * @param \Drupal\Core\Condition\ConditionInterface[] $conditions
   *   List of conditions.
   */
  protected function mergeCacheabilityFromConditions(AccessResult $access, array $conditions) {
    foreach ($conditions as $condition) {
      if ($condition instanceof CacheableDependencyInterface) {
        $access->addCacheTags($condition->getCacheTags());
        $access->addCacheContexts($condition->getCacheContexts());
        $access->setCacheMaxAge(Cache::mergeMaxAges($access->getCacheMaxAge(), $condition->getCacheMaxAge()));
      }
    }
  }

}
