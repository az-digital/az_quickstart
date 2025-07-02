<?php

namespace Drupal\webform_access\Plugin\Block;

use Drupal\Core\Block\BlockBase;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\webform\EntityStorage\WebformEntityStorageTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'webform_access_group_entity' block.
 *
 * @Block(
 *   id = "webform_access_group_entity",
 *   admin_label = @Translation("Webform access group entities"),
 *   category = @Translation("Webform access")
 * )
 */
class WebformAccessGroupEntityBlock extends BlockBase implements ContainerFactoryPluginInterface {

  use WebformEntityStorageTrait;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The webform access group storage.
   *
   * @var \Drupal\webform_access\WebformAccessGroupStorageInterface
   */
  protected $webformAccessGroupStorage;

  /**
   * The 'language_manager' service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = new static($configuration, $plugin_id, $plugin_definition);
    $instance->currentUser = $container->get('current_user');
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->languageManager = $container->get('language_manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    /** @var \Drupal\node\NodeInterface[] $nodes */
    $nodes = $this->getEntityStorage('webform_access_group')->getUserEntities($this->currentUser, 'node');
    if (empty($nodes)) {
      return NULL;
    }

    $langcode = $this->languageManager->getCurrentLanguage()->getId();
    $items = [];
    foreach ($nodes as $node) {
      if ($node->access()) {
        if ($node->hasTranslation($langcode)) {
          $node = $node->getTranslation($langcode);
        }
        $items[] = $node->toLink()->toRenderable();
      }
    }
    if (empty($items)) {
      return NULL;
    }

    return [
      '#theme' => 'item_list',
      '#items' => $items,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    // @todo Setup cache tags and context .
    return 0;
  }

}
