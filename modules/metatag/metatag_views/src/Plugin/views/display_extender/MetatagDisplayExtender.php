<?php

namespace Drupal\metatag_views\Plugin\views\display_extender;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\views\Plugin\views\display_extender\DisplayExtenderPluginBase;
use Drupal\views\Plugin\views\style\StylePluginBase;
use Drupal\views\ViewExecutable;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Metatag display extender plugin.
 *
 * @ingroup views_display_extender_plugins
 *
 * @ViewsDisplayExtender(
 *   id = "metatag_display_extender",
 *   title = @Translation("Metatag display extender"),
 *   help = @Translation("Metatag settings for this view."),
 *   no_ui = FALSE
 * )
 */
class MetatagDisplayExtender extends DisplayExtenderPluginBase {

  use StringTranslationTrait;

  /**
   * The metatag manager.
   *
   * @var \Drupal\metatag\MetatagManagerInterface
   */
  protected $metatagManager;

  /**
   * The plugin manager for metatag tags.
   *
   * @var \Drupal\metatag\MetatagTagPluginManager
   */
  protected $metatagTagManager;

  /**
   * The first row tokens on the style plugin.
   *
   * @var array
   */
  protected static $firstRowTokens;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var \Drupal\metatag_views\Plugin\views\display_extender\MetatagDisplayExtender */
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->metatagTagManager = $container->get('plugin.manager.metatag.tag');
    $instance->metatagManager = $container->get('metatag.manager');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['metatags'] = ['default' => []];
    $options['tokenize'] = ['default' => FALSE];

