<?php

declare(strict_types = 1);

namespace Drupal\migrate_example\Plugin\migrate\source;

use Drupal\Core\Database\Query\SelectInterface;
use Drupal\migrate\Plugin\migrate\source\SqlBase;

/**
 * Source plugin for beer comments.
 *
 * @MigrateSource(
 *   id = "beer_comment"
 * )
 */
final class BeerComment extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query(): SelectInterface {
    $fields = [
      'cid',
      'cid_parent',
      'name',
      'mail',
      'aid',
      'body',
      'bid',
      'subject',
    ];
    return $this->select('migrate_example_beer_comment', 'mec')
      ->fields('mec', $fields)
      ->orderBy('cid_parent', 'ASC');
  }

  /**
   * {@inheritdoc}
   */
  public function fields(): array {
    return [
      'cid' => $this->t('Comment ID'),
      'cid_parent' => $this->t('Parent comment ID in case of comment replies'),
      'name' => $this->t('Comment name (if anon)'),
      'mail' => $this->t('Comment email (if anon)'),
      'aid' => $this->t('Account ID (if any)'),
      'bid' => $this->t('Beer ID that is being commented upon'),
      'subject' => $this->t('Comment subject'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getIds(): array {
    return [
      'cid' => [
        'type' => 'integer',
        'alias' => 'mec',
      ],
    ];
  }

}
