<?php

/**
 * @file
 * theme-settings.php
 *
 * Provides theme settings for Arizona Barrio.
 */

require_once \Drupal::service('extension.list.theme')->getPath('az_barrio') . '/includes/common.inc';

use Drupal\Core\File\Exception\FileException;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\StreamWrapper\StreamWrapperManager;
use Drupal\Core\Url;

/**
 * Implements hook_form_system_theme_settings_alter() for settings form.
 *
 * Replace Barrio setting options with subtheme ones.
 *
 * Example on how to alter theme settings form
 */
function az_barrio_form_system_theme_settings_alter(&$form, FormStateInterface $form_state) {
  // Disable bootstrap_barrio_source and bootstrap_barrio_library settings.
  $form['bootstrap_barrio_source']['#access'] = FALSE;
  $form['bootstrap_barrio_library']['#access'] = FALSE;

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
  unset($form['fonts']['fonts']['bootstrap_barrio_google_fonts']);
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
  $form['fonts']['bootstrap_icons'] = [
    '#type' => 'details',
    '#title' => t('Bootstrap icons'),
    '#collapsible' => TRUE,
    '#collapsed' => FALSE,
  ];
  unset($form['fonts']['icons']['bootstrap_barrio_icons']);
  unset($form['fonts']['bootstrap_icons']);
  $form['fonts']['icons'] = [
    '#type' => 'details',
    '#title' => t('Icons'),
    '#collapsible' => TRUE,
    '#collapsed' => FALSE,
  ];
  $form['fonts']['icons']['az_barrio_icons']['az_barrio_material_symbols_rounded'] = [
    '#type' => 'checkbox',
    '#title' => t('Use Material Symbols Rounded Icons'),
    '#description' => t(
        'If selected, a Google Fonts CDN <code>&lt;link&gt;</code> will be added to every page importing the @material_symbols_rounded_docs_link CSS.', [
          '@material_symbols_rounded_docs_link' => Link::fromTextAndUrl(
            'Material Symbols Rounded icons', Url::fromUri(
                'https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,1,0', [
                  'attributes' => [
                    'target' => '_blank',
                  ],
                ]
            )
          )->toString(),
        ]
    ),
    '#default_value' => theme_get_setting('az_barrio_material_symbols_rounded'),
  ];
  $form['fonts']['icons']['az_barrio_icons']['az_barrio_material_design_sharp_icons'] = [
    '#type' => 'checkbox',
    '#title' => t('(Deprecated) Use Material Design Sharp Icons'),
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
  $form['fonts']['icons']['az_barrio_icons']['az_icons']['az_icons_cdn'] = [
    '#type' => 'fieldset',
    '#title' => t('Arizona Icons CDN Settings'),
    '#states' => [
      'visible' => [
        ':input[name="az_barrio_az_icons_source"]' => ['value' => 'cdn'],
      ],
    ],
  ];
  $form['fonts']['icons']['az_barrio_icons']['az_icons']['az_icons_cdn']['az_icons_cdn_version'] = [
    '#type' => 'radios',
    '#title' => t('Arizona Icons CDN version'),
    '#options' => [
      'stable' => t('Stable version: This option has undergone the most testing within the az_barrio theme. Currently: %stableversion (Recommended).', ['%stableversion' => AZ_ICONS_STABLE_VERSION]),
      'latest' => t('Latest tagged version. The most recently tagged stable release of Arizona Icons. While this has not been explicitly tested on this version of az_barrio, it’s probably OK to use on production sites. Please report bugs to the AZ Digital team.'),
      'main' => t('Latest dev version. This is the tip of the main branch of Arizona Icons. Please do not use on production unless you are following the Arizona Icons project closely. Please report bugs to the AZ Digital team.'),
    ],
    '#default_value' => theme_get_setting('az_icons_cdn_version'),
  ];
  $form['fonts']['icons']['az_barrio_icons']['az_icons']['az_icons_minified'] = [
    '#type'          => 'checkbox',
    '#title'         => t('Use minified version of AZ Icons.'),
    '#default_value' => theme_get_setting('az_icons_minified'),
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
    '#title' => t('AZ Bootstrap CDN version (DEPRECATED)'),
    '#description' => t('This setting is deprecated in favor of allowing choosing your CSS and JS versions separately.  <a href="@bootstrapdeprecated">This setting will be removed in a future version of AZ Barrio</a>.', [
      '@bootstrapdeprecated' => 'https://github.com/az-digital/az_quickstart/issues/1251',
    ]),
    '#options' => [
      'stable' => t('Stable version: This option has undergone the most testing within the az_barrio theme. Currently: %stableversion (Recommended).', ['%stableversion' => AZ_BOOTSTRAP_STABLE_VERSION]),
      'latest-2.x' => t('Latest tagged version. The most recently tagged stable release of AZ Bootstrap. While this has not been explicitly tested on this version of az_barrio, it’s probably OK to use on production sites. Please report bugs to the AZ Digital team.'),
      '2.x' => t('Latest dev version. This is the tip of the 2.x branch of AZ Bootstrap. Please do not use on production unless you are following the AZ Bootstrap project closely. Please report bugs to the AZ Digital team.'),
    ],
    '#default_value' => theme_get_setting('az_bootstrap_cdn_version'),
  ];
  $form['azbs_settings']['settings']['az_bootstrap_cdn']['az_bootstrap_cdn_version_css'] = [
    '#type' => 'radios',
    '#title' => t('AZ Bootstrap CSS CDN version'),
    '#options' => [
      'stable' => t('Stable version: This option has undergone the most testing within the az_barrio theme. Currently: %stableversion (Recommended).', ['%stableversion' => AZ_BOOTSTRAP_STABLE_VERSION]),
      'latest-5.x' => t('Latest tagged version of 5.x. The most recently tagged stable release of AZ Bootstrap. While this has not been explicitly tested on this version of az_barrio, it’s probably OK to use on production sites. Please report bugs to the AZ Digital team.'),
      '5.x' => t('Latest dev version of <code>main</code>. This is the tip of the main branch of AZ Bootstrap. Please do not use on production unless you are following the AZ Bootstrap project closely. Please report bugs to the AZ Digital team.'),
    ],
    '#default_value' => theme_get_setting('az_bootstrap_cdn_version_css'),
  ];
  $form['azbs_settings']['settings']['az_bootstrap_cdn']['az_bootstrap_cdn_version_js'] = [
    '#type' => 'radios',
    '#title' => t('AZ Bootstrap JS CDN version'),
    '#options' => [
      'stable' => t('Stable version: This option has undergone the most testing within the az_barrio theme. Currently: %stableversion (Recommended).', ['%stableversion' => AZ_BOOTSTRAP_STABLE_VERSION]),
      'latest-5.x' => t('Latest tagged version of 5.x. The most recently tagged stable release of AZ Bootstrap. While this has not been explicitly tested on this version of az_barrio, it’s probably OK to use on production sites. Please report bugs to the AZ Digital team.'),
      '5.x' => t('Latest dev version of <code>main</code>. This is the tip of the main branch of AZ Bootstrap. Please do not use on production unless you are following the AZ Bootstrap project closely. Please report bugs to the AZ Digital team.'),
    ],
    '#default_value' => theme_get_setting('az_bootstrap_cdn_version_js'),
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
  // Add new AZ Barrio sidebar position option and help text.
  $form['layout']['sidebar_position']['bootstrap_barrio_sidebar_position']['#options']['az-barrio-both-below'] = t('Both sides below on mobile');
  $form['layout']['sidebar_position']['bootstrap_barrio_sidebar_position']['#description'] = t('Below the Bootstrap md breakpoint, the "Both sides" position places the Sidebar First region <strong>above</strong> the page content while the "Both sides below on mobile" position places both sidebar regions <strong>below</strong> the page content.');
  // Remove sidebar menu on mobile setting.
  $form['layout']['sidebar_position']['az_remove_sidebar_menu_mobile'] = [
    '#type' => 'checkbox',
    '#title' => t('Remove sidebar menu on mobile devices'),
    '#description' => t('If checked, the sidebar menu will not be displayed on mobile devices.'),
    '#default_value' => theme_get_setting('az_remove_sidebar_menu_mobile'),
  ];
  // Remove Navbar options.
  $form['affix']['navbar_top'] = [];
  $form['affix']['navbar'] = [];
  $form['components']['navbar'] = [];
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
    '#description' => t("If you don't have direct file access to the server, use this field to upload your footer logo."),
    '#upload_validators' => [
      'FileExtension' => [
        'extensions' => 'png gif jpg jpeg apng svg webp',
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
  // Clear cached libraries so any Bootstrap changes take effect immediately.
  \Drupal::service('library.discovery')->clear();
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
 * Helper function to determine if is a file.
 *
 * See: https://api.drupal.org/api/drupal/core%21modules%21system%21src%21Form%21ThemeSettingsForm.php/function/ThemeSettingsForm%3A%3AvalidatePath/8.2.x.
 */
function az_barrio_validate_file_path($path) {

  // Absolute local file paths are invalid.
  if (\Drupal::service('file_system')->realpath($path) === $path) {
    return FALSE;
  }

  // A path relative to the Drupal root or a fully qualified URI is valid.
  if (is_file($path)) {
    return $path;
  }

  // Prepend 'public://' for relative file paths within public filesystem.
  if (StreamWrapperManager::getScheme($path) === FALSE) {
    $path = 'public://' . $path;
  }
  if (is_file($path)) {
    return $path;
  }
  return FALSE;
}
