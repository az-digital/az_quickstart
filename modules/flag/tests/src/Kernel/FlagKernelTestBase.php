<?php

declare(strict_types=1);

namespace Drupal\Tests\flag\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\flag\Traits\FlagCreateTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\flag\FlagInterface;

/**
 * Basic setup for kernel tests based around flaggings articles.
 */
abstract class FlagKernelTestBase extends KernelTestBase {

  use FlagCreateTrait;
  use UserCreationTrait;

  /**
   * The flag service.
   *
   * @var \Drupal\flag\FlagServiceInterface
   */
  protected $flagService;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'field',
    'filter',
    'flag',
    'node',
    'text',
    'user',
    'system',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installEntitySchema('flagging');
    $this->installSchema('flag', ['flag_counts']);
    $this->installSchema('node', ['node_access']);
    $this->installConfig(['filter', 'flag', 'node']);

    $this->flagService = \Drupal::service('flag');
  }

  /**
   * Get all flaggings for the given flag.
   *
   * @param \Drupal\flag\FlagInterface $flag
   *   The flag entity.
   *
   * @return \Drupal\flag\FlaggingInterface[]
   *   An array of flaggings.
   */
  protected function getFlagFlaggings(FlagInterface $flag) {
    $query = \Drupal::entityQuery('flagging');
    $query->accessCheck();
    $query->condition('flag_id', $flag->id());
    $ids = $query->execute();

    return \Drupal::entityTypeManager()->getStorage('flagging')->loadMultiple($ids);
  }

}
