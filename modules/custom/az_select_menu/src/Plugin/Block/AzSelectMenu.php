<?php

namespace Drupal\az_select_menu\Plugin\Block;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Template\Attribute;
use Drupal\menu_block\Plugin\Block\MenuBlock;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an extended Menu block.
 *
 * @Block(
 *   id = "az_select_menu",
 *   admin_label = @Translation("Quickstart select menu block"),
 *   category = @Translation("Quickstart select menu block"),
 *   deriver = "Drupal\menu_block\Plugin\Derivative\MenuBlock",
 *   forms = {
 *     "settings_tray" = "\Drupal\system\Form\SystemMenuOffCanvasForm",
 *   },
 * )
 */
class AzSelectMenu extends MenuBlock {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->menuParentFormSelector = $container->get('menu.parent_form_selector');
    $instance->entityTypeManager = $container->get('entity_type.manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $config = $this->configuration;
    $defaults = $this->defaultConfiguration();

    $form = parent::blockForm($form, $form_state);
    $form['az_select_menu'] = [
      '#type' => 'details',
      '#title' => $this->t('Quickstart select menu options.'),
    ];

    $form['az_select_menu']['empty_option'] = [
      '#type' => 'checkbox',
      '#title' => t('Include an empty option.'),
      '#default_value' => $config['az_select_menu']['empty_option'],
    ];

    $form['az_select_menu']['empty_option_label'] = [
      '#type' => 'textfield',
      '#title' => t('Empty option label.'),
      '#default_value' => $config['az_select_menu']['empty_option_label'],
      '#states' => [
        'visible' => [
          ':input[name="az_select_menu[empty_option]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['az_select_menu']['preform_text'] = [
      '#type' => 'textfield',
      '#title' => t('Text to display inline before the select form.'),
      '#default_value' => $config['az_select_menu']['preform_text'],
      '#description' => t('You may use hyphens, underscores, and alphanumeric characters.'),
    ];

    $form['az_select_menu']['preform_text_sr_only'] = [
      '#type' => 'textfield',
      '#title' => t('Form help for screen-readers.'),
      '#default_value' => $config['az_select_menu']['preform_text_sr_only'],
      '#description' => t('Depending on the preform text, screen readers don\'t necessarily show their users this form in a helpful context.'),
    ];

    $form['az_select_menu']['button_text'] = [
      '#type' => 'textfield',
      '#title' => t('Text you would like to appear in the button.'),
      '#default_value' => $config['az_select_menu']['button_text'],
    ];

    $form['az_select_menu']['button_text_sr_only'] = [
      '#type' => 'textfield',
      '#title' => t('Text to help screen-reader users understand what to do.'),
      '#default_value' => $config['az_select_menu']['button_text_sr_only'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $config = parent::defaultConfiguration();
    $config['az_select_menu'] = [
      'empty_option' => TRUE,
      'empty_option_label' => 'choose an option',
      'preform_text' => 'I am a',
      'preform_text_sr_only' => 'Select your audience',
      'button_text' => 'Go',
      'button_text_sr_only' => ' to the page for that group',
    ];

    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);

    $this->configuration['az_select_menu'] = $form_state->getValue('az_select_menu');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = parent::build();
    // dpm($build);
    $form_attributes = new Attribute([
      'id' => 'az-select-menu-form-' . $build['#menu_name'],
      'class' => [
        'form-inline',
      ],
      'data-toggle' => 'popover',
      'data-trigger' => 'focus',
      'data-placement' => 'top',
      'data-content' => t('Please make a selection.'),
    ]);

    $build['#form_attributes'] = $form_attributes;

    $select_attributes = new Attribute([
      'id' => 'az-select-menu-select-' . $build['#menu_name'] . '',
      'class' => [
        'form-control',
        'select-primary',
      ],
      'aria-invalid' => "false",
    ]);

    $build['#select_attributes'] = $select_attributes;

    $button_attributes = new Attribute([
      'id' => 'socks',
      'class' => [
        'form-control',
        'select-primary',
      ],
      'aria-invalid' => "false",
    ]);

    $build['#button_attributes'] = $button_attributes;

    $build['#attached']['library'][] = 'az_select_menu/az_select_menu';
    $build['#attached']['drupalSettings']['azSelectMenu']['ids'][] = 'az_select_menu_' . $build['#menu_name'];

    return $build;
  }

  // /**
  //  * {@inheritdoc}
  //  */
  // public function preprocess(&$variables) {
  //   dpm($variables);
  // // Libraries to attach to this paragraph.
  // $libraries = [];
  // /** @var \Drupal\paragraphs\Entity\Paragraph $paragraph */
  // $paragraph = $variables['paragraph'];
  // // Get plugin configuration.
  // $config = $this->getSettings($paragraph);
  // // Get the paragraph bundle name and compute name of potential library.
  // $bundle = $paragraph->bundle();
  // $libraries[] = 'az_paragraphs.' . $bundle;
  // // Generate library names to check based on view mode.
  // if (!empty($variables['view_mode'])) {
  //   // Potential library for paragraph view mode.
  //   $libraries[] = 'az_paragraphs.' . $variables['view_mode'];
  //   // Bundle-specific view mode libraries.
  //   $libraries[] = 'az_paragraphs.' . $bundle . '_' . $variables['view_mode'];
  // }
  // // Check if any of the potential library names actually exist.
  // foreach ($libraries as $name) {
  //   // Check if library discovery service knows about the library.
  //   $library = $this->libraryDiscovery->getLibraryByName('az_paragraphs', $name);
  // if ($library) {
  //     // If we found a library, attach it to the paragraph.
  //     $variables['#attached']['library'][] = 'az_paragraphs/' . $name;
  //   }
  // }
  // }.
}
