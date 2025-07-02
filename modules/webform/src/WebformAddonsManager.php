<?php

namespace Drupal\webform;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;

/**
 * Webform add-ons manager.
 */
class WebformAddonsManager implements WebformAddonsManagerInterface {

  use StringTranslationTrait;

  /**
   * Projects that provides additional functionality to the Webform module.
   *
   * @var array
   */
  protected $projects;

  /**
   * {@inheritdoc}
   */
  public function getProject($name) {
    $this->initProjects();
    return $this->projects[$name];
  }

  /**
   * {@inheritdoc}
   */
  public function getProjects($category = NULL) {
    $this->initProjects();
    $projects = $this->projects;
    if ($category) {
      foreach ($projects as $project_name => $project) {
        if ($project['category'] !== $category) {
          unset($projects[$project_name]);
        }
      }
    }
    return $projects;
  }

  /**
   * {@inheritdoc}
   */
  public function getThirdPartySettings() {
    $projects = $this->getProjects();
    foreach ($projects as $project_name => $project) {
      if (empty($project['third_party_settings'])) {
        unset($projects[$project_name]);
      }
    }
    return $projects;
  }

  /**
   * {@inheritdoc}
   */
  public function getCategories() {
    $categories = [];
    $categories['applications'] = [
      'title' => $this->t('Applications'),
    ];
    $categories['element'] = [
      'title' => $this->t('Elements'),
    ];
    $categories['enhancement'] = [
      'title' => $this->t('Enhancements'),
    ];
    $categories['integration'] = [
      'title' => $this->t('Integrations'),
    ];
    $categories['mail'] = [
      'title' => $this->t('Mail'),
    ];
    $categories['migrate'] = [
      'title' => $this->t('Migrate'),
    ];
    $categories['multilingual'] = [
      'title' => $this->t('Multilingual'),
    ];
    $categories['spam'] = [
      'title' => $this->t('SPAM Protection'),
    ];
    $categories['submission'] = [
      'title' => $this->t('Submissions'),
    ];
    $categories['validation'] = [
      'title' => $this->t('Validation'),
    ];
    $categories['utility'] = [
      'title' => $this->t('Utility'),
    ];
    $categories['web_services'] = [
      'title' => $this->t('Web services'),
    ];
    $categories['workflow'] = [
      'title' => $this->t('Workflow'),
    ];
    $categories['development'] = [
      'title' => $this->t('Development'),
    ];
    return $categories;
  }

