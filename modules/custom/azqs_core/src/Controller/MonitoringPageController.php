<?php

namespace Drupal\azqs_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\PageCache\ResponsePolicy\KillSwitch;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class MonitoringPageController.
 */
class MonitoringPageController extends ControllerBase {

  /**
   * The page cache kill switch.
   *
   * @var \Drupal\Core\PageCache\ResponsePolicy\KillSwitch
   */
  protected $pageCacheKillSwitch;

  /**
   * Constructs a new MonitoringPageController object.
   */
  public function __construct(KillSwitch $page_cache_kill_switch) {
    $this->pageCacheKillSwitch = $page_cache_kill_switch;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('page_cache_kill_switch')
    );
  }

  /**
   * Deliver.
   *
   * @return array
   *   Return monitoring page render array.
   */
  public function deliver() {
    $this->pageCacheKillSwitch->trigger();
    return [
      '#type' => 'markup',
      '#markup' => '<p>This page is intended for use with uptime monitoring tools.</p>',
      '#attached' => [
        'http_header' => [
          ['X-Robots-Tag', 'none'],
        ],
      ],
    ];
  }

}
