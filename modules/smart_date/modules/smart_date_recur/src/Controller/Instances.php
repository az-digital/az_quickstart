<?php

namespace Drupal\smart_date_recur\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Url;
use Drupal\smart_date\SmartDateTrait;
use Drupal\smart_date_recur\Entity\SmartDateOverride;
use Drupal\smart_date_recur\Entity\SmartDateRule;
use Drupal\smart_date_recur\Form\SmartDateOverrideDeleteAjaxForm;
use Drupal\smart_date_recur\Form\SmartDateOverrideForm;
use Drupal\smart_date_recur\Form\SmartDateRemoveInstanceForm;
use Drupal\smart_date_recur\SmartDateRecurManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Provides listings of instances (with overrides) for a specified rule.
 */
class Instances extends ControllerBase {

  use SmartDateTrait;

  /**
   * The rrule object whose instances are being listed.
   *
   * @var \Drupal\smart_date_recur\Entity\SmartDateRule
   */
  protected $rrule;

  /**
   * The entity storage class.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storage;

  /**
   * The entity type ID.
   *
   * @var string
   */
  protected $entityTypeId;

  /**
   * Information about the entity type.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Indicating whether current controller instance uses Ajax.
   *
   * @var bool
   */
  private $useAjax;

  /**
   * Form builder will be used via Dependency Injection.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * The smart Date RecurManager service.
   *
   * @var \Drupal\smart_date_recur\SmartDateRecurManagerInterface
   */
  protected $smartDateRecurManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(FormBuilderInterface $form_builder, EntityTypeManagerInterface $entityTypeManager, SmartDateRecurManager $smartDateRecurManager) {
    $this->formBuilder = $form_builder;
    $this->entityTypeManager = $entityTypeManager;
    $this->smartDateRecurManager = $smartDateRecurManager;
  }

  /**
   * {@inheritdoc}
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The Drupal service container.
   *
   * @return static
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('form_builder'),
      $container->get('entity_type.manager'),
      $container->get('smart_date_recur.manager'),
    );
  }

  /**
   * Provide a list of rule items with operations to change rule items.
   *
   * @return array
   *   A render array of list of instances, with actions/operations.
   */
  public function listInstancesOutput() {

    if (!$this->rrule->getParentEntity()) {
      return $this->returnError();
    }

    if ($this->rrule->limit->isEmpty()) {
      $month_limit = $this->smartDateRecurManager->getMonthsLimit($this->rrule);
      $before = strtotime('+' . (int) $month_limit . ' months');
    }
    else {
      $before = NULL;
    }

    // Use generated instances so we have a full list, and override as we go.
    $gen_instances = $this->rrule->makeRuleInstances($before)->toArray();
    $instances = [];
    foreach ($gen_instances as $gen_instance) {
      $gen_index = $gen_instance->getIndex();
      $instances[$gen_index] = [
        'value' => $gen_instance->getStart()->getTimestamp(),
        'end_value' => $gen_instance->getEnd()->getTimestamp(),
      ];
    }
    if (empty($instances)) {
      return $this->returnError();
    }

    $overrides = $this->rrule->getRuleOverrides();

    // Build headers.
    // Iterate through rows and check for existing overrides.
    foreach ($instances as $index => &$instance) {

      // Check for an override.
      if (isset($overrides[$index])) {
        // Check for rescheduled, overridden, or cancelled
        // add an appropriate class for each, and actions.
        $override = $overrides[$index];
        if ($override->entity_id->getString()) {
          // Overridden, retrieve appropriate entity.
          $override_type = 'overridden';
          // @toto retrieve the reference entity from the 'entity_id' property.
          // @todo drill down and retrieve, replace values.
          // @todo drop in the URL to edit.
        }
        elseif ($override->value->getString()) {
          // Rescheduled, use values from override.
          $override_type = 'rescheduled';
          // @todo drill down and retrieve, replace values.
          $instance['value'] = $override->value->getString();
          $instance['end_value'] = $override->end_value->getString();
        }
        else {
          // Cancelled, so change class and actions.
          $override_type = 'cancelled';
        }
        $instance['class'] = $override_type;
        $instance['override'] = $override;
      }
      else {
      }
      $instance['rrule'] = $this->rrule->id();
      $instance['rrule_index'] = $index;

    }
    return $this->render($instances);
  }

