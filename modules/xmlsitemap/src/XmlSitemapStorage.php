<?php

namespace Drupal\xmlsitemap;

use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Cache\MemoryCache\MemoryCacheInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\Entity\ConfigEntityStorage;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\State\StateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * XmlSitemap storage service class.
 */
class XmlSitemapStorage extends ConfigEntityStorage {

  /**
   * The state store.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Constructs a ConfigEntityStorage object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\Component\Uuid\UuidInterface $uuid_service
   *   The UUID service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   * @param \Drupal\Core\Cache\MemoryCache\MemoryCacheInterface|null $memory_cache
   *   The memory cache backend.
   */
  public function __construct(EntityTypeInterface $entity_type, ConfigFactoryInterface $config_factory, UuidInterface $uuid_service, LanguageManagerInterface $language_manager, StateInterface $state, MemoryCacheInterface $memory_cache = NULL) {
    parent::__construct($entity_type, $config_factory, $uuid_service, $language_manager, $memory_cache);
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('config.factory'),
      $container->get('uuid'),
      $container->get('language_manager'),
      $container->get('state'),
      $container->has('entity.memory_cache') ? $container->get('entity.memory_cache') : NULL
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function doDelete($entities) {
    // Delete the auxiliar xmlsitemap data.
    foreach ($entities as $entity) {
      $this->state->delete('xmlsitemap.' . $entity->id());
      xmlsitemap_clear_directory($entity, TRUE);
    }

    parent::doDelete($entities);
  }

  /**
   * {@inheritdoc}
   */
  protected function doLoadMultiple(array $ids = NULL) {
    $entities = parent::doLoadMultiple($ids);

    // Load the auxiliar xmlsitemap data and attach it to the entity.
    foreach ($entities as $entity) {
      $settings = $this->state->get('xmlsitemap.' . $entity->id(), [
        'chunks' => NULL,
        'links' => NULL,
        'max_filesize' => NULL,
        'updated' => NULL,
      ]);

      foreach ($settings as $setting => $value) {
        $entity->{$setting} = $value;
      }

      // Load the entity URI.
      $entity->uri = xmlsitemap_sitemap_uri($entity);

      // Load in the default contexts if they haven't been set yet.
      $contexts = xmlsitemap_get_context_info();
      foreach ($contexts as $context_key => $context) {
        if (!isset($entity->context[$context_key]) && isset($context['default'])) {
          $entity->context[$context_key] = $context['default'];
        }
      }

      // Remove invalid contexts.
      $entity->context = array_intersect_key($entity->context, $contexts);
    }

    return $entities;
  }

  /**
   * {@inheritdoc}
   */
  protected function doSave($id, EntityInterface $entity) {
    // Store the xmlsitemap auxiliar data.
    $this->state->set('xmlsitemap.' . $entity->id(), [
      'chunks' => $entity->getChunks(),
      'links' => $entity->getLinks(),
      'max_filesize' => $entity->getMaxFileSize(),
      'updated' => $entity->getUpdated(),
    ]);
    $is_new = parent::doSave($id, $entity);

    return $is_new ? SAVED_NEW : SAVED_UPDATED;
  }

}
