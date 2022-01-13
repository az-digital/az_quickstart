<?php

namespace Drupal\az_news_feeds\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\migrate\Event\EventBase;
use Drupal\migrate\Event\MigrateEvents;
use Drupal\migrate\Event\MigrateImportEvent;
use Drupal\migrate\Event\MigratePreRowSaveEvent;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Modify the default migrate config with user input values.
 */
class AzNewsFeedsMigrateSubscriber implements EventSubscriberInterface {

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;


  /**
   * Constructs a AzNewsFeedsMigrateSubscriber object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory
    ) {
    $this->configFactory = $config_factory;

  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
    );
  }

  /**
   * Helper method to check if we are migrating UArizona News stories.
   *
   * @param \Drupal\migrate\Event\EventBase $event
   *   The migrate event.
   *
   * @return bool
   *   True if we are migrating UArizona News stories, false otherwise.
   */
  protected function isUarizonaNews(EventBase $event) {
    $uarizonaNewsUrl = 'news.arizona.edu';
    $migration = $event->getMigration();
    $source_configuration = $migration->getSourceConfiguration();
    $destination_configuration = $migration->getDestinationConfiguration();
    return !empty($source_configuration['urls']) && $destination_configuration['plugin'] === 'entity:node' && strpos($source_configuration['urls'], $uarizonaNewsUrl);
  }
  
 /**
  * Event which fires before import.
  *
  * @param \Drupal\migrate\Event\MigrateImportEvent $event
  *  Current MigrateImportEvent object.
  */
  public function onPreImport(MigrateImportEvent $event) {
    $migration = $event->getMigration();
    $az_news_feeds_config = $this->configFactory->getEditable('az_news_feeds.settings');
    $selected_terms = $az_news_feeds_config->get('uarizona_news_terms');
    $views_contextual_argument = implode('+', array_keys($selected_terms));
    // $terms = implode('+', array_keys($selected_terms));

    $urls = 'https://news.arizona.edu/feed/json/stories/id/' . $views_contextual_argument;
    // $this->config('migrate_plus.migration_group.az_news_feeds')
    //   ->set('shared_configuration.source.urls', $urls)
    //   ->save();
    if ($this->isUarizonaNews($event)) {
        Drush::output()->writeln($urls);
        // Drush::output()->writeln($sourceTags);

    //   $row = $event->getRow();
    //   $source = $row->getSource();
    //   $destination = $row->getDestination();
    //   $collection = $this->keyValue->get('node_translation_redirect');
    //   $collection->set($source['nid'], [$destination['nid'], $destination['langcode']]);
    }

  }
 /**
  * Event which fires before the Row is going to be saved.
  *
  * @param \Drupal\migrate\Event\MigratePreRowSaveEvent $event
  *  Current MigratePreRowSaveEvent object.
  */
  public function onPreRowSave(MigratePreRowSaveEvent $event) {
    $migration = $event->getMigration();
    $sourceTags =  $event->getRow()->getSourceProperty('tags');
    $az_news_feeds_config = $this->configFactory->getEditable('az_news_feeds.settings');
    $selected_terms = $az_news_feeds_config->get('uarizona_news_terms');


    $toSave =  $event->getRow()->getDestinationProperty('field_az_news_tags');
    // Drush::output()->writeln($toSave);

    //   dpm($event);
    if ($this->isUarizonaNews($event)) {
        // Drush::output()->writeln($selected_terms);
        // Drush::output()->writeln($sourceTags);

    //   $row = $event->getRow();
    //   $source = $row->getSource();
    //   $destination = $row->getDestination();
    //   $collection = $this->keyValue->get('node_translation_redirect');
    //   $collection->set($source['nid'], [$destination['nid'], $destination['langcode']]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [];
    $events[MigrateEvents::PRE_IMPORT] = ['onPreImport'];
    $events[MigrateEvents::PRE_ROW_SAVE] = ['onPreRowSave'];

    return $events;
  }

}