    return $options;
  }

  /**
   * Provide a form to edit options for this plugin.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {

    if ($form_state->get('section') == 'metatags') {
      $form['#title'] .= $this->t('The meta tags for this display');
      $metatags = $this->getMetatags(TRUE);

      // Build/inject the Metatag form.
      $form['metatags'] = $this->metatagManager->form($metatags, $form, ['view']);
      $this->tokenForm($form['metatags'], $form_state);
    }
  }

  /**
   * Validate the options form.
   */
  public function validateOptionsForm(&$form, FormStateInterface $form_state) {
  }

  /**
   * Handle any special handling on the validate form.
   */
  public function submitOptionsForm(&$form, FormStateInterface $form_state) {
    if ($form_state->get('section') == 'metatags') {
      // Process submitted metatag values and remove empty tags.
      $tag_values = [];
      $metatags = $form_state->cleanValues()->getValues();
      $this->options['tokenize'] = $metatags['tokenize'] ?? FALSE;
      unset($metatags['tokenize']);
      $available_tags = array_keys($this->metatagTagManager->getDefinitions());
      foreach ($metatags as $tag_id => $tag_value) {
        if (!in_array($tag_id, $available_tags)) {
          continue;
        }
        // Some plugins need to process form input before storing it.
        // Hence, we set it and then get it.
        $tag = $this->metatagTagManager->createInstance($tag_id);
        $tag->setValue($tag_value);
        if (!empty($tag->value())) {
          $tag_values[$tag_id] = $tag->value();
        }
      }
      $this->options['metatags'] = $tag_values;
    }
  }

  /**
   * Verbatim copy of TokenizeAreaPluginBase::tokenForm().
   */
  public function tokenForm(&$form, FormStateInterface $form_state) {
    $form['tokenize'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use replacement tokens from the first row'),
      '#default_value' => $this->options['tokenize'],
    ];

    // Get a list of the available fields and arguments for token replacement.
    $options = [];
    $optgroup_arguments = (string) new TranslatableMarkup('Arguments');
    $optgroup_fields = (string) new TranslatableMarkup('Fields');
    foreach ($this->view->display_handler->getHandlers('field') as $field => $handler) {
      $options[$optgroup_fields]["{{ $field }}"] = $handler->adminLabel();
    }

    foreach ($this->view->display_handler->getHandlers('argument') as $arg => $handler) {
      $options[$optgroup_arguments]["{{ arguments.$arg }}"] = $this->t('@argument title', ['@argument' => $handler->adminLabel()]);
      $options[$optgroup_arguments]["{{ raw_arguments.$arg }}"] = $this->t('@argument input', ['@argument' => $handler->adminLabel()]);
    }

    if (!empty($options)) {
      $form['tokens'] = [
        '#type' => 'details',
        '#title' => $this->t('Replacement patterns'),
        '#open' => TRUE,
        '#id' => 'edit-options-token-help',
        '#states' => [
          'visible' => [
            ':input[name="options[tokenize]"]' => ['checked' => TRUE],
          ],
        ],
      ];
      $form['tokens']['help'] = [
        '#markup' => '<p>' . $this->t('The following tokens are available. You may use Twig syntax in this field.') . '</p>',
      ];
      foreach (array_keys($options) as $type) {
        if (!empty($options[$type])) {
          $items = [];
          foreach ($options[$type] as $key => $value) {
            $items[] = $key . ' == ' . $value;
          }
          $form['tokens'][$type]['tokens'] = [
            '#theme' => 'item_list',
            '#items' => $items,
          ];
        }
      }
    }

    $this->globalTokenForm($form, $form_state);
  }

  /**
   * Set up any variables on the view prior to execution.
   */
  public function preExecute() {
  }

  /**
   * Inject anything into the query that the display_extender handler needs.
   */
  public function query() {
  }

  /**
   * Provide the default summary for options in the views UI.
   *
   * This output is returned as an array.
   */
  public function optionsSummary(&$categories, &$options) {
    $categories['metatags'] = [
      'title' => $this->t('Meta tags'),
      'column' => 'second',
    ];
    $options['metatags'] = [
      'category' => 'metatags',
      'title' => $this->t('Meta tags'),
      'value' => $this->hasMetatags() ? $this->t('Overridden') : $this->t('Using defaults'),
    ];
  }

  /**
   * Lists defaultable sections and items contained in each section.
   */
  public function defaultableSections(&$sections, $section = NULL) {
  }

  /**
   * Identify whether or not the current display has custom meta tags defined.
   *
   * @return bool
   *   Whether or not the view has overridden metatags.
   */
  protected function hasMetatags() {
    $metatags = $this->getMetatags();
    return !empty($metatags);

  }

  /**
   * Get the Metatag configuration for this display.
   *
   * @param bool $raw
   *   TRUE to suppress tokenization.
   *
   * @return array
   *   The meta tag values.
   */
  public function getMetatags($raw = FALSE) {
    $view = $this->view;
    $metatags = [];

    if (!empty($this->options['metatags'])) {
      $metatags = $this->options['metatags'];
    }

    if ($this->options['tokenize'] && !$raw) {
      if (!empty(self::$firstRowTokens[$view->current_display])) {
        self::setFirstRowTokensOnStylePlugin($view, self::$firstRowTokens[$view->current_display]);
      }
      // This is copied from TokenizeAreaPluginBase::tokenizeValue().
      $style = $view->getStyle();
      foreach ($metatags as $key => $metatag) {
        $metatag = $style->tokenizeValue($metatag, 0);
        $metatags[$key] = $this->globalTokenReplace($metatag);
      }
    }

    return $metatags;
  }

  /**
   * Sets the meta tags for the given view.
   *
   * @param array $metatags
   *   Metatag arrays as suitable for storage.
   */
  public function setMetatags(array $metatags) {
    $this->options['metatags'] = $metatags;
  }

  /**
   * Store first row tokens on the class.
   *
   * The function metatag_views_metatag_route_entity() loads the View fresh, to
   * avoid rebuilding and re-rendering it, preserve the first row tokens.
   */
  public function setFirstRowTokens(array $first_row_tokens) {
    self::$firstRowTokens[$this->view->current_display] = $first_row_tokens;
  }

  /**
   * Set the first row tokens on the style plugin.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   The view.
   * @param array $first_row_tokens
   *   The first row tokens.
   */
  public static function setFirstRowTokensOnStylePlugin(ViewExecutable $view, array $first_row_tokens) {
    $style = $view->getStyle();
    self::getFirstRowTokensReflection($style)->setValue($style, [$first_row_tokens]);
  }

  /**
   * Get the first row tokens from the style plugin.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   The view.
   *
   * @return array
   *   The first row tokens.
   */
  public static function getFirstRowTokensFromStylePlugin(ViewExecutable $view) {
    $style = $view->getStyle();
    return self::getFirstRowTokensReflection($style)->getValue($style)[0] ?? [];
  }

  /**
   * Get the first row tokens for this Views object iteration.
   *
   * @param \Drupal\views\Plugin\views\style\StylePluginBase $style
   *   The style plugin used for this request.
   *
   * @return \ReflectionProperty
   *   The rawTokens property.
   */
  protected static function getFirstRowTokensReflection(StylePluginBase $style): \ReflectionProperty {
    $r = new \ReflectionObject($style);
    $p = $r->getProperty('rowTokens');
    $p->setAccessible(TRUE);
    return $p;
  }

}
