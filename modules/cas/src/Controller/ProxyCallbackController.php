<?php

namespace Drupal\cas\Controller;

use Drupal\cas\Service\CasHelper;
use Drupal\Core\Database\Connection;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Psr\Log\LogLevel;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

/**
 * Provides a controller for the 'cas.proxyCallback' route.
 */
class ProxyCallbackController implements ContainerInjectionInterface {

  /**
   * Used when inserting the CAS PGT into the database.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Used to get params from the current request object.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Used for logging.
   *
   * @var \Drupal\cas\Service\CasHelper
   */
  protected $casHelper;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Database\Connection $database_connection
   *   The database service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The Symfony request stack.
   * @param \Drupal\cas\Service\CasHelper $cas_helper
   *   The CasHelper.
   */
  public function __construct(Connection $database_connection, RequestStack $request_stack, CasHelper $cas_helper) {
    $this->connection = $database_connection;
    $this->requestStack = $request_stack;
    $this->casHelper = $cas_helper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('database'), $container->get('request_stack'), $container->get('cas.helper'));
  }

  /**
   * Route callback for the ProxyGrantingTicket information.
   *
   * This function stores the incoming PGTIOU and pgtId parameters so that
   * the incoming response from the CAS Server can be looked up.
   */
  public function callback() {
    $this->casHelper->log(LogLevel::DEBUG, 'Proxy callback processing started.');

    // @todo Check that request is coming from configured CAS server to avoid
    // filling up the table with bogus pgt values.
    $request = $this->requestStack->getCurrentRequest();

    // Check for both a pgtIou and pgtId parameter. If either is not present,
    // inform CAS Server of an error.
    if (!($request->query->get('pgtId') && $request->query->get('pgtIou'))) {
      $this->casHelper->log(LogLevel::ERROR, "Either pgtId or pgtIou parameters are missing from the request.");
      return new Response('Missing necessary parameters', 400);
    }
    else {
      // Store the pgtIou and pgtId in the database for later use.
      $pgt_id = $request->query->get('pgtId');
      $pgt_iou = $request->query->get('pgtIou');
      $this->storePgtMapping($pgt_iou, $pgt_id);
      $this->casHelper->log(
        LogLevel::DEBUG,
        "Storing pgtId %pgt_id with pgtIou %pgt_iou",
        ['%pgt_id' => $pgt_id, '%pgt_iou' => $pgt_iou]
      );
      // PGT stored properly, tell CAS Server to proceed.
      return new Response('OK', 200);
    }
  }

  /**
   * Store the pgtIou to pgtId mapping in the database.
   *
   * @param string $pgt_iou
   *   The pgtIou from CAS Server.
   * @param string $pgt_id
   *   The pgtId from the CAS server.
   *
   * @codeCoverageIgnore
   */
  protected function storePgtMapping($pgt_iou, $pgt_id) {
    $this->connection->insert('cas_pgt_storage')
      ->fields(
        ['pgt_iou', 'pgt', 'timestamp'],
        [$pgt_iou, $pgt_id, time()]
      )
      ->execute();
  }

}
