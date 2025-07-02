<?php

namespace Drupal\devel;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Toolbar integration handler.
 */
class ToolbarHandler implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The current user.
   */
  protected AccountProxyInterface $account;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    $instance = new self();
    $instance->account = $container->get('current_user');
    $instance->stringTranslation = $container->get('string_translation');

    return $instance;
  }

  /**
   * Hook bridge.
   *
   * @return array
   *   The devel toolbar items render array.
   *
   * @see hook_toolbar()
   */
  public function toolbar() {
    $items['devel'] = [
      '#cache' => [
        'contexts' => ['user.permissions'],
      ],
    ];

    if ($this->account->hasPermission('access devel information')) {
      $items['devel'] += [
        '#type' => 'toolbar_item',
        '#weight' => 999,
        'tab' => [
          '#type' => 'link',
          '#title' => $this->t('Devel'),
          '#url' => Url::fromRoute('devel.admin_settings'),
          '#attributes' => [
            'title' => $this->t('Development menu'),
            'class' => ['toolbar-icon', 'toolbar-icon-devel'],
          ],
        ],
        'tray' => [
          '#heading' => $this->t('Development menu'),
          'devel_menu' => [
            // Currently devel menu is uncacheable, so instead of poisoning the
            // entire page cache we use a lazy builder.
            // @see \Drupal\devel\Plugin\Menu\DestinationMenuLink
            // @see \Drupal\devel\Plugin\Menu\RouteDetailMenuItem
            '#lazy_builder' => ['devel.lazy_builders:renderMenu' , []],
            // Force the creation of the placeholder instead of rely on the
            // automatical placeholdering or otherwise the page results
            // uncacheable when max-age 0 is bubbled up.
            '#create_placeholder' => TRUE,
          ],
          'configuration' => [
            '#type' => 'link',
            '#title' => $this->t('Configure'),
            '#url' => Url::fromRoute('devel.toolbar.settings_form'),
            '#options' => [
              'attributes' => ['class' => ['edit-devel-toolbar']],
            ],
          ],
        ],
        '#attached' => [
          'library' => 'devel/devel-toolbar',
        ],
      ];
    }

    return $items;
  }

}
