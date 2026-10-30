<?php

namespace Drupal\az_search_api\Plugin\search_api\processor;

use Drupal\Core\Access\AccessibleInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Path\PathMatcherInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\search_api\Attribute\SearchApiProcessor;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Skip indexing content that is not accessible to the anonymous user.
 */
#[SearchApiProcessor(
  id: 'az_anonymous_access',
  label: new TranslatableMarkup('Index only entities accessible to the anonymous user'),
  description: new TranslatableMarkup('This performs an anonymous access check with the entity before indexing.'),
  stages: [
    'alter_items' => 10,
  ],
)]
class AZAnonymousAccess extends ProcessorPluginBase {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * The path matcher service.
   *
   * @var \Drupal\Core\Path\PathMatcherInterface
   */
  protected PathMatcherInterface $pathMatcher;

  /**
   * CAS forced login settings.
   *
   * @var ?array
   */
  protected ?array $forcedLoginPaths = NULL;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var static $processor */
    $processor = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $processor->configFactory = $container->get('config.factory');
    $processor->pathMatcher = $container->get('path.matcher');
    return $processor;
  }

  /**
   * Determine if an entity is protected by forced login settings.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to check.
   *
   * @return bool
   *   if the entity is protected by CAS forced login settings.
   */
  public function checkForcedLogin(EntityInterface $entity): bool {
    // Haven't yet fetched the forced login settings. The paths only apply
    // when CAS forced login is enabled.
    if ($this->forcedLoginPaths === NULL) {
      $cas_settings = $this->configFactory->get('cas.settings');
      $this->forcedLoginPaths = $cas_settings->get('forced_login.enabled')
        ? ($cas_settings->get('forced_login.paths') ?? [])
        : [];
    }
    // Forced login is disabled, or CAS is not installed.
    if (empty($this->forcedLoginPaths)) {
      return FALSE;
    }
    // Get the paths associated with this entity.
    $url = $entity->toUrl('canonical');
    $alias = $url->toString();
    $system = '/' . $url->getInternalPath();
    // Get whether the forced login settings are negated (e.g. inversed).
    $negate = !empty($this->forcedLoginPaths['negate']);
    // Get the forced login paths if they exist.
    $pages = $this->forcedLoginPaths['pages'] ?? '';
    $paths = [$alias, $system];
    foreach ($paths as $path) {
      // Check if the result of the match matches the negate state.
      // If match opposite of the negate state, it's intended to be protected.
      if ($this->pathMatcher->matchPath($path, $pages) !== $negate) {
        // @todo this is only intended for testing purposes. It is not intended to be merged.
        \Drupal::logger('az_search_api')->notice("Skipping entity %entity because @path matches against (@pages)", [
          '%entity' => $entity->label(),
          '@path' => $path,
          '@pages' => $pages,
        ]);
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function alterIndexedItems(array &$items) {
    // Get the instance of the anonymous user account.
    $user = User::getAnonymousUser();
    /** @var \Drupal\search_api\Item\ItemInterface $item */
    foreach ($items as $item_id => $item) {
      $entity = $item->getOriginalObject()->getValue();
      if (empty($entity)) {
        continue;
      }
      if ($entity instanceof AccessibleInterface) {
        // Exclude an item if entity is not accessible by the anonymous user.
        if (!$entity->access('view', $user)) {
          unset($items[$item_id]);
          continue;
        }
      }
      if ($entity instanceof EntityInterface) {
        // Exclude an item if entity is protected by CAS forced login settings.
        if ($this->checkForcedLogin($entity)) {
          unset($items[$item_id]);
        }
      }
    }
  }

}
