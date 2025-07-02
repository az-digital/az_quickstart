<?php

namespace Drupal\webform_submission_log\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\webform\EntityStorage\WebformEntityStorageTrait;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformSubmissionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for webform submission log routes.
 *
 * Copied from: \Drupal\dblog\Controller\DbLogController.
 */
class WebformSubmissionLogController extends ControllerBase {

  use WebformEntityStorageTrait;

  /**
   * The database service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The webform request handler.
   *
   * @var \Drupal\webform\WebformRequestInterface
   */
  protected $requestHandler;

  /**
   * The webform submission log manager.
   *
   * @var \Drupal\webform_submission_log\WebformSubmissionLogManagerInterface
   */
  protected $logManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    /** @var \Drupal\webform_submission_log\Controller\WebformSubmissionLogController $instance */
    $instance = parent::create($container);
    $instance->database = $container->get('database');
    $instance->dateFormatter = $container->get('date.formatter');
    $instance->requestHandler = $container->get('webform.request');
    $instance->logManager = $container->get('webform_submission_log.manager');
    $instance->entityTypeManager = $container->get('entity_type.manager');
    return $instance;
  }

  /**
   * Displays a listing of webform submission log messages.
   *
   * @param \Drupal\webform\WebformInterface|null $webform
   *   A webform.
   * @param \Drupal\webform\WebformSubmissionInterface|null $webform_submission
   *   A webform submission.
   * @param \Drupal\Core\Entity\EntityInterface|null $source_entity
   *   A source entity.
   * @param \Drupal\Core\Session\AccountInterface|null $account
   *   A user account.
   *
   * @return array
   *   A render array as expected by drupal_render().
   */
  public function overview(WebformInterface $webform = NULL, WebformSubmissionInterface $webform_submission = NULL, EntityInterface $source_entity = NULL, AccountInterface $account = NULL) {
    // Entities.
    if (empty($webform) && !empty($webform_submission)) {
      $webform = $webform_submission->getWebform();
    }
    if (empty($source_entity) && !empty($webform_submission)) {
      $source_entity = $webform_submission->getSourceEntity();
    }
    $webform_entity = $webform_submission ?: $webform;

    // Header.
    $header = [];
    $header['lid'] = ['data' => $this->t('#'), 'field' => 'log.lid', 'sort' => 'desc'];
    if (empty($webform)) {
      $header['webform_id'] = ['data' => $this->t('Webform', [], ['context' => 'form']), 'field' => 'log.webform_id', 'class' => [RESPONSIVE_PRIORITY_MEDIUM]];
    }
    if (empty($source_entity) && empty($webform_submission)) {
      $header['entity'] = ['data' => $this->t('Submitted to'), 'class' => [RESPONSIVE_PRIORITY_LOW]];
    }
    if (empty($webform_submission)) {
      $header['sid'] = ['data' => $this->t('Submission'), 'field' => 'log.sid'];
    }
    $header['handler_id'] = ['data' => $this->t('Handler'), 'field' => 'log.handler_id'];
    $header['operation'] = ['data' => $this->t('Operation'), 'field' => 'log.operation', 'class' => [RESPONSIVE_PRIORITY_MEDIUM]];
    $header['message'] = ['data' => $this->t('Message'), 'field' => 'log.message', 'class' => [RESPONSIVE_PRIORITY_LOW]];
    $header['uid'] = ['data' => $this->t('User'), 'field' => 'user.name', 'class' => [RESPONSIVE_PRIORITY_LOW]];
    $header['timestamp'] = ['data' => $this->t('Date'), 'field' => 'log.timestamp', 'sort' => 'desc', 'class' => [RESPONSIVE_PRIORITY_LOW]];

    // Query.
    $options = ['header' => $header, 'limit' => 50];
    $logs = $this->logManager->loadByEntities($webform_entity, $source_entity, $account, $options);

    // Rows.
    $rows = [];
    foreach ($logs as $log) {
      $row = [];
      $row['lid'] = $log->lid;
      if (empty($webform)) {
        $log_webform = $this->getWebformStorage()->load($log->webform_id);
        $row['webform_id'] = $log_webform->toLink($log_webform->label(), 'results-log');
      }
      if (empty($source_entity) && empty($webform_submission)) {
        $entity = NULL;
        if ($log->entity_type && $log->entity_id) {
          $entity_type = $log->entity_type;
          $entity_id = $log->entity_id;
          if ($entity = $this->entityTypeManager()->getStorage($entity_type)->load($entity_id)) {
            $row['entity'] = ($entity->hasLinkTemplate('canonical')) ? $entity->toLink() : "$entity_type:$entity_id";
          }
          else {
            $row['entity'] = "$entity_type:$entity_id";
          }
        }
        else {
          $row['entity'] = '';
        }
      }
      if (empty($webform_submission)) {
        if ($log->sid) {
          $log_webform_submission = $this->getSubmissionStorage()->load($log->sid);
          $row['sid'] = [
            'data' => [
              '#type' => 'link',
              '#title' => $log->sid,
              '#url' => $this->requestHandler->getUrl($log_webform_submission, $source_entity, 'webform_submission.log'),
            ],
          ];
        }
        else {
          $row['sid'] = '';
        }
      }
      $row['handler_id'] = $log->handler_id;
      $row['operation'] = $log->operation;
      $row['message'] = [
        'data' => [
          '#markup' => $this->t($log->message, $log->variables),
        ],
      ];
      $row['uid'] = [
        'data' => [
          '#theme' => 'username',
          '#account' => $this->getEntityStorage('user')->load($log->uid),
        ],
      ];
      $row['timestamp'] = $this->dateFormatter->format($log->timestamp, 'short');

      $rows[] = $row;
    }

    $build['table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#sticky' => TRUE,
      '#empty' => $this->t('No log messages available.'),
    ];
    $build['pager'] = ['#type' => 'pager'];
    return $build;
  }

  /**
   * Wrapper that allows the $node to be used as $source_entity.
   */
  public function nodeOverview(WebformInterface $webform = NULL, WebformSubmissionInterface $webform_submission = NULL, EntityInterface $node = NULL) {
    return $this->overview($webform, $webform_submission, $node);
  }

}