  /**
   * Initialize add-on projects.
   */
  protected function initProjects() {
    if (!empty($this->projects)) {
      return;
    }

    $projects = [];

    /* ********************************************************************** */
    // Applications.
    /* ********************************************************************** */

    // Applications: Academic Applications.
    $projects['academic_applications'] = [
      'title' => $this->t('Academic Applications'),
      'description' => $this->t('Provides a simple Webform-based system for applying to academic programs.'),
      'url' => Url::fromUri('https://www.drupal.org/project/academic_applications'),
      'category' => 'applications',
    ];

    /* ********************************************************************** */
    // Element.
    /* ********************************************************************** */

    // Element: Address.
    $projects['address'] = [
      'title' => $this->t('Address'),
      'description' => $this->t('Provides functionality for storing, validating and displaying international postal addresses.'),
      'url' => Url::fromUri('https://www.drupal.org/project/address'),
      'category' => 'element',
      'recommended' => TRUE,
    ];

    // Element: Denormalized Webform Filters.
    $projects['denormalized_webform_filter'] = [
      'title' => $this->t('Denormalized Webform Filters'),
      'description' => $this->t('Filters for denormalized webform database tables.'),
      'url' => Url::fromUri('https://www.drupal.org/project/denormalized_webform_filters'),
      'category' => 'element',
    ];

    // Element: Loqate.
    $projects['loqate'] = [
      'title' => $this->t('Loqate'),
      'description' => $this->t('Provides the webform element called Address Loqate which integration with Loqate (previously PCA/Addressy) address lookup.'),
      'url' => Url::fromUri('https://www.drupal.org/project/loqate'),
      'category' => 'element',
    ];

    // Element: Range Slider.
    $projects['range_slider'] = [
      'title' => $this->t('Range Slider'),
      'description' => $this->t('Integration with http://rangeslider.js.org.'),
      'url' => Url::fromUri('https://github.com/baikho/RangeSlider'),
      'category' => 'element',
    ];

    // Element: Tax Number.
    $projects['tax_number'] = [
      'title' => $this->t('Tax Number'),
      'description' => $this->t('Defines a new plugin type to manage tax number validation. Additionally provides a webform element that uses the same plugin'),
      'url' => Url::fromUri('https://www.drupal.org/project/tax_number'),
      'category' => 'element',
    ];

    // Element: Radios to Slider.
    $projects['radiostoslider'] = [
      'title' => $this->t('Radios to Slider'),
      'description' => $this->t('Provide a webform element with the radios-to-slider jQuery plugin support.'),
      'url' => Url::fromUri('https://www.drupal.org/project/radiostoslider'),
      'category' => 'element',
    ];
    // Element: Webform Alias Container.
    $projects['webform_alias_container'] = [
      'title' => $this->t('Webform Alias Container'),
      'description' => $this->t('Provides a Webform container designed to contain multiple composite elements.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_alias_container'),
      'category' => 'element',
    ];

    // Element: Webform Attachment Gated Download.
    $projects['webform_attachment_gated_download'] = [
      'title' => $this->t('Webform Attachment Gated Download'),
      'description' => $this->t('Provides a field formatter for file, image, and media types which links to a webform.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_attachment_gated_download'),
      'category' => 'element',
    ];

    // Element: Webform Belgian National Insurance Number.
    $projects['webform_rrn_nrn'] = [
      'title' => $this->t('Webform Belgian National Insurance Number'),
      'description' => $this->t('Provides webform fieldtype for the Belgian National Insurance Number.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_rrn_nrn'),
      'category' => 'element',
    ];

    // Element: Webform Composite Tools.
    $projects['webform_composite'] = [
      'title' => $this->t('Webform Composite Tools'),
      'description' => $this->t('Provides a reusable composite element for use on webforms.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_composite'),
      'category' => 'element',
    ];

    // Element: Webform Checkboxes Table.
    $projects['webform_checkboxes_table'] = [
      'title' => $this->t('Webform Checkboxes Table'),
      'description' => $this->t('Displays checkboxes element in a table grid.'),
      'url' => Url::fromUri('https://github.com/minnur/webform_checkboxes_table'),
      'category' => 'element',
    ];

    // Element: Webform Crafty Clicks.
    $projects['webform_craftyclicks'] = [
      'title' => $this->t('Webform Crafty Clicks'),
      'description' => $this->t('Adds Crafty Clicks UK postcode lookup to the Webform Address composite element.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_craftyclicks'),
      'category' => 'element',
    ];

    // Element: Webform DropzoneJS.
    $projects['webform_dropzonejs'] = [
      'title' => $this->t('Webform DropzoneJS'),
      'description' => $this->t('Creates a new DropzoneJS element that you can add to webforms.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_dropzonejs'),
      'category' => 'element',
    ];

    // Element: Dropzonejs Webform.
    $projects['dropzonejs_webform'] = [
      'title' => $this->t('Dropzonejs Webform'),
      'description' => $this->t('Creates a new DropzoneJS element that you can add to webforms. It provides a user-friendly way for users to upload multiple files in a form field.'),
      'url' => Url::fromUri('https://www.drupal.org/project/dropzonejs_webform'),
      'category' => 'element',
    ];

    // Element: Webform Dynamic Autocomplete.
    $projects['webform_dynamic_autocomplete'] = [
      'title' => $this->t('Webform Dynamic Autocomplete'),
      'description' => $this->t('Provides a new element field in webform for Dynamically handling Autocomplete API request.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_dynamic_autocomplete'),
      'category' => 'element',
    ];

    // Element: Webform Entity View.
    $projects['webform_entity_view'] = [
      'title' => $this->t('Webform Entity View'),
      'description' => $this->t('Provides an Entity Reference Webform element that can be picked in the build of a webform and will be rendered in the view.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_entity_view'),
      'category' => 'element',
    ];

    // Element: Webform Entity Reference Exclude field widget.
    $projects['webform_entity_reference_exclude'] = [
      'title' => $this->t('Webform Entity Reference Exclude field widget'),
      'description' => $this->t('Provides a webform entity reference field widget, that allows excluding certain webforms from being selectable.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_entity_reference_exclude'),
      'category' => 'element',
    ];

    // Element: Webform GMap Field.
    $projects['webform_gmap_field'] = [
      'title' => $this->t('Webform GMap Field'),
      'description' => $this->t('Adds a "Map location" component to a webform, which gives users the ability to pick a location from the map by dragging a marker.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_gmap_field'),
      'category' => 'element',
    ];

    // Element: Webform Handsontable.
    $projects['handsontable_yml_webform'] = [
      'title' => $this->t('Webform Handsontable'),
      'description' => $this->t('Allows both the Drupal Form API and the Drupal 8 Webforms module to use the Excel-like Handsontable library.'),
      'url' => Url::fromUri('https://www.drupal.org/project/handsontable_yml_webform'),
      'category' => 'element',
    ];

    // Element: Webform Hierarchy.
    $projects['webform_hierarchy'] = [
      'title' => $this->t('Webform Hierarchy'),
      'description' => $this->t('Provides hierarchical widget for webforms.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_hierarchy'),
      'category' => 'element',
    ];

    // Element: Webform IBAN field .
    $projects['webform_iban_field'] = [
      'title' => $this->t('Webform IBAN field'),
      'description' => $this->t('Provides an IBAN Field to collect a valid IBAN number.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_iban_field'),
      'category' => 'element',
    ];

    // Element: Webform International Telephone National Mode.
    $projects['webform_intl_tel_national_mode'] = [
      'title' => $this->t('Webform International Telephone National Mode'),
      'description' => $this->t('Changes the UX of the out-of-the-box Webform configuration for the telephone element type.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_intl_tel_national_mode'),
      'category' => 'element',
    ];

    // Element: Webform Javascript Field.
    $projects['webform_javascript_field'] = [
      'title' => $this->t('Webform Javascript Field'),
      'description' => $this->t('Provides ability to specify JavaScript snippet for Webform components.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_javascript_field'),
      'category' => 'element',
    ];

    // Element: Webform JavaScript Setting.
    $projects['webform_javascript_setting'] = [
      'title' => $this->t('Webform JavaScript Setting'),
      'description' => $this->t("Allows a webform to pull a Javascript object's setting/property into a hidden field that can be included with a webform submission."),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_javascript_setting'),
      'category' => 'element',
    ];

    // Element: Webform Layout Container.
    $projects['webform_layout_container'] = [
      'title' => $this->t('Webform Layout Container'),
      'description' => $this->t("Provides a layout container element to add to a webform, which uses old fashion floats to support legacy browsers that don't support CSS Flexbox (IE9 and IE10)."),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_layout_container'),
      'category' => 'element',
    ];

    // Element: Webform Location HTML5.
    $projects['webform_location_html5'] = [
      'title' => $this->t('Webform Location HTML5'),
      'description' => $this->t('Provides a webform field, that when the page loads it autofills with the user location, using the browser Geolocation API.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_location_html5'),
      'category' => 'element',
    ];

    // Element: Webform Node Element.
    $projects['webform_node_element'] = [
      'title' => $this->t('Webform Node Element'),
      'description' => $this->t("Provides a 'Node' element to display node content as an element on a webform. Can be modified dynamically using an event handler."),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_node_element'),
      'category' => 'element',
    ];

    // Element: Webform noUiSlider Element.
    $projects['webform_nouislider'] = [
      'title' => $this->t('Webform noUiSlider Element'),
      'description' => $this->t('A lightweight range slider with multi-touch support and a ton of features.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_nouislider'),
      'category' => 'element',
    ];

    // Element: Webform Quiz Elements.
    $projects['webform_quiz_elements'] = [
      'title' => $this->t('Webform Quiz Elements'),
      'description' => $this->t('Create a simple quiz out of a webform with webform quiz elements module.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_quiz_elements'),
      'category' => 'element',
    ];

    // Element: Webform Portuguese NIF.
    $projects['webform_portuguese_nif'] = [
      'title' => $this->t('Webform Portuguese NIF'),
      'description' => $this->t('Provides functionality for collecting, validating and displaying portuguese NIF numbers in a Webform.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_portuguese_nif'),
      'category' => 'element',
    ];

    // Element: Webform Private Elements.
    $projects['webform_private_elements'] = [
      'title' => $this->t('Webform Private Elements'),
      'description' => $this->t('Allows site administrators to define which webform elements are "private" by default.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_private_elements'),
      'category' => 'element',
    ];

    // Element: Webform Promotion Code.
    $projects['webform_promotion_code'] = [
      'title' => $this->t('Webform Promotion Code'),
      'description' => $this->t('Provides a promotion code Webform element.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_promotion_code'),
      'category' => 'element',
    ];

    // Element: Webform Remote Select.
    $projects['webform_remote_select'] = [
      'title' => $this->t('Webform Remote Select'),
      'description' => $this->t('Provides a Webform Select Element whose options are populated from an endpoint through REST services.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_remote_select'),
      'category' => 'element',
    ];

    // Element: Webform RUT.
    $projects['webform_rut'] = [
      'title' => $this->t('Webform RUT'),
      'description' => $this->t("Provides a RUT (A unique identification number assigned to natural or legal persons of Chile) element."),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_rut'),
      'category' => 'element',
    ];

    // Element: Webform Score.
    $projects['webform_score'] = [
      'title' => $this->t('Webform Score'),
      'description' => $this->t("Lets you score an individual user's answers, then store and display the scores."),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_score'),
      'category' => 'element',
    ];

    // Element: Webform Select Collection.
    $projects['webform_select_collection'] = [
      'title' => $this->t('Webform Select Collection'),
      'description' => $this->t('Provides a webform element that groups multiple select elements into single collection.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_select_collection'),
      'category' => 'element',
    ];

    // Element: Webform Simple Hierarchical Select.
    $projects['webform_shs'] = [
      'title' => $this->t('Webform Simple Hierarchical Select'),
      'description' => $this->t('Integrates Simple Hierarchical Select module with Webform.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_shs'),
      'category' => 'element',
    ];

    // Element: Webform SWIFT/BIC Field.
    $projects['webform_bic_field'] = [
      'title' => $this->t('Webform SWIFT/BIC Field'),
      'description' => $this->t('mplements a Webform SWIFT/BIC field. It validates that a value has the proper format of a Business Identifier Code (BIC), also known as SWIFT-BIC, BIC, SWIFT ID or SWIFT code.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_bic_field'),
      'category' => 'element',
    ];

    // Element: Webform Summation Field.
    $projects['webform_summation_field'] = [
      'title' => $this->t('Webform Summation Field'),
      'description' => $this->t('Provides a webform summation field to collect the values of other fields.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_summation_field'),
      'category' => 'element',
    ];

    /* ********************************************************************** */
    // Enhancement.
    /* ********************************************************************** */

    // Enhancement: Config Entity Reference Selection.
    $projects['config_entity_reference_selection'] = [
      'title' => $this->t('Config Entity Reference Selection'),
      'description' => $this->t('Provides an entity reference selection plugin for limiting allowed (webform) config entity choices.'),
      'url' => Url::fromUri('https://www.drupal.org/project/config_entity_reference_selection'),
      'category' => 'enhancement',
    ];

    // Enhancement: Dopup.
    $projects['dopup'] = [
      'title' => $this->t('Dopup'),
      'description' => $this->t('Simple webform popups for lead generation and other marketing needs.'),
      'url' => Url::fromUri('https://www.drupal.org/project/dopup'),
      'category' => 'enhancement',
    ];

    // Enhancement: Formset.
    $projects['formset'] = [
      'title' => $this->t('Formset'),
      'description' => $this->t('Enables the creation of webform sets.'),
      'url' => Url::fromUri('https://github.com/simesy/formset'),
      'category' => 'enhancement',
    ];

    // Enhancement: Metatag Webform.
    $projects['metatag_webform'] = [
      'title' => $this->t('Metatag Webform'),
      'description' => $this->t('Provides the ability to add metatags for webforms.'),
      'url' => Url::fromUri('https://www.drupal.org/project/metatag_webform'),
      'category' => 'enhancement',
    ];

    // Enhancement: Webform Ban.
    $projects['webform_ban'] = [
      'title' => $this->t('Webform Ban'),
      'description' => $this->t('Integration of the Webform module with the core Ban module.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_ban'),
      'category' => 'enhancement',
    ];

    // Enhancement: Webform Bulk Emails.
    $projects['webform_bulk_email'] = [
      'title' => $this->t('Webform Bulk Emails'),
      'description' => $this->t('Provides a webform handler to send webform submission in bulk on a given time schedule.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_bulk_email'),
      'category' => 'enhancement',
    ];

    // Enhancement: Webform Calculation.
    $projects['webform_calculation'] = [
      'title' => $this->t('Webform Calculation'),
      'description' => $this->t('Provides ability to make dynamic calculations using Webform.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_calculation'),
      'category' => 'enhancement',
    ];

    // Enhancement: Webform Cookie.
    $projects['webform_cookie'] = [
      'title' => $this->t('Webform Cookie'),
      'description' => $this->t('Provides a Webform submission handler that sets an arbitrary cookie after submission.'),
      'url' => Url::fromUri('https://github.com/r0nn1ef/webform_cookie'),
      'category' => 'enhancement',
    ];

    // Enhancement: Webform Confirmation File.
    $projects['webform_confirmation_file'] = [
      'title' => $this->t('Webform Confirmation File'),
      'description' => $this->t('Provides a webform handler that streams the contents of a file to a user after completing a webform.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_confirmation_file'),
      'category' => 'enhancement',
    ];

    // Enhancement: Webform Counter.
    $projects['webform_counter'] = [
      'title' => $this->t('Webform Counter'),
      'description' => $this->t('Provides Submissions Counter feature for webforms.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_counter'),
      'category' => 'enhancement',
    ];

    // Enhancement: Webform Creation Date.
    $projects['webform_creation_date'] = [
      'title' => $this->t('Webform Creation Date'),
      'description' => $this->t('Allows to store information about creation/update dates for webforms.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_creation_date'),
      'category' => 'enhancement',
    ];

    // Enhancement: Webform Deter.
    $projects['webform_deter'] = [
      'title' => $this->t('Webform Deter'),
      'description' => $this->t('Applies clientside validation checks to webform fields and warns the user when sensitive information may be contained in data being submitted.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_deter'),
      'category' => 'enhancement',
    ];

    // Enhancement: Webform Discount.
    $projects['webform_discount'] = [
      'title' => $this->t('Webform Discount'),
      'description' => $this->t('Provides the ability to create Discount Codes that can be applied to alter the value of fields in Webform submissions.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_discount'),
      'category' => 'enhancement',
    ];

    // Enhancement: Webform Extra Field.
    $projects['webform_extra_field'] = [
      'title' => $this->t('Webform Extra Field'),
      'description' => $this->t('Provides an extra field for placing a webform in any entity display mode.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_extra_field'),
      'category' => 'enhancement',
    ];

    // Enhancement: Webform Extra Field Validation.
    $projects['webform_extra_field_validation'] = [
      'title' => $this->t('Webform Extra Field Validation'),
      'description' => $this->t('Provides extra validation to webform, allowing you to specify validation rules for your Webform components.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_extra_field_validation'),
      'category' => 'enhancement',
    ];

    // Enhancement: Webform Feedback.
    $projects['webform_feedback'] = [
      'title' => $this->t('Webform Feedback'),
      'description' => $this->t('Adds a lightbox like pop-up for a contact/feedback form based on webform.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_feedback'),
      'category' => 'enhancement',
    ];

    // Enhancement: Webform GeoIP Restriction.
    $projects['webform_geoip_restriction'] = [
      'title' => $this->t('Webform GeoIP Restriction'),
      'description' => $this->t('Adds the possibility of restricting access to webforms by country using the geoip system.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_geoip_restriction'),
      'category' => 'enhancement',
    ];

    // Enhancement: Webform Import Tab.
    $projects['webform_import_tab'] = [
      'title' => $this->t('Webform Import Tab'),
      'description' => $this->t('Provides an import tab in the webform module so that users who can create webforms can import them without needing access to the entire configuration synchronization system.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_import_tab'),
      'category' => 'enhancement',
    ];

    // Enhancement: Webform Inline Entity Form.
    $projects['webform_inline_entity_form'] = [
      'title' => $this->t('Webform Inline Entity Form'),
      'description' => $this->t('Provides an element type that can be added to a webform that embeds an entity form into the webform, saves/updates the entity with the data on form submission, and provides an entity reference as the element value in the submission.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_inline_entity_form'),
      'category' => 'enhancement',
    ];

    // Enhancement: Webform IP Geo.
    $projects['webform_ip_geo'] = [
      'title' => $this->t('Webform IP Geo'),
      'description' => $this->t('Provides a simple way to extract geo data from the IP of a webform submission.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_ip_geo'),
      'category' => 'enhancement',
    ];

    // Enhancement: Webform Navigation.
    $projects['webformnavigation'] = [
      'title' => $this->t('Webform Navigation'),
      'description' => $this->t('Creates a navigation setting for webform that allows users to navigate forwards and backwards through wizard pages when the wizard navigation progress bar is enabled.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webformnavigation'),
      'category' => 'enhancement',
    ];

    // Enhancement: Webform Notify Draft Authors.
    $projects['webform_notify_draft_authors'] = [
      'title' => $this->t('Webform Notify Draft Authors'),
      'description' => $this->t('Enables to notify via email authors of a webform drafts about a webform submission.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_notify_draft_authors'),
      'category' => 'enhancement',
    ];

    // Enhancement: Webform OCR.
    $projects['webform_ocr'] = [
      'title' => $this->t('Webform OCR'),
      'description' => $this->t('OCR images as new Webforms.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_ocr'),
      'category' => 'enhancement',
    ];

    // Enhancement: Webform Paragraphs.
    $projects['webform_paragraphs'] = [
      'title' => $this->t('Webform Paragraphs'),
      'description' => $this->t('Adds a paragraph reference to the webforms when they are submitted from a paragraph context.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_paragraphs'),
      'category' => 'enhancement',
    ];

    // Enhancement: Webform Preset.
    $projects['webform_preset'] = [
      'title' => $this->t('Webform Preset'),
      'description' => $this->t('Manages trusted presets for webform submissions via a secret url. See readme.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_preset'),
      'category' => 'enhancement',
    ];

    // Enhancement: Webform Pre-populate.
    $projects['webform_prepopulate'] = [
      'title' => $this->t('Webform Pre-populate'),
      'description' => $this->t('Pre-populate a Webform with an external data source without disclosing information via the URL.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_prepopulate'),
      'category' => 'enhancement',
    ];

    // Enhancement: Webform Protected Downloads.
    $projects['webform_protected_downloads'] = [
      'title' => $this->t('Webform Protected Downloads'),
      'description' => $this->t('Provides protected file downloads using webforms.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_protected_downloads'),
      'category' => 'enhancement',
    ];

    // Enhancement: Webform Sign PDF Example.
    $projects['webform_sign_pdf_example'] = [
      'title' => $this->t('Webform Sign PDF Example'),
      'description' => $this->t('Digitally sign and print to a form with a header and a footer.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_sign_pdf_example'),
      'category' => 'enhancement',
    ];

    // Enhancement: Webform Simplify.
    $projects['webform_simplify'] = [
      'title' => $this->t('Webform Simplify'),
      'description' => $this->t('Allows certain parts of the Webform user interface to be hidden.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_simplify'),
      'category' => 'enhancement',
    ];

    // Enhancement: Webform Timeout.
    $projects['webform_timeout'] = [
      'title' => $this->t('Webform Timeout'),
      'description' => $this->t('Provides functionality to limit user time during which he is able to make webform submission.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_timeout'),
      'category' => 'enhancement',
    ];

    // Enhancement: Webform Unsubscribe.
    $projects['webform_unsubscribe'] = [
      'title' => $this->t('Webform Unsubscribe'),
      'description' => $this->t('Provides the token for creation of the link for removing a webform submission by an anonymous user.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_unsubscribe'),
      'category' => 'enhancement',
    ];

    // Enhancement: Webform Validation.
    $projects['webform_validation'] = [
      'title' => $this->t('Webform Validation'),
      'description' => $this->t('Add validation rules to Webforms.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_validation'),
      'category' => 'enhancement',
    ];

    // Enhancement: WetBoew Webform Example.
    $projects['wetboew_webform_example'] = [
      'title' => $this->t('WetBoew Webform Example'),
      'description' => $this->t('Provides two webforms that demonstrate how to use server side and clientside wxt style form validation.'),
      'url' => Url::fromUri('https://www.drupal.org/project/wetboew_webform_example'),
      'category' => 'enhancement',
    ];

    // Enhancement: Webform Wizard Full Title.
    $projects['webform_wizard_full_title'] = [
      'title' => $this->t('Webform Wizard Full Title'),
      'description' => $this->t('Extends functionality of Webform so on wizard forms, the title of the wizard page can override the form title.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_wizard_full_title'),
      'category' => 'enhancement',
    ];

    /* ********************************************************************** */
    // Integrations.
    /* ********************************************************************** */

    // Integrations: Webform CiviCRM Integration.
    $projects['webform_civicrm'] = [
      'title' => $this->t('Webform CiviCRM Integration'),
      'description' => $this->t('A powerful, flexible, user-friendly form builder for CiviCRM.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_civicrm'),
      'category' => 'integration',
      'recommended' => TRUE,
    ];

    // Integrations: Webform Content Creator.
    $projects['webform_content_creator'] = [
      'title' => $this->t('Webform Content Creator'),
      'description' => $this->t('Provides the ability to create nodes after submitting webforms, and do mappings between the fields of the created node and webform submission values.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_content_creator'),
      'category' => 'integration',
      'recommended' => TRUE,
    ];

    // Integrations: Webform Entity Handler.
    $projects['webform_entity_handler'] = [
      'title' => $this->t('Webform Entity Handler'),
      'description' => $this->t('Provides the ability to create or update entities with the webform submission values.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_entity_handler'),
      'category' => 'integration',
      'recommended' => TRUE,
    ];

    /* ********************************************************************** */

    // Integrations: AB Webform CDP.
    $projects['abinbev_cdp'] = [
      'title' => $this->t('AB Webform CDP'),
      'description' => $this->t('Provides integration feature for integration webform to CDP database (https://treasuredata.com).'),
      'url' => Url::fromUri('https://www.drupal.org/project/abinbev_cdp'),
      'category' => 'integration',
    ];

    // Integrations: Ansible.
    $projects['ansible'] = [
      'title' => $this->t('Ansible'),
      'description' => $this->t('Run Ansible playbooks using a Webform handler.'),
      'url' => Url::fromUri('https://www.drupal.org/project/ansible'),
      'category' => 'integration',
    ];

    // Integrations: AXEPTA e-POSitivity Payment Gateways.
    $projects['epositivity'] = [
      'title' => $this->t('AXEPTA e-POSitivity Payment Gateways'),
      'description' => $this->t('Receive credit card payments through AXEPTA e-POSitivity Payment Gateways'),
      'url' => Url::fromUri('https://www.drupal.org/project/epositivity'),
      'category' => 'integration',
    ];

    // Integrations: Campaign Monitor Webform Handler.
    $projects['campaign_monitor_webform'] = [
      'title' => $this->t('Campaign Monitor Webform Handler'),
      'description' => $this->t('Integrates the Campaign Monitor API into Drupal and provides a webform submit handler that lets you subscribe users to specific lists on Campaign Monitor.'),
      'url' => Url::fromUri('https://www.drupal.org/project/campaign_monitor_webform'),
      'category' => 'integration',
    ];

    // Integrations: Commerce Webform Order.
    $projects['commerce_webform_order'] = [
      'title' => $this->t('Commerce Webform Order'),
      'description' => $this->t('Integrates Webform with Drupal Commerce and it allows creating orders with the submission data of a Webform via a Webform handler.'),
      'url' => Url::fromUri('https://www.drupal.org/project/commerce_webform_order'),
      'category' => 'integration',
    ];

    // Integrations: ConvertKit - The Creator Marketing Platform.
    $projects['convertkit_esp'] = [
      'title' => $this->t('ConvertKit - The Creator Marketing Platform'),
      'description' => $this->t('Integrates Convertkit API v3. ConvertKit is the go-to marketing hub for creators that helps you grow and monetize your audience with ease.'),
      'url' => Url::fromUri('https://www.drupal.org/project/convertkit_esp'),
      'category' => 'integration',
    ];

    // Integrations: CMRF Form Processor.
    $projects['cmrf_form_processor'] = [
      'title' => $this->t('CMRF Form Processor'),
      'description' => $this->t('Submit Webform actions to the CiviCRM forms_processor with CiviMFR.'),
      'url' => Url::fromUri('https://www.drupal.org/project/cmrf_form_processor'),
      'category' => 'integration',
    ];

    // Integrations: CMRF Reference.
    $projects['cmrf_reference'] = [
      'title' => $this->t('CMRF Reference'),
      'description' => $this->t('Make a reference to CiviCRM in a webform.'),
      'url' => Url::fromUri('https://www.drupal.org/project/cmrf_reference'),
      'category' => 'integration',
    ];

    // Integrations: Discord Webform Handler.
    $projects['discord_webform_handler'] = [
      'title' => $this->t('Discord Webform Handler'),
      'description' => $this->t('Sends webform submission to Discord via webhook.'),
      'url' => Url::fromUri('https://www.drupal.org/project/discord_webform_handler'),
      'category' => 'integration',
    ];

    // Integrations: Domain Webform.
    $projects['domain_webform'] = [
      'title' => $this->t('Domain Webform'),
      'description' => $this->t('Domain integration for the Webform module.'),
      'url' => Url::fromUri('https://github.com/h3rj4n/domain_webform'),
      'category' => 'integration',
    ];

    // Integration: Drip Webform Handler.
    $projects['drip_webform_handler'] = [
      'title' => $this->t('Drip Webform Handler'),
      'description' => $this->t('Allows you to post submissions to Drip.com.'),
      'url' => Url::fromUri('https://www.drupal.org/project/drip_webform_handler'),
      'category' => 'integration',
    ];

    // Integrations: Druminate Webforms.
    $projects['druminate'] = [
      'title' => $this->t('Druminate Webforms'),
      'description' => $this->t('Allows editors to send webform submissions to Luminate Online Surveys.'),
      'url' => Url::fromUri('https://www.drupal.org/project/druminate'),
      'category' => 'integration',
    ];

    // Integrations: Ecomail webform.
    $projects['ecomail_webform'] = [
      'title' => $this->t('Ecomail webform'),
      'description' => $this->t('Provides a Webform handler to add contact to the list of direct emailing service Ecomail.cz.'),
      'url' => Url::fromUri('https://www.drupal.org/project/ecomail_webform'),
      'category' => 'integration',
    ];

    // Integrations: Flashpoint Course Content: Webform.
    $projects['flashpoint_course_webform'] = [
      'title' => $this->t('Flashpoint Course Content: Webform'),
      'description' => $this->t('Integrates Webforms into Flashpoint Courses.'),
      'url' => Url::fromUri('https://www.drupal.org/project/flashpoint_course_webform'),
      'category' => 'integration',
    ];

    // Integrations: Gatsby Webform Backend.
    $projects['react_webform_backend'] = [
      'title' => $this->t('Gatsby Drupal Webform'),
      'description' => $this->t('The goal of this project is to have a react component that generates bootstrap like HTML from webform YAML configuration.'),
      'url' => Url::fromUri('https://www.drupal.org/project/react_webform_backend'),
      'category' => 'integration',
    ];

    // Integrations: GitLab API with Library.
    $projects['gitlab_api'] = [
      'title' => $this->t('GitLab API with Library'),
      'description' => $this->t('Integrates your Drupal site into GitLab using the GitLab API.'),
      'url' => Url::fromUri('https://www.drupal.org/project/gitlab_api'),
      'category' => 'integration',
    ];

    // Integrations: (Google) Datalayer Webform.
    $projects['datalayer_webform'] = [
      'title' => $this->t('(Google) Datalayer Webform'),
      'description' => $this->t('Send datalayer events on Webform submission.'),
      'url' => Url::fromUri('https://www.drupal.org/sandbox/mistermoper/3199908'),
      'category' => 'integration',
    ];

    // Integrations: Group Webform.
    $projects['group_webform'] = [
      'title' => $this->t('Group Webform'),
      'description' => $this->t('Designed to associate group specific webforms with a group when using the Group module.'),
      'url' => Url::fromUri('https://www.drupal.org/project/group_webform'),
      'category' => 'integration',
    ];

    // Integrations: GraphQL Webform.
    $projects['graphql_webform'] = [
      'title' => $this->t('GraphQL Webform'),
      'description' => $this->t('Provides GraphQL integration with the Webform module.'),
      'url' => Url::fromUri('https://www.drupal.org/project/graphql_webform'),
      'category' => 'integration',
    ];

    // Integrations: Headless Ninja React Webform.
    $projects['hn_react_webform'] = [
      'title' => $this->t('Headless Ninja React Webform'),
      'description' => $this->t('With this awesome React component, you can render complete Drupal Webforms in React. With validation, easy custom styling and a modern, clean interface.'),
      'url' => Url::fromUri('https://github.com/headless-ninja/hn-react-webform'),
      'category' => 'integration',
    ];

    // Integrations: ID.me Webform Integration.
    $projects['idme_webform'] = [
      'title' => $this->t('ID.me Webform Integration'),
      'description' => $this->t('Provide the linkage between any Webform and the ID.me service.'),
      'url' => Url::fromUri('https://www.drupal.org/project/idme_webform'),
      'category' => 'integration',
    ];

    // Integrations: Drupal Connector for Janrain Identity Cloud.
    $projects['janrain_connect'] = [
      'title' => $this->t('Janrain Identity Cloud'),
      'description' => $this->t('Integrates the Janrain Service with your Drupal 8 site.'),
      'url' => Url::fromUri('https://www.drupal.org/project/janrain_connect'),
      'category' => 'integration',
    ];

    // Integrations: Live Search - Person.
    $projects['livesearch_person'] = [
      'title' => $this->t('Live Search - Person'),
      'description' => $this->t('Integrates Webform with Livesearch service API from data factory to get the contact info & address for people based on a phone number.'),
      'url' => Url::fromUri('https://www.drupal.org/project/livesearch_person'),
      'category' => 'integration',
    ];

    // Integrations: Mailchimp Webform Handler.
    $projects['mailchimp_webform_handler'] = [
      'title' => $this->t('Mailchimp Webform Handler'),
      'description' => $this->t('Allows you to add a new contact from a webform to a Mailchimp list without enabling a dependent Mailchimp-module.'),
      'url' => Url::fromUri('https://www.drupal.org/project/mailchimp_webform_handler'),
      'category' => 'integration',
    ];

    // Integrations: Marketo MA.
    $projects['marketo_ma'] = [
      'title' => $this->t('Marketo MA Webform'),
      'description' => $this->t('Integrates Marketo MA with Webform module forms.'),
      'url' => Url::fromUri('https://www.drupal.org/project/marketo_ma'),
      'category' => 'integration',
    ];

    // Integrations: Maropost Subscription Webform Handler.
    $projects['maropost_sub_webform_handler'] = [
      'title' => $this->t('Maropost Subscription Webform Handler'),
      'description' => $this->t('A simple Webform handler that allows site builders and developers to easily submit new leads to Maropost Subscriptions.'),
      'url' => Url::fromUri('https://www.drupal.org/project/maropost_sub_webform_handler'),
      'category' => 'integration',
    ];

    // Integrations: Micro Webform.
    $projects['micro_webform'] = [
      'title' => $this->t('Micro Webform'),
      'description' => $this->t('Integrate webform module with a micro site.'),
      'url' => Url::fromUri('https://www.drupal.org/project/micro_webform'),
      'category' => 'integration',
    ];

    // Integrations: Mollie for Drupal.
    $projects['mollie'] = [
      'title' => $this->t('Mollie for Drupal'),
      'description' => $this->t('Enables online payments in Drupal through Mollie.'),
      'url' => Url::fromUri('https://www.drupal.org/project/mollie'),
      'category' => 'integration',
    ];

    // Integrations: Mollie Webform Delete Submission.
    $projects['mollie_webform_delete_submission'] = [
      'title' => $this->t('Mollie Webform Delete Submission'),
      'description' => $this->t('Adds to the webform integration for Mollie for Drupal the deletion of submissions.'),
      'url' => Url::fromUri('https://www.drupal.org/project/mollie_webform_delete_submission'),
      'category' => 'integration',
    ];

    // Integrations: Moosend: Email Marketing Software.
    $projects['moosend_ems'] = [
      'title' => $this->t('Moosend: Email Marketing Software'),
      'description' => $this->t('Integrates Moosend EMS API v3.'),
      'url' => Url::fromUri('https://www.drupal.org/project/moosend_ems'),
      'category' => 'integration',
    ];

    // Integrations: OpenInbound for Drupal.
    $projects['openinbound'] = [
      'title' => $this->t('OpenInbound for Drupal'),
      'description' => $this->t('OpenInbound tracks contacts and their interactions on websites.'),
      'url' => Url::fromUri('https://www.drupal.org/project/openinbound'),
      'category' => 'integration',
    ];

    // Integrations: OpenLayersD8.
    $projects['openlayersd8'] = [
      'title' => $this->t('OpenLayersD8'),
      'description' => $this->t('Provides an example that shows how to create a Webform composite.'),
      'url' => Url::fromUri('https://www.drupal.org/project/openlayersd8'),
      'category' => 'integration',
    ];

    // Integrations: Webform Postcode API.
    $projects['webform_postcodeapi'] = [
      'title' => $this->t('Webform Postcode API'),
      'description' => $this->t('Provides a composite Webform address element with autocompletion based on PostcodeAPI.nu data.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_postcodeapi'),
      'category' => 'integration',
    ];

    // Integrations: Rules Webform.
    $projects['rules_webform'] = [
      'title' => $this->t('Rules Webform'),
      'description' => $this->t("Provides integration of 'Rules' and 'Webform' modules. It enables to get access to webform submission data from rules. Also it provides possibility of altering and removing webform submission data from rules."),
      'url' => Url::fromUri('https://www.drupal.org/project/rules_webform'),
      'category' => 'integration',
    ];

    // Integrations: Sendpulse: Online Marketing.
    $projects['sendinblue_api'] = [
      'title' => $this->t('Sendinblue: Digital Marketing Tool'),
      'description' => $this->t('Integrates Sendinblue API v3.'),
      'url' => Url::fromUri('https://www.drupal.org/project/sendinblue_api'),
      'category' => 'integration',
    ];

    // Integrations: Sendpulse: Online Marketing.
    $projects['sendpulse_api'] = [
      'title' => $this->t('Sendpulse: Online Marketing'),
      'description' => $this->t('Integrates API for the Sendpulse cloud-based marketing solution that allows users to manage email, text messaging and push notifications through a single platform.'),
      'url' => Url::fromUri('https://www.drupal.org/project/sendpulse_api'),
      'category' => 'integration',
    ];

    // Integrations: Sharpspring Webforms.
    $projects['sharpspring_webforms'] = [
      'title' => $this->t('Sharpspring Webforms'),
      'description' => $this->t("Extends the SharpSpring module's functionality to add SharpSpring lead tracking to Webforms."),
      'url' => Url::fromUri('https://www.drupal.org/project/sharpspring_webforms'),
      'category' => 'integration',
    ];

    // Integrations: Sherpa Webform.
    $projects['sherpa_webform'] = [
      'title' => $this->t('Sherpa Webform'),
      'description' => $this->t('Captures Webform submissions, convert them to JSON, and send them to Sherpa.'),
      'url' => Url::fromUri('https://www.drupal.org/project/sherpa_webform'),
      'category' => 'integration',
    ];

    // Integrations: Site Studio Webform.
    $projects['site_studio_webform'] = [
      'title' => $this->t('Site Studio Webform'),
      'description' => $this->t('Provides integration between Site Studio and Webform modules with the help of the custom element of Site Studio.'),
      'url' => Url::fromUri('https://www.drupal.org/project/site_studio_webform'),
      'category' => 'integration',
    ];

    // Integrations: Slack Webform Handler.
    $projects['slack_webform_handler'] = [
      'title' => $this->t('Slack Webform Handler'),
      'description' => $this->t('Send messages to Slack when a webform is submitted.'),
      'url' => Url::fromUri('https://www.drupal.org/project/slack_webform_handler'),
      'category' => 'integration',
    ];

    // Integrations: Streak Connect.
    $projects['streak_connect'] = [
      'title' => $this->t('Streak Connect'),
      'description' => $this->t("Connects your website's contact forms to Streak CRM, automatically creating new contacts upon form submission."),
      'url' => Url::fromUri('https://www.drupal.org/project/streak_connect'),
      'category' => 'integration',
    ];

    // Integrations: Stripe Webform Payment.
    $projects['stripe_webform_payment'] = [
      'title' => $this->t('Stripe Webform Payment'),
      'description' => $this->t('An implementation of Stripe module to integrate Webform with Stripe payment element, Stripe products and Stripe customers.'),
      'url' => Url::fromUri('https://www.drupal.org/project/stripe_webform_payment'),
      'category' => 'integration',
    ];

    // Integrations: Vipps Recurring Payments.
    $projects['vipps_recurring_payments'] = [
      'title' => $this->t('Vipps Recurring Payments'),
      'description' => $this->t('Use Webform with Vipps Recurring Payments.'),
      'url' => Url::fromUri('https://www.drupal.org/project/vipps_recurring_payments'),
      'category' => 'integration',
    ];

    // Integrations: Watson/Silverpop Webform Parser.
    $projects['watson_form_parser'] = [
      'title' => $this->t('Watson/Silverpop Webform Parser'),
      'description' => $this->t('Allows site-builders to import a form that is exported from the Watson Customer Engagement (WCE) WYSIWYG into a Drupal 8 site and parse it into a Webform.'),
      'url' => Url::fromUri('https://www.drupal.org/project/watson_form_parser'),
      'category' => 'integration',
    ];

    // Integrations: Webform AddressFinder.
    $projects['webform_location_addressfinder'] = [
      'title' => $this->t('Webform AddressFinder'),
      'description' => $this->t('Implements integration between Webform and the AddressFinder service (https://addressfinder.com.au/), providing autocompletion and validation for addresses in Australia and New Zealand.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_location_addressfinder'),
      'category' => 'integration',
    ];

    // Integrations: Webform API Handler.
    $projects['webform_api_handler'] = [
      'title' => $this->t('Webform API Handler'),
      'description' => $this->t("Extends Webform's built it Remote Post handler to enable the creation of custom plugins for pre-processing the request Webform makes to an API endpoint, and for processing and displaying the result of the API request."),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_api_handler'),
      'category' => 'integration',
    ];

    // Integrations: Webform Authorize.Net.
    $projects['webform_authorizenet'] = [
      'title' => $this->t('Webform Authorize.Net'),
      'description' => $this->t('Integrates Webform with Authorize.Net.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_authorizenet'),
      'category' => 'integration',
    ];

    // Integrations: Webform Copper CRM.
    $projects['webform_copper'] = [
      'title' => $this->t('Webform Copper'),
      'description' => $this->t('Provides a Webform handler that integrates with Copper CRM.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_copper'),
      'category' => 'integration',
    ];

    // Integrations: Webform Emfluence.
    $projects['emfluence_webform'] = [
      'title' => $this->t('Webform Emfluence'),
      'description' => $this->t("Integrates Emfluence Marketing Platform's contacts/save endpoint and Webform 8.x."),
      'url' => Url::fromUri('https://www.drupal.org/sandbox/huskyninja/3074135'),
      'experimental' => TRUE,
      'category' => 'integration',
    ];

    // Integrations: Webform Entity Builder.
    $projects['webform_entity_builder'] = [
      'title' => $this->t('Webform Entity Builder'),
      'description' => $this->t('Provides support code for the generation and management of entities through webforms.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_entity_builder'),
      'category' => 'integration',
    ];

    // Integrations: Webform E-petition.
    $projects['webform_epetition'] = [
      'title' => $this->t('Webform E-petition'),
      'description' => $this->t('Provides a postcode lookup field to find details and emails on your local parliamentary representatives.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_epetition'),
      'category' => 'integration',
    ];

    // Integration: Webform File Upload and Campaign as Salesforce Lead Attachment.
    $projects['wsla'] = [
      'title' => $this->t('Webform File Upload and Campaign as Salesforce Lead Attachment'),
      'description' => $this->t('This module uses webform properties as setting and allow the file uploaded as lead attachment. Campaign can also be attached with lead using this module.'),
      'url' => Url::fromUri('https://www.drupal.org/project/wsla'),
      'experimental' => TRUE,
      'category' => 'integration',
    ];

    // Integration: Webform iContact.
    $projects['webform_icontact'] = [
      'title' => $this->t('Webform iContact'),
      'description' => $this->t('Send Webform submissions to iContact list.'),
      'url' => Url::fromUri('https://www.drupal.org/sandbox/ibakayoko/2853326'),
      'experimental' => TRUE,
      'category' => 'integration',
    ];

    // Integration: Webform Cart.
    $projects['webform_cart'] = [
      'title' => $this->t('Webform Cart'),
      'description' => $this->t('Allows you to add products to a webform submission.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_cart'),
      'category' => 'integration',
    ];

    // Integration: Webform Donate.
    $projects['webform_donate'] = [
      'title' => $this->t('Webform Donate'),
      'description' => $this->t('Provides components and integration to receive donations with webforms using the Payments module.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_donate'),
      'category' => 'integration',
    ];

    // Integrations: Webform Eloqua.
    $projects['webform_eloqua'] = [
      'title' => $this->t('Webform Eloqua'),
      'description' => $this->t('Integrates Drupal 8 Webforms with Oracle Eloqua.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_eloqua'),
      'category' => 'integration',
    ];

    // Integrations: Webform GoogleSheets.
    $projects['webform_googlesheets'] = [
      'title' => $this->t('Webform GoogleSheets'),
      'description' => $this->t('Allows to append Webform submissions to Google Sheets.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_googlesheets'),
      'category' => 'integration',
    ];

    // Integrations: Webform Group.
    $projects['webform_group'] = [
      'title' => $this->t('Webform Group'),
      'description' => $this->t('Build webform forms connected to groups.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_group'),
      'category' => 'integration',
    ];

    // Integrations: Webform Group Extended.
    $projects['webform_group_extended'] = [
      'title' => $this->t('Webform Group Extended'),
      'description' => $this->t('A drop-in replacement/extension for the webform_group module that is included in the Webform module, to improve the ability to restrict access to webform forms, submissions and elements based on group role and/or group permission, and work within other group contexts.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_group_extended'),
      'category' => 'integration',
    ];

    // Integration: Webform HubSpot.
    $projects['hubspot'] = [
      'title' => $this->t('Webform HubSpot'),
      'description' => $this->t('Provides HubSpot leads API integration with Drupal.'),
      'url' => Url::fromUri('https://www.drupal.org/project/hubspot'),
      'category' => 'integration',
    ];

    // Integrations: Webform Hubspot Integration.
    $projects['hubspot_api_integration'] = [
      'title' => $this->t('Webform Hubspot Integration'),
      'description' => $this->t('Provides a Webform handler that integrates with Hubspot.'),
      'url' => Url::fromUri('https://www.drupal.org/project/hubspot_api_integration'),
      'category' => 'integration',
    ];

    // Integration: Webform Jira Integration.
    $projects['webform_jira'] = [
      'title' => $this->t('Webform Jira Integration'),
      'description' => $this->t('Provides integration for webform submission with Jira.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_jira'),
      'category' => 'integration',
    ];

    // Integration: Webform JIRA service desk integration.
    $projects['webform_jira_service_desk'] = [
      'title' => $this->t('Webform JIRA service desk integration'),
      'description' => $this->t('Enables the user to map Webform elements to Jira Service Desk fields and create an issue on Jira by using the REST API.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_jira_service_desk'),
      'category' => 'integration',
    ];

    // Integrations: Lightweight Webform Mailchimp.
    $projects['ldbase_handlers'] = [
      'title' => $this->t('LDbase Webform Handlers'),
      'description' => $this->t('Webform handlers to create and update LDbase content nodes.'),
      'url' => Url::fromUri('https://github.com/ldbase/ldbase_handlers'),
      'category' => 'integration',
    ];

    // Integrations: Lightweight Webform Mailchimp.
    $projects['lwm'] = [
      'title' => $this->t('Lightweight Webform Mailchimp'),
      'description' => $this->t('Manage and processing a Mailchimp lightweight connection from a Drupal webform.'),
      'url' => Url::fromUri('https://www.drupal.org/project/lwm'),
      'category' => 'integration',
    ];

    // Integrations: Webform MailChimp.
    $projects['webform_mailchimp'] = [
      'title' => $this->t('Webform MailChimp'),
      'description' => $this->t('Posts form submissions to MailChimp list.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_mailchimp'),
      'category' => 'integration',
    ];

    // Integrations: Webform Mattermost.
    $projects['webform_mattermost'] = [
      'title' => $this->t('Webform Mattermost'),
      'description' => $this->t('Adds a handler for sending webform submissions to Mattermost'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_mattermost'),
      'category' => 'integration',
    ];

    // Integrations: Webform Mautic.
    $projects['webform_mautic'] = [
      'title' => $this->t('Webform Mautic'),
      'description' => $this->t('Integrates your Webform submissions with Mautic form submissions.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_mautic'),
      'category' => 'integration',
    ];

    // Integrations: Webform MyEmma.
    $projects['webform_myemma'] = [
      'title' => $this->t('Webform MyEmma'),
      'description' => $this->t('Provides MyEmma subscription field to webforms.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_myemma'),
      'category' => 'integration',
    ];

    // Integrations: Webform Newsletter2Go.
    $projects['webform_newsletter2go'] = [
      'title' => $this->t('Webform Newsletter2Go'),
      'description' => $this->t('Provides Newsletter2Go Webform Integration.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_newsletter2go'),
      'category' => 'integration',
    ];

    // Integrations: Webform Octoa.
    $projects['webform_octoa'] = [
      'title' => $this->t('OS Tickets Webform Handler'),
      'description' => $this->t('Sends webform submissions into the Octoa Lead API'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_octoa'),
      'category' => 'integration',
    ];

    // Integrations: OS Tickets Webform Handler.
    $projects['ostickets'] = [
      'title' => $this->t('OS Tickets Webform Handler'),
      'description' => $this->t('Provides a webform handler that will POST OS tickets on submissions.'),
      'url' => Url::fromUri('https://www.drupal.org/project/ostickets'),
      'category' => 'integration',
    ];

    // Integrations: Webform Pardot.
    $projects['webform_pardot'] = [
      'title' => $this->t('Webform Pardot'),
      'description' => $this->t('Provides a webform handler for posting submissions to Pardot.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_pardot'),
      'category' => 'integration',
    ];

    // Integrations: Webform Product.
    $projects['webform_product'] = [
      'title' => $this->t('Webform Product'),
      'description' => $this->t('Links commerce products to webform elements.'),
      'url' => Url::fromUri('https://github.com/chx/webform_product'),
      'category' => 'integration',
    ];

    // Integrations: Webform SendGrid.
    $projects['webform_sendgrid'] = [
      'title' => $this->t('Webform SendGrid'),
      'description' => $this->t('Provide a webform handler for sending submission data to SendGrids Contact/Marketing/Lists API.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_sendgrid'),
      'category' => 'integration',
    ];

    // Integrations: Webform Simplenews Handler.
    $projects['webform_simplenews_handler'] = [
      'title' => $this->t('Webform Simplenews Handler'),
      'description' => $this->t('Provides a Webform Handler called "Submission Newsletter" that allows to link webform submission to one or more Simplenews newsletter subscriptions.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_simplenews_handler'),
      'category' => 'integration',
    ];

    // Integrations: Webform Slack integration.
    $projects['webform_slack'] = [
      'title' => $this->t('Webform Slack'),
      'description' => $this->t('Provides a Webform handler for posting a message to a slack channel when a submission is saved.'),
      'url' => Url::fromUri('https://www.drupal.org/sandbox/smaz/2833275'),
      'experimental' => TRUE,
      'category' => 'integration',
    ];

    // Integrations: Webform Stripe integration.
    $projects['stripe_webform'] = [
      'title' => $this->t('Webform Stripe'),
      'description' => $this->t('Provides a stripe webform element and default handlers.'),
      'url' => Url::fromUri('https://www.drupal.org/project/stripe_webform'),
      'category' => 'integration',
    ];

    // Integrations: Webform SugarCRM Integration.
    $projects['webform_sugarcrm'] = [
      'title' => $this->t('Webform SugarCRM Integration'),
      'description' => $this->t('Provides integration for webform submission with SugarCRM.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_sugarcrm'),
      'category' => 'integration',
    ];

    // Integrations: Webform to Paypal.
    $projects['webform_to_paypal'] = [
      'title' => $this->t('Webform to Paypal'),
      'description' => $this->t('Adds extra fields and settings to webforms to integrate with Paypal.'),
      'url' => Url::fromUri('https://github.com/IE-Digital/webform_to_paypal'),
      'category' => 'integration',
    ];

    // Integrations: Webform Paypal Standard Checkout.
    $projects['webform_paypal_std_co'] = [
      'title' => $this->t('Webform Paypal Standard Checkout'),
      'description' => $this->t('Adds a Paypal Standard checkout element'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_paypal_std_co'),
      'category' => 'integration',
    ];

    // Integrations: Webform Paypal (Smart Buttons).
    $projects['webform_paypal_smart'] = [
      'title' => $this->t('Webform Paypal (Smart Buttons)'),
      'description' => $this->t('Enables Smart Paypal buttons on Webform submissions.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_paypal_smart'),
      'category' => 'integration',
    ];

    // Integrations: Webform User Registration.
    $projects['webform_user_registration'] = [
      'title' => $this->t('Webform User Registration'),
      'description' => $this->t('Create a new user upon form submission.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_user_registration'),
      'category' => 'integration',
    ];

    // Integrations: Zammad Webform Handler.
    $projects['zammad_webform_handler'] = [
      'title' => $this->t('Zammad Webform Handler'),
      'description' => $this->t('Provides a Zammad Webform Handler, for sending Webform submissions to a Zammad instance.'),
      'url' => Url::fromUri('https://www.drupal.org/project/zammad_webform_handler'),
      'category' => 'integration',
    ];

    // Integrations: Webform Zendesk.
    $projects['zendesk_webform'] = [
      'title' => $this->t('Webform Zendesk'),
      'description' => $this->t('Adds a webform handler to create Zendesk tickets from Drupal webform submissions.'),
      'url' => Url::fromUri('https://github.com/strakers/zendesk-drupal-webform'),
      'category' => 'integration',
    ];

    /* ********************************************************************** */

    // Integrations: Salesforce Web-to-Lead Webform Data Integration.
    $projects['sfweb2lead_webform'] = [
      'title' => $this->t('Salesforce Web-to-Lead Webform Data Integration'),
      'description' => $this->t('Integrates Salesforce Web-to-Lead Form feature with various webforms.'),
      'url' => Url::fromUri('https://www.drupal.org/project/sfweb2lead_webform'),
      'category' => 'integration',
    ];

    // Integrations: Salesforce Marketing Cloud API Integration.
    $projects['marketing_cloud'] = [
      'title' => $this->t('Salesforce Marketing Cloud API Integration'),
      'description' => $this->t('Gives Drupal the ability to communicate with Marketing Cloud.'),
      'url' => Url::fromUri('https://www.drupal.org/project/marketing_cloud'),
      'category' => 'integration',
    ];

    // Integrations: SalesForce Web2Lead Webform Handler.
    $projects['sf_web2lead_webform_handler'] = [
      'title' => $this->t('SalesForce Web2Lead Webform Handler'),
      'description' => $this->t('Extends the Webform module to allow the creation of a webform that feeds to your Salesforce.com Account.'),
      'url' => Url::fromUri('https://www.drupal.org/project/sf_web2lead_webform_handler'),
      'category' => 'integration',
    ];

    // Integrations: Salesforce: Webform to Salesforce Leads.
    $projects['webform_to_leads'] = [
      'title' => $this->t('Salesforce: Webform to Salesforce Leads'),
      'description' => $this->t('Provides a new Webform Handler plugin to send submission data to SalesForce via their API.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_to_leads'),
      'category' => 'integration',
    ];

    // Integrations: Salesforce: Webform to Salesforce DEManager.
    $projects['webform_sf_demanager'] = [
      'title' => $this->t('Salesforce: Webform to Salesforce DEManager'),
      'description' => $this->t('Allows a webform to send information to Salesforce Marketing Cloud trough DEManager.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_sf_demanager'),
      'category' => 'integration',
    ];

    // Integrations: Zammad Webform Handler.
    $projects['zammad_webform_handler'] = [
      'title' => $this->t('Zammad Webform Handler'),
      'description' => $this->t('Provides a Zammad Webform Handler, for sending Webform submissions to a Zammad instance.'),
      'url' => Url::fromUri('https://www.drupal.org/project/zammad_webform_handler'),
      'category' => 'integration',
    ];

    /* ********************************************************************** */
    // Mail.
    /* ********************************************************************** */

    // Mail: Mail System.
    $projects['mailsystem'] = [
      'title' => $this->t('Mail System'),
      'description' => $this->t('Provides a user interface for per-module and site-wide mail system selection.'),
      'url' => Url::fromUri('https://www.drupal.org/project/mailsystem'),
      'category' => 'mail',
    ];

    // Mail: Webform Email Confirmation Link.
    $projects['webform_email_confirmation_link'] = [
      'title' => $this->t('Webform Email Confirmation Link'),
      'description' => $this->t('Add the option to send confirmation emails for webform submitters'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_email_confirmation_link'),
      'category' => 'mail',
    ];

    // Mail: Webform Email Reply.
    $projects['webform_email_reply_d8'] = [
      'title' => $this->t('Webform Email Reply'),
      'description' => $this->t('Allows users to send an email reply to submissions.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_email_reply'),
      'category' => 'mail',
    ];

    // Mail: Flexmail.
    $projects['flexmail'] = [
      'title' => $this->t('Flexmail'),
      'description' => $this->t('Provides Flexmail email service webform integration.'),
      'url' => Url::fromUri('https://www.drupal.org/project/flexmail'),
      'category' => 'mail',
    ];

    // Mail: Mailboxlayer.
    $projects['mailboxlayer'] = [
      'title' => $this->t('Mailboxlayer'),
      'description' => $this->t('Integrates the Mailboxlayer API with the Webforms.'),
      'url' => Url::fromUri('https://www.drupal.org/project/mailboxlayer'),
      'category' => 'mail',
    ];

    // Mail: Mail System: SendGrid Integration.
    $projects['sendgrid_integration'] = [
      'title' => $this->t('SendGrid Integration <em>(requires Mail System)</em>'),
      'description' => $this->t('Provides SendGrid Integration for the Drupal Mail System.'),
      'url' => Url::fromUri('https://www.drupal.org/project/sendgrid_integration'),
      'category' => 'mail',
    ];

    // Mail: Queue Mail.
    $projects['queue_mail'] = [
      'title' => $this->t('Queue Mail'),
      'description' => $this->t('Queues webform email sending so that instead of being sent immediately it is sent on cron or via some other queue processor.'),
      'url' => Url::fromUri('https://www.drupal.org/project/queue_mail'),
      'category' => 'mail',
    ];

    // Mail: Webform Send Draft Link.
    $projects['webform_send_draft_link'] = [
      'title' => $this->t('Webform Send Draft Link'),
      'description' => $this->t('Enables to send a link to a webform draft via email.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_send_draft_link'),
      'category' => 'mail',
    ];

    // Mail: SMTP Authentication Support.
    $projects['smtp'] = [
      'title' => $this->t('SMTP Authentication Support'),
      'description' => $this->t('Allows for site emails to be sent through an SMTP server of your choice.'),
      'url' => Url::fromUri('https://www.drupal.org/project/smtp'),
      'category' => 'mail',
    ];

    // Mail: Mail System: Swift Mailer.
    $projects['swiftmailer'] = [
      'title' => $this->t('Swift Mailer <em>(requires Mail System)</em>'),
      'description' => $this->t('Installs Swift Mailer as a mail system.'),
      'url' => Url::fromUri('https://www.drupal.org/project/swiftmailer'),
      'category' => 'mail',
    ];

    // Mail: Webform Email Reply.
    $projects['webform_email_reply'] = [
      'title' => $this->t('Webform Email Reply'),
      'description' => $this->t('A webform helper module that allows users to send an email reply to submissions.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_email_reply'),
      'category' => 'mail',
    ];

    // Mail: Webform Entity Email.
    $projects['webform_entity_email'] = [
      'title' => $this->t('Webform Entity Email'),
      'description' => $this->t('Provides a webform handler that sends an email rendering a specific entity.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_entity_email'),
      'category' => 'mail',
    ];

    // Mail: Webform Embed.
    $projects['webform_embed'] = [
      'title' => $this->t('Webform Embed'),
      'description' => $this->t('Allows you to embed webforms within an iframe on another site.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_embed'),
      'category' => 'mail',
    ];

    // Mail: Webform Mass Email.
    $projects['webform_mass_email'] = [
      'title' => $this->t('Webform Mass Email'),
      'description' => $this->t('Provides a functionality to send mass email for the subscribers of a webform.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_mass_email'),
      'category' => 'mail',
    ];

    // Mail: Webform Send Multiple Emails.
    $projects['webform_send_multiple_emails'] = [
      'title' => $this->t('Webform Send Multiple Emails'),
      'description' => $this->t('Extends the Webform module Email Handler to send individual emails when multiple recipients are added to the email "to" field.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_send_multiple_emails'),
      'category' => 'mail',
    ];

    /* ********************************************************************** */
    // Multilingual.
    /* ********************************************************************** */

    // Multilingual: Lingotek Translation.
    $projects['lingotek'] = [
      'title' => $this->t('Lingotek Translation.'),
      'description' => $this->t('Translates content, configuration, and interface using the Lingotek Translation Management System.'),
      'url' => Url::fromUri('https://www.drupal.org/project/lingotek'),
      'category' => 'multilingual',
    ];

    // Multilingual: Webform Translation Permissions.
    $projects['webform_translation_permissions'] = [
      'title' => $this->t('Webform Translation Permissions'),
      'description' => $this->t("Defines the following permissions to enable a user to translate a webform's configuration without granting them the 'translate configuration' permission needlessly."),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_translation_permissions'),
      'category' => 'multilingual',
    ];

    /* ********************************************************************** */
    // Migrate.
    /* ********************************************************************** */

    // Migrate: Webform Migrate.
    $projects['webform_migrate'] = [
      'title' => $this->t('Webform Migrate'),
      'description' => $this->t('Provides migration routines from d6, d7 webform to d8 webform.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_migrate'),
      'category' => 'migrate',
      'recommended' => TRUE,
    ];

    /* ********************************************************************** */
    // Spam.
    /* ********************************************************************** */

    // Spam: Antibot.
    $projects['antibot'] = [
      'title' => $this->t('Antibot'),
      'description' => $this->t('Prevent forms from being submitted without JavaScript enabled.'),
      'url' => Url::fromUri('https://www.drupal.org/project/antibot'),
      'category' => 'spam',
      'third_party_settings' => TRUE,
      'recommended' => TRUE,
    ];

    // Spam: CAPTCHA.
    $projects['captcha'] = [
      'title' => $this->t('CAPTCHA'),
      'description' => $this->t('Provides CAPTCHA for adding challenges to arbitrary forms.'),
      'url' => Url::fromUri('https://www.drupal.org/project/captcha'),
      'category' => 'spam',
      'recommended' => TRUE,
    ];

    // Spam: reCAPTCHA.
    $projects['recaptcha'] = [
      'title' => $this->t('reCAPTCHA'),
      'description' => $this->t('Uses the Google <a href=":href">reCAPTCHA</a> web service to improve the CAPTCHA system.', [':href' => 'https://www.google.com/recaptcha/about/']),
      'url' => Url::fromUri('https://www.drupal.org/project/recaptcha'),
      'category' => 'spam',
      'recommended' => TRUE,
    ];

    // Spam: Honeypot.
    $projects['honeypot'] = [
      'title' => $this->t('Honeypot'),
      'description' => $this->t('Mitigates spam form submissions using the honeypot method.'),
      'url' => Url::fromUri('https://www.drupal.org/project/honeypot'),
      'category' => 'spam',
      'third_party_settings' => TRUE,
      'recommended' => TRUE,
    ];

    // Spam: SpamAway.
    $projects['spamaway'] = [
      'title' => $this->t('SpamAway'),
      'description' => $this->t('Provides a webform handler which will mark submissions as SPAM'),
      'url' => Url::fromUri('https://www.drupal.org/project/spamaway'),
      'category' => 'spam',
    ];

    /* ********************************************************************** */

    // Spam: CleanTalk.
    $projects['cleantalk'] = [
      'title' => $this->t('CleanTalk'),
      'description' => $this->t('Antispam service from CleanTalk to protect your site.'),
      'url' => Url::fromUri('https://www.drupal.org/project/cleantalk'),
      'category' => 'spam',
    ];

    // Spam: Human Presence Form Protection.
    $projects['hp'] = [
      'title' => $this->t('Human Presence Form Protection'),
      'description' => $this->t('Human Presence is a fraud prevention and form protection service that uses multiple overlapping strategies to fight form spam.'),
      'url' => Url::fromUri('https://www.drupal.org/project/hp'),
      'category' => 'spam',
    ];

    // Spam: Protected Submissions.
    $projects['protected_forms'] = [
      'title' => $this->t('Protected Forms'),
      'description' => $this->t('Protected Forms is a light-weight, non-intrusive spam protection module that enables rejection of node, comment, webform, user profile, contact form, private message and revision log submissions which contain undesired language characters or preset patterns.'),
      'url' => Url::fromUri('https://www.drupal.org/project/protected_forms'),
      'category' => 'spam',
    ];

    // Spam: Recaptcha Element.
    $projects['recaptcha_element'] = [
      'title' => $this->t('Recaptcha Element'),
      'description' => $this->t('Provides a Webform Handler that allows you to enable reCAPTCHA protection on a webform using the webform UI.'),
      'url' => Url::fromUri('https://www.drupal.org/project/recaptcha_element'),
      'category' => 'spam',
    ];

    // Spam: Simple Google reCAPTCHA.
    $projects['simple_recaptcha'] = [
      'title' => $this->t('Simple Google reCAPTCHA'),
      'description' => $this->t('Provides simple integration with Google reCaptcha, keeping forms and webforms secure.'),
      'url' => Url::fromUri('https://www.drupal.org/project/simple_recaptcha'),
      'category' => 'spam',
    ];

    // Spam:Spam Master.
    $projects['spammaster'] = [
      'title' => $this->t('Spam Master'),
      'description' => $this->t('Spam Master is a Spam Protection Module that blocks new user registrations, comments, and threads with Real Time anti-spam lists.'),
      'url' => Url::fromUri('https://www.drupal.org/project/spammaster'),
      'category' => 'spam',
    ];

    // Spam: Webform Spam Words (WSW).
    $projects['webform_spam_words'] = [
      'title' => $this->t('Webform Spam Words (WSW)'),
      'description' => $this->t('Provides the ability to block spam words for webform fields.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_spam_words'),
      'category' => 'spam',
    ];

    /* ********************************************************************** */
    // Submissions.
    /* ********************************************************************** */

    // Submissions: Webform Analysis.
    $projects['webform_analysis'] = [
      'title' => $this->t('Webform Analysis'),
      'description' => $this->t('Used to obtain statistics on the results of form submissions.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_analysis'),
      'category' => 'submission',
      'recommended' => TRUE,
    ];

    // Submissions: Webform Query.
    $projects['webform_query'] = [
      'title' => $this->t('Webform Query'),
      'description' => $this->t('Query webform submission data.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_query'),
      'category' => 'submission',
      'recommended' => TRUE,
    ];

    // Submissions: Webform Views Integration.
    $projects['webform_views'] = [
      'title' => $this->t('Webform Views'),
      'description' => $this->t('Integrates Webform and Views modules.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_views'),
      'category' => 'submission',
      'recommended' => TRUE,
    ];

    // Submissions: Webform Submission Views Token Field.
    $projects['ws_views_field'] = [
      'title' => $this->t('Webform Submission Views Token Field'),
      'description' => $this->t('Provides a token approach to list WebformSubmission fields in views.'),
      'url' => Url::fromUri('https://www.drupal.org/project/ws_views_field'),
      'category' => 'submission',
      'recommended' => TRUE,
    ];

    /* ********************************************************************** */

    // Submissions: Webform Anonymous Submission.
    $projects['webform_anonymous_submission'] = [
      'title' => $this->t('Webform Anonymous Submission'),
      'description' => $this->t('Provide webform option to submit the webform as anonymous. It unset the username and IP when webform is submitted.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_anonymous_submission'),
      'category' => 'submission',
    ];

    // Submissions: Webform Anonymizer.
    $projects['webform_anonymizer'] = [
      'title' => $this->t('Webform Anonymizer'),
      'description' => $this->t('Anonymizes submissions even when the user is logged in.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_anonymizer'),
      'category' => 'submission',
    ];

    // Submissions: Webform Auto Exports.
    $projects['coc_forms_auto_export'] = [
      'title' => $this->t('Webform Auto Exports'),
      'description' => $this->t('Automatic export for Drupal Webform results.'),
      'url' => Url::fromUri('https://www.drupal.org/project/coc_forms_auto_export'),
      'category' => 'submission',
    ];

    // Submissions: Webform Better Results.
    $projects['webform_better_results'] = [
      'title' => $this->t('Webform Better Results'),
      'description' => $this->t('Adds some additional functionality to the standard webform results list.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_better_results'),
      'category' => 'submission',
    ];

    // Submissions: Webform double opt-in.
    $projects['webform_double_opt_in'] = [
      'title' => $this->t('Webform double opt-in'),
      'description' => $this->t('Provides email double opt-in functionality.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_double_opt_in'),
      'category' => 'submission',
    ];

    // Submissions: Webform Eager Purge.
    $projects['webform_eager_purge'] = [
      'title' => $this->t('Webform Eager Purge'),
      'description' => $this->t('The minimum period for standard purge of webform submissions is a day. Now you can specify it in minutes.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_eager_purge'),
      'category' => 'submission',
    ];

    // Submissions: Webform Invitation.
    $projects['webform_invitation'] = [
      'title' => $this->t('Webform Invitation'),
      'description' => $this->t('Allows you to restrict submissions to a webform by generating codes (which may then be distributed e.g. by email to participants).'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_invitation'),
      'category' => 'submission',
    ];

    // Submissions: Webform Permissions By Term.
    $projects['webform_permissions_by_term'] = [
      'title' => $this->t('Webform Permissions By Term'),
      'description' => $this->t('Extends the functionality of Permissions By Term to be able to limit the webform submissions access by users or roles.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_permissions_by_term'),
      'category' => 'submission',
    ];

    // Submissions: Webform Queue.
    $projects['webform_queue'] = [
      'title' => $this->t('Webform Queue'),
      'description' => $this->t('Posts form submissions into a Drupal queue.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_queue'),
      'category' => 'submission',
    ];

    // Submissions: Webform Resend Submissions.
    $projects['webform_resend_submissions'] = [
      'title' => $this->t('Webform Resend Submissions'),
      'description' => $this->t('Allows you to resend emails from webform submissions using Drush.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_resend_submissions'),
      'category' => 'submission',
    ];

    // Submissions: Webform Sanitize.
    $projects['webform_sanitize'] = [
      'title' => $this->t('Webform Sanitize'),
      'description' => $this->t('Sanitizes submissions to remove potentially sensitive data.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_sanitize'),
      'category' => 'submission',
    ];

    // Submissions: Webform Scheduled Tasks.
    $projects['webform_scheduled_tasks'] = [
      'title' => $this->t('Webform Scheduled Tasks'),
      'description' => $this->t('Allows the regular cleansing/sanitization of sensitive fields in Webform.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_scheduled_tasks'),
      'category' => 'submission',
    ];

    // Submissions: Webform Submission Anonymisation.
    $projects['webform_submission_anonymisation'] = [
      'title' => $this->t('Webform Submission Anonymisation'),
      'description' => $this->t('Remove personal datas from webform submissions.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_submission_anonymisation'),
      'category' => 'submission',
    ];

    // Submissions: Webform Submission Change History.
    $projects['webform_submission_change_history'] = [
      'title' => $this->t('Webform Submission Change History'),
      'description' => $this->t('Allows administrators to track notes on webform submissions.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_submission_change_history'),
      'category' => 'submission',
    ];

    // Submissions: Webform Submission Control.
    $projects['webform_submission_control'] = [
      'title' => $this->t('Webform Submission Control'),
      'description' => $this->t('Limit webform submission to entity.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_submission_control'),
      'category' => 'submission',
    ];

    // Submissions: Webform Submissions Delete.
    $projects['webform_submissions_delete'] = [
      'title' => $this->t('Webform Submissions Delete'),
      'description' => $this->t('Used to delete webform submissions using start date, end date all at once.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_submissions_delete'),
      'category' => 'submission',
    ];

    // Submissions: Timely Webform Reporting.
    $projects['timely_webform_reporting'] = [
      'title' => $this->t('Timely Webform Reporting'),
      'description' => $this->t('Create reports from Webform Submissions on a timely basis.'),
      'url' => Url::fromUri('https://www.drupal.org/project/timely_webform_reporting'),
      'category' => 'submission',
    ];

    // Submissions: Webform Submissions Notification.
    $projects['webform_digests'] = [
      'title' => $this->t('Webform Submissions Notification'),
      'description' => $this->t('Adds a daily digest email for webform submissions.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_digests'),
      'category' => 'submission',
    ];

    // Submissions: Webform Submission Files Download.
    $projects['webform_submission_files_download'] = [
      'title' => $this->t('Webform Submission Files Download'),
      'description' => $this->t('Allows you to download files attached to a single submission'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_submission_files_download'),
      'category' => 'submission',
    ];

    // Submissions: Webform Submission Splitter.
    $projects['webform_submission_splitter'] = [
      'title' => $this->t('Webform Submission Splitter'),
      'description' => $this->t("Adds a webform handler that allows you to select a multiple value element and then 'split' the submission by that element's values."),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_submission_splitter'),
      'category' => 'submission',
    ];

    // Submissions: Webform Views Extras.
    $projects['webform_views_extras'] = [
      'title' => $this->t('Webform Views Extras'),
      'description' => $this->t('Extends Webform views and supports relationships in views with all content entities not only node.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_views_extras'),
      'category' => 'submission',
    ];

    // Submissions: Webform XLSX Export.
    $projects['webform_xlsx_export'] = [
      'title' => $this->t('Webform XLSX Export'),
      'description' => $this->t('Exports Webform submissions in the Office Open XML format.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_xlsx_export'),
      'category' => 'submission',
    ];

    // Submissions: Yet another statistics module.
    $projects['yasm'] = [
      'title' => $this->t('Yet another statistics module'),
      'description' => $this->t('Yes! Another statistics module.'),
      'url' => Url::fromUri('https://www.drupal.org/project/yasm'),
      'category' => 'submission',
    ];

    /* ********************************************************************** */
    // Utility.
    /* ********************************************************************** */

    // Utility: IMCE.
    $projects['imce'] = [
      'title' => $this->t('IMCE'),
      'description' => $this->t('IMCE is an image/file uploader and browser that supports personal directories and quota.'),
      'url' => Url::fromUri('https://www.drupal.org/project/imce'),
      'category' => 'utility',
      'install' => $this->t('The IMCE module makes it easier to update images to webforms and elements.'),
      'recommended' => TRUE,
    ];

    // Utility: Token.
    $projects['token'] = [
      'title' => $this->t('Token'),
      'description' => $this->t('Provides a user interface for the Token API and some missing core tokens.'),
      'url' => Url::fromUri('https://www.drupal.org/project/token'),
      'category' => 'utility',
      'install' => $this->t('The Token module allows site builders to browser available webform-related tokens.'),
      'recommended' => TRUE,
    ];

    // Utility: Webform Media Type.
    $projects['webform_media'] = [
      'title' => $this->t('Webform Media Type'),
      'description' => $this->t("Easily embed webforms into CKEditor with a webform media type that integrates with core's media library."),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_media'),
      'category' => 'utility',
      'recommended' => TRUE,
    ];

    /* ********************************************************************** */

    // Utility: Calendar Links Token.
    $projects['calendar_links_token'] = [
      'title' => $this->t('Calendar Links Token'),
      'description' => $this->t('Generate add to calendar links for Google, iCal, etc using tokens.'),
      'url' => Url::fromUri('https://www.drupal.org/project/calendar_links_token'),
      'category' => 'utility',
    ];

    // Utility: Googalytics Webform.
    $projects['ga_webform'] = [
      'title' => $this->t('Googalytics Webform'),
      'description' => $this->t('Provides integration for Webform into Googalytics module.'),
      'url' => Url::fromUri('https://www.drupal.org/project/ga_webform'),
      'category' => 'utility',
    ];

    // Utility: General Data Protection Regulation Compliance.
    $projects['gdpr_compliance'] = [
      'title' => $this->t('General Data Protection Regulation Compliance'),
      'description' => $this->t('Provides Basic GDPR Compliance use cases via form checkboxes, pop-up alert, and a policy page.'),
      'url' => Url::fromUri('https://www.drupal.org/project/gdpr_compliance'),
      'category' => 'utility',
    ];

    // Utility: EU Cookie Compliance.
    $projects['eu_cookie_compliance'] = [
      'title' => $this->t('EU Cookie Compliance'),
      'description' => $this->t('This module aims at making the website compliant with the new EU cookie regulation.'),
      'url' => Url::fromUri('https://www.drupal.org/project/eu_cookie_compliance'),
      'category' => 'utility',
    ];

    // Utility: Formdazzle!
    $projects['formdazzle'] = [
      'title' => $this->t('Formdazzle!'),
      'description' => $this->t('Provides a set of utilities that make form theming easier.'),
      'url' => Url::fromUri('https://www.drupal.org/project/formdazzle'),
      'category' => 'utility',
    ];

    // Utility: Webform Encrypt.
    $projects['wf_encrypt'] = [
      'title' => $this->t('Webform Encrypt'),
      'description' => $this->t('Provides encryption for webform elements.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_encrypt'),
      'category' => 'utility',
    ];

    // Utility: Webform Ip Track.
    $projects['webform_ip_track'] = [
      'title' => $this->t('Webform Ip Track'),
      'description' => $this->t('Ip Location details as custom tokens to use in webform submission values.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_ip_track'),
      'category' => 'utility',
    ];

    // Utility: Webform Config Key Value.
    $projects['webform_config_key_value'] = [
      'title' => $this->t('Webform Config Key Value'),
      'description' => $this->t('Use the KeyValueStorage to save webform config instead of yaml config storage, allowing webforms to be treated more like content than configuration and are excluded from the configuration imports/exports.'),
      'url' => Url::fromUri('https://www.drupal.org/sandbox/thtas/2994250'),
      'experimental' => TRUE,
      'category' => 'utility',
    ];

    /* ********************************************************************** */
    // Validation.
    /* ********************************************************************** */

    // Validation: Clientside Validation.
    $projects['clientside_validation'] = [
      'title' => $this->t('Clientside Validation'),
      'description' => $this->t('Adds clientside validation to forms.'),
      'url' => Url::fromUri('https://www.drupal.org/project/clientside_validation'),
      'category' => 'validation',
      'recommended' => TRUE,
    ];

    // Validation: Advanced Email Validation.
    $projects['advanced_email_validation'] = [
      'title' => $this->t('Advanced Email Validation'),
      'description' => $this->t('Supplies a Webform validation handler that can be added to apply the available rules to chosen email fields on any webform, with the option to override configuration.'),
      'url' => Url::fromUri('https://www.drupal.org/project/advanced_email_validation'),
      'category' => 'validation',
    ];

    // Validation: Telephone Validation.
    $projects['telephone_validation'] = [
      'title' => $this->t('Telephone Validation'),
      'description' => $this->t('Provides validation for tel form element.'),
      'url' => Url::fromUri('https://www.drupal.org/project/telephone_validation'),
      'category' => 'validation',
    ];

    // Validation: Validators.
    $projects['validators'] = [
      'title' => $this->t('Validators'),
      'description' => $this->t('Provides Symfony (form) Validators for Drupal 8.'),
      'url' => Url::fromUri('https://www.drupal.org/project/validators'),
      'category' => 'validation',
    ];

    // Webform Handler: Compare Fields.
    $projects['webform_handler_compare_fields'] = [
      'title' => $this->t('Webform Handler: Compare Fields'),
      'description' => $this->t('Validation handler to compare two fields on a webform.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_handler_compare_fields'),
      'category' => 'validation',
    ];

    /* ********************************************************************** */
    // Web services.
    /* ********************************************************************** */

    // Web services: Decoupled Kit.
    $projects['decoupled_kit'] = [
      'title' => $this->t('Decoupled Kit'),
      'description' => $this->t('allows to solve some tasks of the decoupled Drupal.'),
      'url' => Url::fromUri('https://www.drupal.org/project/decoupled_kit'),
      'category' => 'web_services',
    ];

    // Web services: Gatsby Drupal Webform.
    $projects['gatsby_drupal_webform'] = [
      'title' => $this->t('Gatsby Drupal Webform'),
      'description' => $this->t('React component and graphql fragments for webforms. Goal of this project is to have a react component that generates bootstrap like HTML from webform YAML configuration.'),
      'url' => Url::fromUri('https://github.com/oikeuttaelaimille/gatsby-drupal-webform'),
      'category' => 'web_services',
    ];

    // Web services: Webform REST.
    $projects['webform_rest'] = [
      'title' => $this->t('Webform REST'),
      'description' => $this->t('Retrieve and submit webforms via REST.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_rest'),
      'category' => 'web_services',
    ];

    // Web services: Webform JSON:API.
    $projects['webform_jsonapi'] = [
      'title' => $this->t('Webform JSON:API'),
      'description' => $this->t('Provides a webform integration with JSON:API to expose webform elements.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_jsonapi'),
      'category' => 'web_services',
    ];

    // Web services: Webform JSON Schema.
    $projects['webform_jsonschema'] = [
      'title' => $this->t('Webform JSON Schema'),
      'description' => $this->t('Expose webforms as JSON Schema, UI Schema, and Form Data. Make webforms work with react-jsonschema-form.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_jsonschema'),
      'category' => 'web_services',
    ];

    /* ********************************************************************** */
    // Workflow.
    /* ********************************************************************** */

    // Workflow: Config Entity Revisions.
    $projects['config_entity_revisions'] = [
      'title' => $this->t('Config Entity Revisions'),
      'description' => $this->t('Provide revisions and moderation for Webforms.'),
      'url' => Url::fromUri('https://www.drupal.org/project/config_entity_revisions'),
      'category' => 'workflow',
    ];

    // Workflow: Maestro.
    $projects['maestro'] = [
      'title' => $this->t('Maestro Workflow Engine'),
      'description' => $this->t('A business process workflow solution that allows you to create and automate a sequence of tasks representing any business, document approval or collaboration process.'),
      'url' => Url::fromUri('https://www.drupal.org/project/maestro'),
      'category' => 'workflow',
    ];

    // Workflow: Workflows Field.
    $projects['workflows_field'] = [
      'title' => $this->t('Workflows Field'),
      'description' => $this->t('A business process workflow solution that allows you to create and automate a sequence of tasks representing any business, document approval or collaboration process.'),
      'url' => Url::fromUri('https://www.drupal.org/project/workflows_field'),
      'category' => 'workflow',
    ];

    // Workflow: Webform Workflows Element.
    $projects['webform_workflows_element'] = [
      'title' => $this->t('Webform Workflows Element'),
      'description' => $this->t('Provides a new element type for Webforms (D8+) that uses the core Workflows functionality to move submissions through a webform.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_workflows_element'),
      'category' => 'workflow',
    ];

    // Workflow: Webform Revision UI.
    $projects['webform_revision_ui'] = [
      'title' => $this->t('Webform Revision UI'),
      'description' => $this->t('Adds Webform Revision UI.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_revision_ui'),
      'category' => 'workflow',
    ];

    /* ********************************************************************** */
    // Development.
    /* ********************************************************************** */

    // Devel: Maillog / Mail Developer.
    $projects['maillog'] = [
      'title' => $this->t('Maillog / Mail Developer'),
      'description' => $this->t('Utility to log all Mails for debugging purposes. It is possible to suppress mail delivery for e.g. dev or staging systems.'),
      'url' => Url::fromUri('https://www.drupal.org/project/maillog'),
      'category' => 'development',
      'recommended' => TRUE,
    ];

    // Devel: Webform Submissions List Decorator.
    $projects['webform_list_decorator'] = [
      'title' => $this->t('Webform Submissions List Decorator'),
      'description' => $this->t('Override submissions list and allows user hide columns of webform submissions in submissions list.'),
      'url' => Url::fromUri('https://www.drupal.org/sandbox/antonkerbel/3098999'),
      'category' => 'development',
    ];

    $this->projects = $projects;
  }

}
