<?php

declare(strict_types=1);

namespace Drupal\views_remote_data_pokeapi\EventSubscriber;

use Drupal\file\Entity\File;
use Drupal\node\Entity\Node;
use Drupal\views\ResultRow;
use Drupal\views_remote_data\Events\RemoteDataLoadEntitiesEvent;
use Drupal\views_remote_data\Events\RemoteDataQueryEvent;
use Drupal\views_remote_data_pokeapi\PokeApi;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Test subscriber for populating values in test views.
 */
final class ViewsRemoteDataSubscriber implements EventSubscriberInterface {

  /**
   * The PokeApi client.
   *
   * @var \Drupal\views_remote_data_pokeapi\PokeApi
   */
  private PokeApi $pokeApi;

  /**
   * Constructs a new ViewsRemoteDataSubscriber object.
   *
   * @param \Drupal\views_remote_data_pokeapi\PokeApi $poke_api
   *   The PokeApi client.
   */
  public function __construct(PokeApi $poke_api) {
    $this->pokeApi = $poke_api;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      RemoteDataQueryEvent::class => 'onQuery',
      RemoteDataLoadEntitiesEvent::class => 'onLoadEntities',
    ];
  }

  /**
   * Subscribes to populate entities against the results.
   *
   * @param \Drupal\views_remote_data\Events\RemoteDataLoadEntitiesEvent $event
   *   The event.
   *
   * @todo need tests which test this.
   */
  public function onLoadEntities(RemoteDataLoadEntitiesEvent $event): void {
    $supported_bases = [
      'views_remote_data_pokeapi',
    ];
    $base_tables = array_keys($event->getView()->getBaseTables());
    if (count(array_intersect($supported_bases, $base_tables)) > 0) {
      foreach ($event->getResults() as $result) {
        assert(property_exists($result, 'names'));
        assert(property_exists($result, 'flavor_text_entries'));
        assert(property_exists($result, 'varieties'));

        $varieties = array_filter($result->varieties, static fn (array $data) => $data['is_default']);
        $variant = $this->pokeApi->get($varieties[0]['pokemon']['url']);
        $image = $variant['sprites']['other']['official-artwork']['front_default'] ?? $variant['sprites']['front_default'];
        // @note: does not work with image styles.
        $file = File::create([
          'filename' => basename($image),
          'uri' => $image,
        ]);

        // Set the entity ID to verify tags bubble to the query's cache tags.
        $result->_entity = Node::create([
          'title' => array_map(
            static fn (array $data) => $data['name'],
            self::filterByLanguage($result->names, 'en')
          ),
          'field_description' => array_map(
            static fn (array $data) => $data['flavor_text'],
            self::filterByVersion(self::filterByLanguage($result->flavor_text_entries, 'en'))
          ),
          'type' => 'pokemon',
          'field_image' => $file,
        ]);
      }
    }
  }

  /**
   * Filters data by language.
   *
   * @param array $data
   *   The data.
   * @param string $langcode
   *   The language code.
   *
   * @return array
   *   The data, filtered by language.
   */
  private static function filterByLanguage(array $data, string $langcode): array {
    return array_filter($data, static fn (array $data) => $data['language']['name'] === $langcode);
  }

  /**
   * Filters data by versions.
   *
   * @param array $data
   *   The data.
   *
   * @return array
   *   The data, filtered versions.
   */
  private static function filterByVersion(array $data): array {
    // Fake filter to grab data from latest version.
    return [end($data)];
  }

  /**
   * Subscribes to populate the view results.
   *
   * @param \Drupal\views_remote_data\Events\RemoteDataQueryEvent $event
   *   The event.
   */
  public function onQuery(RemoteDataQueryEvent $event): void {
    $supported_bases = [
      'views_remote_data_pokeapi',
    ];
    $base_tables = array_keys($event->getView()->getBaseTables());
    if (count(array_intersect($supported_bases, $base_tables)) > 0) {
      $species = $this->pokeApi->listSpecies($event->getOffset(), $event->getLimit());
      foreach ($species['results'] as $record) {
        $event->addResult(new ResultRow(
          $this->pokeApi->getSpecies($record['name'])
        ));
      }
    }
  }

}
