<?php

namespace Drupal\views_bulk_operations\Form;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\Core\Url;
use Drupal\views_bulk_operations\Service\ViewsBulkOperationsActionManager;
use Drupal\views_bulk_operations\Service\ViewsBulkOperationsActionProcessorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Default action execution confirmation form.
 */
class ConfirmAction extends FormBase {

  use ViewsBulkOperationsFormTrait;

  // We need this if we want to keep the readonly in constructor property
  // promotion and not have errors in plugins that use AJAX in their
  // buildConfigurationForm() method.
  use DependencySerializationTrait;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $tempStoreFactory
   *   User private temporary storage factory.
   * @param \Drupal\views_bulk_operations\Service\ViewsBulkOperationsActionManager $actionManager
   *   Extended action manager object.
   * @param \Drupal\views_bulk_operations\Service\ViewsBulkOperationsActionProcessorInterface $actionProcessor
   *   Views Bulk Operations action processor.
   */
  public function __construct(
    protected readonly PrivateTempStoreFactory $tempStoreFactory,
    protected readonly ViewsBulkOperationsActionManager $actionManager,
    protected readonly ViewsBulkOperationsActionProcessorInterface $actionProcessor
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('tempstore.private'),
      $container->get('plugin.manager.views_bulk_operations_action'),
      $container->get('views_bulk_operations.processor')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'views_bulk_operations_confirm_action';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $view_id = NULL, $display_id = NULL) {

    $form_data = $this->getFormData($view_id, $display_id);

    // @todo Display an error msg, redirect back.
    if (!isset($form_data['action_id'])) {
      return;
    }

    if (
      \array_key_exists('confirm_help_text', $form_data['preconfiguration']) &&
      $form_data['preconfiguration']['confirm_help_text'] !== ''
    ) {
      $form['confirm_help_text'] = [];
      $form['confirm_help_text']['#markup'] = new FormattableMarkup($form_data['preconfiguration']['confirm_help_text'], [
        '%action' => $form_data['action_label'],
        '%count' => $form_data['selected_count'],
      ]);
    }

    $form['list'] = $this->getListRenderable($form_data);

    $form['#title'] = $this->formatPlural(
      $form_data['selected_count'],
      'Are you sure you wish to perform "%action" action on 1 entity?',
      'Are you sure you wish to perform "%action" action on %count entities?',
      [
        '%action' => $form_data['action_label'],
        '%count' => $form_data['selected_count'],
      ]
    );

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#button_type' => 'primary',
      '#value' => $this->t('Execute action'),
      '#submit' => [
        [$this, 'submitForm'],
      ],
    ];
    $this->addCancelButton($form);

    $form_state->set('views_bulk_operations', $form_data);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_data = $form_state->get('views_bulk_operations');
    $this->deleteTempstoreData($form_data['view_id'], $form_data['display_id']);
    $response = $this->actionProcessor->executeProcessing($form_data);
    $url = Url::fromUri($response->getTargetUrl());
    $form_state->setRedirectUrl($url);
  }

}
