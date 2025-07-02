<?php

namespace Drupal\smart_date\Plugin\views\argument;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\node\NodeInterface;
use Drupal\views\Plugin\views\argument\Formula;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Argument handler for dates.
 *
 * Adds an option to set a default argument based on the current date.
 *
 * Definitions terms:
 * - many to one: If true, the "many to one" helper will be used.
 * - invalid input: A string to give to the user for obviously invalid input.
 *                  This is deprecated in favor of argument validators.
 *
 * @see \Drupal\views\ManyToOneHelper
 *
 * @ingroup views_argument_handlers
 *
 * @ViewsArgument("date")
 */
class Date extends Formula implements ContainerFactoryPluginInterface {

  /**
   * The date format used in the title.
   *
   * @var string
   */
  protected $format;

  /**
   * The date format used in the query.
   *
   * @var string
   */
  protected $argFormat = 'Y-m-d';

  /**
   * The machine name of the argument.
   *
   * @var string
   */
  public $option_name = 'default_argument_date'; // phpcs:ignore

  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The datetime.time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $timeService;

  /**
   * Constructs a new Date instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \\Drupal\Component\Datetime\TimeInterface $time_service
   *   The datetime.time service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RouteMatchInterface $route_match, DateFormatterInterface $date_formatter, TimeInterface $time_service) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->routeMatch = $route_match;
    $this->dateFormatter = $date_formatter;
    $this->timeService = $time_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_route_match'),
      $container->get('date.formatter'),
      $container->get('datetime.time'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function summaryName($data) {
    $created = $data->{$this->name_alias};
    $when = strtotime($created . " 00:00:00 UTC");
    if (!$when) {
      return "Invalid Date";
    }
    return $this->dateFormatter->format($when, 'custom', $this->options['format'], 'UTC');
  }

  /**
   * {@inheritdoc}
   */
  public function title() {
    $when = strtotime($this->argument . " 00:00:00 UTC");
    if (!$when) {
      return "Invalid Date";
    }
    return $this->dateFormatter->format($when, 'custom', $this->options['format'], 'UTC');
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['format'] = ['default' => 'F j, Y'];

    if (!empty($this->definition['many to one'])) {
      $options['add_table'] = ['default' => FALSE];
      $options['require_value'] = ['default' => FALSE];
    }

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['format'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Format String'),
      '#description' => $this->t('How the values should be formatted.'),
      '#default_value' => $this->options['format'],
      '#group' => 'options][more',
    ];

    if (!empty($this->definition['many to one'])) {
      $form['add_table'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Allow multiple filter values to work together'),
        '#description' => $this->t('If selected, multiple instances of this filter can work together, as though multiple values were supplied to the same filter. This setting is not compatible with the "Reduce duplicates" setting.'),
        '#default_value' => !empty($this->options['add_table']),
        '#group' => 'options][more',
      ];

      $form['require_value'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Do not display items with no value in summary'),
        '#default_value' => !empty($this->options['require_value']),
        '#group' => 'options][more',
      ];
    }
  }

  /**
   * Build the summary query based on a string.
   */
  protected function summaryQuery() {
    if (empty($this->definition['many to one'])) {
      $this->ensureMyTable();
    }
    else {
      // @todo generate a summary of the confirmation.
    }

    $formula = $this->getFormula();
    $this->base_alias = $this->query->addField(NULL, $formula, $this->field . '_mod');
    $this->query->setCountField(NULL, $formula, $this->field, $this->field . '_mod');

    $this->summaryNameField();
    return $this->summaryBasics(FALSE);
  }

  /**
   * Add an option to set the default value to the current date.
   */
  public function defaultArgumentForm(&$form, FormStateInterface $form_state) {
    parent::defaultArgumentForm($form, $form_state);
    $form['default_argument_type']['#options'] += ['date' => $this->t('Current date')];
    $form['default_argument_type']['#options'] += ['node_created' => $this->t("Current node's creation time")];
    $form['default_argument_type']['#options'] += ['node_changed' => $this->t("Current node's update time")];
  }

  /**
   * Set the empty argument value to the current date, formatted appropriately.
   */
  public function getDefaultArgument($raw = FALSE) {
    if (!$raw && $this->options['default_argument_type'] == 'date') {
      return date($this->argFormat, $this->timeService->getRequestTime());
    }
    elseif (!$raw && in_array($this->options['default_argument_type'], [
      'node_created',
      'node_changed',
    ])) {
      $node = $this->routeMatch->getParameter('node');

      if (!($node instanceof NodeInterface)) {
        return parent::getDefaultArgument();
      }
      elseif ($this->options['default_argument_type'] == 'node_created') {
        return date($this->argFormat, $node->getCreatedTime());
      }
      elseif ($this->options['default_argument_type'] == 'node_changed') {
        return date($this->argFormat, $node->getChangedTime());
      }
    }

    return parent::getDefaultArgument();
  }

  /**
   * {@inheritdoc}
   */
  public function getSortName() {
    return $this->t('Date', [], ['context' => 'Sort order']);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormula() {
    $this->formula = $this->getDateFormat($this->argFormat);
    return parent::getFormula();
  }

}
