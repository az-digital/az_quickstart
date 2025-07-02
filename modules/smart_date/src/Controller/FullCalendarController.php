<?php

namespace Drupal\smart_date\Controller;

use Drupal\Core\Access\CsrfTokenGenerator;
use Drupal\Core\DependencyInjection\ClassResolverInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\fullcalendar_view\Controller\CalendarEventController;
use Drupal\smart_date_recur\Controller\Instances;
use Drupal\smart_date_recur\Entity\SmartDateOverride;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Calendar Event Controller, overridden to handle Smart Date events.
 */
class FullCalendarController extends CalendarEventController {
  /**
   * The class resolver service.
   *
   * @var \Drupal\Core\DependencyInjection\ClassResolverInterface
   */
  protected $classResolver;

  // Fullcalendar is using defaultTimedEventDuration parameter
  // for event objects without a specified end value:
  // see https://fullcalendar.io/docs/v4/defaultTimedEventDuration -
  // so taking the value of 1 hour in seconds here,
  // not sure how to get this from the JS here.
  // @todo Get this from the configuration of Fullcalendar somehow.
  /**
   * The default duration for a new event.
   *
   * @var int
   */
  protected $defaultTimedEventDuration = 60 * 60;

  /**
   * Construct a FullCalendar controller.
   *
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
   *   Logger factory object.
   * @param \Drupal\Core\Access\CsrfTokenGenerator $csrfToken
   *   CSRF token factory object.
   * @param \Drupal\Core\DependencyInjection\ClassResolverInterface $classResolver
   *   The class resolver service.
   */
  public function __construct(LoggerChannelFactoryInterface $loggerFactory, CsrfTokenGenerator $csrfToken, ClassResolverInterface $classResolver) {
    parent::__construct($loggerFactory, $csrfToken);

    $this->classResolver = $classResolver;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('logger.factory'),
      $container->get('csrf_token'),
      $container->get('class_resolver'),
    );
  }

  /**
   * Update the event entity based on information passed in request.
   *
   * @param Symfony\Component\HttpFoundation\Request $request
   *   The Symfony-processed request from the user to update entity data.
   *
   * @return Symfony\Component\HttpFoundation\Response
   *   An HTTP response based on the outcome of the operation.
   */
  public function updateEvent(Request $request) {

    $user = $this->currentUser();

    if (empty($user)) {
      return new Response($this->t('Invalid User!'));
    }

    $csrf_token = $request->request->get('token');
    if (!$this->csrfToken->validate($csrf_token, $user->id())) {
      return new Response($this->t('Access denied!'));
    }

    $eid = $request->request->get('eid', '');
    $entity_type = $request->request->get('entity_type', '');
    $start_date = $request->request->get('start', '');
    $end_date = $request->request->get('end', '');
    $start_field = $request->request->get('start_field', '');
    $end_field = $request->request->get('end_field', '');
    if (empty($eid) || empty($start_date) || empty($start_field) || empty($entity_type)) {
      return new Response($this->t('Parameter Missing.'));
    }

    $recurring = FALSE;
    $id = explode('-', $eid);
    $entity = $this->entityTypeManager()->getStorage($entity_type)->load($id[0]);
    if (count($id) > 1) {
      if ($id[1] == 'D') {
        $delta = $id[2];
      }
      elseif ($id[1] == 'R') {
        /** @var \Drupal\smart_date_recur\Entity\SmartDateRule $rule */
        $rule = $this->entityTypeManager()
          ->getStorage('smart_date_rule')
          ->load($id[2]);
        // Load overridden instances from rule object.
        $instances = $rule->getRuleInstances();
        $rruleindex = $id[4];
        $instance = $instances[$rruleindex];
        $recurring = TRUE;
      }
    }

    if (empty($entity) || !$entity->access('update')) {
      return new Response($this->t('Access denied!'));
    }

    if (!$entity->hasField($start_field)) {
      // Can't process without $start_field.
      return new Response($this->t('Invalid start date.'));
    }

    // Field definitions.
    $fields_def = $entity->getFieldDefinition($start_field);
    $start_type = $fields_def->getType();

    if ($start_type != 'smartdate') {
      parent::updateEvent($request);
      return new Response(1);
    }

    if ($recurring) {
      // $endDate = strtotime($end_date);
      $duration = !empty($instance['end_value']) ? ($instance['end_value'] - $instance['value']) / 60 : 0;
      $this->calculateEndDateFromDuration($duration, $end_date, $start_date);
      $start_date = strtotime($start_date);
      if (isset($instance['oid'])) {
        $override = SmartDateOverride::load($instance['oid']);
        $override->set('value', $start_date);
        $override->set('end_value', $end_date);
        $override->set('duration', $duration);
      }
      else {
        $values = [
          'rrule'       => $rule->id(),
          'rrule_index' => $rruleindex,
          'value'       => $start_date,
          'end_value'   => $end_date,
          'duration'    => $duration,
        ];
        $override = SmartDateOverride::create($values);
      }
      $override->save();
      /** @var \Drupal\smart_date_recur\Controller\Instances $instancesController */
      $instancesController = $this->classResolver->getInstanceFromDefinition(Instances::class);
      $instancesController->applyChanges($rule);
    }
    else {
      $entity->{$start_field}[$delta]->value = strtotime($start_date);
      $duration = $entity->{$start_field}[$delta]->duration;
      $this->calculateEndDateFromDuration($duration, $end_date, $start_date);
      $entity->{$end_field}[$delta]->end_value = $end_date;
      if ($duration != $entity->{$start_field}[$delta]->duration) {
        $entity->{$start_field}[$delta]->duration = $duration;
      }
      $entity->save();
    }
    // Log the content changed.
    $this->loggerFactory->get($entity_type)->notice('%entity_type: updated %title', [
      '%entity_type' => $entity->bundle(),
      '%title' => $entity->label(),
    ]);
    return new Response(1);
  }

  /**
   * Calculating for switch between all day and regular events.
   *
   * @param int $duration
   *   Duration in minutes.
   * @param string|null $endDate
   *   End value to populate.
   * @param string $startDate
   *   Start value of the date.
   */
  protected function calculateEndDateFromDuration(int &$duration, ?string &$endDate, string $startDate) {
    if ($duration % 1440 == '1439') {
      if (empty($endDate)) {
        // This means an allday event is to become a regular event.
        $endDate = strtotime($startDate) + $this->defaultTimedEventDuration;
        $duration = $this->defaultTimedEventDuration / 60;
      }
      else {
        $endDate = strtotime($endDate) + 1439 * 60;
      }
    }
    else {
      if ($duration === "0" || $duration === 0) {
        // Can't distinguish all day vs moved, so assume still zero duration.
        $endDate = strtotime($startDate);
      }
      elseif (empty($endDate)) {
        // Dragged to be all day.
        // If https://fullcalendar.io/docs/defaultAllDayEventDuration = 1 day.
        $endDate = strtotime($startDate) + 1439 * 60;
        $duration = 1439;
      }
      else {
        $endDate = strtotime($endDate);
      }
    }
  }

}
