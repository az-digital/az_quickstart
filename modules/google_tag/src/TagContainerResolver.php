<?php

declare(strict_types=1);

namespace Drupal\google_tag;

use Drupal\Component\Plugin\Exception\ContextException;
use Drupal\Component\Plugin\Exception\MissingValueContextException;
use Drupal\Core\Condition\ConditionAccessResolverTrait;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Context\ContextHandlerInterface;
use Drupal\Core\Plugin\Context\ContextRepositoryInterface;
use Drupal\Core\Plugin\ContextAwarePluginInterface;
use Drupal\google_tag\Entity\TagContainer;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Resolves google tag config based on requests.
 */
final class TagContainerResolver {

  use ConditionAccessResolverTrait;

  /**
   * Special object storage to store google tag entity per request.
   *
   * @var \SplObjectStorage
   *
   * @phpstan-var \SplObjectStorage<\Symfony\Component\HttpFoundation\Request, TagContainer|null>
   */
  private \SplObjectStorage $resolved;

  /**
   * The Request Stack Service.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  private RequestStack $requestStack;

  /**
   * The Entity Type Manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private EntityTypeManagerInterface $entityTypeManager;

  /**
   * The Context Repository Service.
   *
   * @var \Drupal\Core\Plugin\Context\ContextRepositoryInterface
   */
  private ContextRepositoryInterface $contextRepository;

  /**
   * The Context Handler.
   *
   * @var \Drupal\Core\Plugin\Context\ContextHandlerInterface
   */
  private ContextHandlerInterface $contextHandler;

  /**
   * GoogleTagResolver constructor.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   Request stack.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   * @param \Drupal\Core\Plugin\Context\ContextRepositoryInterface $contextRepository
   *   Context repository.
   * @param \Drupal\Core\Plugin\Context\ContextHandlerInterface $contextHandler
   *   Context handler.
   */
  public function __construct(RequestStack $requestStack, EntityTypeManagerInterface $entityTypeManager, ContextRepositoryInterface $contextRepository, ContextHandlerInterface $contextHandler) {
    $this->requestStack = $requestStack;
    $this->entityTypeManager = $entityTypeManager;
    $this->contextRepository = $contextRepository;
    $this->contextHandler = $contextHandler;
    $this->resolved = new \SplObjectStorage();
  }

  /**
   * Resolves google tag config based on request.
   *
   * @return \Drupal\google_tag\Entity\TagContainer|null
   *   Google tag entity if resolved, otherwise null.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function resolve(): ?TagContainer {
    $request = $this->requestStack->getCurrentRequest();
    if ($request === NULL) {
      return NULL;
    }
    if (!$this->resolved->contains($request)) {
      $storage = $this->entityTypeManager->getStorage('google_tag_container');
      $config_ids = $storage->getQuery()
        // @todo remove after https://github.com/mglaman/phpstan-drupal/issues/479
        ->accessCheck()
        ->condition('status', 1)
        ->sort('weight')
        ->execute();
      /** @var array<string, TagContainer> $configs */
      $configs = $storage->loadMultiple($config_ids);
      foreach ($configs as $config) {
        if (!$this->passesConditions($config)) {
          continue;
        }
        $this->resolved[$request] = $config;
        break;
      }
    }
    return $this->resolved[$request] ?? NULL;
  }

  /**
   * Checks if google tag entity passes conditions.
   *
   * @param \Drupal\google_tag\Entity\TagContainer $entity
   *   Google tag config object.
   *
   * @return bool
   *   True if passes, else false.
   */
  private function passesConditions(TagContainer $entity): bool {
    $conditions = [];
    $missing_context = FALSE;
    $missing_value = FALSE;
    foreach ($entity->getInsertionConditions() as $condition_id => $condition) {
      if ($condition instanceof ContextAwarePluginInterface) {
        try {
          $contexts = $this->contextRepository->getRuntimeContexts(array_values($condition->getContextMapping()));
          $this->contextHandler->applyContextMapping($condition, $contexts);
        }
        catch (MissingValueContextException $missingValueContextException) {
          $missing_value = TRUE;
        }
        catch (ContextException $contextException) {
          $missing_context = TRUE;
        }
      }
      $conditions[$condition_id] = $condition;
    }
    if ($missing_context || $missing_value) {
      return FALSE;
    }
    return $this->resolveConditions($conditions, 'and');
  }

}
