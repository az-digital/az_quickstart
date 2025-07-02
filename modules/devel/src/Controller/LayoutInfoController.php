<?php

namespace Drupal\devel\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Layout\LayoutPluginManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns response for Layout Info route.
 */
class LayoutInfoController extends ControllerBase {

  /**
   * The Layout Plugin Manager.
   */
  protected LayoutPluginManagerInterface $layoutPluginManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    $instance = parent::create($container);
    $instance->layoutPluginManager = $container->get('plugin.manager.core.layout');
    $instance->stringTranslation = $container->get('string_translation');

    return $instance;
  }

  /**
   * Builds the Layout Info page.
   *
   * @return array
   *   Array of page elements to render.
   */
  public function layoutInfoPage(): array {
    $headers = [
      $this->t('Icon'),
      $this->t('Label'),
      $this->t('Description'),
      $this->t('Category'),
      $this->t('Regions'),
      $this->t('Provider'),
    ];

    $rows = [];

    foreach ($this->layoutPluginManager->getDefinitions() as $layout) {
      $rows[] = [
        'icon' => ['data' => $layout->getIcon()],
        'label' => $layout->getLabel(),
        'description' => $layout->getDescription(),
        'category' => $layout->getCategory(),
        'regions' => implode(', ', $layout->getRegionLabels()),
        'provider' => $layout->getProvider(),
      ];
    }

    $output['layouts'] = [
      '#type' => 'table',
      '#header' => $headers,
      '#rows' => $rows,
      '#empty' => $this->t('No layouts available.'),
      '#attributes' => [
        'class' => ['devel-layout-list'],
      ],
    ];

    return $output;
  }

}
