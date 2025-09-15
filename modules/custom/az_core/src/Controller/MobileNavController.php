<?php

namespace Drupal\az_core\Controller;

use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Block\BlockManager;
use Drupal\Core\Cache\CacheableAjaxResponse;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a controller to update a mobile nav block.
 *
 * @internal
 *   Controller classes are internal.
 */
class MobileNavController implements ContainerInjectionInterface {

  /**
   * The block manager.
   *
   * @var \Drupal\Core\Block\BlockManager
   */
  protected $blockManager;

  /**
   * MobileNavController constructor.
   *
   * @param \Drupal\Core\Block\BlockManager $block_manager
   *   The block manager.
   */
  public function __construct(BlockManager $block_manager) {
    $this->blockManager = $block_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.block')
    );
  }

  /**
   * Callback for the Quickstart mobile nav block.
   *
   * @param string $menu_root
   *   The menu link ID for the root of the nav menu.
   * @param string $current_page
   *   The menu link ID for the current page.
   *
   * @return \Drupal\Core\Cache\CacheableAjaxResponse
   *   An CacheableAjaxResponse object.
   */
  public function mobileNavCallback($menu_root = '', $current_page = 'none') {
    $mobile_nav_block = $this->blockManager->createInstance('mobile_nav_block',
      [
        'menu_root' => $menu_root,
        'current_page' => $current_page,
      ]);
    $mobile_nav_block_build = $mobile_nav_block->build();

    $cacheable_response = new CacheableAjaxResponse();
    $cacheable_response->addCommand(new ReplaceCommand('#az_mobile_nav_menu', $mobile_nav_block_build));

    // Add necessary cacheable metadata for the Mobile Nav Block.
    $cacheableMetadata = $cacheable_response->getCacheableMetadata();
    $cacheableMetadata->addCacheTags(['config:system.menu.main']);
    $cacheableMetadata->addCacheContexts(['route']);

    return $cacheable_response;
  }

}
