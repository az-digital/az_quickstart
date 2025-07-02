<?php

declare(strict_types=1);

namespace Drupal\masquerade_log;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Url;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Wraps a logger service in order to log also the original user.
 */
class MasqueradeLogLogger implements LoggerInterface {

  use LoggerTrait;

  /**
   * The decorated service.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $originalService;

  /**
   * The session.
   *
   * @var \Symfony\Component\HttpFoundation\Session\SessionInterface
   */
  protected $session;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new decorator service.
   *
   * @param \Psr\Log\LoggerInterface $original_service
   *   The decorated service.
   * @param \Symfony\Component\HttpFoundation\Session\SessionInterface $session
   *   The session.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(LoggerInterface $original_service, SessionInterface $session, EntityTypeManagerInterface $entity_type_manager) {
    $this->originalService = $original_service;
    $this->session = $session;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function log($level, $message, array $context = []): void {
    // Access MetadataBag to check masquerading.
    $metadata_bag = $this->session->getMetadataBag();
    if (method_exists($metadata_bag, 'getMasquerade') && $original_uid = $metadata_bag->getMasquerade()) {
      $user_storage = $this->entityTypeManager->getStorage('user');
      $original_account = $user_storage->load($original_uid);
      // Ensure a proper log message with masquerade details.
      $path = Url::fromRoute('user.page')->toString();
      $message .= ' <p>[masquerading <a href="' . $path . '/@original_uid">@original_username</a>, uid @original_uid]</p>';
      $context['@original_uid'] = $original_uid;
      $context['@original_username'] = $original_account->label();
    }
    $this->originalService->log($level, $message, $context);
  }

  /**
   * {@inheritdoc}
   */
  public function __call(string $method, array $args) {
    return $this->originalService->{$method}(...$args);
  }

}
