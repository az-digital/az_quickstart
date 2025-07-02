<?php

declare(strict_types=1);

namespace Drupal\Tests\easy_breadcrumb\Kernel;

use Drupal\Core\Routing\NullRouteMatch;
use Drupal\Core\Routing\RequestContext;
use Drupal\Core\Routing\RouteMatch;
use Drupal\Core\Url;
use Drupal\KernelTests\KernelTestBase;
use Drupal\easy_breadcrumb\EasyBreadcrumbBuilder;
use Drupal\easy_breadcrumb\EasyBreadcrumbConstants;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

/**
 * Tests the easy breadcrumb builder.
 *
 * @group easy_breadcrumb
 */
class EasyBreadcrumbBuilderTest extends KernelTestBase {
  /**
   * {@inheritdoc}
   */
  protected static $modules = ['easy_breadcrumb', 'system', 'easy_breadcrumb_test'];

  /**
   * Tests the front page with an invalid path.
   */
  public function testFrontpageWithInvalidPaths() {
    \Drupal::configFactory()->getEditable(EasyBreadcrumbConstants::MODULE_SETTINGS)
      ->set('include_invalid_paths', TRUE)
      ->set('include_title_segment', TRUE)
      ->save();
    \Drupal::configFactory()->getEditable('system.site')
      ->set('page.front', '/path')
      ->save();

    $request_context = new RequestContext();

    $breadcrumb_builder = new EasyBreadcrumbBuilder($request_context,
      \Drupal::service('access_manager'),
      \Drupal::service('router'),
      \Drupal::service('request_stack'),
      \Drupal::service('path_processor_manager'),
      \Drupal::service('config.factory'),
      \Drupal::service('easy_breadcrumb.title_resolver'),
      \Drupal::service('current_user'),
      \Drupal::service('path.current'),
      \Drupal::service('plugin.manager.menu.link'),
      \Drupal::service('language_manager'),
      \Drupal::service('entity_type.manager'),
      \Drupal::service('entity.repository'),
      \Drupal::service('logger.factory'),
      \Drupal::service('messenger'),
      \Drupal::service('module_handler'),
      \Drupal::service('path.matcher')
    );

    $route_match = new RouteMatch('test_front', new Route('/front'));
    $result = $breadcrumb_builder->build($route_match);
    $this->assertCount(0, $result->getLinks());
  }

  /**
   * Provides data for the get title string test.
   */
  public static function providerTestGetTitleString() {
    return [
      ['easy_breadcrumb_test.title_string'],
      ['easy_breadcrumb_test.title_formattable_markup'],
      ['easy_breadcrumb_test.title_markup'],
      ['easy_breadcrumb_test.title_translatable_markup'],
      ['easy_breadcrumb_test.title_render_array'],
    ];
  }

  /**
   * Tests getting title string from the various ways route titles can be set.
   *
   * @param string $route_name
   *   The route to test.
   *
   * @dataProvider providerTestGetTitleString
   */
  public function testGetTitleString($route_name) {
    $url = Url::fromRoute($route_name);
    $request_context = new RequestContext();

    $breadcrumb_builder = new EasyBreadcrumbBuilder($request_context,
      \Drupal::service('access_manager'),
      \Drupal::service('router'),
      \Drupal::service('request_stack'),
      \Drupal::service('path_processor_manager'),
      \Drupal::service('config.factory'),
      \Drupal::service('easy_breadcrumb.title_resolver'),
      \Drupal::service('current_user'),
      \Drupal::service('path.current'),
      \Drupal::service('plugin.manager.menu.link'),
      \Drupal::service('language_manager'),
      \Drupal::service('entity_type.manager'),
      \Drupal::service('entity.repository'),
      \Drupal::service('logger.factory'),
      \Drupal::service('messenger'),
      \Drupal::service('module_handler'),
      \Drupal::service('path.matcher')
    );

    $request = Request::create('/' . $url->getInternalPath());
    $router = \Drupal::service('router.no_access_checks');
    $route_match = new RouteMatch($route_name, $router->match($url->getInternalPath())['_route_object']);
    $result = $breadcrumb_builder->getTitleString($request, $route_match, []);
    $this->assertIsString($result);
  }

