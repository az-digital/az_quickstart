<?php

namespace Drupal\workbench_access\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Creates a block to show editorial status.
 *
 * @Block(
 *   id = "workbench_access_block",
 *   admin_label = @Translation("Workbench Access information"),
 *   context_definitions = {
 *     "node" = @ContextDefinition("entity:node", label = @Translation("Current node"))
 *   },
 * )
 */
class WorkbenchAccessBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new WorkbenchAccessBlock.
   *
   * @param array $configuration
   *   Configuration.
   * @param string $plugin_id
   *   ID.
   * @param array|object $plugin_definition
   *   Definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entityTypeManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    // Using a context definition ensures the cacheability metadata from the
    // node is applied to the block output.
    // @see \Drupal\Core\Plugin\Context\ContextHandler::applyContextMapping
    if (($node = $this->getContextValue('node')) && $node instanceof NodeInterface) {
      $scheme_storage = $this->entityTypeManager->getStorage('access_scheme');
      if ($schemes = $scheme_storage->loadMultiple()) {
        /** @var \Drupal\workbench_access\Entity\AccessSchemeInterface $scheme */
        foreach ($schemes as $scheme) {
          $active = $scheme->getAccessScheme();
          if ($values = $active->getEntityValues($node)) {
            foreach ($values as $value) {
              $element = $active->load($value);
              // @todo This needs to be tested better.
              $build['#theme'] = 'item_list';
              $build['#items']['#title'] = $this->t('Editorial sections:');
              $build['#items'][] = $element['label'];
              $build['#plain_text'] = TRUE;
            }
          }
        }
      }
    }
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    $contexts = parent::getCacheContexts();
    // The output varies per user permissions.
    $contexts[] = 'user.permissions';
    return $contexts;
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    return AccessResult::allowedIfHasPermissions($account, [
      'administer workbench access',
      'view workbench access information',
    ], 'OR');
  }

}
