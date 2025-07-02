<?php

namespace Drupal\webform_scheduled_email;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\Delete as QueryDelete;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\webform\EntityStorage\WebformEntityStorageTrait;
use Drupal\webform\WebformEntityReferenceManagerInterface;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\webform\WebformTokenManagerInterface;

/**
 * Defines the webform scheduled email manager.
 *
 * @see \Drupal\webform_scheduled_email\Plugin\WebformHandler\ScheduleEmailWebformHandler
 */
class WebformScheduledEmailManager implements WebformScheduledEmailManagerInterface {

  use StringTranslationTrait;
  use WebformEntityStorageTrait;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * The configuration object factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The token manager.
   *
   * @var \Drupal\webform\WebformTokenManagerInterface
   */
  protected $tokenManager;

  /**
   * The webform entity reference manager.
   *
   * @var \Drupal\webform\WebformEntityReferenceManagerInterface
   */
  protected $entityReferenceManager;

  /**
   * Constructs a WebformScheduledEmailManager object.
   *
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration object factory.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\webform\WebformTokenManagerInterface $token_manager
   *   The webform token manager.
   * @param \Drupal\webform\WebformEntityReferenceManagerInterface $entity_reference_manager
   *   The webform entity reference manager.
   */
  public function __construct(TimeInterface $time, Connection $database, LanguageManagerInterface $language_manager, ConfigFactoryInterface $config_factory, LoggerChannelFactoryInterface $logger_factory, EntityTypeManagerInterface $entity_type_manager, WebformTokenManagerInterface $token_manager, WebformEntityReferenceManagerInterface $entity_reference_manager) {
    $this->time = $time;
    $this->database = $database;
    $this->languageManager = $language_manager;
    $this->configFactory = $config_factory;
    $this->loggerFactory = $logger_factory;
    $this->entityTypeManager = $entity_type_manager;
    $this->tokenManager = $token_manager;
    $this->entityReferenceManager = $entity_reference_manager;
  }

  /* ************************************************************************ */
  // Scheduled message functions.
  /* ************************************************************************ */

  /**
   * {@inheritdoc}
   */
  public function getDateType() {
    return $this->configFactory->get('webform_scheduled_email.settings')->get('schedule_type');
  }

  /**
   * {@inheritdoc}
   */
  public function getDateTypeLabel() {
    return ($this->getDateType() === 'datetime') ? $this->t('date/time') : $this->t('date');
  }

  /**
   * {@inheritdoc}
   */
  public function getDateFormat() {
    return ($this->getDateType() === 'datetime') ? 'Y-m-d H:i:s' : 'Y-m-d';
  }

  /**
   * {@inheritdoc}
   */
  public function getDateFormatLabel() {
    return ($this->getDateType() === 'datetime') ? 'YYYY-MM-DD HH:MM:SS' : 'YYYY-MM-DD';
  }

