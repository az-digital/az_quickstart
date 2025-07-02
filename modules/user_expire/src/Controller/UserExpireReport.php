<?php

namespace Drupal\user_expire\Controller;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Render\RendererInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Report controller of User Expire module.
 */
class UserExpireReport extends ControllerBase {

  /**
   * The database service.
   */
  protected Connection $database;

  /**
   * The date formatter service.
   */
  protected DateFormatterInterface $dateFormatter;

  /**
   * The time service.
   */
  protected TimeInterface $time;

  /**
   * The renderer.
   */
  protected RendererInterface $renderer;

  /**
   * Constructs a \Drupal\user_expire\Controller object.
   *
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\Core\Database\Connection $database
   *   The database service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   */
  public function __construct(DateFormatterInterface $date_formatter, Connection $database, TimeInterface $time, RendererInterface $renderer) {
    $this->dateFormatter = $date_formatter;
    $this->database = $database;
    $this->time = $time;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('date.formatter'),
      $container->get('database'),
      $container->get('datetime.time'),
      $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function listOfUsers() {
    $header = [
      'username' => [
        'data' => $this->t('Username'),
        'field' => 'u.name',
      ],
      'access' => [
        'data' => $this->t('Last access'),
        'field' => 'u.access',
      ],
      'expiration' => [
        'data' => $this->t('Expiration'),
        'field' => 'expiration',
        'sort' => 'asc',
      ],
    ];
    $rows = [];

    $query = $this->database->select('user_expire', 'ue');
    $query->join('users_field_data', 'u', 'ue.uid = u.uid');

    $query = $query->extend('Drupal\Core\Database\Query\PagerSelectExtender')->extend('Drupal\Core\Database\Query\TableSortExtender');
    $query
      ->fields('u', ['uid', 'name', 'access'])
      ->fields('ue', ['expiration'])
      ->limit(50)
      ->orderByHeader($header);

    $accounts = $query->execute();

    foreach ($accounts as $account) {
      $username = [
        '#theme' => 'username',
        '#account' => $this->entityTypeManager()->getStorage('user')->load($account->uid),
      ];

      $rows[$account->uid] = [
        'username' => $this->renderer->render($username),
        'access' => $account->access ? $this->t('@time ago', ['@time' => $this->dateFormatter->formatInterval($this->time->getRequestTime() - $account->access)]) : $this->t('never'),
        'expiration' => $this->t('@time from now', ['@time' => $this->dateFormatter->formatInterval($account->expiration - $this->time->getRequestTime())]),
      ];
    }

    $table = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
    ];

    return $table;
  }

}
