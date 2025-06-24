<?php

declare(strict_types=1);

namespace Drupal\az_finder\Plugin\views\exposed_form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\better_exposed_filters\Plugin\views\exposed_form\BetterExposedFilters;
use Drupal\views\Attribute\ViewsExposedForm;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Exposed form plugin that provides a basic exposed form.
 *
 * @ingroup views_exposed_form_plugins
 */
#[ViewsExposedForm(
  id: 'az_better_exposed_filters',
  title: new TranslatableMarkup('Quickstart Exposed Filters'),
  help: new TranslatableMarkup('Better exposed filters with additional Quickstart Settings.')
)]
class QuickstartExposedFilters extends BetterExposedFilters {

  /**
   * Drupal\Core\Config\ConfigFactoryInterface definition.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->configFactory = $container->get('config.factory');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function exposedFormAlter(&$form, FormStateInterface $form_state): void {
    parent::exposedFormAlter($form, $form_state);
    $options = $this->options;
    if ($options['skip_link']) {
      $skip_link_id = $options['skip_link_id'] ?? 'search-filters';
      $form['#prefix'] = '<div id="' . $skip_link_id . '">';
      $form['#suffix'] = '</div>';
    }
    if ($options['orientation'] === 'vertical') {
      $form['#attributes']['class'][] = 'az-bef-vertical';
    }
    if ($options['orientation'] === 'horizontal') {
      $form['#attributes']['class'][] = 'az-bef-horizontal';
    }
    // Mark form as QuickstartExposedFilters form for easier alterations.
    $form['#context']['az_better_exposed_filters'] = TRUE;
    $form['#attributes']['data-az-better-exposed-filters'] = TRUE;
    if ($options['reset_button'] === TRUE && isset($form['actions']) && isset($form['actions']['reset'])) {
      $form['#attached']['library'][] = 'az_finder/active-filter-reset';
      // Clone the reset button.
      $reset_button = $form['actions']['reset'];
      if ($options['reset_button_position'] === 'top') {
        $reset_button['#weight'] = -1000;
      }
      $existing_classes = $reset_button['#attributes']['class'] ?? [];
      $reset_button['#attributes']['class'] = array_merge($existing_classes, [
        'btn',
        'btn-sm',
        'btn-primary',
        'btn-block',
        'js-active-filters-reset',
        'mx-1',
        'mb-3',
      ]);
      // Add the reset button visibility setting to the drupalSettings array.
      if ($this->options['bef']['general']['reset_button_always_show'] === TRUE) {
        $form['#attached']['drupalSettings']['azFinder']['alwaysDisplayResetButton'] = TRUE;
      }
      else {
        $reset_button['#attributes']['class'][] = 'd-none';
        $form['#attached']['drupalSettings']['azFinder']['alwaysDisplayResetButton'] = FALSE;
        unset($reset_button['#access']);
      }
      // Add the reset button counter setting to the drupalSettings array.
      if ($this->options['reset_button_counter'] === TRUE) {
        $form['#attached']['library'][] = 'az_finder/active-filter-count';

        $count = [
          '#type' => 'html_tag',
          '#tag' => 'span',
          '#attributes' => [
            'class' => [
              'js-active-filter-count',
              'ml-2',
            ],
            'aria-live' => 'polite',
            'role' => 'status',
          ],
        ];

        $reset_button['count'] = $count;
        $reset_button['#type'] = 'html_tag';
        $reset_button['#tag'] = 'button';
        $reset_button['#attributes']['type'] = 'button';
        $reset_button['#attached']['library'][] = 'az_finder/active-filter-reset';
        unset($reset_button['#pre_render']);
      }
      // Add the cloned reset button at the beginning of the form.
      $form['top_reset'] = $reset_button;
      // Hide the original reset button.
      $form['actions']['reset']['#access'] = FALSE;
    }

    // Add view display attribute to the container for GTM event functionality.
    $view_display = str_replace(
      ['views_exposed_form__', '__'],
      ['', ':'],
      $form['#theme'],
      )[0];
    $form['#attributes']['data-az-view-display'] = str_replace(':', '-', $view_display);

    // Attach JS library to send GTM events if they are enabled for this view.
    $gtm_enabled_views = $this->configFactory->get('az_finder.settings')->get('gtm_enabled_views');
    if (!empty($gtm_enabled_views) && $gtm_enabled_views[$view_display]) {
      $form['#attached']['library'][] = 'az_finder/gtm-events';
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions(): array {
    $options = parent::defineOptions();
    $options['reset_button_position'] = ['default' => 'bottom'];
    $options['reset_button_counter'] = ['default' => FALSE];
    $options['orientation'] = ['default' => 'horizontal'];
    $options['skip_link'] = ['default' => FALSE];
    $options['skip_link_text'] = ['default' => $this->t('Skip to search and filter')];
    $options['skip_link_id'] = ['default' => 'search-filter'];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state): void {
    parent::buildOptionsForm($form, $form_state);
    $reset_button_option = $form['bef']['general']['reset_button'];
    unset($form['bef']['general']['reset_button']);
    $form['bef']['general']['reset_button'] = $reset_button_option;
    $form['bef']['general']['reset_button_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Reset Button Settings'),
      '#open' => TRUE,
      '#states' => [
        'visible' => [
          ':input[name="exposed_form_options[reset_button]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    if (isset($form['bef']['general']['reset_button_always_show'])) {
      $form['bef']['general']['reset_button_settings']['reset_button_always_show'] = $form['bef']['general']['reset_button_always_show'];
      unset($form['bef']['general']['reset_button_always_show']);
    }
    if (isset($form['bef']['general']['reset_button_label'])) {
      $form['bef']['general']['reset_button_settings']['reset_button_label'] = $form['bef']['general']['reset_button_label'];
      unset($form['bef']['general']['reset_button_label']);
    }
    $form['bef']['general']['reset_button_settings']['reset_button_position'] = [
      '#type' => 'select',
      '#title' => $this->t('Reset Button Position'),
      '#options' => [
        'top' => $this->t('Top'),
        'bottom' => $this->t('Bottom'),
      ],
      '#default_value' => $this->options['reset_button_position'] ?? 'top',
      '#description' => $this->t('Select where to place the reset button in the form.'),
    ];
    $form['bef']['general']['reset_button_settings']['reset_button_counter'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show active filter counter'),
      '#description' => $this->t('Show a counter of active filters within the reset button next to the text.'),
      '#default_value' => $this->options['reset_button_counter'] ?? FALSE,
    ];
    $form['bef']['general']['skip_link'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Add skip link to the top of the view results'),
      '#description' => $this->t('Add a skip link to the top of the view results to allow keyboard users to skip to the search and filter form.'),
      '#default_value' => $this->options['skip_link'] ?? TRUE,
    ];

    $form['bef']['general']['skip_link_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Skip Link Settings'),
      '#open' => TRUE,
      '#states' => [
        'visible' => [
          ':input[name="exposed_form_options[bef][general][skip_link]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['bef']['general']['skip_link_settings']['skip_link_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Text'),
      '#description' => $this->t('The text to display for the skip link.'),
      '#default_value' => $this->options['skip_link_text'] ?? $this->t('Skip to search and filter'),
    ];
    $form['bef']['general']['skip_link_settings']['skip_link_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('ID/Anchor'),
      '#default_value' => $this->options['skip_link_id'] ?? 'search-filter',
      '#description' => $this->t('The ID or anchor to link to within the view.'),
    ];
    $form['bef']['general']['orientation'] = [
      '#type' => 'radios',
      '#title' => $this->t('Orientation'),
      '#description' => $this->t('The orientation of the filters within the exposed form.'),
      '#options' => [
        'horizontal' => $this->t('Horizontal'),
        'vertical' => $this->t('Vertical'),
      ],
      '#default_value' => $this->options['orientation'] ?? TRUE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function submitOptionsForm(&$form, FormStateInterface $form_state): void {
    // Extract the entire 'bef' section from the form state.
    $bef_settings = $form_state->getValue(['exposed_form_options', 'bef']);
    // Check and ensure the 'general' section exists within 'bef'.
    if (isset($bef_settings['general'])) {
      // Directly handle the 'reset_button_settings' within 'general'.
      // This involves manually saving the new settings introduced or
      // modified in buildOptionsForm.
      $general_settings = $bef_settings['general'];
      if (isset($general_settings['reset_button_settings'])) {
        $reset_button_settings = $general_settings['reset_button_settings'];
        $this->options['bef']['general']['reset_button_always_show'] = $reset_button_settings['reset_button_always_show'] ?? FALSE;
        $this->options['reset_button_position'] = $reset_button_settings['reset_button_position'] ?? 'bottom';
        $this->options['reset_button_counter'] = $reset_button_settings['reset_button_counter'] ?? FALSE;
        $this->options['orientation'] = $general_settings['orientation'] ?? 'vertical';
        $this->options['skip_link'] = $general_settings['skip_link'] ?? FALSE;
        $this->options['skip_link_text'] = $general_settings['skip_link_settings']['skip_link_text'] ?? $this->t('Skip to search and filter');
        $this->options['skip_link_id'] = $general_settings['skip_link_settings']['skip_link_id'] ?? 'search-filter';
        unset($general_settings['orientation']);
        unset($general_settings['skip_link']);
        unset($general_settings['skip_link_settings']);
        unset($general_settings['reset_button_settings']);
        // Reassign 'general' back to 'bef' to reflect our changes.
        $bef_settings['general'] = $general_settings;
        // Update 'bef' in the form_state to reflect our changes.
        $form_state->setValue(['exposed_form_options', 'bef'], $bef_settings);
      }
    }
    parent::submitOptionsForm($form, $form_state);
  }

}
