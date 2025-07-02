<?php

declare(strict_types=1);

namespace Drupal\Tests\redirect\Kernel\Migrate\d7;

use Drupal\redirect\Entity\Redirect;
use Drupal\Tests\migrate_drupal\Kernel\MigrateDrupalTestBase;

/**
 * Base for d7_path_redirect source plugin tests.
 *
 * @group redirect
 */
abstract class PathRedirectTestBase extends MigrateDrupalTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['redirect', 'link', 'path_alias'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('path_alias');
  }

  /**
   * Asserts various aspects of a redirect entity.
   *
   * @param int $id
   *   The entity ID in the form ENTITY_TYPE.BUNDLE.FIELD_NAME.
   * @param string $source_url
   *   The expected source url.
   * @param string $redirect_url
   *   The expected redirect url.
   * @param string $status_code
   *   The expected status code.
   */
  protected function assertEntity($id, $source_url, $redirect_url, $status_code) {
    /** @var \Drupal\redirect\Entity\Redirect $redirect */
    $redirect = Redirect::load($id);
    $this->assertSame($this->getMigration('d7_path_redirect')
      ->getIdMap()
      ->lookupDestinationIds([$id]), [[$redirect->id()]]);
    $this->assertSame($source_url, $redirect->getSourceUrl());
    $this->assertSame($redirect_url, $redirect->getRedirectUrl()
      ->toUriString());
    $this->assertSame($status_code, $redirect->getStatusCode());
  }

}
