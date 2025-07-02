<?php

namespace Drupal\externalauth\Plugin\migrate\source;

use Drupal\Core\Database\Query\SelectInterface;
use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Drupal authmap source from database.
 *
 * @MigrateSource(
 *   id = "authmap",
 *   source_module = "user"
 * )
 */
class Authmap extends DrupalSqlBase {

  /**
   * {@inheritdoc}
   */
  public function query(): SelectInterface {
    return $this->select('authmap', 'a')->fields('a');
  }

  /**
   * {@inheritdoc}
   */
  public function fields(): array {
    return [
      'uid' => $this->t('User’s users.uid.'),
      'authname' => $this->t('Unique authentication name.'),
      'module' => $this->t('Module which is controlling the authentication.'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getIds(): array {
    return [
      'uid' => [
        'type' => 'integer',
      ],
    ];
  }

}
