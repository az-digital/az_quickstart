<?php

namespace Drupal\az_migration\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Process Plugin to map view and display for Chunks View paragraphs.
 *
 * @MigrateProcessPlugin(
 *   id = "paragraphs_chunks_view_display_mapping"
 * )
 */
class ParagraphsChunksViewDisplayMapping extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {

    // Collecting the view data field values.
    $view_data = [];

    // Getting view name and display name.
    $view_display = explode("|", $value['vname']);

    // View argument mapping.
    if ($value['vargs'] !== "") {
      $view_data['argument'] = $value['vargs'];
    }

    $value = [];
    $uaqs_events = [
      'view' => 'az_events',
      'display' => [
        'default' => 'page_1',
        'page' => 'page_1',
        'list_block' => 'page_1',
        'card_group_block' => 'az_grid',
        'block_1' => 'az_sidebar',
      ],
    ];
    $uaqs_news = [
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
    ];
    $uaqs_person_directory = [
      'view' => 'az_person',
      'display' => [
        'default' => 'grid',
        'page' => 'grid',
        'page_1' => 'row',
      ],
    ];
    $uaqs_content_chunks_views_page_by_category = [
      'view' => 'az_page_by_category',
      'display' => [
        'default' => 'row',
        'page' => 'row',
        'page_1' => 'grid',
      ],
    ];
    $view_mapping = [
      'uaqs_events' => $uaqs_events,
      'uaqs_news' => $uaqs_news,
      'uaqs_person_directory' => $uaqs_person_directory,
      'uaqs_content_chunks_views_page_by_category' => $uaqs_content_chunks_views_page_by_category,
    ];

    if (isset($view_mapping[$view_display[0]])) {
      $value['target_id'] = $view_mapping[$view_display[0]]['view'];
      $value['display_id'] = $view_mapping[$view_display[0]]['display'][$view_display[1]];
    }
    else {
      $value['target_id'] = $view_display[0];
      $value['display_id'] = $view_display[1];
    }

    // Setting Items per page: 6 for 3 Column news block.
    if ($view_display[0] === 'uaqs_news' && $view_display[1] === 'three_col_news_block') {
      $view_data['limit'] = 6;
    }

    $value['data'] = serialize($view_data);

    return $value;
  }

}
