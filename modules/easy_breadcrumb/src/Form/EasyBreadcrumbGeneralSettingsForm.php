<?php

namespace Drupal\easy_breadcrumb\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\easy_breadcrumb\EasyBreadcrumbConstants;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Build Easy Breadcrumb settings form.
 */
class EasyBreadcrumbGeneralSettingsForm extends ConfigFormBase {
  use StringTranslationTrait;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'easy_breadcrumb_general_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [EasyBreadcrumbConstants::MODULE_SETTINGS];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config(EasyBreadcrumbConstants::MODULE_SETTINGS);

    // Details for grouping general settings fields.
    $details_general = [
      '#type' => 'details',
      '#title' => $this->t('General settings'),
      '#open' => TRUE,
    ];

    $details_advanced = [
      '#type' => 'details',
      '#title' => $this->t('Advanced settings'),
      '#open' => TRUE,
    ];

    // If never set before ensure Applies to administration pages is on.
    $applies_admin_routes = $config->get(EasyBreadcrumbConstants::APPLIES_ADMIN_ROUTES);
    if (!isset($applies_admin_routes)) {
      $applies_admin_routes = TRUE;
    }
    $details_general[EasyBreadcrumbConstants::APPLIES_ADMIN_ROUTES] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Applies to administration pages'),
      '#description' => $this->t('Uncheck to disable Easy breadcrumb for administration pages and routes like this one.'),
      '#default_value' => $applies_admin_routes,
    ];

    $details_general[EasyBreadcrumbConstants::INCLUDE_INVALID_PATHS] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Include invalid paths alias as plain-text segments'),
      '#description' => $this->t('Include the invalid paths alias as plain-text segments in the breadcrumb.'),
      '#default_value' => $config->get(EasyBreadcrumbConstants::INCLUDE_INVALID_PATHS),
    ];

    $details_general[EasyBreadcrumbConstants::INCLUDE_TITLE_SEGMENT] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Include the current page as a segment in the breadcrumb'),
      '#description' => $this->t('Include the current page as the last segment in the breadcrumb.'),
      '#default_value' => $config->get(EasyBreadcrumbConstants::INCLUDE_TITLE_SEGMENT),
    ];

    $details_general[EasyBreadcrumbConstants::REMOVE_REPEATED_SEGMENTS] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Remove repeated identical segments'),
      '#description' => $this->t('Remove segments of the breadcrumb that are identical.'),
      '#default_value' => $config->get(EasyBreadcrumbConstants::REMOVE_REPEATED_SEGMENTS),
    ];

    $details_general[EasyBreadcrumbConstants::REMOVE_REPEATED_SEGMENTS_TEXT_ONLY] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Remove repeated identical segments - only validate on the text'),
      '#description' => $this->t('When removing identical segments only text is matched upon.'),
      '#default_value' => $config->get(EasyBreadcrumbConstants::REMOVE_REPEATED_SEGMENTS_TEXT_ONLY),
      '#states' => [
        'invisible' => [
          ':input[name="' . EasyBreadcrumbConstants::REMOVE_REPEATED_SEGMENTS . '"]' => ['checked' => FALSE],
        ],
      ],
    ];

    $details_general[EasyBreadcrumbConstants::INCLUDE_HOME_SEGMENT] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Include the front page as a segment in the breadcrumb'),
      '#description' => $this->t('Include the front page as the first segment in the breadcrumb.'),
      '#default_value' => $config->get(EasyBreadcrumbConstants::INCLUDE_HOME_SEGMENT),
    ];

    $details_general[EasyBreadcrumbConstants::ALTERNATIVE_TITLE_FIELD] = [
      '#type' => 'textfield',
      '#title' => $this->t('Alternative title field name for breadcrumb'),
      '#description' => $this->t('This field name is to be added in the entity to display the alternative title in the breadcrumb.'),
      '#default_value' => $config->get(EasyBreadcrumbConstants::ALTERNATIVE_TITLE_FIELD),
    ];

    $details_general[EasyBreadcrumbConstants::HOME_SEGMENT_TITLE] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title for the front page segment in the breadcrumb'),
      '#description' => $this->t('Text to be displayed as the front page segment. This field works together with the "Include the front page as a segment in the breadcrumb"-option.'),
      '#default_value' => $config->get(EasyBreadcrumbConstants::HOME_SEGMENT_TITLE),
    ];

    $details_general[EasyBreadcrumbConstants::TITLE_FROM_PAGE_WHEN_AVAILABLE] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use the real page title when available'),
      '#description' => $this->t('Use the real page title when it is available instead of always deducing it from the URL.'),
      '#default_value' => $config->get(EasyBreadcrumbConstants::TITLE_FROM_PAGE_WHEN_AVAILABLE),
    ];

    $details_general[EasyBreadcrumbConstants::USE_MENU_TITLE_AS_FALLBACK] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use menu title when available'),
      '#description' => $this->t('Use menu title instead of raw path component. The real page title setting above will take precedence over this setting. So, one or the other, but not both.'),
      '#default_value' => $config->get(EasyBreadcrumbConstants::USE_MENU_TITLE_AS_FALLBACK),
    ];

    $menu_list = array_map(function ($menu) {
      return $menu->label();
    }, $this->entityTypeManager->getStorage('menu')->loadMultiple());
    asort($menu_list);
    $details_general[EasyBreadcrumbConstants::MENU_TITLE_PREFERRED_MENU] = [
      '#type' => 'select',
      '#title' => $this->t('Preferred menu'),
      '#options' => $menu_list,
      '#empty_option' => $this->t('- None -'),
      '#empty_value' => '',
      '#description' => $this->t('Preferred menu to use as menu title source. Useful if menu links with identical paths exist in multiple menus.'),
      '#default_value' => $config->get(EasyBreadcrumbConstants::MENU_TITLE_PREFERRED_MENU),
      '#states' => [
        'disabled' => [
          ':input[name="' . EasyBreadcrumbConstants::USE_MENU_TITLE_AS_FALLBACK . '"]'
          => ['checked' => FALSE],
        ],
        'invisible' => [
          ':input[name="' . EasyBreadcrumbConstants::USE_MENU_TITLE_AS_FALLBACK . '"]'
          => ['checked' => FALSE],
        ],
      ],
    ];

    $details_general[EasyBreadcrumbConstants::USE_PAGE_TITLE_AS_MENU_TITLE_FALLBACK] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use page title as fallback for menu title'),
      '#description' => $this->t('Use page title as fallback if menu title cannot be found. This option works when not using "real page title" above.'),
      '#default_value' => $config->get(EasyBreadcrumbConstants::USE_PAGE_TITLE_AS_MENU_TITLE_FALLBACK),
      // This option is evaluated only if the USE_MENU_TITLE_AS_FALLBACK
      // is checked.
      '#states' => [
        'disabled' => [
          ':input[name="' . EasyBreadcrumbConstants::USE_MENU_TITLE_AS_FALLBACK . '"]' => ['checked' => FALSE],
        ],
        'invisible' => [
          ':input[name="' . EasyBreadcrumbConstants::USE_MENU_TITLE_AS_FALLBACK . '"]' => ['checked' => FALSE],
        ],
      ],
    ];

    $details_general[EasyBreadcrumbConstants::USE_SITE_TITLE] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use site title as the front page segment'),
      '#description' => $this->t('Use site title as the front page segment. This field works together with the "Include the front page as a segment in the breadcrumb"-option.'),
      '#default_value' => $config->get(EasyBreadcrumbConstants::USE_SITE_TITLE),
    ];

    $details_general[EasyBreadcrumbConstants::ADD_STRUCTURED_DATA_JSON_LD] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Add current breadcrumb as structured data.'),
      '#description'   => $this->t('Check to have the current breadcrumb trail added as <a href="@href" target="_blank">structured data</a> in JSON-LD to the HTML <code><head></code>.', ['@href' => 'https://developers.google.com/search/docs/data-types/breadcrumb']),
      '#default_value' => $config->get(EasyBreadcrumbConstants::ADD_STRUCTURED_DATA_JSON_LD),
    ];

    $details_general[EasyBreadcrumbConstants::FOLLOW_REDIRECTS] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Follow redirects.'),
      '#description'   => $this->t('In case the <a href="@href" target="_blank">redirect module</a> is enabled, follow the configured redirects', ['@href' => 'https://www.drupal.org/project/redirect']),
      '#default_value' => $config->get(EasyBreadcrumbConstants::FOLLOW_REDIRECTS),
    ];

    // Formats the excluded paths array as line separated list of paths
    // before displaying them.
    $excluded_paths = $config->get(EasyBreadcrumbConstants::EXCLUDED_PATHS);

    $details_advanced[EasyBreadcrumbConstants::EXCLUDED_PATHS] = [
      '#type' => 'textarea',
      '#title' => $this->t('Paths to be excluded while generating segments'),
      '#description' => $this->t('Enter a line separated list of paths to be excluded while generating the segments.
      Slashes must be escaped i.e.: ( foo/bar should be foo\/bar ) Paths may use simple regex, i.e.: report\/2[0-9][0-9][0-9].'),
      '#default_value' => $excluded_paths,
    ];

    $details_general[EasyBreadcrumbConstants::LIMIT_SEGMENT_DISPLAY] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Limit breadcrumb trail segments'),
      '#description' => $this->t('Limit the number of displayed breadcrumb trail segments.'),
      '#default_value' => $config->get(EasyBreadcrumbConstants::LIMIT_SEGMENT_DISPLAY),
    ];

    $details_general[EasyBreadcrumbConstants::SEGMENT_DISPLAY_LIMIT] = [
      '#type' => 'number',
      '#title' => $this->t('Breadcrumb segment count'),
      '#description' => $this->t('Number of breadcrumb trail segments to display'),
      '#default_value' => $config->get(EasyBreadcrumbConstants::SEGMENT_DISPLAY_LIMIT),
    ];

    $details_general[EasyBreadcrumbConstants::SEGMENT_DISPLAY_MINIMUM] = [
      '#type' => 'number',
      '#title' => $this->t('Breadcrumb segment minimum count'),
      '#description' => $this->t('Minimum number of breadcrumb trail segments needed to display the breadcrumbs.'),
      '#default_value' => $config->get(EasyBreadcrumbConstants::SEGMENT_DISPLAY_MINIMUM),
    ];

    // Formats the excluded paths array as line separated list of paths
    // before displaying them.
    $replaced_titles = $config->get(EasyBreadcrumbConstants::REPLACED_TITLES);

    $details_advanced[EasyBreadcrumbConstants::REPLACED_TITLES] = [
      '#type' => 'textarea',
      '#title' => $this->t('Titles to be replaced while generating segments'),
      '#description' => $this->t('Enter a line separated list of titles with their replacements separated by ::.<br>
			For example TITLE::DIFFERENT_TITLE<br>This field works together with the option "Use the real page title when available" option.'),
      '#default_value' => $replaced_titles,
    ];

    // Formats the custom paths array as line separated list of paths
    // before displaying them.
    $custom_paths = $config->get(EasyBreadcrumbConstants::CUSTOM_PATHS);

    $details_advanced[EasyBreadcrumbConstants::CUSTOM_PATHS] = [
      '#type' => 'textarea',
      '#title' => $this->t('Paths to replace with custom breadcrumbs'),
      '#description' => $this->t('Enter a line separated list of internal paths followed by breadcrumb pattern. Separate crumbs from their path with a vertical bar ("|"). Separate crumbs with double-colon ("::"). Omit the URL to display an unlinked crumb. Fields will be trimmed to remove extra start/end spaces, so you can use them to help format your input, if desired. Replaced Titles will not be processed on custom paths. Excluded paths listed here will have breadcrumbs added. Examples (with and without extra spacing):<br><code>/news/archive/site_launched  ::  News | /news  ::  Archive | /news/archive  ::  Site Launched<br>/your/path::LinkedCrumb1|url1::LinkedCrumb2|url2::UnlinkedCrumb3</code><br><p>It is also possible to express the path to be matched as a <a href="https://www.php.net/manual/en/book.pcre.php" target="_blank">regex expression</a>. "regex!" must be added to the start of the path to match in order for it to be interpreted as regex:<br><code>regex!/news/archive/\d{4} ::  News | /news  ::  Archive | /news/archive</code><p>Expressions can even include matching groups which can be referenced in the path of a segment path:<br><code>regex!/groups/([^/]*)/info :: Groups | /groups :: Group | /groups/$1</code></p><p>To use the current page title as a title component, use <code>&lt;title&gt;</code> (Must have <strong>"Use the real page title when available"</strong> enabled)</p>'),
      '#default_value' => $custom_paths,
    ];

    $details_advanced[EasyBreadcrumbConstants::HOME_SEGMENT_KEEP] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display the front page segment on the front page'),
      '#description' => $this->t('If checked, the Home segment will be displayed on the front page.'),
      '#default_value' => $config->get(EasyBreadcrumbConstants::HOME_SEGMENT_KEEP),
      '#states' => [
        'visible' => [
          ':input[name="' . EasyBreadcrumbConstants::HOME_SEGMENT_TITLE . '"]' => ['empty' => FALSE],
        ],
      ],
    ];

    $details_advanced[EasyBreadcrumbConstants::HOME_SEGMENT_VALIDATION_SKIP] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Do not check for path, that is duplicate of home page.'),
      '#description' => $this->t('If checked, validation for similarity between the breadcrumb element and the home page, will be skipped.'),
      '#default_value' => $config->get(EasyBreadcrumbConstants::HOME_SEGMENT_VALIDATION_SKIP),
    ];

    $details_advanced[EasyBreadcrumbConstants::TITLE_SEGMENT_AS_LINK] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Make the current page title segment a link'),
      '#description' => $this->t('Prints the page title segment as a link. This option works together with the "Include the current page as a segment in the breadcrumb"-option.'),
      '#default_value' => $config->get(EasyBreadcrumbConstants::TITLE_SEGMENT_AS_LINK),
    ];

    $details_advanced[EasyBreadcrumbConstants::LANGUAGE_PATH_PREFIX_AS_SEGMENT] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Make the language path prefix a segment'),
      '#description' => $this->t('On multilingual sites where a path prefix ("/en") is used, add this in the breadcrumb.'),
      '#default_value' => $config->get(EasyBreadcrumbConstants::LANGUAGE_PATH_PREFIX_AS_SEGMENT),
    ];

    $details_advanced[EasyBreadcrumbConstants::ABSOLUTE_PATHS] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use absolute path for Breadcrumb links'),
      '#description' => $this->t('By selecting, absolute paths will be used (default: false) instead of relative.'),
      '#default_value' => $config->get(EasyBreadcrumbConstants::ABSOLUTE_PATHS),
    ];

    $details_advanced[EasyBreadcrumbConstants::HIDE_SINGLE_HOME_ITEM] = [
      '#type' => 'checkbox',
      '#title' => $this->t("Hide link to home page if it's the only breadcrumb item"),
      '#description' => $this->t('Hide the breadcrumb when it only links to the home page and nothing more.'),
      '#default_value' => $config->get(EasyBreadcrumbConstants::HIDE_SINGLE_HOME_ITEM),
    ];

    $details_advanced[EasyBreadcrumbConstants::TERM_HIERARCHY] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Add parent hierarchy'),
      '#description' => $this->t('Add all taxonomy parents in the crumb for current term.'),
      '#default_value' => $config->get(EasyBreadcrumbConstants::TERM_HIERARCHY),
    ];

    $details_advanced[EasyBreadcrumbConstants::CAPITALIZATOR_MODE] = [
      '#type' => 'select',
      '#title' => $this->t("Transformation mode for the segments' titles"),
      '#options' => [
        'none' => $this->t('None'),
        'ucwords' => $this->t("Capitalize the first letter of each word in the segment"),
        'ucfirst' => $this->t("Only capitalize the first letter of each segment"),
        'ucall' => $this->t("Capitalize all the letters of each word in the segment"),
        'ucforce' => $this->t("Capitalize only the words that are set below"),
      ],
      '#description' => $this->t("Choose the transformation mode you want to apply to the segments' titles. E.g.: 'blog/once-a-time' -> 'Home >> Blog >> Once a Time'."),
      '#default_value' => $config->get(EasyBreadcrumbConstants::CAPITALIZATOR_MODE),
    ];

    // Formats the ignored-words array as space separated list of words
    // (word1 word2 wordN) before displaying them.
    $capitalizator_ignored_words_arr = $config->get(EasyBreadcrumbConstants::CAPITALIZATOR_IGNORED_WORDS) ?? [];
    $capitalizator_ignored_words = @implode(' ', $capitalizator_ignored_words_arr);

    $details_advanced[EasyBreadcrumbConstants::CAPITALIZATOR_IGNORED_WORDS] = [
      '#type' => 'textarea',
      '#rows' => 3,
      '#title' => $this->t("Words to be ignored by the 'capitalizator'"),
      '#description' => $this->t("Enter a space separated list of words to be ignored by the 'capitalizator'. This will be applied only to the words not at the beginning of each segment. E.g.: of and."),
      '#default_value' => $capitalizator_ignored_words,
      '#states' => [
        'visible' => [
          ':input[name="' . EasyBreadcrumbConstants::CAPITALIZATOR_MODE . '"]' => ['value' => 'ucwords'],
        ],
        'invisible' => [
          ':input[name="' . EasyBreadcrumbConstants::CAPITALIZATOR_MODE . '"]' => ['!value' => 'ucwords'],
        ],
      ],
    ];

    // Formats the forced-words array as space separated list of words
    // (word1 word2 wordN) before displaying them.
    $capitalizator_forced_words_arr = $config->get(EasyBreadcrumbConstants::CAPITALIZATOR_FORCED_WORDS) ?? [];
    $capitalizator_forced_words = @implode(' ', $capitalizator_forced_words_arr);

    $details_advanced[EasyBreadcrumbConstants::CAPITALIZATOR_FORCED_WORDS] = [
      '#type' => 'textarea',
      '#rows' => 3,
      '#title' => $this->t("Words to be forced to capitalized by the 'capitalizator'"),
      '#description' => $this->t("Enter a space separated list of words to be forced by the 'capitalizator'. This will be applied only to the words that are listed. This field is case sensitive. E.g.: if you want to capitalize your brand's name."),
      '#default_value' => $capitalizator_forced_words,
      '#states' => [
        'visible' => [
          ':input[name="' . EasyBreadcrumbConstants::CAPITALIZATOR_MODE . '"]' => ['value' => 'ucforce'],
        ],
        'invisible' => [
          ':input[name="' . EasyBreadcrumbConstants::CAPITALIZATOR_MODE . '"]' => ['!value' => 'ucforce'],
        ],
      ],
    ];

    $details_advanced[EasyBreadcrumbConstants::CAPITALIZATOR_FORCED_WORDS_CASE_SENSITIVITY] = [
      '#type' => 'checkbox',
      '#title' => $this->t("Use case sensitivity when matching words to be forced to capitalization."),
      '#default_value' => $config->get(EasyBreadcrumbConstants::CAPITALIZATOR_FORCED_WORDS_CASE_SENSITIVITY),
      '#states' => [
        'visible' => [
          ':input[name="' . EasyBreadcrumbConstants::CAPITALIZATOR_MODE . '"]' => ['value' => 'ucforce'],
        ],
        'invisible' => [
          ':input[name="' . EasyBreadcrumbConstants::CAPITALIZATOR_MODE . '"]' => ['!value' => 'ucforce'],
        ],
      ],
      '#description' => $this->t("If checked, it matches drupal with drupal, druPAL with druPAL. Unchecked, it matches drupal with Drupal, drupal with druPAL."),
    ];

    $details_advanced[EasyBreadcrumbConstants::CAPITALIZATOR_FORCED_WORDS_FIRST_LETTER] = [
      '#type' => 'checkbox',
      '#title' => $this->t("Make the first letters of each segment capitalized."),
      '#default_value' => $config->get(EasyBreadcrumbConstants::CAPITALIZATOR_FORCED_WORDS_FIRST_LETTER),
      '#states' => [
        'visible' => [
          ':input[name="' . EasyBreadcrumbConstants::CAPITALIZATOR_MODE . '"]' => ['value' => 'ucforce'],
        ],
        'invisible' => [
          ':input[name="' . EasyBreadcrumbConstants::CAPITALIZATOR_MODE . '"]' => ['!value' => 'ucforce'],
        ],
      ],
    ];

    $details_advanced[EasyBreadcrumbConstants::TRUNCATOR_MODE] = [
      '#type' => 'checkbox',
      '#title' => $this->t("Truncate the page's title to a maximum number."),
      '#description' => $this->t("Example: if you set it to 10, from <em>Long page title</em> will be <em>Long pa...</em>"),
      '#default_value' => $config->get(EasyBreadcrumbConstants::TRUNCATOR_MODE),
    ];

    $details_advanced[EasyBreadcrumbConstants::TRUNCATOR_LENGTH] = [
      '#type' => 'number',
      '#title' => $this->t("Set the limit of truncation"),
      '#default_value' => $config->get(EasyBreadcrumbConstants::TRUNCATOR_LENGTH),
      '#states' => [
        'visible' => [
          ':input[name="' . EasyBreadcrumbConstants::TRUNCATOR_MODE . '"]' => ['checked' => TRUE],
        ],
        'invisible' => [
          ':input[name="' . EasyBreadcrumbConstants::TRUNCATOR_MODE . '"]' => ['checked' => FALSE],
        ],
      ],
    ];

    $details_advanced[EasyBreadcrumbConstants::TRUNCATOR_DOTS] = [
      '#type' => 'checkbox',
      '#title' => $this->t("The truncated page's title will have 3 dots in its end."),
      '#default_value' => $config->get(EasyBreadcrumbConstants::TRUNCATOR_DOTS),
      '#states' => [
        'visible' => [
          ':input[name="' . EasyBreadcrumbConstants::TRUNCATOR_MODE . '"]' => ['checked' => TRUE],
        ],
        'invisible' => [
          ':input[name="' . EasyBreadcrumbConstants::TRUNCATOR_MODE . '"]' => ['checked' => FALSE],
        ],
      ],
    ];

    $form = [];

    // Inserts the details for grouping general settings fields.
    $form[EasyBreadcrumbConstants::MODULE_NAME][] = $details_general;
    $form[EasyBreadcrumbConstants::MODULE_NAME][] = $details_advanced;

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $settings = $this->configFactory->getEditable(EasyBreadcrumbConstants::MODULE_SETTINGS);

    // Get the values.
    $values = $form_state->cleanValues()->getValues();

    // Convert words lists to arrays where required.
    $keys_to_process = [
      EasyBreadcrumbConstants::CAPITALIZATOR_IGNORED_WORDS,
      EasyBreadcrumbConstants::CAPITALIZATOR_FORCED_WORDS,
    ];
    foreach ($keys_to_process as $key) {
      $values[$key] = $this->processValuesToArray($values[$key]);
    }

    foreach ($values as $field_key => $field_value) {
      $settings->set($field_key, $field_value);
    }
    $settings->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Pre-processes the list of words for storing them as an array.
   *
   * Replaces line-endings by spaces and splits words by spaces.
   * E.g.: array('of','and').
   *
   * @param string $words
   *   A string of words.
   *
   * @return array
   *   An array of processed words.
   */
  private function processValuesToArray($words) {
    return preg_split('/\s+/', $words, -1, PREG_SPLIT_NO_EMPTY);
  }

}
