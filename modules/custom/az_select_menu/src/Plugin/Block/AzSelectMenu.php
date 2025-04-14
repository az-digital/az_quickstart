<?php

namespace Drupal\az_select_menu\Plugin\Block;

use Drupal\Component\Utility\Html;
use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Template\Attribute;
use Drupal\menu_block\Plugin\Block\MenuBlock;
use Drupal\menu_block\Plugin\Derivative\MenuBlock as MenuBlockDeriver;
use Drupal\system\Form\SystemMenuOffCanvasForm;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an extended Menu block.
 */
#[Block(
  id: 'az_select_menu',
  admin_label: new TranslatableMarkup('Quickstart select menu block'),
  category: new TranslatableMarkup('Quickstart select menu block'),
  deriver: MenuBlockDeriver::class,
  forms: [
    'settings_tray' => SystemMenuOffCanvasForm::class,
  ],
)]
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
    $form['menu_levels']['depth']['#access'] = FALSE;
    $form['menu_levels']['expand_all_items']['#access'] = FALSE;

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
          ':input[name="settings[az_select_menu][empty_option]"]' => ['checked' => TRUE],
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
      '#description' => t('Depending on the preform text, screen readers do not necessarily show their users this form in a helpful context.'),
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
    $menu_name = Html::getUniqueId('az-' . $build['#menu_name']);

    $form_attributes = new Attribute([
      'id' => $menu_name . '-form',
      'data-bs-toggle' => 'popover',
      'data-trigger' => 'focus',
      'data-placement' => 'top',
      'data-content' => t('Please make a selection.'),
    ]);

    $build['#form_attributes'] = $form_attributes;

    $select_attributes = new Attribute([
      'id' => $menu_name . '-select',
      'class' => [
        'form-control',
        'select-primary',
      ],
      'aria-invalid' => "false",
    ]);

    $build['#select_attributes'] = $select_attributes;

    $button_attributes = new Attribute([
      'id' => $menu_name . '-button',
      'class' => [
        'btn',
        'btn-primary',
        'js_select_menu_button',
        'disabled',
      ],
      'aria-disabled' => 'true',
      'role' => 'button',
      'type' => 'button',
      'tabindex' => '0',
    ]);

    $build['#button_attributes'] = $button_attributes;

    $build['#attached']['library'][] = 'az_select_menu/az_select_menu';
    $build['#attached']['drupalSettings']['azSelectMenu']['ids'][$menu_name] = $menu_name . '-form';

    return $build;
  }

}
