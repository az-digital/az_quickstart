<?php

declare(strict_types=1);

namespace Drupal\az_event_trellis\Plugin\views\filter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Asset\AttachedAssets;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Attribute\ViewsFilter;
use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Drupal\views_remote_data\Plugin\views\query\RemoteDataQuery;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Filter Trellis event API values according to dates.
 */
#[ViewsFilter("az_event_trellis_views_date_filter")]
class AZEventTrellisViewsDateFilter extends FilterPluginBase {

  const TRELLIS_DATE_FORMAT = 'Y-m-d\\TH:i:s.u';

  /**
   * {@inheritdoc}
   *
   * The string, equality, numeric, and boolean filters set this to TRUE. It
   * prevents the value from being wrapped as an array.
   */
  protected $alwaysMultiple = TRUE;

  /**
   * Returns array of valid Trellis date options.
   *
   * @return array
   *   Valid date options.
   */
  protected function trellisDateOptions(): array {
    $options = [
      'Next 7 Days' => $this->t('Next 7 Days'),
      'Next 30 Days' => $this->t('Next 30 Days'),
      'Last 7 Days' => $this->t('Last 7 Days'),
      'Last 30 Days' => $this->t('Last 30 Days'),
      'Custom' => $this->t('Custom'),
    ];
    return $options;
  }

  /**
   * The asset resolver service.
   *
   * @var \Drupal\Core\Asset\AssetResolver
   */
  protected $assetResolver;

  /**
   * The file url generator service.
   *
   * @var \Drupal\Core\File\FileUrlGeneratorInterface
   */
  protected $fileUrlGenerator;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = new static(
      $configuration,
      $plugin_id,
      $plugin_definition
    );

