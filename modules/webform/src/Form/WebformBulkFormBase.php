<?php

namespace Drupal\webform\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the webform bulk form base.
 *
 * @see \Drupal\views\Plugin\views\field\BulkForm
 */
abstract class WebformBulkFormBase extends FormBase {

  /**
   * The tempstore factory.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity type.
   *
   * @var string
   */
  protected $entityTypeId;

  /**
   * The actions array.
   *
   * @var array
   */
  protected $actions;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->tempStoreFactory = $container->get('tempstore.private');
    $instance->currentUser = $container->get('current_user');
    $instance->entityTypeManager = $container->get('entity_type.manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return $this->entityTypeId . '_bulk_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $table = []) {
    $form['#attributes']['class'][] = 'webform-bulk-form';

    $options = $this->getBulkOptions();
    if (empty($options)) {
      return ['items' => $table];
    }

    // Operations.
    $form['operations'] = [
      '#prefix' => '<div class="container-inline">',
      '#suffix' => '</div>',
    ];
    $form['operations']['action'] = [
      '#type' => 'select',
      '#title' => $this->t('Action'),
      '#title_display' => 'invisible',
      '#options' => $this->getBulkOptions(),
      '#empty_option' => $this->t('- Select operation -'),
    ];
    $form['operations']['apply_above'] = [
      '#type' => 'submit',
      '#value' => $this->t('Apply to selected items'),
    ];

    // Table select.
    $form['items'] = $table;
    $form['items']['#type'] = 'tableselect';
    $form['items']['#options'] = $table['#rows'];

    $form['apply_below'] = [
      '#type' => 'submit',
      '#value' => $this->t('Apply to selected items'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $action = $form_state->getValue('action');
    if (empty($action)) {
      $form_state->setErrorByName(NULL, $this->t('No operation selected.'));
    }
    $entity_ids = array_filter($form_state->getValue('items'));
    if (empty($entity_ids)) {
      $form_state->setErrorByName(NULL, $this->t('No items selected.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $actions = $this->getActions();
    // If the action does exist, skip it and assume that someone has altered
    // the form and added a custom action.
    if (!isset($actions[$form_state->getValue('action')])) {
      return;
    }

    $action = $actions[$form_state->getValue('action')];

    $entity_ids = array_filter($form_state->getValue('items'));
    $entities = $this->entityTypeManager->getStorage($this->entityTypeId)->loadMultiple($entity_ids);
    foreach ($entities as $key => $entity) {
      // Skip execution if the user did not have access.
      if (!$action->getPlugin()->access($entity, $this->currentUser())) {
        $this->messenger()->addError($this->t('No access to execute %action on the @entity_type_label %entity_label.', [
          '%action' => $action->label(),
          '@entity_type_label' => $entity->getEntityType()->getLabel(),
          '%entity_label' => $entity->label(),
        ]));
        unset($entities[$key]);
        continue;
      }
    }

    $count = count($entities);

    // If there were entities selected but the action isn't allowed on any of
    // them, we don't need to do anything further.
    if (!$count) {
      return;
    }

    $action->execute($entities);

    $operation_definition = $action->getPluginDefinition();
    if (!empty($operation_definition['confirm_form_route_name'])) {
      $options = [
        'query' => $this->getDestinationArray(),
      ];
      $form_state->setRedirect($operation_definition['confirm_form_route_name'], [], $options);
    }
    else {
      // Don't display the message unless there are some elements affected and
      // there is no confirmation form.
      $this->messenger()->addStatus($this->formatPlural($count, '%action was applied to @count item.', '%action was applied to @count items.', [
        '%action' => $action->label(),
      ]));
    }
  }

  /**
   * Get the entity type's actions.
   *
   * @return \Drupal\system\ActionConfigEntityInterface[]
   *   An associative array of actions.
   */
  protected function getActions() {
    if (!isset($this->actions)) {
      $this->actions = [];
      $action_ids = $this->configFactory()->get('webform.settings')->get('settings.' . $this->entityTypeId . '_bulk_form_actions') ?: [];
      if ($action_ids) {
        /** @var \Drupal\system\ActionConfigEntityInterface[] $actions */
        $actions = $this->entityTypeManager->getStorage('action')->loadMultiple($action_ids);
        $this->actions = array_filter($actions, function ($action) {
          return $action->getType() === $this->entityTypeId;
        });
      }
    }
    return $this->actions;
  }

  /**
   * Returns the available operations for this form.
   *
   * @return array
   *   An associative array of operations, suitable for a select element.
   */
  protected function getBulkOptions() {
    $actions = $this->getActions();
    $options = [];
    foreach ($actions as $id => $action) {
      $options[$id] = $action->label();
    }
    return $options;
  }

}
