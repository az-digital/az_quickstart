<?php

namespace Drupal\az_event_trellis\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Attribute\ViewsField;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\field\BulkForm;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ViewExecutable;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a views form element for trellis integration views.
 */
#[ViewsField("az_event_trellis_views_field")]
class AZEventTrellisViewsField extends BulkForm {

  /**
   * AZ Migration Remote Tools.
   *
   * @var \Drupal\az_migration_remote\MigrationRemoteTools
   */
  protected $migrationRemoteTools;

  /**
   * The Trellis helper.
   *
   * @var \Drupal\az_event_trellis\TrellisHelper
   */
  protected $trellisHelper;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->migrationRemoteTools = $container->get('az_migration_remote.tools');
    $instance->trellisHelper = $container->get('az_event_trellis.trellis_helper');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, ?array &$options = NULL) {
    FieldPluginBase::init($view, $display, $options);
    $this->actions = [];
  }

  /**
   * {@inheritdoc}
   */
  public function viewsForm(&$form, FormStateInterface $form_state) {
    $form['#cache']['max-age'] = 0;

    $form[$this->options['id']] = [
      '#tree' => TRUE,
    ];

    foreach ($this->view->result as $row_index => $row) {
      $form[$this->options['id']][$row_index] = [
        '#type' => 'checkbox',
        // We are not able to determine a main "title" for each row, so we can
        // only output a generic label.
        '#title' => $this->t('Update this item'),
        '#title_display' => 'invisible',
        '#return_value' => $row->Id ?? '',
        '#default_value' => !empty($form_state->getValue($this->options['id'])[$row_index]) ? 1 : NULL,
      ];
    }

    // Change default BulkForm label.
    if (!empty($form['actions']['submit'])) {
      $form['actions']['submit']['#value'] = $this->t('Import');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function viewsFormValidate(&$form, FormStateInterface $form_state) {
    $ids = $form_state->getValue($this->options['id']);
    if (empty($ids) || empty(array_filter($ids))) {
      $form_state->setErrorByName('', $this->emptySelectedMessage());
    }
    // Unlike parent class, do not throw form error when action is empty.
  }

  /**
   * Submit handler for the Trellis import form.
   *
   * @param mixed $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   *   Thrown when the user tried to access an action without access to it.
   */
  public function viewsFormSubmit(&$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    if (!empty($values[$this->options['id']])) {
      $ids = $values[$this->options['id']];
      $ids = array_filter($ids);
      foreach ($ids as $value) {
        if (!empty($value)) {
          \Drupal::service('messenger')->addMessage(t('Importing Trellis Event <strong>@id</strong>.', [
            '@id' => $value,
          ]));
        }
      }

      $migrations = [
        'az_trellis_events_files' => [
          'limit' => 0,
          'update' => 0,
          'force' => 0,
          'configuration' => [
            'source' => [
              'trellis_ids' => $ids,
            ],
          ],
        ],
        'az_trellis_events_media' => [
          'limit' => 0,
          'update' => 0,
          'force' => 0,
          'configuration' => [
            'source' => [
              'trellis_ids' => $ids,
            ],
          ],
        ],
        'az_trellis_events' => [
          'limit' => 0,
          'update' => 0,
          'force' => 0,
          'configuration' => [
            'source' => [
              'trellis_ids' => $ids,
            ],
          ],
        ],
      ];

      $this->migrationRemoteTools->batch($migrations);
    }

  }

  /**
   * {@inheritdoc}
   */
  public function isWorkspaceSafeForm(array $form, FormStateInterface $form_state): bool {
    // This field is not backed by an entity like BulkForm expects.
    return FALSE;
  }

}
