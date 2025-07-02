<?php

declare(strict_types=1);

namespace Drupal\Tests\google_tag\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Base class for google_tag tests.
 */
abstract class GoogleTagTestCase extends KernelTestBase {

  use AssertGoogleTagTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'path_alias',
    'user',
    'google_tag',
  ];

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    // With respect to the change record https://www.drupal.org/node/3394444,
    // it is required to register path_alias entity schema in kernel tests.
    if (version_compare(\Drupal::VERSION, '10.3', '>=')) {
      $this->installEntitySchema('path_alias');
    }
    $this->installConfig(['system', 'user']);
  }

  /**
   * Sends a request to drupal kernel and builds the response.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Request.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   Response.
   *
   * @throws \Exception
   */
  protected function doRequest(Request $request): Response {
    $response = $this->container->get('http_kernel')->handle($request);
    $content = $response->getContent();
    self::assertNotFalse($content);
    $this->setRawContent($content);
    return $response;
  }

  /**
   * Loads the fixture file into database.
   *
   * @param string $fixture_file
   *   Fixture file path.
   * @param string $module_name
   *   Module providing the fixture file.
   *
   * @throws \Exception
   */
  protected function loadFixture(string $fixture_file, string $module_name = 'google_tag'): void {
    $module_path = $this->container->get('module_handler')
      ->getModule($module_name)
      ->getPath();
    $path = sprintf('%s/tests/fixtures/%s', $module_path, $fixture_file);
    require $path;
  }

}