  /**
   * Tests a custom path override with an unrouted URL. (Issue #3480899)
   */
  public function testCustomPathWithUnroutedUrl() {
    \Drupal::configFactory()->getEditable(EasyBreadcrumbConstants::MODULE_SETTINGS)
      ->set(EasyBreadcrumbConstants::CUSTOM_PATHS, '/test/easy-breadcrumb-custom-path :: Part 1 | /part-1')
      ->save();

    $request = Request::create('/test/easy-breadcrumb-custom-path');
    $request_context = new RequestContext();
    $request_context->fromRequest($request);

    $breadcrumb_builder = new EasyBreadcrumbBuilder($request_context,
      \Drupal::service('access_manager'),
      \Drupal::service('router'),
      \Drupal::service('request_stack'),
      \Drupal::service('path_processor_manager'),
      \Drupal::service('config.factory'),
      \Drupal::service('easy_breadcrumb.title_resolver'),
      \Drupal::service('current_user'),
      \Drupal::service('path.current'),
      \Drupal::service('plugin.manager.menu.link'),
      \Drupal::service('language_manager'),
      \Drupal::service('entity_type.manager'),
      \Drupal::service('entity.repository'),
      \Drupal::service('logger.factory'),
      \Drupal::service('messenger'),
      \Drupal::service('module_handler'),
      \Drupal::service('path.matcher')
    );

    $result = $breadcrumb_builder->build(new NullRouteMatch());
    $this->assertCount(1, $result->getLinks());
    $this->assertEquals('Part 1', $result->getLinks()[0]->getText());
    $this->assertEquals('base:part-1', $result->getLinks()[0]->getUrl()->toUriString());
  }

  /**
   * Tests a custom path override with a route match. (Issue #3480899)
   */
  public function testCustomPathWithRoutedUrl() {
    \Drupal::configFactory()->getEditable(EasyBreadcrumbConstants::MODULE_SETTINGS)
      ->set(EasyBreadcrumbConstants::CUSTOM_PATHS, '/test/easy-breadcrumb-custom-path :: Part 1 | /part-1')
      ->save();

    $route_name = 'easy_breadcrumb_test.custom_path';

    $url = Url::fromRoute($route_name);
    $request_context = new RequestContext();

    $breadcrumb_builder = new EasyBreadcrumbBuilder($request_context,
      \Drupal::service('access_manager'),
      \Drupal::service('router'),
      \Drupal::service('request_stack'),
      \Drupal::service('path_processor_manager'),
      \Drupal::service('config.factory'),
      \Drupal::service('easy_breadcrumb.title_resolver'),
      \Drupal::service('current_user'),
      \Drupal::service('path.current'),
      \Drupal::service('plugin.manager.menu.link'),
      \Drupal::service('language_manager'),
      \Drupal::service('entity_type.manager'),
      \Drupal::service('entity.repository'),
      \Drupal::service('logger.factory'),
      \Drupal::service('messenger'),
      \Drupal::service('module_handler'),
      \Drupal::service('path.matcher')
    );

    $router = \Drupal::service('router.no_access_checks');
    $route_match = new RouteMatch($route_name, $router->match($url->getInternalPath())['_route_object']);
    $result = $breadcrumb_builder->build($route_match);
    $this->assertCount(1, $result->getLinks());
    $this->assertEquals('Part 1', $result->getLinks()[0]->getText());
    $this->assertEquals('base:part-1', $result->getLinks()[0]->getUrl()->toUriString());
  }

  /**
   * Tests a custom path override with regex.
   */
  public function testCustomPathWithRegex() {
    \Drupal::configFactory()->getEditable(EasyBreadcrumbConstants::MODULE_SETTINGS)
      ->set(EasyBreadcrumbConstants::CUSTOM_PATHS, 'regex!/test/.+ :: Part 1 | /part-1')
      ->save();

    $request = Request::create('/test/easy-breadcrumb-custom-path');
    $request_context = new RequestContext();
    $request_context->fromRequest($request);

    $breadcrumb_builder = new EasyBreadcrumbBuilder($request_context,
      \Drupal::service('access_manager'),
      \Drupal::service('router'),
      \Drupal::service('request_stack'),
      \Drupal::service('path_processor_manager'),
      \Drupal::service('config.factory'),
      \Drupal::service('easy_breadcrumb.title_resolver'),
      \Drupal::service('current_user'),
      \Drupal::service('path.current'),
      \Drupal::service('plugin.manager.menu.link'),
      \Drupal::service('language_manager'),
      \Drupal::service('entity_type.manager'),
      \Drupal::service('entity.repository'),
      \Drupal::service('logger.factory'),
      \Drupal::service('messenger'),
      \Drupal::service('module_handler'),
      \Drupal::service('path.matcher')
    );

    $result = $breadcrumb_builder->build(new NullRouteMatch());
    $this->assertCount(1, $result->getLinks());
    $this->assertEquals('Part 1', $result->getLinks()[0]->getText());
    $this->assertEquals('base:part-1', $result->getLinks()[0]->getUrl()->toUriString());
  }

