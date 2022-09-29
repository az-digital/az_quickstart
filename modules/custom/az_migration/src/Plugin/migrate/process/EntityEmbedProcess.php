<?php

namespace Drupal\az_migration\Plugin\migrate\process;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Drupal\Core\Database\Database;

/**
 * Process Plugin to handle embedded entities in HTML text.
 *
 * @MigrateProcessPlugin(
 *   id = "az_entity_embed_process"
 * )
 *
 * This plugin processes HTML text that has had markup embedded within
 * it from the entity_embed module of D7. It does this by parsing the relevant
 * HTML, seeking out embed tags, and transforming the id numbers to those of
 * the destination system. In order to do this, it may need additional
 * information about custom migrations to know which migrations and view_modes
 * are needed to properly lookup the id numbers and displays of custom content.
 *
 * The optional migrations key may be used to specify a migration for any given
 * source content types. This is used to compute id number change between soure
 * and destination locales for custom content types.
 *
 * The optional view_modes key may be used to specify how view_modes are mapped
 * from the source system to the destination.
 *
 * @code
 *   process:
 *     field_example:
 *       plugin: entity_embed_process
 *       source: example
 *       migrations:
 *         my_content_type1: my_migration1
 *         my_content_type2: my_migration2
 *       view_modes:
 *         original_view_mode1: new_view_mode1
 *         original_view_mode2: new_view_mode2
 * @endcode
 */
