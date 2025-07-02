<?php

declare(strict_types = 1);

namespace Drupal\migrate_example\Plugin\migrate\source;

use Drupal\Core\Database\Query\SelectInterface;
use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Row;

/**
 * Source plugin for beer user accounts.
 *
 * @MigrateSource(
 *   id = "beer_user"
 * )
 */
final class BeerUser extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query(): SelectInterface {
    $fields = [
      'aid',
      'status',
      'registered',
      'username',
      'nickname',
      'password',
      'email',
      'sex',
      'beers',
    ];
    return $this->select('migrate_example_beer_account', 'mea')
      ->fields('mea', $fields);
  }

  /**
   * {@inheritdoc}
   */
  public function fields(): array {
    return [
      'aid' => $this->t('Account ID'),
      'status' => $this->t('Blocked/Allowed'),
      'registered' => $this->t('Registered date'),
      'username' => $this->t('Account name (for login)'),
      'nickname' => $this->t('Account name (for display)'),
      'password' => $this->t('Account password (raw)'),
      'email' => $this->t('Account email'),
      'sex' => $this->t('Gender'),
      'beers' => $this->t('Favorite beers, pipe-separated'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getIds(): array {
    return [
      'aid' => [
        'type' => 'integer',
        'alias' => 'mea',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row): bool {
    // A prepareRow() is the most common place to perform custom run-time
    // processing that isn't handled by an existing process plugin. It is called
    // when the raw data has been pulled from the source, and provides the
    // opportunity to modify or add to that data, creating the canonical set of
    // source data that will be fed into the processing pipeline.
    // In our particular case, the list of a user's favorite beers is a pipe-
    // separated list of beer IDs. The processing pipeline deals with arrays
    // representing multi-value fields naturally, so we want to explode that
    // string to an array of individual beer IDs.
    if ($value = $row->getSourceProperty('beers')) {
      $row->setSourceProperty('beers', explode('|', $value));
    }
    // Always call your parent! Essential processing is performed in the base
    // class. Be mindful that prepareRow() returns a boolean status - if FALSE
    // that indicates that the item being processed should be skipped. Unless
    // we're deciding to skip an item ourselves, let the parent class decide.
    return parent::prepareRow($row);
  }

}
