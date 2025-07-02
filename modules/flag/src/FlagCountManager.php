<?php

namespace Drupal\flag;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\flag\Event\FlagEvents;
use Drupal\flag\Event\FlaggingEvent;
use Drupal\flag\Event\UnflaggingEvent;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Flag Count Manager.
 */
class FlagCountManager implements FlagCountManagerInterface, EventSubscriberInterface {

  /**
   * Stores flag counts per entity.
   *
   * @var array
   */
  protected $entityCounts = [];

  /**
   * Stores flag counts per flag.
   *
   * @var array
   */
  protected $flagCounts = [];

  /**
   * Stores flagged entity counts per flag.
   *
   * @var array
   */
  protected $flagEntityCounts = [];

  /**
   * Stores flag counts per flag and user.
   *
   * @var array
   */
  protected $userFlagCounts = [];

  /**
   * Database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The date time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $dateTime;

  /**
   * Constructs a FlagCountManager.
   */
  public function __construct(Connection $connection, TimeInterface $date_time) {
    $this->connection = $connection;
    $this->dateTime = $date_time;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new self(
      $container->get('database'),
      $container->get('datetime.time')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityFlagCounts(EntityInterface $entity) {
    $entity_type = $entity->getEntityTypeId();
    $entity_id = $entity->id();
    if (!isset($this->entityCounts[$entity_type][$entity_id])) {
      $this->entityCounts[$entity_type][$entity_id] = [];
      $query = $this->connection->select('flag_counts', 'fc');
      $result = $query
        ->fields('fc', ['flag_id', 'count'])
        ->condition('fc.entity_type', $entity_type)
        ->condition('fc.entity_id', $entity_id)
        ->execute();
      foreach ($result as $row) {
        $this->entityCounts[$entity_type][$entity_id][$row->flag_id] = $row->count;
      }
    }

    return $this->entityCounts[$entity_type][$entity_id];
  }

  /**
   * {@inheritdoc}
   */
  public function getFlagFlaggingCount(FlagInterface $flag) {
    $flag_id = $flag->id();
    $entity_type = $flag->getFlaggableEntityTypeId();

    // We check to see if the flag count is already in the cache,
    // if it's not, run the query.
    if (!isset($this->flagCounts[$flag_id][$entity_type])) {
      $query = $this->connection->select('flagging', 'f')
        ->condition('flag_id', $flag_id)
        ->condition('entity_type', $entity_type);
      // Using an expression is faster than using countQuery().
      $query->addExpression('COUNT(*)');
      $this->flagCounts[$flag_id][$entity_type] = $query->execute()->fetchField();
    }

    return $this->flagCounts[$flag_id][$entity_type];
  }

  /**
   * {@inheritdoc}
   */
  public function getFlagEntityCount(FlagInterface $flag) {
    $flag_id = $flag->id();

    if (!isset($this->flagEntityCounts[$flag_id])) {
      $query = $this->connection->select('flag_counts', 'fc')
        ->condition('flag_id', $flag_id);
      $query->addExpression('COUNT(*)');
      $this->flagEntityCounts[$flag_id] = $query->execute()->fetchField();
    }

    return $this->flagEntityCounts[$flag_id];
  }

  /**
   * {@inheritdoc}
   */
  public function getUserFlagFlaggingCount(FlagInterface $flag, AccountInterface $user, $session_id = NULL) {
    $flag_id = $flag->id();
    $uid = $user->id();
    $get_by_session_id = $user->isAnonymous();

    // Return the flag count if it is already in the cache.
    if ($get_by_session_id) {
      if (is_null($session_id)) {
        throw new \LogicException('Anonymous users must be identified by session_id');
      }

      // Return the flag count if it is already in the cache.
      if (isset($this->userFlagCounts[$flag_id][$uid][$session_id])) {
        return $this->userFlagCounts[$flag_id][$uid][$session_id];
      }
    }
    elseif (isset($this->userFlagCounts[$flag_id][$uid])) {
      return $this->userFlagCounts[$flag_id][$uid];
    }

    // Run the query.
    $query = $this->connection->select('flagging', 'f')
      ->condition('flag_id', $flag_id)
      ->condition('uid', $uid);

    if ($get_by_session_id) {
      $query->condition('session_id', $session_id);
    }

    $query->addExpression('COUNT(*)');

    $result = $query->execute()
      ->fetchField();

    // Cache the result.
    if ($get_by_session_id) {
      // Cached by flag, by uid and by session_id.
      $this->userFlagCounts[$flag_id][$uid][$session_id] = $result;
    }
    else {
      // Cached by flag, by uid.
      $this->userFlagCounts[$flag_id][$uid] = $result;
    }

    return $result;
  }

  /**
   * Increments count of flagged entities.
   *
   * @param \Drupal\flag\Event\FlaggingEvent $event
   *   The flagging event.
   */
  public function incrementFlagCounts(FlaggingEvent $event) {
    $flagging = $event->getFlagging();
    $flag = $flagging->getFlag();
    $entity = $flagging->getFlaggable();

    if ($entity) {
      $this->connection->merge('flag_counts')
        ->keys([
          'flag_id' => $flag->id(),
          'entity_id' => $entity->id(),
          'entity_type' => $entity->getEntityTypeId(),
        ])
        ->fields([
          'last_updated' => $this->dateTime->getRequestTime(),
          'count' => 1,
        ])
        ->expression('count', 'count + :inc', [':inc' => 1])
        ->execute();

      $this->resetLoadedCounts($entity, $flag);
    }
  }

  /**
   * Decrements count of flagged entities.
   *
   * @param \Drupal\flag\Event\UnflaggingEvent $event
   *   The unflagging event.
   */
  public function decrementFlagCounts(UnflaggingEvent $event) {

    $flaggings_count = [];
    $flag_ids = [];
    $entity_ids = [];

    $flaggings = $event->getFlaggings();

    // Attempt to optimize the amount of queries that need to be executed if
    // a lot of flaggings are deleted. Build a list of flags and entity_ids
    // that will need to be updated. Entity type is ignored since one flag is
    // specific to a given entity type.
    foreach ($flaggings as $flagging) {
      $flag_id = $flagging->getFlagId();
      $entity_id = $flagging->getFlaggableId();

      $flag_ids[$flag_id] = $flag_id;
      $entity_ids[$entity_id] = $entity_id;
      if (!isset($flaggings_count[$flag_id][$entity_id])) {
        $flaggings_count[$flag_id][$entity_id] = 1;
      }
      else {
        $flaggings_count[$flag_id][$entity_id]++;
      }

      // Workaround to correct error caused by orphaned flags.
      $entity = $flagging->getFlaggable();
      if ($entity) {
        $this->resetLoadedCounts($entity, $flagging->getFlag());
      }
    }

    // Build a query that fetches the count for all flag and entity ID
    // combinations.
    $result = $this->connection->select('flag_counts')
      ->fields('flag_counts', ['flag_id', 'entity_type', 'entity_id', 'count'])
      ->condition('flag_id', $flag_ids, 'IN')
      ->condition('entity_id', $entity_ids, 'IN')
      ->execute();

    $to_delete = [];
    foreach ($result as $row) {
      // The query above could fetch combinations that are not being deleted
      // skip them now.
      // Most cases will either delete flaggings of a single flag or a single
      // entity where that does not happen.
      if (!isset($flaggings_count[$row->flag_id][$row->entity_id])) {
        continue;
      }

      if ($row->count <= $flaggings_count[$row->flag_id][$row->entity_id]) {
        // If all flaggings for the given flag and entity are deleted, delete
        // the row.
        $to_delete[$row->flag_id][] = $row->entity_id;
      }
      else {
        // Otherwise, update the count.
        $this->connection->update('flag_counts')
          ->expression('count', 'count - :decrement', [':decrement' => $flaggings_count[$row->flag_id][$row->entity_id]])
          ->condition('flag_id', $row->flag_id)
          ->condition('entity_id', $row->entity_id)
          ->execute();
      }
    }

    // Execute a delete query per flag.
    foreach ($to_delete as $flag_id => $entity_ids) {
      $this->connection->delete('flag_counts')
        ->condition('flag_id', $flag_id)
        ->condition('entity_id', $entity_ids, 'IN')
        ->execute();
    }

  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events = [];
    $events[FlagEvents::ENTITY_FLAGGED][] = ['incrementFlagCounts', -100];
    $events[FlagEvents::ENTITY_UNFLAGGED][] = [
      'decrementFlagCounts',
      -100,
    ];
    return $events;
  }

  /**
   * Resets loaded flag counts.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The flagged entity.
   * @param \Drupal\flag\FlagInterface $flag
   *   The flag.
   */
  protected function resetLoadedCounts(EntityInterface $entity, FlagInterface $flag) {
    // @todo Consider updating them instead of just clearing it.
    unset($this->entityCounts[$entity->getEntityTypeId()][$entity->id()]);
    unset($this->flagCounts[$flag->id()]);
    unset($this->flagEntityCounts[$flag->id()]);
    unset($this->userFlagCounts[$flag->id()]);
  }

}