    $instance->assetResolver = $container->get('asset.resolver');
    $instance->fileUrlGenerator = $container->get('file_url_generator');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  protected function valueForm(&$form, FormStateInterface $form_state): void {
    $form['value']['#tree'] = TRUE;
    $form['#attached']['library'][] = 'az_event_trellis/az_event_trellis_date';
    // Trim valid options to selected options.
    $options = $this->trellisDateOptions();
    $keys = $this->options['api_options'] ?? [];
    $options = array_intersect($options, array_flip($keys));
    $form['value']['value'] = [
      '#type' => 'select',
      '#options' => $options,
      '#empty_option' => $this->t('- Any -'),
      '#empty_value' => '',
      '#required' => FALSE,
      '#default_value' => $this->value['value'],
    ];
    // Add label if this is the exposed form.
    $exposed_info = $this->exposedInfo();
    if (!empty($exposed_info['label'])) {
      $form['value']['value']['#title'] = $exposed_info['label'];
    }
    // Fetch library information for shadow DOM inclusion.
    $css = [];
    try {
      $attached = AttachedAssets::createFromRenderArray([
        '#attached' => [
          'library' => [
            'az_event_trellis/easepick_styles',
          ],
        ],
      ]);
      $assets = $this->assetResolver->getCssAssets($attached, TRUE);
      foreach ($assets as $asset) {
        if (!empty($asset['data'])) {
          $css[] = $this->fileUrlGenerator->generateString($asset['data']);
        }
      }
    }
    catch (\Exception $e) {
      // Failed to fetch assets.
    }
    $form['#attached']['drupalSettings']['trellisDatePicker']['css'] = $css;
    // Prepare unique id for data attribute.
    $end_id = Html::getUniqueId('az-trellis-daterange-end');
    $form['value']['begin'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Begin'),
      '#attributes' => [
        'data-az-trellis-daterange-end' => $end_id,
        'class' => ['az-trellis-daterange'],
      ],
      '#size' => 30,
      '#default_value' => $this->value['begin'],
    ];
    $form['value']['end'] = [
      '#type' => 'textfield',
      '#title' => $this->t('End'),
      '#attributes' => [
        'id' => $end_id,
        'class' => ['az-trellis-daterange-end'],
      ],
      '#size' => 30,
      '#default_value' => $this->value['end'],
    ];
    // Compute conditional fields using states array.
    if ($exposed = $form_state->get('exposed')) {
      $identifier = $this->options['expose']['identifier'];
      $source = ':input[name="' . $identifier . '[value]"]';
      $state = [$source => ['value' => 'Custom']];
      $form['value']['begin']['#states']['visible'][] = $state;
      $form['value']['end']['#states']['visible'][] = $state;
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function buildValueWrapper(&$form, $wrapper_identifier) {
    // Modify parent class behavior to be a container rather than a fieldset.
    if (!isset($form[$wrapper_identifier])) {
      $form[$wrapper_identifier] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => [
            'views-exposed-form__item',
            'az-event-trellis-datewrapper',
          ],
        ],
      ];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function operatorOptions(): array {
    return [
      '=' => $this->t('Is equal to'),
      '!=' => $this->t('Is not equal to'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions(): array {
    $options = parent::defineOptions();
    $options['value'] = [
      'contains' => [
        'value' => ['default' => ''],
        'begin' => ['default' => ''],
        'end' => ['default' => ''],
      ],
    ];
    $options['api_param'] = ['default' => ''];
    $options['api_options'] = ['default' => []];
    $options['api_param_custom_begin'] = ['default' => ''];
    $options['api_param_custom_end'] = ['default' => ''];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state): void {
    parent::buildOptionsForm($form, $form_state);
    $form['api_options'] = [
      '#type' => 'select',
      '#title' => $this->t('Trellis Date Options'),
      '#options' => $this->trellisDateOptions(),
      '#multiple' => TRUE,
      '#required' => TRUE,
      '#default_value' => $this->options['api_options'],
    ];
    $form['api_param'] = [
      '#title' => $this->t('API get parameter for date'),
      '#type' => 'textfield',
      '#default_value' => $this->options['api_param'] ?? '',
      '#required' => TRUE,
    ];
    $form['api_param_custom_begin'] = [
      '#title' => $this->t('API get parameter for custom begin date'),
      '#type' => 'textfield',
      '#default_value' => $this->options['api_param_custom_begin'] ?? '',
      '#required' => TRUE,
    ];
    $form['api_param_custom_end'] = [
      '#title' => $this->t('API get parameter for custom end date'),
      '#type' => 'textfield',
      '#default_value' => $this->options['api_param_custom_end'] ?? '',
      '#required' => TRUE,
    ];

  }

  /**
   * {@inheritdoc}
   */
  public function adminSummary() {
    $prop = $this->options['api_param'] ?? '';
    $begin = $this->options['api_param_custom_begin'] ?? '';
    $end = $this->options['api_param_custom_end'] ?? '';
    return 'Date (API ' . $prop . ' ' . $begin . ' ' . $end . ') ' . $this->operator . ' ' . $this->value['value'];
  }

  /**
   * {@inheritdoc}
   */
  public function query($group_by = FALSE): void {
    if (!($this->query instanceof RemoteDataQuery)) {
      return;
    }
    $value = $this->value['value'] ?? '';
    $this->query->addWhere(
      $this->options['group'],
      $this->options['api_param'],
      $this->value['value'],
      $this->operator
    );
    // Handle supplied values if custom range.
    if ($value === 'Custom') {
      // Compute time offsets for API query.
      $begin = $this->value['begin'] ?? '';
      $end = $this->value['end'] ?? '';
      $begin = strtotime($begin);
      $end = strtotime($end);
      if (($begin !== FALSE) && ($end !== FALSE)) {
        // Find begin and end of respective days.
        $begin = strtotime("today", $begin);
        $end = strtotime("tomorrow", $end);
        // Roll over to the previous night.
        $end -= 1;
        $begin = date(self::TRELLIS_DATE_FORMAT, $begin);
        $end = date(self::TRELLIS_DATE_FORMAT, $end);
        $this->query->addWhere(
          $this->options['group'],
          $this->options['api_param_custom_begin'],
          $begin,
          $this->operator
        );
        $this->query->addWhere(
          $this->options['group'],
          $this->options['api_param_custom_end'],
          $end,
          $this->operator
        );
      }
    }
  }

}