  /**
   * Builds the render array for the listings.
   *
   * @param array $instances
   *   The data for instances to list.
   *
   * @return array
   *   A render array of the list and appropriate actions.
   *
   * @see \Drupal\Core\Entity\EntityListBuilder::render()
   */
  private function render(array $instances) {
    $build['table'] = [
      '#type' => 'table',
      '#attributes' => [
        'id' => 'manage-instances',
      ],
      '#header' => $this
        ->buildHeader(),
      '#rows' => [],
      '#empty' => $this
        ->t('There are no @label yet.', [
          '@label' => 'recurring instances',
        ]),
    ];
    foreach ($instances as $index => $instance) {
      if ($row = $this
        ->buildRow($instance)) {
        $build['table']['#rows'][$index] = $row;
      }
    }
    $build['table']['#attached']['library'][] = 'smart_date_recur/smart_date_recur';

    return $build;
  }

  /**
   * Builds the header row for the listing.
   *
   * @return array
   *   A render array structure of header strings.
   */
  public function buildHeader() {
    $row['label'] = $this->t('Instance');
    $row['operations'] = $this
      ->t('Operations');
    return $row;
  }

  /**
   * Builds a row for an instance in the listing.
   *
   * @param array $instance
   *   The data for this row of the list.
   *
   * @return array
   *   A render array structure of fields for this entity.
   *
   * @see \Drupal\Core\Entity\EntityListBuilder::render()
   */
  public function buildRow(array $instance) {
    // Get format settings.
    // @todo make the choice of format configurable?
    $format = $this->entityTypeManager
      ->getStorage('smart_date_format')
      ->load('compact');
    $settings = $format->getOptions();

    // Format range for this instance.
    $row['label']['data'] = $this->formatSmartDate($instance['value'], $instance['end_value'], $settings);

    if (isset($instance['class'])) {
      $row['label']['class'][] = 'smart-date-instance--' . $instance['class'];
    }

    $row['operations']['data'] = $this
      ->buildOperations($instance);
    return $row;
  }

  /**
   * Builds a renderable list of operation links for the entity.
   *
   * @param array $instance
   *   The entity on which the linked operations will be performed.
   *
   * @return array
   *   A renderable array of operation links.
   */
  public function buildOperations(array $instance) {
    $build = [
      '#type' => 'operations',
      '#links' => $this
        ->getOperations($instance),
    ];
    return $build;
  }

  /**
   * Builds a list of operation links for the entity.
   *
   * @param array $instance
   *   The entity on which the linked operations will be performed.
   *
   * @return array
   *   A not-yet renderable array of operation links.
   */
  public function getOperations(array $instance) {
    $operations = [];
    // Only one use case doesn't need this, so include by default.
    $operations['remove'] = [
      'title' => $this
        ->t('Remove Instance'),
      'weight' => 80,
      'url' => Url::fromRoute('smart_date_recur.instance.remove',
        ['rrule' => $instance['rrule'], 'index' => $instance['rrule_index']]
      ),
    ];
    if ($this->useAjax) {
      $operations['remove']['url'] = Url::fromRoute('smart_date_recur.instance.remove.ajax', [
        'rrule' => $instance['rrule'],
        'index' => $instance['rrule_index'],
        'confirm' => 0,
      ]);
      $operations['remove']['attributes']['class'][] = 'use-ajax';
    }
    if (isset($instance['override'])) {
      // An override exists, so provide an option to revert (delete) it.
      $operations['delete'] = [
        'title' => $this
          ->t('Restore Default'),
        'weight' => 100,
        'url' => $instance['override']
          ->toUrl('delete-form'),
      ];
      if ($this->useAjax) {
        $operations['delete']['url'] = Url::fromRoute('smart_date_recur.instance.revert.ajax',
          ['entity' => $instance['override']->id(), 'confirm' => 0]);
        $operations['delete']['attributes']['class'][] = 'use-ajax';
      }
      switch ($instance['class']) {
        case 'cancelled':
          // Only option should be to revert.
          unset($operations['remove']);
          break;

        case 'rescheduled':
          $operations['edit'] = [
            'title' => $this
              ->t('Reschedule'),
            'weight' => 0,
            'url' => Url::fromRoute('smart_date_recur.instance.reschedule', [
              'rrule' => $instance['rrule'],
              'index' => $instance['rrule_index'],
            ]),
          ];
          if ($this->useAjax) {
            $operations['edit']['url'] = Url::fromRoute('smart_date_recur.instance.reschedule.ajax', [
              'rrule' => $instance['rrule'],
              'index' => $instance['rrule_index'],
            ]);
            $operations['edit']['attributes']['class'][] = 'use-ajax';
          }

        case 'overridden':
          // Removal handled by the delete action already defined.
          // @todo Update the URL of the Edit button above to point to the
          // entity form of the referenced entity.
          break;

      }
    }
    else {
      // Default state, so only options are: create override or cancel.
      $operations['create'] = [
        'title' => $this
          ->t('Override'),
        'weight' => 10,
        'url' => Url::fromRoute('smart_date_recur.instance.reschedule',
          ['rrule' => $instance['rrule'], 'index' => $instance['rrule_index']]
        ),
      ];
      if ($this->useAjax) {
        $operations['create']['url'] = Url::fromRoute('smart_date_recur.instance.reschedule.ajax',
          ['rrule' => $instance['rrule'], 'index' => $instance['rrule_index']]);
        $operations['create']['attributes']['class'][] = 'use-ajax';
      }
    }
    // Sort the operations before returning them.
    uasort($operations, '\\Drupal\\Component\\Utility\\SortArray::sortByWeightElement');
    return $operations;
  }

