<?php

/**
 * @file
 * theme-settings.php
 *
 * Provides theme settings for Arizona Barrio.
 */

//phpcs:ignore Security.BadFunctions.EasyRFI.WarnEasyRFI
require_once \Drupal::service('extension.list.theme')->getPath('az_barrio') . '/includes/common.inc';

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\File\Exception\FileException;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Core\StreamWrapper\StreamWrapperManager;

/**
 * Implements hook_form_system_theme_settings_alter() for settings form.
 *
 * Replace Barrio setting options with subtheme ones.
 *
 * Example on how to alter theme settings form
 */
function az_barrio_form_system_theme_settings_alter(&$form, FormStateInterface $form_state) {

  // Change collapsible fieldsets (now details) to default #open => FALSE.
  $form['theme_settings']['#open'] = FALSE;
  $form['logo']['#open'] = FALSE;
  $form['favicon']['#open'] = FALSE;

  // Vertical tabs.
  $form['bootstrap'] = [
    '#type' => 'vertical_tabs',
    '#prefix' => '<h2><small>' . t('Bootstrap settings') . '</small></h2>',
    '#weight' => -10,
  ];

  // Layout.
  $form['layout'] = [
    '#type' => 'details',
    '#title' => t('Layout'),
    '#group' => 'bootstrap',
  ];

  // Container.
  $form['layout']['container'] = [
    '#type' => 'details',
    '#title' => t('Container'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
  ];
  $form['layout']['container']['az_barrio_fluid_container'] = [
    '#type' => 'checkbox',
    '#title' => t('Fluid container'),
    '#default_value' => theme_get_setting('az_barrio_fluid_container'),
    '#description' => t('Use <code>.container-fluid</code> class. See @bootstrap_fluid_containers_link.', [
      '@bootstrap_fluid_containers_link' => Link::fromTextAndUrl('Containers in the Bootstrap 4 documentation',
      Url::fromUri('https://getbootstrap.com/docs/4.3/layout/overview/',
      ['absolute' => TRUE, 'fragment' => 'containers']))->toString(),
    ]),
  ];

  // List of regions.
  $theme = \Drupal::theme()->getActiveTheme()->getName();
  $region_list = system_region_list($theme);

  // Only for initial setup if not defined on install.
  $nowrap = [
    'breadcrumb',
    'highlighted',
    'content',
    'primary_menu',
    'header',
    'sidebar_first',
    'sidebar_second',
  ];

  // Region.
  $form['layout']['region'] = [
    '#type' => 'details',
    '#title' => t('Region'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
  ];
  foreach ($region_list as $name => $description) {
    if (theme_get_setting('az_barrio_region_clean_' . $name) !== NULL) {
      $region_clean = theme_get_setting('az_barrio_region_clean_' . $name);
    }
    else {
      $region_clean = in_array($name, $nowrap);
    }
    if (theme_get_setting('az_barrio_region_class_' . $name) !== NULL) {
      $region_class = theme_get_setting('az_barrio_region_class_' . $name);
    }
    else {
      $region_class = $region_clean ? NULL : 'row';
    }

    $form['layout']['region'][$name] = [
      '#type' => 'details',
      '#title' => $description,
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    ];
    $form['layout']['region'][$name]['az_barrio_region_clean_' . $name] = [
      '#type' => 'checkbox',
      '#title' => t('Clean wrapper for @description region', ['@description' => $description]),
      '#default_value' => $region_clean,
    ];
    $form['layout']['region'][$name]['az_barrio_region_class_' . $name] = [
      '#type' => 'textfield',
      '#title' => t('Classes for @description region', ['@description' => $description]),
      '#default_value' => $region_class,
      '#size' => 40,
      '#maxlength' => 40,
    ];
  }

  // Sidebar Position.
  $form['layout']['sidebar_position'] = [
    '#type' => 'details',
    '#title' => t('Sidebar position'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
  ];
  $form['layout']['sidebar_position']['az_barrio_sidebar_position'] = [
    '#type' => 'select',
    '#title' => t('Sidebars position'),
    '#default_value' => theme_get_setting('az_barrio_sidebar_position'),
    '#options' => [
      'left' => t('Left'),
      'both' => t('Both sides'),
      'right' => t('Right'),
    ],
  ];
  $form['layout']['sidebar_position']['az_barrio_content_offset'] = [
    '#type' => 'select',
    '#title' => t('Content offset'),
    '#default_value' => theme_get_setting('az_barrio_content_offset'),
    '#options' => [
      0 => t('None'),
      1 => t('1 cols'),
      2 => t('2 cols'),
    ],
  ];

  // Sidebar first layout.
  $form['layout']['sidebar_first'] = [
    '#type' => 'details',
    '#title' => t('Sidebar First Layout'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
  ];
  $form['layout']['sidebar_first']['az_barrio_sidebar_collapse'] = [
    '#type' => 'checkbox',
    '#title' => t('Sidebar collapse'),
    '#default_value' => theme_get_setting('az_barrio_sidebar_collapse'),
  ];
  $form['layout']['sidebar_first']['az_barrio_sidebar_first_width'] = [
    '#type' => 'select',
    '#title' => t('Sidebar first width'),
    '#default_value' => theme_get_setting('az_barrio_sidebar_first_width'),
    '#options' => [
      2 => t('2 cols'),
      3 => t('3 cols'),
      4 => t('4 cols'),
    ],
  ];
  $form['layout']['sidebar_first']['az_barrio_sidebar_first_offset'] = [
    '#type' => 'select',
    '#title' => t('Sidebar first offset'),
    '#default_value' => theme_get_setting('az_barrio_sidebar_first_offset'),
    '#options' => [
      0 => t('None'),
      1 => t('1 cols'),
      2 => t('2 cols'),
    ],
  ];

  // Sidebar second layout.
  $form['layout']['sidebar_second'] = [
    '#type' => 'details',
    '#title' => t('Sidebar second layout'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
  ];
  $form['layout']['sidebar_second']['az_barrio_sidebar_second_width'] = [
    '#type' => 'select',
    '#title' => t('Sidebar second width'),
    '#default_value' => theme_get_setting('az_barrio_sidebar_second_width'),
    '#options' => [
      2 => t('2 cols'),
      3 => t('3 cols'),
      4 => t('4 cols'),
    ],
  ];
  $form['layout']['sidebar_second']['az_barrio_sidebar_second_offset'] = [
    '#type' => 'select',
    '#title' => t('Sidebar second offset'),
    '#default_value' => theme_get_setting('az_barrio_sidebar_second_offset'),
    '#options' => [
      0 => t('None'),
      1 => t('1 cols'),
      2 => t('2 cols'),
    ],
  ];

  // Components.
  $form['components'] = [
    '#type' => 'details',
    '#title' => t('Components'),
    '#group' => 'bootstrap',
  ];

  // Buttons.
  $form['components']['buttons'] = [
    '#type' => 'details',
    '#title' => t('Buttons'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
  ];
  $form['components']['buttons']['az_barrio_button'] = [
    '#type' => 'checkbox',
    '#title' => t('Convert input submit to button element'),
    '#default_value' => theme_get_setting('az_barrio_button'),
    '#description' => t('There is a known issue where Ajax exposed filters do not if this setting is enabled.'),
  ];
  $form['components']['buttons']['az_barrio_button_size'] = [
    '#type' => 'select',
    '#title' => t('Default button size'),
    '#default_value' => theme_get_setting('az_barrio_button_size'),
    '#empty_option' => t('Normal'),
    '#options' => [
      'btn-sm' => t('Small'),
      'btn-lg' => t('Large'),
    ],
  ];
  $form['components']['buttons']['az_barrio_button_outline'] = [
    '#type' => 'checkbox',
    '#title' => t('Button with outline format'),
    '#default_value' => theme_get_setting('az_barrio_button_outline'),
    '#description' => t('Use <code>.btn-default-outline</code> class. See @bootstrap_outline_buttons_link.', [
      '@bootstrap_outline_buttons_link' => Link::fromTextAndUrl('Outline buttons in the Bootstrap 4 documentation',
      Url::fromUri('https://getbootstrap.com/docs/4.3/components/buttons/',
      ['absolute' => TRUE, 'fragment' => 'outline-buttons']))->toString(),
    ]),
  ];

  // Navbar.
  $form['components']['navbar'] = [
    '#type' => 'details',
    '#title' => t('Navbar'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
  ];
  $form['components']['navbar']['az_barrio_navbar_container'] = [
    '#type' => 'checkbox',
    '#title' => t('Navbar width container'),
    '#description' => t('Check if navbar width will be inside container or fluid width.'),
    '#default_value' => theme_get_setting('az_barrio_navbar_container'),
  ];
  $form['components']['navbar']['az_barrio_navbar_toggle'] = [
    '#type' => 'select',
    '#title' => t('Navbar toggle size'),
    '#description' => t('Select size for navbar to collapse.'),
    '#default_value' => theme_get_setting('az_barrio_navbar_toggle'),
    '#options' => [
      'navbar-toggleable-lg' => t('Large'),
      'navbar-toggleable-md' => t('Medium'),
      'navbar-toggleable-sm' => t('Small'),
      'navbar-toggleable-xs' => t('Extra small'),
    ],
  ];
  $form['components']['navbar']['az_barrio_navbar_top_navbar'] = [
    '#type' => 'checkbox',
    '#title' => t('Navbar top is navbar'),
    '#description' => t('Check if navbar top .navbar class should be added.'),
    '#default_value' => theme_get_setting('az_barrio_navbar_top_navbar'),
  ];
  $form['components']['navbar']['az_barrio_navbar_top_position'] = [
    '#type' => 'select',
    '#title' => t('Navbar top position'),
    '#description' => t('Select your navbar top position.'),
    '#default_value' => theme_get_setting('az_barrio_navbar_top_position'),
    '#options' => [
      'fixed-top' => t('Fixed top'),
      'fixed-bottom' => t('Fixed bottom'),
      'sticky-top' => t('Sticky top'),
    ],
    '#empty_option' => t('Normal'),
  ];
  $form['components']['navbar']['az_barrio_navbar_top_color'] = [
    '#type' => 'select',
    '#title' => t('Navbar top link color'),
    '#default_value' => theme_get_setting('az_barrio_navbar_top_color'),
    '#options' => [
      'navbar-light' => t('Light'),
      'navbar-dark' => t('Dark'),
    ],
    '#empty_option' => t('Default'),
  ];
  $form['components']['navbar']['az_barrio_navbar_top_background'] = [
    '#type' => 'select',
    '#title' => t('Navbar top background color'),
    '#default_value' => theme_get_setting('az_barrio_navbar_top_background'),
    '#options' => [
      'bg-primary' => t('Primary'),
      'bg-light' => t('Light'),
      'bg-dark' => t('Dark'),
    ],
    '#empty_option' => t('Default'),
  ];
  $form['components']['navbar']['az_barrio_navbar_position'] = [
    '#type' => 'select',
    '#title' => t('Navbar position'),
    '#default_value' => theme_get_setting('az_barrio_navbar_position'),
    '#options' => [
      'fixed-top' => t('Fixed top'),
      'fixed-bottom' => t('Fixed bottom'),
      'sticky-top' => t('Sticky top'),
    ],
    '#empty_option' => t('Normal'),
  ];
  $form['components']['navbar']['az_barrio_navbar_color'] = [
    '#type' => 'select',
    '#title' => t('Navbar link color'),
    '#default_value' => theme_get_setting('az_barrio_navbar_color'),
    '#options' => [
      'navbar-light' => t('Light'),
      'navbar-dark' => t('Dark'),
    ],
    '#empty_option' => t('Default'),
  ];
  $form['components']['navbar']['az_barrio_navbar_background'] = [
    '#type' => 'select',
    '#title' => t('Navbar background color'),
    '#default_value' => theme_get_setting('az_barrio_navbar_background'),
    '#options' => [
      'bg-primary' => t('Primary'),
      'bg-light' => t('Light'),
      'bg-dark' => t('Dark'),
    ],
    '#empty_option' => t('Default'),
  ];
  // Allow custom classes on Navbars.
  $form['components']['navbar']['az_barrio_navbar_top_class'] = [
    '#type' => 'textfield',
    '#title' => t('Custom classes for Navbar Top'),
    '#default_value' => theme_get_setting('az_barrio_navbar_top_class'),
    '#size' => 40,
    '#maxlength' => 40,
  ];
  $form['components']['navbar']['az_barrio_navbar_class'] = [
    '#type' => 'textfield',
    '#title' => t('Custom classes for Navbar'),
    '#default_value' => theme_get_setting('az_barrio_navbar_class'),
    '#size' => 40,
    '#maxlength' => 40,
  ];

  // Messages.
  $form['components']['alerts'] = [
    '#type' => 'details',
    '#title' => t('Messages'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
  ];
  $form['components']['alerts']['az_barrio_messages_widget'] = [
    '#type' => 'select',
    '#title' => t('Messages widget'),
    '#default_value' => theme_get_setting('az_barrio_messages_widget'),
    '#options' => [
      'default' => t('Alerts classic'),
      'alerts' => t('Alerts bottom'),
      'toasts' => t('Toasts'),
    ],
  ];

  // Form.
  $form['components']['form'] = [
    '#type' => 'details',
    '#title' => t('Form'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
  ];
  $form['components']['form']['az_barrio_radio'] = [
    '#type' => 'select',
    '#title' => t('Radio widget'),
    '#default_value' => theme_get_setting('az_barrio_radio'),
    '#options' => [
      'standard' => t('Standard'),
      'custom' => t('Custom'),
    ],
  ];
  $form['components']['form']['az_barrio_checkbox'] = [
    '#type' => 'select',
    '#title' => t('Checkbox widget'),
    '#default_value' => theme_get_setting('az_barrio_checkbox'),
    '#options' => [
      'standard' => t('Standard'),
      'custom' => t('Custom'),
      'switch' => t('Switch'),
    ],
  ];
  $form['components']['form']['az_barrio_select'] = [
    '#type' => 'select',
    '#title' => t('Select widget'),
    '#default_value' => theme_get_setting('az_barrio_select'),
    '#options' => [
      'standard' => t('Standard'),
      'custom' => t('Custom'),
    ],
  ];
  $form['components']['form']['az_barrio_file'] = [
    '#type' => 'select',
    '#title' => t('File widget'),
    '#default_value' => theme_get_setting('az_barrio_file'),
    '#options' => [
      'standard' => t('Standard'),
      'custom' => t('Custom'),
    ],
  ];

  // Affix.
  $form['affix'] = [
    '#type' => 'details',
    '#title' => t('Affix'),
    '#group' => 'bootstrap',
  ];
  $form['affix']['navbar_top'] = [
    '#type' => 'details',
    '#title' => t('Affix navbar top'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
  ];
  $form['affix']['navbar_top']['az_barrio_navbar_top_affix'] = [
    '#type' => 'checkbox',
    '#title' => t('Affix navbar top'),
    '#default_value' => theme_get_setting('az_barrio_navbar_top_affix'),
  ];
  $form['affix']['navbar'] = [
    '#type' => 'details',
    '#title' => t('Affix navbar'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
  ];
  $form['affix']['navbar']['az_barrio_navbar_affix'] = [
    '#type' => 'checkbox',
    '#title' => t('Affix navbar'),
    '#default_value' => theme_get_setting('az_barrio_navbar_affix'),
  ];
  $form['affix']['sidebar_first'] = [
    '#type' => 'details',
    '#title' => t('Affix sidebar first'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
  ];
  $form['affix']['sidebar_first']['az_barrio_sidebar_first_affix'] = [
    '#type' => 'checkbox',
    '#title' => t('Affix sidebar first'),
    '#default_value' => theme_get_setting('az_barrio_sidebar_first_affix'),
  ];
  $form['affix']['sidebar_second'] = [
    '#type' => 'details',
    '#title' => t('Affix sidebar second'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
  ];
  $form['affix']['sidebar_second']['az_barrio_sidebar_second_affix'] = [
    '#type' => 'checkbox',
    '#title' => t('Affix sidebar second'),
    '#default_value' => theme_get_setting('az_barrio_sidebar_second_affix'),
  ];

  // Scroll Spy.
  $form['scroll_spy'] = [
    '#type' => 'details',
    '#title' => t('Scroll Spy'),
    '#group' => 'bootstrap',
  ];
  $form['scroll_spy']['az_barrio_scroll_spy'] = [
    '#type' => 'textfield',
    '#title' => t('Scrollspy element ID'),
    '#description' => t('Specify a valid jQuery ID for the element containing a .nav that will behave with scrollspy.'),
    '#default_value' => theme_get_setting('az_barrio_scroll_spy'),
    '#size' => 40,
    '#maxlength' => 40,
  ];

  // Fonts.
  $form['fonts'] = [
    '#type' => 'details',
    '#title' => t('Fonts & icons'),
    '#group' => 'bootstrap',
  ];
  $form['fonts']['fonts'] = [
    '#type' => 'details',
    '#title' => t('Fonts'),
    '#collapsible' => TRUE,
    '#collapsed' => FALSE,
  ];
  $form['fonts']['icons'] = [
    '#type' => 'details',
    '#title' => t('Icons'),
    '#collapsible' => TRUE,
    '#collapsed' => FALSE,
  ];

  // Colors.
  $form['colors'] = [
    '#type' => 'details',
    '#title' => t('Colors'),
    '#group' => 'bootstrap',
  ];
  // System messages.
  $form['colors']['alerts'] = [
    '#type' => 'details',
    '#title' => t('System messages'),
    '#collapsible' => TRUE,
    '#collapsed' => FALSE,
  ];
  $form['colors']['alerts']['az_barrio_system_messages'] = [
    '#type' => 'select',
    '#title' => t('System messages color scheme'),
    '#default_value' => theme_get_setting('az_barrio_system_messages'),
    '#empty_option' => t('Default'),
    '#options' => [
      'messages_light' => t('Light'),
      'messages_dark' => t('Dark'),
    ],
    '#description' => t('Replace the standard color scheme for system messages with a Google Material Design color scheme.'),
  ];
  $form['colors']['tables'] = [
    '#type' => 'details',
    '#title' => t('Tables'),
    '#collapsible' => TRUE,
    '#collapsed' => FALSE,
  ];
  $form['colors']['tables']['az_barrio_table_style'] = [
    '#type' => 'select',
    '#title' => t('Table cell style'),
    '#default_value' => theme_get_setting('az_barrio_table_style'),
    '#empty_option' => t('Default'),
    '#options' => [
      'table-striped' => t('Striped'),
      'table-bordered' => t('Bordered'),
    ],
  ];
  $form['colors']['tables']['az_barrio_table_hover'] = [
    '#type' => 'checkbox',
    '#title' => t('Hover effect over table cells'),
    '#default_value' => theme_get_setting('az_barrio_table_hover'),
  ];
  $form['colors']['tables']['az_barrio_table_head'] = [
    '#type' => 'select',
    '#title' => t('Table header color scheme'),
    '#default_value' => theme_get_setting('az_barrio_table_head'),
    '#empty_option' => t('Default'),
    '#options' => [
      'thead-light' => t('Light'),
      'thead-dark' => t('Dark'),
    ],
  ];

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

  // Land Acknowledgment.
  $form['az_settings']['settings']['land_acknowledgment'] = [
    '#type' => 'checkbox',
    '#title' => t('Land Acknowledgment'),
    '#description' => t('With few execeptions, this should always be enabled.'),
    '#default_value' => theme_get_setting('land_acknowledgment'),
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

  // Fonts and Icons.
  $form['fonts']['fonts']['az_barrio_font'] = [
    '#type' => 'checkbox',
    '#title' => t('Use the centrally-managed Typekit webfont, Proxima Nova'),
    '#default_value' => theme_get_setting('az_barrio_font'),
    '#description' => t(
        'If selected, a Typekit CDN <code>&lt;link&gt;</code> will be added to every page importing the @proxima_nova_docs_link CSS.', [
          '@proxima_nova_docs_link' => Link::fromTextAndUrl(
            'Arizona Digital, centrally-managed Proxima Nova font', Url::fromUri(
                'https://digital.arizona.edu/arizona-bootstrap/docs/2.0/content/font/',
                [
                  'attributes' => [
                    'target' => '_blank',
                  ],
                ]
            )
          )->toString(),
        ]
    ),
  ];
  $form['fonts']['icons']['az_barrio_icons']['az_barrio_material_design_sharp_icons'] = [
    '#type' => 'checkbox',
    '#title' => t('Use Material Design Sharp Icons'),
    '#description' => t(
        'If selected, a Google Fonts CDN <code>&lt;link&gt;</code> will be added to every page importing the @material_design_sharp_icons_docs_link CSS.', [
          '@material_design_sharp_icons_docs_link' => Link::fromTextAndUrl(
            'Material Design Sharp icons', Url::fromUri(
                'https://material.io/resources/icons/?style=sharp', [
                  'attributes' => [
                    'target' => '_blank',
                  ],
                ]
            )
          )->toString(),
        ]
    ),
    '#default_value' => theme_get_setting('az_barrio_material_design_sharp_icons'),
  ];
  $form['fonts']['icons']['az_barrio_icons']['az_barrio_az_icons'] = [
    '#type' => 'checkbox',
    '#title' => t('Use AZ Icons'),
    '#description' => t(
        'If selected, a Arizona Digital CDN <code>&lt;link&gt;</code> will be added to every page importing the @az_icons_link CSS.', [
          '@az_icons_link' => Link::fromTextAndUrl(
            'Arizona icons', Url::fromUri(
                'https://github.com/az-digital/az-icons', [
                  'attributes' => [
                    'target' => '_blank',
                  ],
                ]
            )
          )->toString(),
        ]
    ),
    '#default_value' => theme_get_setting('az_barrio_az_icons'),
  ];
  $form['fonts']['icons']['az_barrio_icons']['az_icons'] = [
    '#type' => 'fieldset',
    '#title' => t('AZ Icons Settings'),
    '#states' => [
      'visible' => [
        ':input[name="az_barrio_az_icons"]' => ['checked' => TRUE],
      ],
    ],
    '#default_value' => theme_get_setting('az_barrio_az_icons'),
  ];

  $form['fonts']['icons']['az_barrio_icons']['az_icons']['az_barrio_az_icons_source'] = [
    '#type' => 'radios',
    '#title' => t('Arizona Icons Source'),
    '#options' => [
      'cdn' => t(
        'Use external copy of @azicons hosted on the CDN.', [
          '@azicons' => Link::fromTextAndUrl(
            'AZ Icons', Url::fromUri(
                'https://github.com/az-digital/az-icons', [
                  'attributes' => [
                    'target' => '_blank',
                  ],
                ]
            )
          )->toString(),
        ],

      ),
      'local' => t('Use local copy of AZ Icons packaged with AZ Barrio (%stableversion).', ['%stableversion' => AZ_ICONS_STABLE_VERSION]),
    ],
    '#default_value' => theme_get_setting('az_barrio_az_icons_source'),
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
    '#prefix' => t(
        'AZ Barrio requires the <a href="@azbootstrap">AZ Bootstrap</a> front-end framework. AZ Bootstrap can either be loaded from the local copy packaged with AZ Barrio or from the AZ Bootstrap CDN.', [
          '@azbootstrap' => 'http://digital.arizona.edu/arizona-bootstrap',
        ]
    ),
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
      'main' => t('Latest dev version. This is the tip of the main branch of AZ Bootstrap. Please do not use on production unless you are following the AZ Bootstrap project closely. Please report bugs to the AZ Digital team.'),
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
  $form['azbs_settings']['settings']['az_bootstrap_style']['sticky_footer'] = [
    '#type' => 'checkbox',
    '#title' => t('Use the AZ Bootstrap sticky footer template.'),
    '#default_value' => theme_get_setting('sticky_footer'),
  ];
  // Responsive Header Grid.
  $form['layout']['header_grid'] = [
    '#type' => 'details',
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    '#title' => t('Responsive Header Grid'),
    '#description' => t('The header typically contains two columns on small screen sizes and larger with the "Site branding" region on the left and with "Header 1" and "Header 2" on the right.'),
  ];
  $form['layout']['header_grid']['header_one_col_classes'] = [
    '#type' => 'textfield',
    '#title' => t('Column one classes'),
    '#description' => t('Responsive column classes for the parent <code>div</code> of the Site branding region. Should contain a string with classes separated by a space.'),
    '#default_value' => theme_get_setting('header_one_col_classes'),
  ];
  $form['layout']['header_grid']['header_two_col_classes'] = [
    '#type' => 'textfield',
    '#title' => t('Column two classes'),
    '#description' => t('Responsive column classes for the parent <code>div</code> of the Header 1 and Header 2 regions. Should contain a string with classes separated by a space.'),
    '#default_value' => theme_get_setting('header_two_col_classes'),
  ];
  // Remove Navbar options.
  $form['affix']['navbar_top'] = [];
  $form['affix']['navbar'] = [];
  $form['components']['navbar'] = [];
  // Components.
  $form['components']['navbar_offcanvas'] = [
    '#type' => 'details',
    '#title' => t('Navbar with Off Canvas Drawer for mobile devices.'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
  ];
  $form['components']['navbar_offcanvas']['az_barrio_navbar_offcanvas'] = [
    '#type' => 'checkbox',
    '#title' => t('Use Navbar Off Canvas'),
    '#description' => t('Check to use the Arizona Bootstrap Off Canvas Navbar instead of the bootstrap navbar.'),
    '#default_value' => theme_get_setting('az_barrio_navbar_offcanvas'),
  ];
  // Logos.
  $form['logo']['az_barrio_logo_svg_inline'] = [
    '#type' => 'checkbox',
    '#title' => t('Inline logo SVG (Experimental)'),
    '#default_value' => theme_get_setting('az_barrio_logo_svg_inline') ? TRUE : FALSE,
    '#description' => t('If logo is SVG image then inline it content in the page instead of using image tag to render it. This is useful when you need to control SVG logo with theme CSS.'),
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
    '#title' => t('Footer Logo Image'),
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
  $form['footer_logo']['settings']['az_barrio_footer_logo_svg_inline'] = [
    '#type' => 'checkbox',
    '#title' => t('Inline footer logo SVG (Experimental)'),
    '#default_value' => theme_get_setting('az_barrio_footer_logo_svg_inline') ? TRUE : FALSE,
    '#description' => t('If logo is SVG image then inline it content in the page instead of using image tag to render it. This is useful when you need to control SVG logo with theme CSS.'),
  ];
  $form['footer_logo']['settings']['footer_logo_path'] = [
    '#type' => 'textfield',
    '#title' => t('Path to custom footer logo'),
    '#description' => t('The path to the file you would like to use as your footer logo file instead of the logo in the header.'),
    '#default_value' => theme_get_setting('footer_logo_path'),
  ];
  $form['footer_logo']['settings']['footer_logo_upload'] = [
    '#type' => 'file',
    '#title' => t('Upload footer logo image'),
    '#maxlength' => 40,
    '#description' => t("If you don't have direct file access to the server, use this field to upload your footer logo."),
    '#upload_validators' => [
      'file_validate_extensions' => [
        'png gif jpg jpeg apng svg',
      ],
    ],
  ];
  $form['footer_logo']['settings']['footer_logo_link_destination'] = [
    '#type' => 'url',
    '#title' => t('Footer logo external link destination'),
    '#description' => t('If blank, the footer logo links to the homepage; otherwise, enter an external site URL. Example: https://www.arizona.edu/'),
    '#default_value' => theme_get_setting('footer_logo_link_destination'),
  ];
  $form['footer_logo']['settings']['footer_logo_alt_text'] = [
    '#required' => TRUE,
    '#type' => 'textfield',
    '#title' => t('Footer logo alt text'),
    '#description' => t('Alternative text is used by screen readers, search engines, and when the image cannot be loaded. By adding alt text you improve accessibility and search engine optimization.'),
    '#default_value' => theme_get_setting('footer_logo_alt_text'),
    '#element_validate' => ['token_element_validate'],
  ];
  $form['footer_logo']['settings']['footer_logo_title_text'] = [
    '#required' => TRUE,
    '#type' => 'textfield',
    '#title' => t('Footer logo title text'),
    '#description' => t('Title text is used in the tool tip when a user hovers their mouse over the image. Adding title text makes it easier to understand the context of an image and improves usability.'),
    '#default_value' => theme_get_setting('footer_logo_title_text'),
    '#element_validate' => ['token_element_validate'],
  ];
  $form['footer_logo']['settings']['tokens'] = [
    '#theme' => 'token_tree_link',
    '#global_types' => TRUE,
    '#click_insert' => TRUE,
  ];
  $form['#validate'][] = 'az_barrio_form_system_theme_settings_validate';
  $form['#submit'][] = 'az_barrio_form_system_theme_settings_submit';
}

/**
 * Submit handler for az_barrio_form_settings.
 */
function az_barrio_form_system_theme_settings_submit($form, FormStateInterface &$form_state) {
  $config_key = $form_state->getValue('config_key');
  $config = \Drupal::getContainer()->get('config.factory')->getEditable($config_key);
  $values = $form_state->getValues();
  // If the user uploaded a new logo or favicon, save it to a permanent location
  // and use it in place of the default theme-provided file.
  $default_scheme = \Drupal::config('system.file')->get('default_scheme');
  try {
    if (!empty($values['footer_logo_upload'])) {
      //phpcs:ignore Security.BadFunctions.FilesystemFunctions.WarnFilesystem
      $filename = \Drupal::service('file_system')->copy($values['footer_logo_upload']->getFileUri(), $default_scheme . '://');
      $form_state->setValue('footer_logo_path', $filename);
      $form_state->setValue('footer_default_logo', 0);
    }
  }
  catch (FileException $e) {
    // Ignore.
  }
  $form_state->unsetValue('footer_logo_upload');
  // theme_settings_convert_to_config($values, $config)->save();
  // Clear cached libraries so any Bootsrap changes take effect immmediately.
  \Drupal::service('library.discovery')->clearCachedDefinitions();
}

/**
 * Form validator for az_barrio_form_settings.
 */
function az_barrio_form_system_theme_settings_validate($form, FormStateInterface &$form_state) {
  if (isset($form['footer_logo'])) {
    $file = _file_save_upload_from_form($form['footer_logo']['settings']['footer_logo_upload'], $form_state, 0);
    if ($file) {
      // Put the temporary file in form_values so we can save it on submit.
      $form_state->setValue('footer_logo_upload', $file);
    }
  }
  // If the user provided a path for a footer logo, make sure a file exists at
  // that path.
  if ($form_state->getValue('footer_logo_path')) {
    // @todo Use the validatePath function from ThemeSettingsForm Class here?
    $path = az_barrio_validate_file_path($form_state->getValue('footer_logo_path'));
    if (!$path) {
      $form_state->setErrorByName('footer_logo_path', t('The custom footer logo path is invalid.'));
    }
  }
}

/**
 * Helper function to determin if is a file.
 *
 * See: https://api.drupal.org/api/drupal/core%21modules%21system%21src%21Form%21ThemeSettingsForm.php/function/ThemeSettingsForm%3A%3AvalidatePath/8.2.x.
 */
function az_barrio_validate_file_path($path) {

  // Absolute local file paths are invalid.
  //phpcs:ignore Security.BadFunctions.FilesystemFunctions.WarnFilesystem
  if (\Drupal::service('file_system')->realpath($path) === $path) {
    return FALSE;
  }

  // A path relative to the Drupal root or a fully qualified URI is valid.
  //phpcs:ignore Security.BadFunctions.FilesystemFunctions.WarnFilesystem
  if (is_file($path)) {
    return $path;
  }

  // Prepend 'public://' for relative file paths within public filesystem.
  if (StreamWrapperManager::getScheme($path) === FALSE) {
    $path = 'public://' . $path;
  }
  //phpcs:ignore Security.BadFunctions.FilesystemFunctions.WarnFilesystem
  if (is_file($path)) {
    return $path;
  }
  return FALSE;
}
