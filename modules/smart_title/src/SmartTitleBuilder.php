<?php

namespace Drupal\smart_title;

use Drupal\Component\Utility\Html;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Smart Title builder.
 */
class SmartTitleBuilder implements ContainerInjectionInterface {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a new SmartTitleBuilder.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(ModuleHandlerInterface $module_handler) {
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('module_handler')
    );
  }

  /**
   * Applies smart title to an entity build.
   *
   * @param array $build
   *   A renderable array representing the entity content or form.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   * @param \Drupal\Core\Entity\Display\EntityViewDisplayInterface $display
   *   The entity display holding the display options configured for the entity
   *   components.
   */
  public function buildView(array &$build, EntityInterface $entity, EntityViewDisplayInterface $display) {
    // For first, we need the entity type dependent name of the label base
    // field.
    $labelKey = $entity->getEntityType()->getKey('label');
    $entity_type = $entity->getEntityTypeId();
    $defaults = _smart_title_defaults($entity_type, TRUE);
    $smart_title_settings = $display->getThirdPartySetting('smart_title', 'settings', $defaults);
    $smart_title_tag = isset(_smart_title_tag_options()[$smart_title_settings['smart_title__tag']]) || empty($smart_title_settings['smart_title__tag']) ?
      $smart_title_settings['smart_title__tag'] :
      $defaults['smart_title__tag'];

    if (!empty($build[$labelKey])) {
      $smart_title = $build[$labelKey];
    }
    else {
      $smart_title = $entity->$labelKey->view([
        'label' => 'hidden',
      ]);
    }

    if ($smart_title_settings['smart_title__link']) {
      $title_markup = $smart_title;
      $smart_title = $entity->toLink()->toRenderable();
      $smart_title['#title'] = $title_markup;
    }

    if (!empty($smart_title_tag)) {
      $smart_title['#theme_wrappers']['smart_title'] = [
        '#tag' => $smart_title_tag,
      ];

      if (!empty($smart_title_settings['smart_title__classes'])) {
        foreach ($smart_title_settings['smart_title__classes'] as $class_raw) {
          $smart_title['#theme_wrappers']['smart_title']['#attributes']['class'][] = Html::getClass($class_raw);
        }
      }
    }

    $context = [
      'entity' => $entity,
      'display' => $display,
      'view_mode' => $display->getOriginalMode(),
    ];
    $this->moduleHandler->alter('smart_title', $smart_title, $context);

    $build['smart_title'] = $smart_title;
  }

}
