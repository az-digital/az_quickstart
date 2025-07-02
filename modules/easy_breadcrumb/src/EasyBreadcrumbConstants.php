<?php

namespace Drupal\easy_breadcrumb;

/**
 * EasyBreadcrumb module's constants.
 */
class EasyBreadcrumbConstants {

  /**
   * Module's name.
   */
  const MODULE_NAME = 'easy_breadcrumb';

  /**
   * Module's settings.
   */
  const MODULE_SETTINGS = 'easy_breadcrumb.settings';
  /**
   * Flag for applying easy breadcrumb to admin routes.
   */
  const APPLIES_ADMIN_ROUTES = 'applies_admin_routes';

  /**
   * Flag for including invalid paths while generating the breadcrumb segments.
   */
  const INCLUDE_INVALID_PATHS = 'include_invalid_paths';

  /**
   * List of paths to be excluded from the generated segments.
   */
  const EXCLUDED_PATHS = 'excluded_paths';

  /**
   * List of titles to replace.
   */
  const REPLACED_TITLES = 'replaced_titles';

  /**
   * List of paths for custom breadcrumbs.
   */
  const CUSTOM_PATHS = 'custom_paths';

  /**
   * Separator between segments.
   */
  const SEGMENTS_SEPARATOR = 'segments_separator';

  /**
   * Flag for including or not the front page as a segment.
   */
  const INCLUDE_HOME_SEGMENT = 'include_home_segment';

  /**
   * Alternative title field for breadcrumbs.
   */
  const ALTERNATIVE_TITLE_FIELD = 'alternative_title_field';

  /**
   * Title for the front page segment.
   */
  const HOME_SEGMENT_TITLE = 'home_segment_title';

  /**
   * Flag for keeping the breadcrumb on the front page.
   */
  const HOME_SEGMENT_KEEP = 'home_segment_keep';

  /**
   * Flag for skipping validation for links the same as home page one.
   */
  const HOME_SEGMENT_VALIDATION_SKIP = 'home_segment_validation_skip';

  /**
   * Flag for including or not the page's title as a segment.
   */
  const INCLUDE_TITLE_SEGMENT = 'include_title_segment';

  /**
   * Flag for printing the page's title as a link, or printing it as a text.
   */
  const TITLE_SEGMENT_AS_LINK = 'title_segment_as_link';

  /**
   * Use the page's title when it is available.
   */
  const TITLE_FROM_PAGE_WHEN_AVAILABLE = 'title_from_page_when_available';

  /**
   * Transformation mode to apply to the segments.
   */
  const CAPITALIZATOR_MODE = 'capitalizator_mode';

  /**
   * List of words to be ignored by the 'capitalizator'. E.g.: of and.
   */
  const CAPITALIZATOR_IGNORED_WORDS = 'capitalizator_ignored_words';

  /**
   * List of words to be forced by the 'capitalizator'. E.g.: your brand's name.
   */
  const CAPITALIZATOR_FORCED_WORDS = 'capitalizator_forced_words';

  /**
   * List of words to be forced by the 'capitalizator'. E.g.: your brand's name.
   */
  const CAPITALIZATOR_FORCED_WORDS_FIRST_LETTER = 'capitalizator_forced_words_first_letter';

  /**
   * Logical value to 'Make the first letters of each segment capitalized'.
   */
  const CAPITALIZATOR_FORCED_WORDS_CASE_SENSITIVITY = 'capitalizator_forced_words_case_sensitivity';

  /**
   * Flag for showing the language prefix as its own segment.
   */
  const LANGUAGE_PATH_PREFIX_AS_SEGMENT = 'language_path_prefix_as_segment';

  /**
   * Use menu title as fallback.
   */
  const USE_MENU_TITLE_AS_FALLBACK = 'use_menu_title_as_fallback';

  /**
   * Use page title as fallback for menu title.
   */
  const USE_PAGE_TITLE_AS_MENU_TITLE_FALLBACK = 'use_page_title_as_menu_title_fallback';

  /**
   * Preferred menu to use as menu title source.
   */
  const MENU_TITLE_PREFERRED_MENU = 'menu_title_preferred_menu';

  /**
   * Use site title as the front page segment.
   */
  const USE_SITE_TITLE = 'use_site_title';

  /**
   * Flag for removing repeated identical segments from the breadcrumb.
   */
  const REMOVE_REPEATED_SEGMENTS = 'remove_repeated_segments';

  /**
   * Flag to remove repeated segments from breadcrumb ignoring url information.
   */
  const REMOVE_REPEATED_SEGMENTS_TEXT_ONLY = 'remove_repeated_segments_text_only';

  /**
   * Flag for storing absolute path settings.
   */
  const ABSOLUTE_PATHS = 'absolute_paths';

  /**
   * Flag for storing single home item settings.
   */
  const HIDE_SINGLE_HOME_ITEM = 'hide_single_home_item';

  /**
   * Flag for using term hierarchy.
   */
  const TERM_HIERARCHY = 'term_hierarchy';

  /**
   * Flag for adding the breadcrumb as structured to the HTML head.
   */
  const ADD_STRUCTURED_DATA_JSON_LD = 'add_structured_data_json_ld';

  /**
   * Follow redirects in case the redirect module is enabled.
   */
  const FOLLOW_REDIRECTS = 'follow_redirects';

  /**
   * Limit breadcrumb trail segments.
   */
  const LIMIT_SEGMENT_DISPLAY = 'limit_segment_display';

  /**
   * Breadcrumb segment count.
   */
  const SEGMENT_DISPLAY_LIMIT = 'segment_display_limit';

  /**
   * Breadcrumb segment minimum count.
   */
  const SEGMENT_DISPLAY_MINIMUM = 'segment_display_minimum';

  /**
   * Breadcrumb truncator mode.
   */
  const TRUNCATOR_MODE = 'truncator_mode';

  /**
   * Breadcrumb truncator length.
   */
  const TRUNCATOR_LENGTH = 'truncator_length';

  /**
   * Breadcrumbs truncator dots.
   */
  const TRUNCATOR_DOTS = 'truncator_dots';

  /**
   * Default list of excluded paths.
   *
   * @return array
   *   Default list of ignored paths.
   */
  public static function defaultExcludedPaths() {
    static $default_excluded_paths = [
      'search',
      'search/node',
    ];

    return $default_excluded_paths;
  }

  /**
   * Default list of replaced titles.
   *
   * @return array
   *   Default list of replaced titles.
   */
  public static function defaultReplacedTitles() {

    return [];
  }

  /**
   * Default list of replaced paths.
   *
   * @return array
   *   Default list of replaced paths.
   */
  public static function defaultCustomPaths() {

    return [];
  }

}
