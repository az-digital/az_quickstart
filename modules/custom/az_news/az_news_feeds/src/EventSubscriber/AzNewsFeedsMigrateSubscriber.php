<?php

namespace Drupal\az_news_feeds\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\migrate\Event\EventBase;
use Drupal\migrate\Event\MigrateEvents;
use Drupal\migrate\Event\MigrateImportEvent;
use Drupal\migrate\Event\MigratePreRowSaveEvent;
use Drupal\migrate_plus\Entity\Migration;
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
    if ($migration->id() === 'az_news_feed_stories') {
      // Change the news.arizona.edu feed url.
      $az_news_feeds_config = $this->configFactory->getEditable('az_news_feeds.settings');
      $selected_terms = $az_news_feeds_config->get('uarizona_news_terms');
      $views_contextual_argument = implode('+', array_keys($selected_terms));
      $urls = 'https://news.arizona.edu/feed/json/stories/id/' . $views_contextual_argument;
      $event_migration = Migration::load($migration->id());
      $source = $event_migration->get('source');
      $source_count = $migration->getProcess();
      $source['urls'] = $urls;
      \Drupal::logger('az_news_feeds')->notice(print_r($source_count, TRUE));
      // $process = [
      //   'plugin' => 'sub_process',
      //   'source' => 'field_az_news_tags',
      //   'process' => [
      //     'target_id' => [
      //       [
      //         'plugin' => 'paragraphs_lookup',
      //         'tags' => 'Field Collection Content',
      //         'source' => 'value',
      //       ],
      //       [
      //         'plugin' => 'extract',
      //         'index' => [
      //           'id',
      //         ],
      //       ],
      //     ],
      //     'target_revision_id' => [
      //       [
      //         'plugin' => 'paragraphs_lookup',
      //         'tags' => [
      //           'Field Collection Revisions Content',
      //           'Field Collection Content',
      //         ],
      //         'tag_ids' => [
      //           'Field Collection Revisions Content' => [
      //             'revision_id',
      //           ],
      //           'Field Collection Content' => [
      //             'value',
      //           ],
      //         ],
      //       ],
      //       [
      //         'plugin' => 'extract',
      //         'index' => [
      //           'revision_id',
      //         ],
      //       ],
      //     ],
      //   ],
      // ];
  
      $event_migration->set('source', $source);
      $event_migration->save();
    }
  }

//  /**
//   * Event which fires before the Row is going to be saved.
//   *
//   * @param \Drupal\migrate\Event\MigratePreRowSaveEvent $event
//   *  Current MigratePreRowSaveEvent object.
//   */
//   public function onPreRowSave(MigratePreRowSaveEvent $event) {
//     $migration = $event->getMigration();
//     if ($migration->id() === 'az_news_feed_stories') {
//       $az_news_feeds_config = $this->configFactory->getEditable('az_news_feeds.settings');
//       $selected_terms = array_values($az_news_feeds_config->get('uarizona_news_terms'));
//       $row = $event->getRow();
//       $sourceTags =  explode(', ', $row->getSourceProperty('tags'));
//       $pruned_tags = implode(', ', array_values(array_intersect($sourceTags, $selected_terms)));
//       $row->setDestinationProperty('field_az_news_tags', $pruned_tags);
//       \Drupal::logger('az_news_feeds')->notice(print_r($pruned_tags, TRUE));
//     }
//   }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [];
    $events[MigrateEvents::PRE_IMPORT] = ['onPreImport'];
    // $events[MigrateEvents::PRE_ROW_SAVE] = ['onPreRowSave'];

    return $events;
  }

}
