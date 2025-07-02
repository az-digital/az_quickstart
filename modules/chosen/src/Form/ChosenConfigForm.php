<?php

namespace Drupal\chosen\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Messenger\MessengerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Extension\ThemeHandler;

/**
 * Implements a ChosenConfig form.
 */
class ChosenConfigForm extends ConfigFormBase {

  /**
   * The Messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Theme handler.
   *
   * @var \Drupal\Core\Extension\ThemeHandler
   *   Theme handler.
   */
  protected $themeHandler;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->themeHandler = $container->get('theme_handler');
    $instance->messenger = $container->get('messenger');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'chosen_config_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['chosen.settings'];
  }

  /**
   * Chosen configuration form.
   *
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $chosen_path = _chosen_lib_get_chosen_path();
    if (!$chosen_path) {
      $url = Url::fromUri(CHOSEN_WEBSITE_URL);
      $link = Link::fromTextAndUrl($this->t('Chosen JavaScript file'), $url)->toString();

      $this->messenger->addError($this->t('The library could not be detected. You need to download the @chosen and extract the entire contents of the archive into the %path directory on your server.',
        ['@chosen' => $link, '%path' => 'libraries']
      ));
      return $form;
    }
    $form = parent::buildForm($form, $form_state);

    // Chosen settings:
    $chosen_conf = $this->configFactory->get('chosen.settings');

    $form['minimum_single'] = [
      '#type' => 'select',
      '#title' => $this->t('Minimum number of options for single select'),
      '#options' => array_merge(['0' => $this->t('Always apply')], range(1, 25)),
      '#default_value' => $chosen_conf->get('minimum_single'),
      '#description' => $this->t('The minimum number of options to apply Chosen for single select fields. Example : choosing 10 will only apply Chosen if the number of options is greater or equal to 10.'),
    ];

    $form['minimum_multiple'] = [
      '#type' => 'select',
      '#title' => $this->t('Minimum number of options for multi select'),
      '#options' => array_merge(['0' => $this->t('Always apply')], range(1, 25)),
      '#default_value' => $chosen_conf->get('minimum_multiple'),
      '#description' => $this->t('The minimum number of options to apply Chosen for multi select fields. Example : choosing 10 will only apply Chosen if the number of options is greater or equal to 10.'),
    ];

    $form['disable_search_threshold'] = [
      '#type' => 'select',
      '#title' => $this->t('Minimum number to show Search on Single Select'),
      '#options' => array_merge(['0' => $this->t('Always apply')], range(1, 25)),
      '#default_value' => $chosen_conf->get('disable_search_threshold'),
      '#description' => $this->t('The minimum number of options to apply Chosen search box. Example : choosing 10 will only apply Chosen search if the number of options is greater or equal to 10.'),
    ];

    $form['max_shown_results'] = [
      '#type' => 'number',
      '#title' => $this->t('Maximum shown results'),
      '#default_value' => $chosen_conf->get('max_shown_results'),
      '#description' => $this->t('Only show the first (n) matching options in the results. This can be used to increase performance for selects with very many options. Leave blank to show all results.'),
    ];

    $form['minimum_width'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Minimum width of widget'),
      '#field_suffix' => 'px',
      '#size' => 3,
      '#default_value' => $chosen_conf->get('minimum_width'),
      '#description' => $this->t('The minimum width of the Chosen widget. Leave blank to have chosen determine this.'),
    ];

    $form['use_relative_width'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use relative width'),
      '#default_value' => $chosen_conf->get('use_relative_width'),
      '#description' => $this->t('The relative width (% instead of px) of the Chosen widget.'),
    ];

    $form['jquery_selector'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Apply Chosen to the following elements'),
      '#description' => $this->t('A comma-separated list of jQuery selectors to apply Chosen to, such as <code>select#edit-operation, select#edit-type</code> or <code>.chosen-select</code>. Defaults to <code>select</code> to apply Chosen to all <code>&lt;select&gt;</code> elements.'),
      '#default_value' => $chosen_conf->get('jquery_selector'),
    ];

    $form['options'] = [
      '#type' => 'details',
      '#title' => $this->t('Chosen general options'),
      '#open' => TRUE,
    ];

    $form['options']['search_contains'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Search also in the middle of words'),
      '#default_value' => $chosen_conf->get('search_contains'),
      '#description' => $this->t('Per default chosen searches only at beginning of words. Enable this option will also find results in the middle of words.
      Example: Search for <em>land</em> will also find <code>Switzer<strong>land</strong></code>.'),
    ];

    $form['options']['disable_search'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Disable search box'),
      '#default_value' => $chosen_conf->get('disable_search'),
      '#description' => $this->t('Enable or disable the search box in the results list to filter out possible options.'),
    ];

    $form['options']['allow_single_deselect'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow Deselect on Single Selects'),
      '#default_value' => $chosen_conf->get('allow_single_deselect'),
      '#description' => $this->t('Enable or disable the deselect on single selects. Requires an empty default option.'),
    ];

    $form['options']['add_helper_buttons'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Add "none" and "all" buttons.'),
      '#default_value' => $chosen_conf->get('add_helper_buttons'),
      '#description' => $this->t('Add "none" and "all" buttons next to the chosen widget.'),
    ];

    $form['theme_options'] = [
      '#type' => 'details',
      '#title' => $this->t('Chosen per theme options'),
      '#open' => TRUE,
    ];

    $default_disabled_themes = $chosen_conf->get('disabled_themes');
    $default_disabled_themes = is_array($default_disabled_themes) ? $default_disabled_themes : [];
    $form['theme_options']['disabled_themes'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Disable the default Chosen theme for the following themes'),
      '#options' => $this->chosen_enabled_themes_options(),
      '#default_value' => $default_disabled_themes,
      '#description' => $this->t('Enable or disable the default Chosen CSS file. Select a theme if it contains custom styles for Chosen replacements.'),
    ];

    $form['options']['chosen_include'] = [
      '#type' => 'radios',
      '#title' => $this->t('Use chosen for admin pages and/or front end pages'),
      '#options' => [
        CHOSEN_INCLUDE_EVERYWHERE => $this->t('Include Chosen on every page'),
        CHOSEN_INCLUDE_ADMIN => $this->t('Include Chosen only on admin pages'),
        CHOSEN_INCLUDE_NO_ADMIN => $this->t('Include Chosen only on front end pages'),
      ],
      '#default_value' => $chosen_conf->get('chosen_include'),
    ];

    $form['strings'] = [
      '#type' => 'details',
      '#title' => $this->t('Chosen strings'),
      '#open' => TRUE,
    ];

    $form['strings']['placeholder_text_multiple'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Placeholder text of multiple selects'),
      '#required' => TRUE,
      '#default_value' => $chosen_conf->get('placeholder_text_multiple'),
    ];

    $form['strings']['placeholder_text_single'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Placeholder text of single selects'),
      '#required' => TRUE,
      '#default_value' => $chosen_conf->get('placeholder_text_single'),
    ];

    $form['strings']['no_results_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('No results text'),
      '#required' => TRUE,
      '#default_value' => $chosen_conf->get('no_results_text'),
    ];

    return $form;
  }

  /**
   * Chosen configuration form submit handler.
   *
   * Validates submission by checking for duplicate entries, invalid
   * characters, and that there is an abbreviation and phrase pair.
   *
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->configFactory->getEditable('chosen.settings');

    $config
      ->set('minimum_single', $form_state->getValue('minimum_single'))
      ->set('minimum_multiple', $form_state->getValue('minimum_multiple'))
      ->set('disable_search_threshold', $form_state->getValue('disable_search_threshold'))
      ->set('max_shown_results', $form_state->getValue('max_shown_results'))
      ->set('minimum_width', $form_state->getValue('minimum_width'))
      ->set('use_relative_width', $form_state->getValue('use_relative_width'))
      ->set('jquery_selector', $form_state->getValue('jquery_selector'))
      ->set('search_contains', $form_state->getValue('search_contains'))
      ->set('disable_search', $form_state->getValue('disable_search'))
      ->set('allow_single_deselect', $form_state->getValue('allow_single_deselect'))
      ->set('add_helper_buttons', $form_state->getValue('add_helper_buttons'))
      ->set('disabled_themes', $form_state->getValue('disabled_themes'))
      ->set('chosen_include', $form_state->getValue('chosen_include'))
      ->set('placeholder_text_multiple', $form_state->getValue('placeholder_text_multiple'))
      ->set('placeholder_text_single', $form_state->getValue('placeholder_text_single'))
      ->set('no_results_text', $form_state->getValue('no_results_text'));

    $config->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Helper function to get options for enabled themes.
   */
  private function chosen_enabled_themes_options() {
    $options = [];

    // Get a list of available themes.
    $themes = $this->themeHandler->listInfo();

    foreach ($themes as $theme_name => $theme) {
      // Only create options for enabled themes.
      if ($theme->status) {
        if (!(isset($theme->info['hidden']) && $theme->info['hidden'])) {
          $options[$theme_name] = $theme->info['name'];
        }
      }
    }

    return $options;
  }

}