    /**
   * Tests a custom path override with title replacement and an unrouted url. (Issue #3271576)
   */
  public function testCustomPathWithTitleAndUnroutedUrl() {
    \Drupal::configFactory()->getEditable(EasyBreadcrumbConstants::MODULE_SETTINGS)
      ->set(EasyBreadcrumbConstants::CUSTOM_PATHS, 'regex!/test/.+ :: Part 1 | /part-1 :: <title>')
      ->set(EasyBreadcrumbConstants::TITLE_FROM_PAGE_WHEN_AVAILABLE, true)
      ->save();

    $request = Request::create('/test/easy-breadcrumb-custom-path');
    $request_context = new RequestContext();
    $request_context->fromRequest($request);

    $breadcrumb_builder = new EasyBreadcrumbBuilder($request_context,
      \Drupal::service('access_manager'),
      \Drupal::service('router'),
      \Drupal::service('request_stack'),
      \Drupal::service('path_processor_manager'),
      \Drupal::service('config.factory'),
      \Drupal::service('easy_breadcrumb.title_resolver'),
      \Drupal::service('current_user'),
      \Drupal::service('path.current'),
      \Drupal::service('plugin.manager.menu.link'),
      \Drupal::service('language_manager'),
      \Drupal::service('entity_type.manager'),
      \Drupal::service('entity.repository'),
      \Drupal::service('logger.factory'),
      \Drupal::service('messenger'),
      \Drupal::service('module_handler'),
      \Drupal::service('path.matcher')
    );

    $result = $breadcrumb_builder->build(new NullRouteMatch());
    $this->assertCount(2, $result->getLinks());
    $this->assertEquals('Part 1', $result->getLinks()[0]->getText());
    $this->assertEquals('base:part-1', $result->getLinks()[0]->getUrl()->toUriString());
    $this->assertEquals('Easy Breadcrumb Custom Path Test', $result->getLinks()[1]->getText());
    $this->assertEquals('route:<none>', $result->getLinks()[1]->getUrl()->toUriString());
  }

  /**
   * Tests a custom path override with title replacement and a route match. (Issue #3271576)
   */
  public function testCustomPathWithTitleAndRoutedUrl() {
    \Drupal::configFactory()->getEditable(EasyBreadcrumbConstants::MODULE_SETTINGS)
      ->set(EasyBreadcrumbConstants::CUSTOM_PATHS, 'regex!/test/.+ :: Part 1 | /part-1 :: <title>')
      ->set(EasyBreadcrumbConstants::TITLE_FROM_PAGE_WHEN_AVAILABLE, true)
      ->save();

    $route_name = 'easy_breadcrumb_test.custom_path';

    $url = Url::fromRoute($route_name);
    $request_context = new RequestContext();

    $breadcrumb_builder = new EasyBreadcrumbBuilder($request_context,
      \Drupal::service('access_manager'),
      \Drupal::service('router'),
      \Drupal::service('request_stack'),
      \Drupal::service('path_processor_manager'),
      \Drupal::service('config.factory'),
      \Drupal::service('easy_breadcrumb.title_resolver'),
      \Drupal::service('current_user'),
      \Drupal::service('path.current'),
      \Drupal::service('plugin.manager.menu.link'),
      \Drupal::service('language_manager'),
      \Drupal::service('entity_type.manager'),
      \Drupal::service('entity.repository'),
      \Drupal::service('logger.factory'),
      \Drupal::service('messenger'),
      \Drupal::service('module_handler'),
      \Drupal::service('path.matcher')
    );

    $router = \Drupal::service('router.no_access_checks');
    $route_match = new RouteMatch($route_name, $router->match($url->getInternalPath())['_route_object']);
    $result = $breadcrumb_builder->build($route_match);
    $this->assertCount(2, $result->getLinks());
    $this->assertEquals('Part 1', $result->getLinks()[0]->getText());
    $this->assertEquals('base:part-1', $result->getLinks()[0]->getUrl()->toUriString());
    $this->assertEquals('Easy Breadcrumb Custom Path Test', $result->getLinks()[1]->getText());
    $this->assertEquals('route:<none>', $result->getLinks()[1]->getUrl()->toUriString());
  }
}