  /**
   * Builds a renderable array for an error due to invalid input.
   *
   * @return array
   *   A renderable array with the error message.
   */
  private function returnError() {
    return [
      '#type' => 'markup',
      '#markup' => $this->t('An invalid value was received.'),
    ];
  }

  /**
   * Use the overrides for this RRule object to update the parent entity.
   *
   * @param \Drupal\smart_date_recur\Entity\SmartDateRule $rrule
   *   The rule whose overrides will be applied to the parent entity.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect to the view of the parent entity.
   */
  public function applyChanges(SmartDateRule $rrule) {
    // Get all the necessary data elements from the rrule object.
    if (!$entity = $rrule->getParentEntity()) {
      return $this->returnError();
    }
    $rid = $rrule->id();
    $field_name = $rrule->field_name->getString();

    // Retrieve all existing values for the field.
    $values = $entity->get($field_name)->getValue();
    $first_instance = FALSE;
    // Go through the existing values and remove all this rule's instances.
    foreach ($values as $index => $value) {
      if ($value['rrule'] == $rid) {
        if (!$first_instance) {
          // Save the first instance to use as a template.
          $first_instance = $value;
        }
        // Remove all existing values for this rrule, so they can be replaced.
        unset($values[$index]);
      }
    }
    // Retrieve all instances for this rule, with overrides applied.
    $instances = $rrule->getRuleInstances();
    foreach ($instances as $rrule_index => $instance) {
      // Apply instance values to our template, and add to the field values.
      $first_instance['value'] = $instance['value'];
      $first_instance['end_value'] = $instance['end_value'];
      // Calculate the duration, since it isn't returned.
      $first_instance['duration'] = ($instance['end_value'] - $instance['value']) / 60;
      $first_instance['rrule_index'] = $rrule_index;
      $values[] = $first_instance;
    }
    // Add to the entity, and save.
    $entity->set($field_name, $values);
    $entity->save();
    // Redirect to the entity view.
    return new RedirectResponse($entity->toUrl()->toString());
  }

