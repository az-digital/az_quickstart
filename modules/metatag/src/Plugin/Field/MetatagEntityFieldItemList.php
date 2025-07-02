<?php

namespace Drupal\metatag\Plugin\Field;

use Drupal\Core\Field\FieldItemList;
use Drupal\Core\Render\RenderContext;
use Drupal\Core\Render\RendererInterface;
use Drupal\metatag\MetatagManagerInterface;
use Drupal\metatag\TypedData\ComputedItemListTrait;

/**
 * Defines a metatag list class for better normalization targeting.
 */
class MetatagEntityFieldItemList extends FieldItemList {

  use ComputedItemListTrait;

  /**
   * Whether the metatags have been generated.
   *
   * This allows the cached value to be recomputed after the entity is saved.
   *
   * @var bool
   */
  protected $metatagsGenerated = FALSE;

  /**
   * {@inheritdoc}
   */
  protected function valueNeedsRecomputing() {
    return !$this->getEntity()->isNew() && !$this->metatagsGenerated;
  }

  /**
   * {@inheritdoc}
   */
  protected function computeValue() {
    $entity = $this->getEntity();
    if ($entity->isNew()) {
      return;
    }
    $renderer = \Drupal::service('renderer');
    assert($renderer instanceof RendererInterface);

    // @todo capture the cacheable metadata and properly bubble it up.
    // @see https://www.drupal.org/project/metatag/issues/3175269
    // @see https://www.drupal.org/project/metatag/issues/3039650
    $tags = $renderer->executeInRenderContext(new RenderContext(), static function () use ($entity) {
      $metatag_manager = \Drupal::service('metatag.manager');
      assert($metatag_manager instanceof MetatagManagerInterface);

      $metatags_for_entity = $metatag_manager->tagsFromEntityWithDefaults($entity);

      // Trigger hook_metatags_alter().
      // Allow modules to override tags or entity used for token replacements.
      $context = [
        'entity' => &$entity,
      ];
      \Drupal::service('module_handler')->alter('metatags', $metatags_for_entity, $context);

      return $metatag_manager->generateRawElements($metatags_for_entity, $entity);
    });
    $this->list = [];
    foreach ($tags as $tag) {
      $offset = count($this->list);
      $this->list[] = $this->createItem($offset, [
        'tag' => $tag['#tag'],
        'attributes' => $tag['#attributes'],
      ]);
    }

    $this->metatagsGenerated = TRUE;
  }

}
