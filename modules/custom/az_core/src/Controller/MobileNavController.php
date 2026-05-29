<?php

namespace Drupal\az_core\Controller;

use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Block\BlockManager;
use Drupal\Core\Cache\CacheableAjaxResponse;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * MobileNavController constructor.
   *
   * @param \Drupal\Core\Block\BlockManager $block_manager
   *   The block manager.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(BlockManager $block_manager, RequestStack $request_stack) {
    $this->blockManager = $block_manager;
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.block'),
      $container->get('request_stack')
    );
  }

  /**
   * Callback for the Quickstart mobile nav block.
   *
   * @param string $menu_id
   *   The menu ID.
   * @param string $menu_root
   *   The menu link ID for the root of the nav menu.
   *
   * @return \Drupal\Core\Cache\CacheableAjaxResponse
   *   An CacheableAjaxResponse object.
   */
  public function mobileNavCallback($menu_id, $menu_root = '') {
    $request = $this->requestStack->getCurrentRequest();
    if (!$request || !$request->isXmlHttpRequest()) {
      throw new NotFoundHttpException();
    }

    $mobile_nav_block = $this->blockManager->createInstance('mobile_nav_block',
      [
        'menu_id' => $menu_id,
        'menu_root' => $menu_root,
      ]);
    $mobile_nav_block_build = $mobile_nav_block->build();

    $cacheable_response = new CacheableAjaxResponse();
    $cacheable_response->addCommand(new ReplaceCommand('#az_mobile_nav_menu', $mobile_nav_block_build));

    // Add necessary cacheable metadata for the Mobile Nav Block.
    $cacheableMetadata = $cacheable_response->getCacheableMetadata();
    $cacheableMetadata->addCacheTags(['config:system.menu.' . $menu_id]);
    $cacheableMetadata->addCacheContexts(['route']);

    return $cacheable_response;
  }

}