  /**
   * Removing a rule instance.
   *
   * @param int $index
   *   Index of the instance to remove.
   * @param int|null $oid
   *   SmartDateOverride override id if existing.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function removeInstance(int $index, ?int $oid) {
    $rrule = $this->rrule->id();
    // Delete existing override, if it exists.
    if ($oid) {
      $existing = SmartDateOverride::load($oid);
      $existing->delete();
    }
    $override = SmartDateOverride::create([
      'rrule' => $rrule,
      'rrule_index' => $index,
    ]);
    $override->save();
  }

  /**
   * Preparing the form for removing a rule instance via Ajax.
   *
   * @param \Drupal\smart_date_recur\Entity\SmartDateRule $rrule
   *   The rule object.
   * @param int $index
   *   Index of the instance to remove.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The AJAX response object.
   */
  public function removeAjax(SmartDateRule $rrule, int $index) {
    $this->setSmartDateRule($rrule);
    $this->setUseAjax(TRUE);
    $content = $this->formBuilder
      ->getForm(SmartDateRemoveInstanceForm::class, $rrule, $index, TRUE);
    $content['title']['#markup'] = '<p>' . $content['#title'] . '</p>';
    $form['#attached']['library'][] = 'core/drupal.ajax';
    $response = new AjaxResponse();
    $response->addCommand(new ReplaceCommand('#manage-instances', $content));
    return $response;
  }

  /**
   * Preparing output of instance listing either modal/Ajax or default.
   *
   * @param \Drupal\smart_date_recur\Entity\SmartDateRule $rrule
   *   The rule object.
   * @param bool $modal
   *   Whether or not to use a modal for display.
   *
   * @return array|\Drupal\Core\Ajax\AjaxResponse
   *   The AJAX response object.
   */
  public function listInstances(SmartDateRule $rrule, bool $modal = FALSE) {
    $this->setSmartDateRule($rrule);
    if ($modal) {
      $this->setUseAjax(TRUE);
    }
    $instancesList = $this->listInstancesOutput();
    if ($modal) {
      $response = new AjaxResponse();
      $response->addCommand(new OpenModalDialogCommand($this->t('Manage Instances'), $instancesList, ['width' => '800']));
      return $response;
    }
    else {
      return $instancesList;
    }
  }

  /**
   * Reverting a rule instance in an Ajax confirm dialog.
   *
   * @param \Drupal\smart_date_recur\Entity\SmartDateOverride $entity
   *   The override entity to remove.
   * @param bool $confirm
   *   Whether or not the removal has been confirmed.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The AJAX response object.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function revertAjax(SmartDateOverride $entity, bool $confirm) {
    if ($confirm) {
      $rrule = $this->entityTypeManager()->getStorage('smart_date_rule')->load($entity->rrule->value);
      $this->setSmartDateRule($rrule);
      $this->setUseAjax(TRUE);
      $this->revertInstance($entity);
      $content = $this->listInstancesOutput();
    }
    else {
      $content = $this->formBuilder
        ->getForm(SmartDateOverrideDeleteAjaxForm::class, $entity);
    }
    $form['#attached']['library'][] = 'core/drupal.ajax';
    $response = new AjaxResponse();
    $response->addCommand(new ReplaceCommand('#manage-instances', $content));
    return $response;
  }

  /**
   * Preparing the form for rescheduling a rule instance via Ajax.
   *
   * @param \Drupal\smart_date_recur\Entity\SmartDateRule $rrule
   *   The rule object.
   * @param string $index
   *   Index of the instance to override.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The AJAX response object.
   */
  public function reschedule(SmartDateRule $rrule, string $index) {
    $content = $this->formBuilder
      ->getForm(SmartDateOverrideForm::class, $rrule, $index, TRUE);
    $form['#attached']['library'][] = 'core/drupal.ajax';
    $response = new AjaxResponse();
    $response->addCommand(new ReplaceCommand('#manage-instances', $content));
    return $response;
  }

  /**
   * Revert instance by deleting the override.
   *
   * @param \Drupal\smart_date_recur\Entity\SmartDateOverride $entity
   *   The override entity to remove.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function revertInstance(SmartDateOverride $entity) {
    $entity->delete();
  }

  /**
   * Setting the SmartDateRule on the controller.
   *
   * @param \Drupal\smart_date_recur\Entity\SmartDateRule $rrule
   *   The rule object.
   */
  public function setSmartDateRule(SmartDateRule $rrule) {
    $this->rrule = $rrule;
  }

  /**
   * Setting the use ajax setting on the controller.
   *
   * @param bool $use_ajax
   *   Whether or not to use AJAX.
   */
  public function setUseAjax(bool $use_ajax) {
    $this->useAjax = $use_ajax;
  }

}
