<?php

namespace Drupal\az_news_feeds\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\migrate\Event\EventBase;
use Drupal\migrate\Event\MigrateEvents;
use Drupal\migrate\Event\MigrateImportEvent;
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
   * Helper method to check if we are migrating UArizona News stories.
   *
   * @param \Drupal\migrate\Event\EventBase $event
   *   The migrate event.
   *
   * @return bool
   *   True if we are migrating UArizona News stories, false otherwise.
   */
  protected function isUarizonaNews(EventBase $event): bool {
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
   *   Current MigrateImportEvent object.
   */
  public function onPreImport(MigrateImportEvent $event) {

    /** @var \Drupal\migrate\Plugin\MigrationInterface $migration */
    $migration = $event->getMigration();
    if (
      $migration->id() === 'az_news_feed_stories' ||
      $migration->id() === 'az_news_feed_stories_files' ||
      $migration->id() === 'az_news_feed_stories_media'
    ) {
      // Change the news.arizona.edu feed url.
      $az_news_feeds_config = $this->configFactory->getEditable('az_news_feeds.settings');
      $base_uri = $az_news_feeds_config->get('uarizona_news_base_uri');
      $content_path = $az_news_feeds_config->get('uarizona_news_content_path');
      $selected_terms = $az_news_feeds_config->get('uarizona_news_terms');
      $views_contextual_argument = implode('+', array_keys($selected_terms));
      $urls = $base_uri . $content_path . $views_contextual_argument;
      $sourceConfig['urls'] = $urls;

      if ($migration->id() === 'az_news_feed_stories') {
        $processConfig = $migration->getProcess();
        $sourceConfig = $migration->getSourceConfiguration();
        $array_intersect_process = [
          'plugin' => 'array_intersect',
          'match'  => array_values($selected_terms),
        ];
        $var_dump_process = [
          'plugin' => 'callback',
          'callable'  => 'var_dump',
          // 'source' => $processConfig['field_az_news_tags_processed']
        ];

        // $processConfig['field_az_news_tags_processed'][] = $var_dump_process;
        $processConfig['field_az_news_tags_processed'][] = $array_intersect_process;
        // $processConfig['field_az_news_tags_processed'][] = $var_dump_process;
        $migration->set('process', $processConfig);
        $migration->set('source', $sourceConfig);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events = [];
    $events[MigrateEvents::PRE_IMPORT] = ['onPreImport'];

    return $events;
  }

}
