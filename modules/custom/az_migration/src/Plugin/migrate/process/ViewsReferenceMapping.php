<?php

namespace Drupal\az_migration\Plugin\migrate\process;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\Attribute\MigrateProcess;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Drupal\views\Plugin\views\HandlerBase as ViewsHandler;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Process plugin that maps QS1 viewfield fields QS2 viewsreference fields.
 *
 * Used to map view and display names referenced in QS1 field_uaqs_view source
 * field values into viewsreference field values suitable for use in
 * field_az_view_reference destination fields in QS2.  Also handles migrating
 * viewsfield argument values using a the specified taxonomy term migrations.
 *
 * Expects a field_uaqs_view source field value.
 *
 * Available configuration keys (optional):
 * - views_mapping : May be used to specify how custom source view and display
 *   names are mapped to destination views as well as configure which migrations
 *   are used to perform argument ID migration lookups on.  Default mappings for
 *   QS1 views and displays are built-in but can be overridden using this
 *   configuration key.
 *
 * Examples:
 *
 * Consider a paragraph item migration, where you want to map the views
 * references in the source field_uaqs_view to QS2 view and display names and
 * also map additional custom source view and display names to the proper
 * destination view and display names and also migrate the argument values using
 * a custom taxonomy term migration.
 * @code
 * process:
 *   field_az_view_reference:
 *     source: field_uaqs_view
 *     plugin: az_views_reference_mapping
 *     view_mapping:
 *       neuroscience_faculty_directory:
 *         view: neuroscience_faculty_directory
 *         display:
 *           page: page
 *         argument_migrations:
 *           - az_person_categories
 *           - neuroscience_person_categories
 * @endcode
 */
#[MigrateProcess('az_views_reference_mapping')]
class ViewsReferenceMapping extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The migrate lookup service.
   *
   * @var \Drupal\migrate\MigrateLookupInterface
   */
  protected $migrateLookup;

  /**
   * Provides Quickstart 1 to Quickstart 2 view mapping data.
   *
   * @var array
   */
  const VIEW_MAPPING = [
    'uaqs_events' => [
      'view' => 'az_events',
      'display' => [
        'default' => 'page_1',
        'page' => 'page_1',
        'list_block' => 'page_1',
        'card_group_block' => 'az_grid',
        'block_1' => 'az_sidebar',
      ],
      'argument_migrations' => [
        'az_event_categories',
      ],
    ],
    'uaqs_news' => [
      'view' => 'az_news',
      'display' => [
        'default' => 'az_grid',
        'three_col_news_block_3' => 'az_grid',
        'three_col_news_block' => 'az_grid',
        'sidebar_promoted_news' => 'az_sidebar',
        'uaqs_teaser_list_page' => 'az_teaser_grid',
        'uaqs_media_list_page' => 'az_paged_row',
        'recent_news_marquee' => 'marquee',
        'recent_news_medium_media_list' => 'az_paged_row',
      ],
      'argument_migrations' => [
        'az_news_tags',
      ],
    ],
    'uaqs_person_directory' => [
      'view' => 'az_person',
      'display' => [
        'default' => 'grid',
        'page' => 'grid',
        'page_1' => 'row',
      ],
      'argument_migrations' => [
        'az_person_categories',
        'az_person_categories_secondary',
      ],
    ],
    'uaqs_content_chunks_views_page_by_category' => [
      'view' => 'az_page_by_category',
      'display' => [
        'default' => 'row',
        'page' => 'row',
        'page_1' => 'grid',
      ],
      'argument_migrations' => [
        'az_flexible_page_categories',
      ],
    ],
    'uaqs_alphabetical_listing' => [
      'view' => 'az_alphabetical_listing',
      'display' => [
        'default' => 'az_alphabetical_listing_main',
        'page' => 'az_alphabetical_listing_main',
      ],
      'argument_migrations' => [],
    ],
    'uaqs_hero_carousel' => [
      'view' => 'az_carousel',
      'display' => [
        'default' => 'front_carousel_block',
        'hero_block' => 'front_carousel_block',
        'hero_nav' => 'front_carousel_block',
      ],
      'argument_migrations' => [],
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
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $transformedValue = [];
    $viewData = [];
    $viewMapping = self::VIEW_MAPPING;

    if (!empty($this->configuration['view_mapping']) && is_array($this->configuration['view_mapping'])) {
      $viewMapping = array_merge($viewMapping, $this->configuration['view_mapping']);
    }

    // Get view and display name.
    $viewDisplay = explode("|", $value['vname']);
    $view = $viewDisplay[0];
    $display = $viewDisplay[1];

    if (isset($viewMapping[$view])) {
      $transformedValue['target_id'] = $viewMapping[$view]['view'];
      $transformedValue['display_id'] = $viewMapping[$view]['display'][$display];
      $argumentMigrations = $viewMapping[$view]['argument_migrations'];
    }
    else {
      $transformedValue['target_id'] = $view;
      $transformedValue['display_id'] = $display;
      $argumentMigrations = NULL;
    }

    if (!empty($value['vargs']) && !empty($argumentMigrations)) {
      // Parse view arguments.
      $arguments = [];
      $rawArguments = explode('/', $value['vargs']);
      foreach ($rawArguments as $rawArgument) {
        /** @var \stdClass $parsedArgument */
        $parsedArgument = ViewsHandler::breakString($rawArgument);
        $arguments[] = $parsedArgument;
      }

      $migratedArguments = [];
      foreach ($arguments as &$argument) {
        foreach ($argument->value as $i => $argValue) {
          $ids = $this->migrateLookup->lookup($argumentMigrations, [$argValue]);
          $id = reset($ids);
          // @todo Create stub if no matching ID found?
          if (!empty($id)) {
            $migratedId = reset($id);
            $argument->value[$i] = $migratedId;
          }
        }
        $migratedArguments[] = $argument;
      }
      $transformedArguments = [];
      foreach ($migratedArguments as $argument) {
        if (!empty($argument->operator) && count($argument->value) > 1) {
          $separator = ($argument->operator === 'and') ? ',' : '+';
          $transformedArguments[] = implode($separator, $argument->value);
        }
        else {
          $transformedArguments[] = reset($argument->value);
        }
      }
      $viewData['argument'] = implode('/', $transformedArguments);
    }
    $transformedValue['data'] = serialize($viewData);

    return $transformedValue;
  }

}