class EntityEmbedProcess extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The migrate lookup service.
   *
   * @var \Drupal\migrate\MigrateLookupInterface
   */
  protected $migrateLookup;

  /**
   * The migrate stub service.
   *
   * @var \Drupal\migrate\MigrateStubInterface
   */
  protected $migrateStub;

  /**
   * The entity type manager service..
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Mapping of content type to migration name.
   *
   * @var array
   */
  const MIGRATION_MAPPING = [
    'uaqs_flexible_page' => 'az_node_flexible_page',
    'uaqs_page' => 'az_node_uaqs_basic_page_to_az_page',
    'uaqs_carousel_item' => 'az_node_carousel',
    'uaqs_event' => 'az_node_event',
    'uaqs_news' => 'az_node_news',
    'uaqs_person' => 'az_node_person',
  ];

  /**
   * Mapping of type and view mode to new view mode.
   *
   * @var array
   */
  const VIEW_MODES = [
    'file' => [
      'default' => 'az_small',
      'preview' => 'media_library',
      'uaqs_inline_link' => 'az_card_image',
      'uaqs_small' => 'az_small',
      'uaqs_medium' => 'az_medium',
      'uaqs_large' => 'az_large',
      'uaqs_square' => 'az_square',
      'uaqs_media_list' => 'az_card_image',
    ],
    'node' => [
      'default' => 'full',
      'full' => 'full',
      'teaser' => 'teaser',
      'rss' => 'rss',
      'search_index' => 'search_index',
      'search_result' => 'search_result',
      'token' => 'token',
      'uaqs_featured_content' => 'full',
      'uaqs_teaser' => 'teaser',
      'uaqs_sidebar_teaser_list' => 'az_minimal_media_list',
      'uaqs_med_media_list' => 'az_medium_media_list',
      'uaqs_card' => 'az_card',
      'uaqs_marquee' => 'az_marquee',
    ],
  ];

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
    );

    $instance->migrateLookup = $container->get('migrate.lookup');
    $instance->migrateStub = $container->get('migrate.stub');
    $instance->entityTypeManager = $container->get('entity_type.manager');
    return $instance;
  }

  /**
   * Generate a translated DOM element for the new embed.
   *
   * @param string $id
   *   Source id (nid, etc.)
   * @param string $type
   *   Embed type, eg. node, file.
   * @param string $tag
   *   HTML Tag type to use for embed.
   * @param string $view
   *   Drupal view mode.
   * @param \DOMDocument $dom
   *   The active DOM.
   * @param \DOMElement $element
   *   The element to be replaced.
   * @param string $migration
   *   Migration name of id, for stubbing/lookup.
   * @param string $storage
   *   Storage controller for embedded item, eg. node or media.
   *
   * @return \DOMDocument
   *   The new DOM element to use for replacement. NULL if none.
   */
  public function updateEmbedTag($id, $type, $tag, $view, \DOMDocument $dom, \DOMElement $element, $migration, $storage) {

    if (empty($id) || empty($type) || empty($tag)) {
      return NULL;
    }

    // Set up our replacement element.
    $changed = $dom->createElement($tag);
    $changed->setAttribute('data-entity-type', $type);
    $ids = $this->migrateLookup->lookup($migration, [$id]);
    if (empty($ids)) {
      $ids = $this->migrateStub->createStub($migration, [$id]);
    }
    // We eventually found our id, by lookup or stubbing it.
    if (!empty($ids)) {
      $id = reset($ids);
      if (!empty($id)) {
        $eid = reset($id);
        $entity = $this->entityTypeManager->getStorage($storage)->load($eid);
        if ($entity) {
          $changed->setAttribute('data-entity-uuid', $entity->uuid());
        }
      }
    }

    // Data alignment.
    $align = $element->getAttribute('data-align');
    if (!empty($align)) {
      $changed->setAttribute('data-align', $align);
    }

    // Alt text.
    $alt = $element->getAttribute('alt');
    if (!empty($alt)) {
      $changed->setAttribute('alt', $alt);
    }

    // Type specific attributes.
    switch ($type) {
      case 'file':
        $changed->setAttribute('data-view-mode', $view);
        break;

      case 'node':
        $changed->setAttribute('data-embed-button', "az_embed_content");
        $changed->setAttribute('data-entity-embed-display', "view_mode:node.{$view}");
        break;
    }
    return $changed;
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {

    // Return $value if there are no <drupal-entity> elements.
    if (strpos($value, '<drupal-entity ') === FALSE) {
      return $value;
    }
    // Convert $value to UTF-8.
    $value = mb_convert_encoding($value, 'HTML-ENTITIES', 'UTF-8');

    $dom = new \DOMDocument('1.0', 'UTF-8');
    $dom->loadHTML($value, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    $elements = $dom->getElementsByTagName("drupal-entity");

    // Configuration of custom content.
    $migrations = self::MIGRATION_MAPPING;
    $view_modes = self::VIEW_MODES;

    if (!empty($this->configuration['migrations']) && is_array($this->configuration['migrations'])) {
      $migrations = array_merge($migrations, $this->configuration['migrations']);
    }
    if (!empty($this->configuration['view_modes']) && is_array($this->configuration['view_modes'])) {
      $view_modes = array_merge($view_modes, $this->configuration['view_modes']);
    }

    // We iterate backwards because it simplifies removal logic.
    for ($i = $elements->length - 1; $i >= 0; $i--) {
      $element = $elements->item($i);
      $type = $element->getAttribute('data-entity-type');
      $id = $element->getAttribute('data-entity-id');
      $view = 'default';

      // See if we have a view mode.
      $settings = $element->getAttribute('data-entity-embed-settings');
      if (!empty($settings)) {
        $settings = json_decode($settings, TRUE);
        $v = $view;
        // Attempt to map our view mode from QS1 to QS2.
        if (!empty($settings['view_mode'])) {
          $v = $settings['view_mode'];
        }
        elseif (!empty($settings['file_view_mode'])) {
          $v = $settings['file_view_mode'];
        }
        if (isset($view_modes[$type][$v])) {
          $view = $view_modes[$type][$v];
        }
      }

      $post = NULL;
      switch ($type) {
        // Embedded file.
        case 'file':
          $post = $this->updateEmbedTag($id, 'media', 'drupal-media', $view, $dom, $element, 'az_media', 'media');
          break;

        // Embedded node. Special consideration, as we need to know which
        // migration the node is part of, if any. Migration of an Embedded
        // node only is defined if it's a type that can be migrated.
        case 'node':
          // Lookup of content type.
          $node_type = Database::getConnection('migrate')
            ->query('SELECT type FROM {node} WHERE nid = :nid', [':nid' => $id])
            ->fetchField();
          if (!empty($node_type)) {
            // Map our D7 node type to a migration. If we can't, we have no
            // guarantee our node is a migrated one.
            if (!empty($migrations[$node_type])) {
              $migration = $migrations[$node_type];
              $post = $this->updateEmbedTag($id, 'node', 'drupal-entity', $view, $dom, $element, $migration, 'node');
            }

          }

          break;

        // Unimplemented type.
        default:
          break;
      }
      // No replacement was created. Remove the DOM element.
      if (empty($post)) {
        $element->parentNode->removeChild($element);
      }
      // Insert the new element into the DOM.
      else {
        $element->parentNode->replaceChild($post, $element);
      }
    }

    $value = $dom->SaveHTML();
    return $value;
  }

}
