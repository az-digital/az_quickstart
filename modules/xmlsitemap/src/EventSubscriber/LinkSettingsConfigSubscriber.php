<?php

namespace Drupal\xmlsitemap\EventSubscriber;

use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Config\ConfigCrudEvent;
use Drupal\Core\Config\ConfigEvents;
use Drupal\Core\Config\ConfigRenameEvent;
use Drupal\xmlsitemap\XmlSitemapLinkStorageInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * A subscriber for updating sitemap links when changing link bundle settings.
 */
class LinkSettingsConfigSubscriber implements EventSubscriberInterface {

  /**
   * The link storage.
   *
   * @var \Drupal\xmlsitemap\XmlSitemapLinkStorageInterface
   */
  protected $linkStorage;

  /**
   * The cache tags invalidator.
   *
   * @var \Drupal\Core\Cache\CacheTagsInvalidatorInterface
   */
  protected $cacheTagsInvalidator;

  /**
   * Constructs a LinkSettingsConfigSubscriber object.
   *
   * @param \Drupal\xmlsitemap\XmlSitemapLinkStorageInterface $link_storage
   *   The link storage.
   * @param \Drupal\Core\Cache\CacheTagsInvalidatorInterface $cache_tags_invalidator
   *   The cache tags invalidator.
   */
  public function __construct(XmlSitemapLinkStorageInterface $link_storage, CacheTagsInvalidatorInterface $cache_tags_invalidator) {
    $this->linkStorage = $link_storage;
    $this->cacheTagsInvalidator = $cache_tags_invalidator;
  }

  /**
   * Updates XML sitemap links when their link bundle settings are changed.
   *
   * @param \Drupal\Core\Config\ConfigCrudEvent $event
   *   The configuration event to process.
   */
  public function onChange(ConfigCrudEvent $event) {
    $config = $event->getConfig();
    if (!$config->isNew() && $ids = $this->isLinkSettingsConfig($config->getName())) {
      [$entity_type, $bundle] = explode('.', $ids, 2);
      if ($config->get('status') != $config->getOriginal('status')) {
        // Status is an integer in the database but is a boolean in config.
        $this->linkStorage->updateMultiple(['status' => (int) $config->get('status')], [
          'type' => $entity_type,
          'subtype' => $bundle,
          'status_override' => 0,
        ]);
      }
      if ($config->get('priority') != $config->getOriginal('priority')) {
        $this->linkStorage->updateMultiple(['priority' => $config->get('priority')], [
          'type' => $entity_type,
          'subtype' => $bundle,
          'priority_override' => 0,
        ]);
      }
      $this->cacheTagsInvalidator->invalidateTags(['xmlsitemap']);
    }
  }

  /**
   * Updates XML sitemap links when their link bundle settings are changed.
   *
   * @param \Drupal\Core\Config\ConfigRenameEvent $event
   *   The configuration event to process.
   */
  public function onRename(ConfigRenameEvent $event) {
    if ($ids = $this->isLinkSettingsConfig($event->getConfig()->getName())) {
      [$entity_type, $bundle_new] = explode($ids, '.', 2);
      $old_ids = $this->isLinkSettingsConfig($event->getOldName());
      [, $bundle_old] = explode('.', $old_ids, 2);
      $this->linkStorage->updateMultiple(['subtype' => $bundle_new], ['type' => $entity_type, 'subtype' => $bundle_old]);
      $this->cacheTagsInvalidator->invalidateTags(['xmlsitemap']);
    }
  }

  /**
   * Removes XML sitemap links when their link bundle settings are deleted.
   *
   * @param \Drupal\Core\Config\ConfigCrudEvent $event
   *   The configuration event to process.
   */
  public function onDelete(ConfigCrudEvent $event) {
    if ($ids = $this->isLinkSettingsConfig($event->getConfig()->getName())) {
      [$entity_type, $bundle] = explode('.', $ids, 2);
      $this->linkStorage->deleteMultiple(['type' => $entity_type, 'subtype' => $bundle]);
      $this->cacheTagsInvalidator->invalidateTags(['xmlsitemap']);
    }
  }

  /**
   * Tests if a configuration name is an XML sitemap link bundle setting.
   *
   * @param string $name
   *   The configuration name.
   *
   * @return string|null
   *   The string containing the entitytype.bundle if this config matches, or
   *   NULL otherwise.
   */
  protected function isLinkSettingsConfig(string $name): ?string {
    if (preg_match('/^xmlsitemap\.settings\.(\w+\.\w+)$/', $name, $matches)) {
      return $matches[1];
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[ConfigEvents::SAVE][] = ['onChange'];
    $events[ConfigEvents::RENAME][] = ['onRename'];
    $events[ConfigEvents::DELETE][] = ['onDelete'];
    return $events;
  }

}
