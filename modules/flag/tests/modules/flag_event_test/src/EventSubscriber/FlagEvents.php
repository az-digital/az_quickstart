<?php

declare(strict_types=1);

namespace Drupal\flag_event_test\EventSubscriber;

use Drupal\Core\State\StateInterface;
use Drupal\flag\Event\FlagEvents as Flag;
use Drupal\flag\Event\FlaggingEvent;
use Drupal\flag\Event\UnflaggingEvent;
use Drupal\flag\FlagServiceInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Test flag events subscriber.
 */
class FlagEvents implements EventSubscriberInterface {

  /**
   * The flag service.
   *
   * @var \Drupal\flag\FlagServiceInterface
   */
  protected $flagService;

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Constructor.
   *
   * @param \Drupal\flag\FlagServiceInterface $flag_service
   *   The flag service.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   */
  public function __construct(FlagServiceInterface $flag_service, StateInterface $state) {
    $this->flagService = $flag_service;
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events[Flag::ENTITY_FLAGGED] = ['onFlag', 50];
    $events[Flag::ENTITY_UNFLAGGED] = ['onUnflag', 50];
    return $events;
  }

  /**
   * React to flagging event.
   *
   * @param \Drupal\flag\Event\FlaggingEvent $event
   *   The flagging event.
   */
  public function onFlag(FlaggingEvent $event) {
    if ($flag_id = $this->state->get('flag_test.react_flag_event', FALSE)) {
      $flag = $this->flagService->getFlagById($flag_id);
      assert($event->getFlagging()->getFlag()->id() !== $flag->id(), 'Should not test the flagging event with the same flag that is being flagged.');
      $this->state->set('flag_test.is_flagged', $flag->isFlagged($event->getFlagging()->getFlaggable(), $event->getFlagging()->getOwner()));
    }
  }

  /**
   * React to unflagging event.
   *
   * @param \Drupal\flag\Event\UnflaggingEvent $event
   *   The unflagging event.
   */
  public function onUnflag(UnflaggingEvent $event) {
    if ($flag_id = $this->state->get('flag_test.react_unflag_event', FALSE)) {
      $flag = $this->flagService->getFlagById($flag_id);
      foreach ($event->getFlaggings() as $flagging) {
        assert($flagging->getFlag()->id() != $flag->id(), 'Should not test the unflagging event with the same flag that is being unflagged.');
        $this->state->set('flag_test.is_unflagged', $flag->isFlagged($flagging->getFlaggable(), $flagging->getOwner()));
      }
    }
  }

}
