<?php

/**
 * @file
 * theme-settings.php
 *
 * Provides theme settings for Arizona Barrio.
 */

include_once dirname(__FILE__) . '/includes/common.inc';

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Implements hook_form_system_theme_settings_alter() for settings form.
 *
 * Replace Barrio setting options with subtheme ones.
 *
 * Example on how to alter theme settings form
 */
function az_barrio_form_system_theme_settings_alter(&$form, FormStateInterface $form_state) {
  $form['footer_logo']['#open'] = FALSE;

  // AZ Barrio settings.
  $form['az_settings'] = [
    '#type' => 'details',
    '#title' => t('Arizona Barrio'),
    '#group' => 'bootstrap',
    '#weight' => -10,
  ];

  // Institutional logo.
  $form['az_settings']['settings']['institutional_logo'] = [
    '#type' => 'fieldset',
    '#title' => t('Institutional Logo Settings'),
  ];
  $form['az_settings']['settings']['institutional_logo']['wordmark'] = [
    '#type' => 'checkbox',
    '#title' => t('Institutional header wordmark logo'),
    '#description' => t('With few exceptions, this should always be enabled.'),
    '#default_value' => theme_get_setting('wordmark'),
  ];

  // Main menu.
  $form['az_settings']['settings']['main_menu'] = [
    '#type' => 'fieldset',
    '#title' => t('Main Menu Settings'),
  ];
  $form['az_settings']['settings']['main_menu']['az_main_menu_style'] = [
    '#type' => 'radios',
    '#options' => [
      'bootstrap' => t("Render the main menu element using AZ Bootstrap's Dropdown Navbar component."),
      'superfish' => t('Render the main menu element using Superfish (requires AZQS Navigation & Superfish modules).'),
    ],
    '#title' => t('Main menu style'),
    '#default_value' => theme_get_setting('az_main_menu_style'),
    '#prefix' => t("AZ Barrio can render the 'Main menu' page element in a number of different styles. The AZ Bootstrap Dropdown Navbar component style will be used as the fallback option if the dependencies for the other styles are missing."),
  ];
  $form['az_settings']['settings']['main_menu']['az_main_menu_style']['menu_style_enhancements'] = [
    '#type' => 'fieldset',
    '#weight' => 100,
    '#title' => t('Menu Style Enhancements'),
    '#collapsible' => FALSE,
    '#collapsed' => FALSE,
    '#states' => [
      'visible' => [
        ':input[name="az_main_menu_style"]' => ['value' => 'bootstrap'],
      ],
    ],
  ];
  $form['az_settings']['settings']['main_menu']['az_main_menu_style']['menu_style_enhancements']['az_bs_overlay_menu_scroll'] = [
    '#type' => 'checkbox',
    '#title' => t('Overlay Menu Scroll'),
    '#default_value' => theme_get_setting('az_bs_overlay_menu_scroll'),
    '#description' => t('Render the main menu element using AZQS customized Bootstrap Overlay Menu Scroll Navigation.'),
  ];

  // Information security and privacy link.
  $form['az_settings']['settings']['info_security_privacy'] = [
    '#type' => 'checkbox',
    '#title' => t('University Information Security and Privacy link'),
    '#description' => t('With few execeptions, this should always be enabled.'),
    '#default_value' => theme_get_setting('info_security_privacy'),
  ];

  // Copyright notice.
  $form['az_settings']['settings']['copyright_notice'] = [
    '#type' => 'textfield',
    '#title' => t('Copyright notice'),
    '#maxlength' => 512,
    '#description' => t('A copyright notice for this site. The value here will appear after a "Copyright YYYY" notice (where YYYY is the current year).'),
    '#default_value' => theme_get_setting('copyright_notice'),
  ];

  // Hide front page title.
  $form['az_settings']['settings']['az_hide_front_title'] = [
    '#type' => 'checkbox',
    '#title' => t('Hide title of front page node'),
    '#description' => t('If this is checked, the title of the node being displayed on the front page will not be visible'),
    '#default_value' => theme_get_setting('az_hide_front_title'),
  ];

  // Back-to-top button.
  $form['az_settings']['settings']['az_back_to_top'] = [
    '#type' => 'checkbox',
    '#title' => t('Add back to top button to pages longer than 4 screens (browser window height).'),
    '#default_value' => theme_get_setting('az_back_to_top'),
  ];

  // AZ Bootstrap settings.
  $form['azbs_settings'] = [
    '#type' => 'details',
    '#title' => t('Arizona Bootstrap'),
    '#group' => 'bootstrap',
    '#weight' => -9,
  ];
  $form['azbs_settings']['settings']['az_bootstrap_source'] = [
    '#type' => 'radios',
    '#title' => t('AZ Bootstrap Source'),
    '#options' => [
      'local' => t('Use local copy of AZ Bootstrap packaged with AZ Barrio (%stableversion).', ['%stableversion' => AZ_BOOTSTRAP_STABLE_VERSION]),
      'cdn' => t('Use external copy of AZ Bootstrap hosted on the AZ Bootstrap CDN.'),
    ],
    '#default_value' => theme_get_setting('az_bootstrap_source'),
    '#prefix' => t('AZ Barrio requires the <a href="@azbootstrap">AZ Bootstrap</a> front-end framework. AZ Bootstrap can either be loaded from the local copy packaged with UA Zen or from the <a href="@azbootstrapcdn">AZ Bootstrap CDN</a>.', [
      '@azbootstrap' => 'http://uadigital.arizona.edu/ua-bootstrap',
      '@azbootstrapcdn' => 'https://cdn.uadigital.arizona.edu/lib/ua-bootstrap',
    ]),
    '#description' => '<div class="alert alert-info messages info">' . t('<strong>NOTE:</strong> The AZ Bootstrap CDN is the preferred method for providing huge performance gains in load time.') . '</div>',
  ];
  $form['azbs_settings']['settings']['az_bootstrap_cdn'] = [
    '#type' => 'fieldset',
    '#title' => t('AZ Bootstrap CDN Settings'),
    '#states' => [
      'visible' => [
        ':input[name="az_bootstrap_source"]' => ['value' => 'cdn'],
      ],
    ],
  ];
  $form['azbs_settings']['settings']['az_bootstrap_cdn']['az_bootstrap_cdn_version'] = [
    '#type' => 'radios',
    '#title' => t('AZ Bootstrap CDN version'),
    '#options' => [
      'stable' => t('Stable version: This option has undergone the most testing within the az_barrio theme. Currently: %stableversion (Recommended).', ['%stableversion' => AZ_BOOTSTRAP_STABLE_VERSION]),
      'latest' => t('Latest tagged version. The most recently tagged stable release of AZ Bootstrap. While this has not been explicitly tested on this version of az_barrio, it’s probably OK to use on production sites. Please report bugs to the AZ Digital team.'),
      'master' => t('Latest dev version. This is the tip of the master branch of AZ Bootstrap. Please do not use on production unless you are following the ua-bootstrap project closely. Please report bugs to the AZ Digital team.'),
    ],
    '#default_value' => theme_get_setting('az_bootstrap_cdn_version'),
  ];
  $form['azbs_settings']['settings']['az_bootstrap_minified'] = [
    '#type'          => 'checkbox',
    '#title'         => t('Use minified version of AZ Bootstrap.'),
    '#default_value' => theme_get_setting('az_bootstrap_minified'),
  ];
  $form['azbs_settings']['settings']['az_bootstrap_style'] = [
    '#type' => 'fieldset',
    '#title' => t('AZ Bootstrap Style Settings'),
  ];
  $form['azbs_settings']['settings']['az_bootstrap_style']['az_brand_icons_source'] = [
    '#type' => 'radios',
    '#title' => t('AZ Brand Icons Source'),
    '#options' => [
      'local' => t('Use local copy of <a href="@azbrandicons">UA Brand Icons</a> packaged with AZ Barrio (%stableversion).', [
        '@azbrandicons' => 'http://uadigital.arizona.edu/ua-bootstrap/components.html#ua-brand-icons',
        '%stableversion' => AZ_BRAND_ICONS_STABLE_VERSION,
      ]),
      'cdn' => t('Use external copy of <a href="@azbrandicons">UA Brand Icons</a> hosted on the CDN.', [
        '@azbrandicons' => 'http://uadigital.arizona.edu/ua-bootstrap/components.html#ua-brand-icons',
      ]),
    ],
    '#default_value' => theme_get_setting('az_brand_icons_source'),
  ];
  $form['azbs_settings']['settings']['az_bootstrap_style']['az_brand_icons_class'] = [
    '#type' => 'checkbox',
    '#title' => t('Add <code>ua-brand-icons</code> class to <code>html</code> element.'),
    '#default_value' => theme_get_setting('az_brand_icons_class'),
  ];
  $form['azbs_settings']['settings']['az_bootstrap_style']['external_links'] = [
    '#type' => 'checkbox',
    '#title' => t('Use AZ Bootstrap external links styling.'),
    '#default_value' => theme_get_setting('external_links'),
  ];
  $form['azbs_settings']['settings']['az_bootstrap_style']['sticky_footer'] = [
    '#type' => 'checkbox',
    '#title' => t('Use the AZ Bootstrap sticky footer template.'),
    '#default_value' => theme_get_setting('sticky_footer'),
  ];

  // Material Design icons.
  $form['material_design_icon_settings'] = [
    '#type' => 'details',
    '#title' => t('Material Design Icons'),
    '#group' => 'bootstrap',
    '#weight' => -8,
  ];
  $form['material_design_icon_settings']['settings']['material_design_icons']['use_material_design_sharp_icons'] = [
    '#type' => 'checkbox',
    '#title' => t('Use Material Design Sharp Icons'),
    '#default_value' => theme_get_setting('use_material_design_sharp_icons'),
    '#description' => t('If selected, a Google Fonts CDN <code>&lt;link&gt;</code> will be added to every page importing the @material_design_icons_docs_link CSS.',
    ['@material_design_icons_docs_link' => Link::fromTextAndUrl('sharp style of Material Design icons', Url::fromUri('https://material.io/resources/icons/?style=sharp'))->toString()]),
  ];

  // Components.
  $form['components']['navbar']['bootstrap_barrio_navbar_top_background']['#options'] = [
    'bg-primary' => t('Primary'),
    'bg-secondary' => t('Secondary'),
    'bg-light' => t('Light'),
    'bg-dark' => t('Dark'),
    'bg-white' => t('White'),
    'bg-transparent' => t('Transparent'),
  ];
  $form['components']['navbar']['bootstrap_barrio_navbar_background']['#options'] = [
    'bg-primary' => t('Primary'),
    'bg-secondary' => t('Secondary'),
    'bg-light' => t('Light'),
    'bg-dark' => t('Dark'),
    'bg-white' => t('White'),
    'bg-transparent' => t('Transparent'),
  ];

  // Primary logo.
  $form['logo']['primary_logo_alt_text'] = [
    '#type' => 'textfield',
    '#title' => t('Custom primary logo alt text'),
    '#description' => t('Alternative text is used by screen readers, search engines, and when the image cannot be loaded. By adding alt text you improve accessibility and search engine optimization.'),
    '#default_value' => theme_get_setting('primary_logo_alt_text'),
    '#element_validate' => ['token_element_validate'],
  ];
  $form['logo']['primary_logo_title_text'] = [
    '#type' => 'textfield',
    '#title' => t('Custom primary logo title text'),
    '#description' => t('Title text is used in the tool tip when a user hovers their mouse over the image. Adding title text makes it easier to understand the context of an image and improves usability.'),
    '#default_value' => theme_get_setting('primary_logo_title_text'),
    '#element_validate' => ['token_element_validate'],
  ];
  $form['logo']['tokens'] = [
    '#theme' => 'token_tree_link',
    '#global_types' => TRUE,
    '#click_insert' => TRUE,
  ];

  // Footer logo.
  $form['footer_logo'] = [
    '#type' => 'details',
    '#title' => t('Logo Footer Image'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
  ];
  $form['footer_logo']['footer_default_logo'] = [
    '#type' => 'checkbox',
    '#title' => t('Use the default logo'),
    '#default_value' => theme_get_setting('footer_default_logo'),
    '#tree' => FALSE,
    '#description' => t('Check here if you want the theme to use the logo supplied with it.'),
  ];
  $form['footer_logo']['settings'] = [
    '#type' => 'container',
    '#states' => [
      // Hide the logo settings when using the default logo.
      'invisible' => [
        'input[name="footer_default_logo"]' => [
          'checked' => TRUE,
        ],
      ],
    ],
  ];
  $form['footer_logo']['footer_logo_link_destination'] = [
    '#type' => 'textfield',
    '#title' => t('Footer logo link destination'),
    '#description' => t('Where should the footer logo link to. Example: &#x3C;front&#x3E;'),
    '#default_value' => theme_get_setting('footer_logo_link_destination'),
  ];
  $form['footer_logo']['footer_logo_alt_text'] = [
    '#type' => 'textfield',
    '#title' => t('Footer logo alt text'),
    '#description' => t('Alternative text is used by screen readers, search engines, and when the image cannot be loaded. By adding alt text you improve accessibility and search engine optimization.'),
    '#default_value' => theme_get_setting('footer_logo_alt_text'),
    '#element_validate' => ['token_element_validate'],
  ];
  $form['footer_logo']['footer_logo_title_text'] = [
    '#type' => 'textfield',
    '#title' => t('Footer logo title text'),
    '#description' => t('Title text is used in the tool tip when a user hovers their mouse over the image. Adding title text makes it easier to understand the context of an image and improves usability.'),
    '#default_value' => theme_get_setting('footer_logo_title_text'),
    '#element_validate' => ['token_element_validate'],
  ];
  $form['footer_logo']['tokens'] = [
    '#theme' => 'token_tree_link',
    '#global_types' => TRUE,
    '#click_insert' => TRUE,
  ];
  $form['footer_logo']['settings']['footer_logo_path'] = [
    '#type' => 'textfield',
    '#title' => t('Path to custom footer logo'),
    '#description' => t('The path to the file you would like to use as your footer logo file instead of the logo in the header.'),
    '#default_value' => theme_get_setting('footer_logo'),
  ];
  $form['footer_logo']['settings']['footer_logo_upload'] = [
    '#type' => 'file',
    '#title' => t('Upload footer logo image'),
    '#maxlength' => 40,
    '#description' => t("If you don't have direct file access to the server, use this field to upload your footer logo."),
  ];
}