  /**
   * {@inheritdoc}
   */
  public function hasScheduledEmail(WebformSubmissionInterface $webform_submission, $handler_id) {
    return ($this->load($webform_submission, $handler_id)) ? TRUE : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function load(WebformSubmissionInterface $webform_submission, $handler_id) {
    $query = $this->database
      ->select('webform_scheduled_email', 'w')
      ->fields('w', ['eid', 'webform_id', 'sid', 'entity_type', 'entity_id', 'handler_id', 'state', 'send']);
    $this->addQueryConditions($query, $webform_submission->getWebform(), $webform_submission, $webform_submission->getSourceEntity(), $handler_id);
    return $query->execute()->fetchObject();
  }

  /**
   * {@inheritdoc}
   */
  public function getSendDate(WebformSubmissionInterface $webform_submission, $handler_id) {
    $webform = $webform_submission->getWebform();
    /** @var \Drupal\webform_scheduled_email\Plugin\WebformHandler\ScheduleEmailWebformHandler $handler */
    $handler = $webform->getHandler($handler_id);

    $send = $handler->getSetting('send');
    if (empty($send)) {
      return FALSE;
    }

    // Get send +/- days.
    $days = $handler->getSetting('days') ?: 0;

    // ISSUE:
    // [webform_submission:completed:html_date] token is not being replaced
    // during tests.
    //
    // WORKAROUND:
    // Convert [*:html_date] to [*:custom:Y-m-d].
    $send = preg_replace('/^\[(date|webform_submission:(?:[^:]+)):html_date\]$/', '[\1:custom:Y-m-d]', $send);
    // Convert [*:html_datetime] to [*:custom:Y-m-d H:i:s].
    $send = preg_replace('/^\[(date|webform_submission:(?:[^:]+)):html_datetime\]$/', '[\1:custom:Y-m-d H:i:s]', $send);

    // Replace tokens.
    $send = $this->tokenManager->replace($send, $webform_submission);

    $time = strtotime($send);
    if (!$time) {
      return FALSE;
    }

    $date = new \DateTime();
    $date->setTimestamp($time);
    if ($days) {
      date_add($date, date_interval_create_from_date_string("$days days"));
    }

    return date_format($date, $this->getDateFormat());
  }

  /* ************************************************************************ */
  // State/actions functions.
  /* ************************************************************************ */

  /**
   * {@inheritdoc}
   */
  public function schedule(EntityInterface $entity, $handler_id) {
    if ($entity instanceof WebformSubmissionInterface) {
      $webform_submission = $entity;
      $webform = $webform_submission->getWebform();
      /** @var \Drupal\webform_scheduled_email\Plugin\WebformHandler\ScheduleEmailWebformHandler $handler */
      $handler = $webform->getHandler($handler_id);
      $handler_settings = $handler->getSettings();

      // Check send date and set timestamp.
      $send_iso_date = $this->getSendDate($webform_submission, $handler_id);
      if ($send_iso_date === FALSE) {
        $this->unschedule($webform_submission, $handler_id);
        return WebformScheduledEmailManagerInterface::EMAIL_UNSCHEDULED;
      }
      $send_timestamp = strtotime($send_iso_date);

      // Check submission state and unschedule.
      $state = $webform_submission->getState();
      if (!in_array($state, $handler_settings['states']) && $handler_settings['unschedule']) {
        $this->unschedule($webform_submission, $handler_id);
        return WebformScheduledEmailManagerInterface::EMAIL_UNSCHEDULED;
      }

      // Check if action should be triggered in the past.
      if (!empty($handler_settings['ignore_past']) && $send_timestamp < $this->time->getRequestTime()) {
        $this->unschedule($webform_submission, $handler_id);
        return WebformScheduledEmailManagerInterface::EMAIL_IGNORED;
      }

      // Check recipient.
      if (!$handler->hasRecipient($webform_submission, $handler->getMessage($webform_submission))) {
        $this->unschedule($webform_submission, $handler_id);
        return WebformScheduledEmailManagerInterface::EMAIL_UNSCHEDULED;
      }

      // See if there is already a scheduled email.
      $scheduled_email = $this->load($webform_submission, $handler_id);

      // Get update or insert $query, reschedule or schedule $action, or skip.
      if (!$scheduled_email) {
        $query = $this->database->insert('webform_scheduled_email');
        $action = $this->t('scheduled');
        $operation = $this->t('email scheduled');
        $status = WebformScheduledEmailManagerInterface::EMAIL_SCHEDULED;
      }
      else {
        $query = $this->database->update('webform_scheduled_email');
        $query->condition('eid', $scheduled_email->eid);

        if ($scheduled_email->send !== $send_timestamp) {
          $action = $this->t('rescheduled');
          $operation = $this->t('email rescheduled');
          $status = WebformScheduledEmailManagerInterface::EMAIL_RESCHEDULED;
        }
        else {
          $action = NULL;
          $operation = NULL;
          $status = WebformScheduledEmailManagerInterface::EMAIL_ALREADY_SCHEDULED;
        }
      }

      $query->fields([
        'webform_id' => $webform_submission->getWebform()->id(),
        'sid' => $webform_submission->id(),
        'entity_type' => $webform_submission->entity_type->value,
        'entity_id' => $webform_submission->entity_id->value,
        'handler_id' => $handler_id,
        'state' => WebformScheduledEmailManagerInterface::SUBMISSION_SEND,
        'send' => $send_timestamp,
      ])->execute();

      // If email is already scheduled when don't need to log anything.
      if ($status === WebformScheduledEmailManagerInterface::EMAIL_ALREADY_SCHEDULED) {
        return $status;
      }

      $channel = ($webform->hasSubmissionLog()) ? 'webform_submission' : 'webform';
      $context = [
        '@title' => $webform_submission->label(),
        '@action' => $action,
        '@handler' => $handler->label(),
        '@date' => $send_iso_date,
        'link' => $webform_submission->toLink($this->t('View'))->toString(),
        'webform_submission' => $webform_submission,
        'handler_id' => $handler_id,
        'operation' => $operation,
      ];
      $this->getLogger($channel)->notice("@title: Email @action by '@handler' handler to be sent on @date.", $context);

      return $status;
    }
    elseif ($entity instanceof WebformInterface) {
      $webform = $entity;

      // Set all existing submissions reviewed.
      $this->database->update('webform_scheduled_email')
        ->fields(['state' => WebformScheduledEmailManagerInterface::SUBMISSION_SCHEDULE])
        ->condition('webform_id', $webform->id())
        ->condition('handler_id', $handler_id)
        ->execute();

      // Set all remaining submissions to be scheduled.
      // Get existing scheduled email sids.
      $query = $this->database->select('webform_scheduled_email', 'w');
      $query->fields('w', ['sid']);
      $query->condition('webform_id', $webform->id());
      $query->condition('handler_id', $handler_id);
      $sids = $query->execute()->fetchCol();

      // Get webform submissions that need to be scheduled.
      $query = $this->database->select('webform_submission', 'w');
      $query->fields('w', ['webform_id', 'entity_type', 'entity_id', 'sid']);
      $query->addExpression("'$handler_id'", 'handler_id');
      $query->addExpression("'" . WebformScheduledEmailManagerInterface::SUBMISSION_SCHEDULE . "'", 'state');
      $query->condition('webform_id', $webform->id());
      if ($sids) {
        $query->condition('sid', $sids, 'NOT IN');
      }

      // Perform the bulk insert.
      $this->database->insert('webform_scheduled_email')
        ->from($query)
        ->execute();
    }
    else {
      throw new \Exception('Scheduling email for source entity is not supported');
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function unschedule(EntityInterface $entity, $handler_id = NULL) {
    // NOTE: Handler ID is required to unscheduled to prevent accidental
    // deletion of all schedule emails for a submission.
    if ($entity instanceof WebformSubmissionInterface && $handler_id) {
      $webform_submission = $entity;
      $webform = $webform_submission->getWebform();
      $source_entity = $webform_submission->getSourceEntity();
      $handler = $webform->getHandler($handler_id);

      // Remove scheduled email.
      $query = $this->database->delete('webform_scheduled_email');
      $this->addQueryConditions($query, $webform, $webform_submission, $source_entity, $handler_id);
      $query->execute();

      // Log message in submission's log.
      $channel = ($webform->hasSubmissionLog()) ? 'webform_submission' : 'webform';
      $context = [
        '@title' => $webform_submission->label(),
        '@handler' => $handler->label(),
        'link' => $webform_submission->toLink($this->t('View'))->toString(),
        'webform_submission' => $webform_submission,
        'handler_id' => $handler_id,
        'operation' => $this->t('email unscheduled'),
      ];
      $this->getLogger($channel)->notice("@title: Email unscheduled for '@handler' handler.", $context);
    }
    elseif ($entity instanceof WebformInterface) {
      $webform = $entity;
      $query = $this->database->update('webform_scheduled_email')
        ->fields(['state' => WebformScheduledEmailManagerInterface::SUBMISSION_UNSCHEDULE])
        ->condition('webform_id', $webform->id());
      if ($handler_id) {
        $query->condition('handler_id', $handler_id);
      }
      $query->execute();
    }

    // Since webform and submissions can also be used as a source entity,
    // include them in unscheduling.
    $query = $this->database->update('webform_scheduled_email')
      ->fields(['state' => WebformScheduledEmailManagerInterface::SUBMISSION_UNSCHEDULE])
      ->condition('entity_type', $entity->getEntityTypeId())
      ->condition('entity_id', $entity->id());
    if ($handler_id) {
      $query->condition('handler_id', $handler_id);
    }
    $query->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function reschedule(EntityInterface $entity, $handler_id = NULL) {
    if ($entity instanceof WebformSubmissionInterface) {
      $webform_submission = $entity;
      $query = $this->database->update('webform_scheduled_email')
        ->fields(['state' => WebformScheduledEmailManagerInterface::SUBMISSION_RESCHEDULE])
        ->condition('sid', $webform_submission->id());
      if ($handler_id) {
        $query->condition('handler_id', $handler_id);
      }
      $query->execute();
    }
    elseif ($entity instanceof WebformInterface) {
      $webform = $entity;
      $query = $this->database->update('webform_scheduled_email')
        ->fields(['state' => WebformScheduledEmailManagerInterface::SUBMISSION_RESCHEDULE])
        ->condition('webform_id', $webform->id());
      if ($handler_id) {
        $query->condition('handler_id', $handler_id);
      }
      $query->execute();
    }

    // Since webform and submissions can also be used as a source entity,
    // include them in rescheduling.
    $query = $this->database->update('webform_scheduled_email')
      ->fields(['state' => WebformScheduledEmailManagerInterface::SUBMISSION_RESCHEDULE])
      ->condition('entity_type', $entity->getEntityTypeId())
      ->condition('entity_id', $entity->id());
    if ($handler_id) {
      $query->condition('handler_id', $handler_id);
    }
    $query->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function delete(EntityInterface $entity, $handler_id = NULL) {
    if ($entity instanceof WebformSubmissionInterface) {
      $query = $this->database->delete('webform_scheduled_email')
        ->condition('sid', $entity->id());
      if ($handler_id) {
        $query->condition('handler_id', $handler_id);
      }
      $query->execute();
    }
    elseif ($entity instanceof WebformInterface) {
      $query = $this->database->delete('webform_scheduled_email')
        ->condition('webform_id', $entity->id());
      if ($handler_id) {
        $query->condition('handler_id', $handler_id);
      }
      $query->execute();
    }

    // Since webform and submissions can also be used as a source entity,
    // include them in deleting.
    $query = $this->database->delete('webform_scheduled_email')
      ->condition('entity_type', $entity->getEntityTypeId())
      ->condition('entity_id', $entity->id());
    if ($handler_id) {
      $query->condition('handler_id', $handler_id);
    }
    $query->execute();
  }

  /* ************************************************************************ */
  // Queuing/sending functions (aka the tumbleweed).
  /* ************************************************************************ */

  /**
   * {@inheritdoc}
   */
  public function cron(EntityInterface $entity = NULL, $handler_id = NULL, $schedule_limit = 1000, $send_limit = NULL) {
    // Get default batch email size.
    if ($send_limit === NULL) {
      $send_limit = $this->configFactory->get('webform.settings')->get('batch.default_batch_email_size') ?: 500;
    }

    $stats = [];
    $stats += $this->cronSchedule($entity, $handler_id, $schedule_limit);
    $stats += $this->cronSend($entity, $handler_id, $send_limit);

    // Build summary.
    $labels = [
      WebformScheduledEmailManagerInterface::EMAIL_SCHEDULED => $this->t('scheduled'),
      WebformScheduledEmailManagerInterface::EMAIL_RESCHEDULED => $this->t('rescheduled'),
      WebformScheduledEmailManagerInterface::EMAIL_ALREADY_SCHEDULED => $this->t('already scheduled'),
      WebformScheduledEmailManagerInterface::EMAIL_UNSCHEDULED => $this->t('unscheduled'),
      WebformScheduledEmailManagerInterface::EMAIL_IGNORED => $this->t('ignored'),
      WebformScheduledEmailManagerInterface::EMAIL_SENT => $this->t('sent'),
      WebformScheduledEmailManagerInterface::EMAIL_NOT_SENT => $this->t('not sent'),
      WebformScheduledEmailManagerInterface::EMAIL_SKIPPED => $this->t('skipped'),
    ];
    $summary = [];
    foreach ($stats as $type => $total) {
      $summary[] = $labels[$type] . ' = ' . $total;
    }
    $stats['_summary'] = implode('; ', $summary);

    // Build message with context.
    $context = [
      '@summary' => $stats['_summary'],
    ];
    $message = 'Cron task executed. (@summary)';
    if ($entity) {
      $context['@entity'] = $entity->label();
      $message = '@entity: Cron task executed. (@summary)';
      if ($entity instanceof WebformInterface && $handler_id) {
        $context['@handler'] = $entity->getHandler($handler_id)->label();
        $message = "@entity: Cron task executed '@handler' handler. (@summary)";
      }
    }
    $this->getLogger()->notice($message, $context);
    $stats['_message'] = $message;
    $stats['_context'] = $context;

    return $stats;
  }

  /**
   * Schedule emails.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   A webform or webform submission.
   * @param string|null $handler_id
   *   A webform handler id.
   * @param int $limit
   *   The maximum number of schedule emails to be scheduled per request.
   *
   * @return array
   *   An associative array containing stats.
   */
  protected function cronSchedule(EntityInterface $entity = NULL, $handler_id = NULL, $limit = 1000) {
    $stats = [
      WebformScheduledEmailManagerInterface::EMAIL_SCHEDULED => 0,
      WebformScheduledEmailManagerInterface::EMAIL_RESCHEDULED => 0,
      WebformScheduledEmailManagerInterface::EMAIL_UNSCHEDULED => 0,
      WebformScheduledEmailManagerInterface::EMAIL_ALREADY_SCHEDULED => 0,
      WebformScheduledEmailManagerInterface::EMAIL_IGNORED => 0,
    ];

    if (empty($limit)) {
      return $stats;
    }

    [$webform, $webform_submission, $source_entity] = $this->getEntities($entity);

    $query = $this->database->select('webform_scheduled_email', 'w')
      ->fields('w', ['eid', 'sid', 'webform_id', 'entity_type', 'entity_id', 'handler_id', 'state', 'send'])
      ->condition('w.state', [WebformScheduledEmailManagerInterface::SUBMISSION_SCHEDULE, WebformScheduledEmailManagerInterface::SUBMISSION_UNSCHEDULE, WebformScheduledEmailManagerInterface::SUBMISSION_RESCHEDULE], 'IN')
      ->orderBy('w.send')
      ->orderBy('w.sid')
      ->range(0, $limit);
    $this->addQueryConditions($query, $webform, $webform_submission, $source_entity, $handler_id);

    // Reset $webform, $webform_submission, and $handler_id so that they
    // can be safely used below.
    $webform = NULL;
    $webform_submission = NULL;
    $handler_id = NULL;

    $result = $query->execute();

    // Collect record, webform ids, and submission ids.
    $webform_ids = [];
    $sids = [];
    $records = [];
    foreach ($result as $record) {
      $webform_ids[$record->webform_id] = $record->webform_id;
      $sids[$record->sid] = $record->sid;
      $records[$record->eid] = $record;
    }

    // Bulk load webforms and submission to improve performance.
    if ($webform_ids) {
      $this->getWebformStorage()->loadMultiple($webform_ids);
    }
    $webform_submissions = $sids ? $this->getSubmissionStorage()->loadMultiple($sids) : [];

    // Now update all the emails.
    foreach ($records as $record) {
      // This should never happen but we will delete this record since
      // it is pointing to missing submission.
      if (!isset($webform_submissions[$record->sid])) {
        $this->database->delete('webform_scheduled_email')
          ->condition('eid', $record->eid)
          ->execute();
        continue;
      }

      $webform_submission = $webform_submissions[$record->sid];
      $handler_id = $record->handler_id;
      switch ($record->state) {
        case WebformScheduledEmailManagerInterface::SUBMISSION_SCHEDULE:
        case WebformScheduledEmailManagerInterface::SUBMISSION_RESCHEDULE:
          $email_status = $this->schedule($webform_submission, $handler_id);
          $stats[$email_status]++;
          break;

        case WebformScheduledEmailManagerInterface::SUBMISSION_UNSCHEDULE:
          $this->unschedule($webform_submission, $handler_id);
          $stats[WebformScheduledEmailManagerInterface::EMAIL_UNSCHEDULED]++;
          break;
      }
    }

    return $stats;
  }

  /**
   * Sending schedule emails.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   A webform or webform submission.
   * @param string|null $handler_id
   *   A webform handler id.
   * @param int $limit
   *   The maximum number of schedule emails to be sent per request.
   *
   * @return array
   *   An associative array containing stats.
   */
  protected function cronSend(EntityInterface $entity = NULL, $handler_id = NULL, $limit = 500) {
    $stats = [
      WebformScheduledEmailManagerInterface::EMAIL_SENT => 0,
      WebformScheduledEmailManagerInterface::EMAIL_NOT_SENT => 0,
      WebformScheduledEmailManagerInterface::EMAIL_SKIPPED => 0,
    ];
    if (empty($limit)) {
      return $stats;
    }

    [$webform, $webform_submission, $source_entity] = $this->getEntities($entity);

    // IMPORTANT: Only scheduled emails with state = ::SUBMISSION_SEND will
    // be sent.
    $query = $this->database->select('webform_scheduled_email', 'w')
      ->fields('w', ['eid', 'sid', 'webform_id', 'entity_type', 'entity_id', 'handler_id', 'send'])
      ->condition('w.state', WebformScheduledEmailManagerInterface::SUBMISSION_SEND)
      ->condition('w.send', time(), '<')
      ->orderBy('w.send')
      ->range(0, $limit);
    $this->addQueryConditions($query, $webform, $webform_submission, $source_entity, $handler_id);

    // Reset $webform, $webform_submission, and $handler_id so that they
    // can be safely used below.
    $webform = NULL;
    $webform_submission = NULL;
    $handler_id = NULL;

    // Get pending emails.
    $result = $query->execute();

    $eids = [];
    foreach ($result as $record) {
      $sid = $record->sid;
      $webform_id = $record->webform_id;
      $handler_id = $record->handler_id;

      $eids[] = $record->eid;

      /** @var \Drupal\webform\WebformSubmissionInterface $webform_submission */
      $webform_submission = $this->getSubmissionStorage()->load($sid);
      // This should rarely happen and the orphaned record will be deleted.
      if (!$webform_submission) {
        continue;
      }

      $webform = $webform_submission->getWebform();

      /** @var \Drupal\webform_scheduled_email\Plugin\WebformHandler\ScheduleEmailWebformHandler $handler */
      $handler = $webform_submission->getWebform()->getHandler($handler_id);
      // This should rarely happen and the orphaned record will be deleted.
      if (!$handler) {
        continue;
      }

      if ($handler->isDisabled()) {
        // Disable sending email.
        $action = $this->t('skipped (disabled)');
        $operation = $this->t('scheduled email disabled');
        $stat = WebformScheduledEmailManagerInterface::EMAIL_SKIPPED;
      }
      elseif (!$handler->checkConditions($webform_submission)) {
        // Skip sending email.
        $action = $this->t('skipped (conditions not met)');
        $operation = $this->t('scheduled email skipped');
        $stat = WebformScheduledEmailManagerInterface::EMAIL_SKIPPED;
      }
      else {
        // Switch to submission language.
        $original_language = $this->languageManager->getConfigOverrideLanguage();
        $switch_languages = ($webform_submission->language()->getId() !== $original_language->getId());
        if ($switch_languages) {
          $this->languageManager->setConfigOverrideLanguage($webform_submission->language());
          // Reset the webform, submission, and handler.
          $this->getWebformStorage()->resetCache([$webform_id]);
          $this->getSubmissionStorage()->resetCache([$sid]);
          // Reload the webform, submission, and handler.
          $webform = $this->getWebformStorage()->load($webform_id);
          $webform_submission = $this->getSubmissionStorage()->load($sid);
          $handler = $webform->getHandler($handler_id);
        }

        // Send (translated) email.
        $message = $handler->getMessage($webform_submission);
        $status = $handler->sendMessage($webform_submission, $message);

        // Switch back to original language.
        if ($switch_languages) {
          $this->languageManager->setConfigOverrideLanguage($original_language);
          // Reset the webform, submission, and handler.
          $this->getWebformStorage()->resetCache([$webform_id]);
          $this->getSubmissionStorage()->resetCache([$sid]);
          // Reload the webform, submission, and handler.
          $webform = $this->getWebformStorage()->load($webform_id);
          $webform_submission = $this->getSubmissionStorage()->load($sid);
          $handler = $webform->getHandler($handler_id);
        }

        $action = ($status) ? $this->t('sent') : $this->t('not sent');
        $operation = ($status) ? $this->t('scheduled email sent') : $this->t('scheduled email not sent');
        $stat = ($status) ? WebformScheduledEmailManagerInterface::EMAIL_SENT : WebformScheduledEmailManagerInterface::EMAIL_NOT_SENT;
      }

      $channel = ($webform->hasSubmissionLog()) ? 'webform_submission' : 'webform';
      $context = [
        '@title' => $webform_submission->label(),
        '@action' => $action,
        '@handler' => $handler->label(),
        'link' => $webform_submission->toLink($this->t('View'))->toString(),
        'webform_submission' => $webform_submission,
        'handler_id' => $handler_id,
        'operation' => $operation,
      ];
      $this->getLogger($channel)->notice('Scheduled email @action for @handler handler.', $context);

      // Increment stat.
      $stats[$stat]++;
    }

    // Delete sent emails from table.
    if ($eids) {
      $this->database->delete('webform_scheduled_email')
        ->condition('eid', $eids, 'IN')
        ->execute();
    }

    return $stats;
  }

  /* ************************************************************************ */
  // Statistic/tracking functions.
  /* ************************************************************************ */

  /**
   * {@inheritdoc}
   */
  public function stats(EntityInterface $entity = NULL, $handler_id = NULL) {
    return [
      WebformScheduledEmailManagerInterface::SUBMISSION_WAITING => $this->waiting($entity, $handler_id),
      WebformScheduledEmailManagerInterface::SUBMISSION_QUEUED => $this->queued($entity, $handler_id),
      WebformScheduledEmailManagerInterface::SUBMISSION_READY => $this->ready($entity, $handler_id),
      WebformScheduledEmailManagerInterface::SUBMISSION_TOTAL => $this->total($entity, $handler_id),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function waiting(EntityInterface $entity = NULL, $handler_id = NULL) {
    return $this->total($entity, $handler_id, WebformScheduledEmailManagerInterface::SUBMISSION_WAITING);
  }

  /**
   * {@inheritdoc}
   */
  public function queued(EntityInterface $entity = NULL, $handler_id = NULL) {
    return $this->total($entity, $handler_id, WebformScheduledEmailManagerInterface::SUBMISSION_QUEUED);
  }

  /**
   * {@inheritdoc}
   */
  public function ready(EntityInterface $entity = NULL, $handler_id = NULL) {
    return $this->total($entity, $handler_id, WebformScheduledEmailManagerInterface::SUBMISSION_READY);
  }

  /**
   * {@inheritdoc}
   */
  public function total(EntityInterface $entity = NULL, $handler_id = NULL, $state = FALSE) {
    [$webform, $webform_submission, $source_entity] = $this->getEntities($entity);

    $query = $this->database->select('webform_scheduled_email', 'w');
    $this->addQueryConditions($query, $webform, $webform_submission, $source_entity, $handler_id, $state);
    return $query->countQuery()->execute()->fetchField();
  }

  /* ************************************************************************ */
  // Helper functions.
  /* ************************************************************************ */

  /**
   * Get webform or webform_submission logger.
   *
   * @param string $channel
   *   The logger channel. Defaults to 'webform'.
   *
   * @return \Drupal\Core\Logger\LoggerChannelInterface
   *   Webform logger
   */
  protected function getLogger($channel = 'webform') {
    return $this->loggerFactory->get($channel);
  }

  /**
   * Inspects an entity and returns the associates webform, webform submission, and/or source entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface|null $entity
   *   A webform, webform submission, or source entity.
   *
   * @return array
   *   An array containing webform, webform submission, and source entity.
   */
  protected function getEntities(EntityInterface $entity = NULL) {
    $webform = NULL;
    $webform_submission = NULL;
    $source_entity = NULL;

    if ($entity instanceof WebformInterface) {
      $webform = $entity;
    }
    elseif ($entity instanceof WebformSubmissionInterface) {
      $webform_submission = $entity;
      $webform = $webform_submission->getWebform();
    }
    elseif ($entity instanceof EntityInterface) {
      $source_entity = $entity;
      $webform = $this->entityReferenceManager->getWebform($source_entity);
    }

    return [$webform, $webform_submission, $source_entity];
  }

  /**
   * Add condition to scheduled email query.
   *
   * @param \Drupal\Core\Database\Query\AlterableInterface|\Drupal\Core\Database\Query\Delete $query
   *   The query instance.
   * @param \Drupal\webform\WebformInterface|null $webform
   *   A webform.
   * @param \Drupal\webform\WebformSubmissionInterface|null $webform_submission
   *   A webform submission.
   * @param \Drupal\Core\Entity\EntityInterface|null $source_entity
   *   A source entity.
   * @param string|null $handler_id
   *   A webform handler id.
   * @param string|null $state
   *   The state of the scheduled emails.
   */
  protected function addQueryConditions($query, WebformInterface $webform = NULL, WebformSubmissionInterface $webform_submission = NULL, EntityInterface $source_entity = NULL, $handler_id = NULL, $state = NULL) {
    $prefix = ($query instanceof QueryDelete) ? '' : 'w.';

    if ($webform) {
      $query->condition($prefix . 'webform_id', $webform->id());
    }

    if ($webform_submission) {
      $query->condition($prefix . 'sid', $webform_submission->id());
    }

    if ($source_entity) {
      $query->condition($prefix . 'entity_type', $source_entity->getEntityTypeId());
      $query->condition($prefix . 'entity_id', $source_entity->id());
    }

    if ($handler_id && ($webform || $webform_submission)) {
      $query->condition($prefix . 'handler_id', $handler_id);
    }

    switch ($state) {
      case WebformScheduledEmailManagerInterface::SUBMISSION_SCHEDULE:
      case WebformScheduledEmailManagerInterface::SUBMISSION_UNSCHEDULE:
      case WebformScheduledEmailManagerInterface::SUBMISSION_RESCHEDULE:
      case WebformScheduledEmailManagerInterface::SUBMISSION_SEND:
        $query->condition($prefix . 'state', $state);
        break;

      case WebformScheduledEmailManagerInterface::SUBMISSION_WAITING:
        $query->condition($prefix . 'state', WebformScheduledEmailManagerInterface::SUBMISSION_SEND, '<>');
        break;

      case WebformScheduledEmailManagerInterface::SUBMISSION_QUEUED:
      case WebformScheduledEmailManagerInterface::SUBMISSION_READY:
        $query->condition($prefix . 'state', WebformScheduledEmailManagerInterface::SUBMISSION_SEND);
        $query->isNotNull($prefix . 'send');
        $query->condition($prefix . 'send', time(), ($state === WebformScheduledEmailManagerInterface::SUBMISSION_QUEUED) ? '>=' : '<');
        $query->condition($prefix . 'state', WebformScheduledEmailManagerInterface::SUBMISSION_SEND);
        break;
    }
  }

}
