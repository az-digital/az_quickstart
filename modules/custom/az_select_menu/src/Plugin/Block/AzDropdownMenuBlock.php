<?php

namespace Drupal\az_select_menu\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Core\Render\Markup;
use Drupal\Core\Session\AccountInterface;
use Drupal\menu_block\Plugin\Block\MenuBlock;
use Drupal\system\Entity\Menu;
use Drupal\system\Plugin\Block\SystemMenuBlock;
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
class AzDropdownMenuBlock extends MenuBlock {

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

    return $build;
  }



}

