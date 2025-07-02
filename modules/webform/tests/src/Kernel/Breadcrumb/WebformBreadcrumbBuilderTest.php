<?php

namespace Drupal\Tests\webform\Kernel\Breadcrumb;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Link;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\Container;

/**
 * Test webform breadcrumb builder.
 *
 * @see: \Drupal\Tests\forum\Unit\Breadcrumb\ForumBreadcrumbBuilderBaseTest
 * @see: \Drupal\Tests\forum\Unit\Breadcrumb\ForumNodeBreadcrumbBuilderTest
 *
 * @coversDefaultClass \Drupal\webform\Breadcrumb\WebformBreadcrumbBuilder
 *
 * @group webform
 */
class WebformBreadcrumbBuilderTest extends UnitTestCase {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * The webform request handler.
   *
   * @var \Drupal\webform\WebformRequestInterface
   */
  protected $requestHandler;

  /**
   * The translation manager.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface
   */
  protected $translationManager;

  /**
   * The webform breadcrumb builder.
   *
   * @var \Drupal\webform\Breadcrumb\WebformBreadcrumbBuilder
   */
  protected $breadcrumbBuilder;

  /**
   * Node.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $node;

  /**
   * Node with access.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $nodeAccess;

  /**
   * Webform.
   *
   * @var \Drupal\webform\WebformInterface
   */
  protected $webform;

  /**
   * Webform with access.
   *
   * @var \Drupal\webform\WebformInterface
   */
  protected $webformAccess;

  /**
   * Webform with access and is template.
   *
   * @var \Drupal\webform\WebformInterface
   */
  protected $webformTemplate;

  /**
   * Webform submission.
   *
   * @var \Drupal\webform\WebformSubmissionInterface
   */
  protected $webformSubmission;

  /**
   * Webform submission with access.
   *
   * @var \Drupal\webform\WebformSubmissionInterface
   */
  protected $webformSubmissionAccess;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->setUpMockEntities();

    // Make some test doubles.
    $this->moduleHandler = $this->createMock('Drupal\Core\Extension\ModuleHandlerInterface');
    $this->configFactory = $this->getConfigFactoryStub([
      'webform.settings' => ['ui' => ['toolbar_item' => FALSE]],
    ]);
    $this->requestHandler = $this->createMock('Drupal\webform\WebformRequestInterface');
    $this->translationManager = $this->createMock('Drupal\Core\StringTranslation\TranslationInterface');

    // Make an object to test.
    $this->breadcrumbBuilder = $this->getMockBuilder('Drupal\webform\Breadcrumb\WebformBreadcrumbBuilder')
      ->setConstructorArgs([$this->moduleHandler, $this->requestHandler, $this->translationManager, $this->configFactory])
      ->onlyMethods([])
      ->getMock();

    // Enable the webform_templates.module, so that we can testing breadcrumb
    // typing for templates.
    $this->moduleHandler->expects($this->any())
      ->method('moduleExists')
      ->with('webform_templates')
      ->will($this->returnValue(TRUE));

    // Add a translation manager for t().
    $translation_manager = $this->getStringTranslationStub();
    $property = new \ReflectionProperty('Drupal\webform\Breadcrumb\WebformBreadcrumbBuilder', 'stringTranslation');
    $property->setAccessible(TRUE);
    $property->setValue($this->breadcrumbBuilder, $translation_manager);

    // Setup mock cache context container.
    // @see \Drupal\Core\Breadcrumb\Breadcrumb
    // @see \Drupal\Core\Cache\RefinableCacheableDependencyTrait
    $cache_contexts_manager = $this->createMock('Drupal\Core\Cache\Context\CacheContextsManager');
    $cache_contexts_manager->method('assertValidTokens')->willReturn(TRUE);
    $container = new Container();
    $container->set('cache_contexts_manager', $cache_contexts_manager);
    \Drupal::setContainer($container);
  }

  /* ************************************************************************ */
  // Below test is passing locally but failing on Drupal.org.
  /* ************************************************************************ */

  /**
   * Tests WebformBreadcrumbBuilder::applies().
   *
   * @param bool $expected
   *   WebformBreadcrumbBuilder::applies() expected result.
   * @param string|null $route_name
   *   (optional) A route name.
   * @param array $parameter_map
   *   (optional) An array of parameter names and values.
   *
   * @dataProvider providerTestApplies
   * @covers ::applies
   */
  public function testApplies($expected, $route_name = NULL, array $parameter_map = []) {
    $route_match = $this->getMockRouteMatch($route_name, $parameter_map);
    $this->assertEquals($expected, $this->breadcrumbBuilder->applies($route_match));
  }

  /**
   * Provides test data for testApplies().
   *
   * @return array
   *   Array of datasets for testApplies().
   */
  public function providerTestApplies() {
    $this->setUpMockEntities();
    $tests = [
      [FALSE],
      [FALSE, 'not'],
      [FALSE, 'webform'],
      [FALSE, 'entity.webform'],
      [TRUE, 'entity.webform.handler.'],
      [TRUE, 'entity.webform_ui.element'],
      [TRUE, 'entity.webform.user.submissions'],
      // Source entity.
      [TRUE, 'entity.{source_entity}.webform'],
      [TRUE, 'entity.{source_entity}.webform_submission'],
      [TRUE, 'entity.node.webform'],
      [TRUE, 'entity.node.webform_submission'],
      [TRUE, 'entity.node.webform.user.submissions'],
      // Submissions.
      [FALSE, 'entity.webform.user.submission'],
      [TRUE, 'entity.webform.user.submission', [['webform_submission', $this->webformSubmissionAccess]]],
      [TRUE, 'webform', [['webform_submission', $this->webformSubmissionAccess]]],
      // Translations.
      [FALSE, 'entity.webform.config_translation_overview'],
      [TRUE, 'entity.webform.config_translation_overview', [['webform', $this->webformAccess]]],
    ];
    return $tests;
  }

  /**
   * Tests WebformBreadcrumbBuilder::type.
   *
   * @param bool $expected
   *   WebformBreadcrumbBuilder::type set via
   *   WebformBreadcrumbBuilder::applies().
   * @param string|null $route_name
   *   (optional) A route name.
   * @param array $parameter_map
   *   (optional) An array of parameter names and values.
   *
   * @dataProvider providerTestType
   * @covers ::applies
   */
  public function testType($expected, $route_name = NULL, array $parameter_map = []) {
    $route_match = $this->getMockRouteMatch($route_name, $parameter_map);
    $this->breadcrumbBuilder->applies($route_match);
    $this->assertEquals($expected, $this->breadcrumbBuilder->getType());
  }

  /**
   * Provides test data for testType().
   *
   * @return array
   *   Array of datasets for testType().
   */
  public function providerTestType() {
    $this->setUpMockEntities();
    $tests = [
      [NULL],
      // Source entity.
      ['webform_source_entity', 'entity.{source_entity}.webform'],
      ['webform_source_entity', 'entity.{source_entity}.webform_submission'],
      ['webform_source_entity', 'entity.node.webform'],
      ['webform_source_entity', 'entity.node.webform_submission'],
      // Element.
      ['webform_element', 'entity.webform_ui.element'],
      // Handler.
      ['webform_handler', 'entity.webform.handler.'],
      // User submissions.
      ['webform_user_submissions', 'entity.webform.user.submissions'],
      ['webform_source_entity', 'entity.{source_entity}.webform.user.submissions'],
      ['webform_source_entity', 'entity.node.webform.user.submissions'],
      // User submission.
      ['webform_user_submission', 'entity.webform.user.submission', [['webform_submission', $this->webformSubmission]]],
      // Submission.
      [NULL, 'entity.webform_submission.canonical', [['webform_submission', $this->webformSubmission]]],
      ['webform_submission', 'entity.webform_submission.canonical', [['webform_submission', $this->webformSubmissionAccess]]],
      // Webform.
      [NULL, 'entity.webform.canonical', [['webform', $this->webform]]],
      ['webform', 'entity.webform.canonical', [['webform', $this->webformAccess]]],
      // Webform template.
      ['webform_template', 'entity.webform.canonical', [['webform', $this->webformTemplate]]],
    ];
    return $tests;
  }

  /**
   * Test build source entity breadcrumbs.
   */
  public function testBuildSourceEntity() {
    $this->setSourceEntity($this->nodeAccess);
    $route_match = $this->getMockRouteMatch('entity.node.webform', [
      ['webform', $this->webformAccess],
      ['node', $this->nodeAccess],
    ]);
    $links = [
      Link::createFromRoute('Home', '<front>'),
      $this->node->toLink(),
    ];
    $this->assertLinks($route_match, $links);
  }

  /**
   * Test build source entity submissions breadcrumbs.
   */
  public function testBuildSourceEntitySubmissions() {
    $this->setSourceEntity($this->nodeAccess);
    $route_match = $this->getMockRouteMatch('entity.node.webform.user.submission', [
      ['webform_submission', $this->webformSubmissionAccess],
      ['webform', $this->webform],
      ['node', $this->node],
    ]);
    $links = [
      Link::createFromRoute('Home', '<front>'),
      $this->node->toLink(),
      Link::createFromRoute('Submissions', 'entity.node.webform.user.submissions', ['node' => 1]),
    ];
    $this->assertLinks($route_match, $links);
  }

  /**
   * Test build source entity submissions breadcrumbs.
   */
  public function testBuildSourceEntityResults() {
    $this->setSourceEntity($this->nodeAccess);
    $route_match = $this->getMockRouteMatch('entity.node.webform_submission.canonical', [
      ['webform_submission', $this->webformSubmissionAccess],
      ['webform', $this->webform],
      ['node', $this->node],
    ]);
    $links = [
      Link::createFromRoute('Home', '<front>'),
      $this->node->toLink(),
      Link::createFromRoute('Results', 'entity.node.webform.results_submissions', ['node' => 1]),
    ];
    $this->assertLinks($route_match, $links);
  }

  /**
   * Test build source entity submissions breadcrumbs.
   */
  public function testBuildSourceEntityUserResults() {
    $this->setSourceEntity($this->node);
    $webform_submission_access = $this->createMock('Drupal\webform\WebformSubmissionInterface');
    $webform_submission_access->expects($this->any())
      ->method('access')
      ->will($this->returnCallback(function ($operation) {
        return ($operation === 'view_own');
      }));
    $route_match = $this->getMockRouteMatch('entity.node.webform_submission.canonical', [
      ['webform_submission', $webform_submission_access],
      ['webform', $this->webform],
      ['node', $this->node],
    ]);
    $links = [
      Link::createFromRoute('Home', '<front>'),
      $this->node->toLink(),
      Link::createFromRoute('Results', 'entity.node.webform.user.submissions', ['node' => 1]),
    ];
    $this->assertLinks($route_match, $links);
  }

  /**
   * Test build templates breadcrumbs.
   */
  public function testBuildTemplates() {
    $route_match = $this->getMockRouteMatch('entity.webform.canonical', [
      ['webform', $this->webformTemplate],
    ]);
    $links = [
      Link::createFromRoute('Home', '<front>'),
      Link::createFromRoute('Administration', 'system.admin'),
      Link::createFromRoute('Structure', 'system.admin_structure'),
      Link::createFromRoute('Webforms', 'entity.webform.collection'),
      Link::createFromRoute('Templates', 'entity.webform.templates'),
    ];
    $this->assertLinks($route_match, $links);
  }

  /**
   * Test build element breadcrumbs.
   */
  public function testBuildElements() {
    $route_match = $this->getMockRouteMatch('entity.webform_ui.element', [
      ['webform', $this->webform],
    ]);
    $links = [
      Link::createFromRoute('Home', '<front>'),
      Link::createFromRoute('Administration', 'system.admin'),
      Link::createFromRoute('Structure', 'system.admin_structure'),
      Link::createFromRoute('Webforms', 'entity.webform.collection'),
      Link::createFromRoute($this->webform->label(), 'entity.webform.canonical', ['webform' => $this->webform->id()]),
      Link::createFromRoute('Elements', 'entity.webform.edit_form', ['webform' => $this->webform->id()]),
    ];
    $this->assertLinks($route_match, $links);
  }

  /**
   * Test build handler breadcrumbs.
   */
  public function testBuildHandlers() {
    // Check source entity.
    $route_match = $this->getMockRouteMatch('entity.webform.handler.add_form', [
      ['webform', $this->webform],
    ]);
    $links = [
      Link::createFromRoute('Home', '<front>'),
      Link::createFromRoute('Administration', 'system.admin'),
      Link::createFromRoute('Structure', 'system.admin_structure'),
      Link::createFromRoute('Webforms', 'entity.webform.collection'),
      Link::createFromRoute($this->webform->label(), 'entity.webform.canonical', ['webform' => $this->webform->id()]),
      Link::createFromRoute('Emails / Handlers', 'entity.webform.handlers', ['webform' => $this->webform->id()]),
    ];
    $this->assertLinks($route_match, $links);
  }

  /**
   * Test build submissions breadcrumbs.
   */
  public function testBuildSubmissions() {
    $route_match = $this->getMockRouteMatch('entity.webform_submission.canonical', [
      ['webform_submission', $this->webformSubmissionAccess],
    ]);
    $links = [
      Link::createFromRoute('Home', '<front>'),
      Link::createFromRoute('Administration', 'system.admin'),
      Link::createFromRoute('Structure', 'system.admin_structure'),
      Link::createFromRoute('Webforms', 'entity.webform.collection'),
      Link::createFromRoute($this->webform->label(), 'entity.webform.canonical', ['webform' => $this->webform->id()]),
      Link::createFromRoute('Results', 'entity.webform.results_submissions', ['webform' => $this->webform->id()]),
    ];
    $this->assertLinks($route_match, $links);
  }

  /**
   * Test build user submissions breadcrumbs.
   */
  public function testBuildUserSubmissions() {
    // Check without view own access.
    $route_match = $this->getMockRouteMatch('entity.webform.user.submission', [
      ['webform_submission', $this->webformSubmission],
    ]);
    $links = [
      Link::createFromRoute($this->webform->label(), 'entity.webform.canonical', ['webform' => $this->webform->id()]),
    ];
    $this->assertLinks($route_match, $links);

    // Check with view own access.
    $route_match = $this->getMockRouteMatch('entity.webform.user.submission', [
      ['webform_submission', $this->webformSubmissionAccess],
    ]);
    $links = [
      Link::createFromRoute($this->webform->label(), 'entity.webform.canonical', ['webform' => $this->webform->id()]),
      Link::createFromRoute('Submissions', 'entity.webform.user.submissions', ['webform' => $this->webform->id()]),
    ];
    $this->assertLinks($route_match, $links);

  }

  /**
   * Test build user submission breadcrumbs.
   */
  public function testBuildUserSubmission() {
    $route_match = $this->getMockRouteMatch('entity.webform.user.submissions', [
      ['webform', $this->webform],
    ]);
    $links = [
      Link::createFromRoute($this->webform->label(), 'entity.webform.canonical', ['webform' => $this->webform->id()]),
    ];
    $this->assertLinks($route_match, $links);
  }

  /* ************************************************************************ */
  // Helper functions.
  /* ************************************************************************ */

  /**
   * Assert breadcrumb builder generates links for specified route match.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   A mocked route match.
   * @param array $links
   *   An array of breadcrumb links.
   */
  protected function assertLinks(RouteMatchInterface $route_match, array $links): void {
    $this->breadcrumbBuilder->applies($route_match);
    $breadcrumb = $this->breadcrumbBuilder->build($route_match);
    $this->assertEquals($links, $breadcrumb->getLinks());
  }

  /**
   * Set request handler's source entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   An entity.
   */
  protected function setSourceEntity(EntityInterface $entity) {
    // Set the node as the request handler's source entity.
    $this->requestHandler->expects($this->any())
      ->method('getCurrentSourceEntity')
      ->will($this->returnValue($entity));
  }

  /**
   * Get mock route match.
   *
   * @param string|null $route_name
   *   (optional) A route name.
   * @param array $parameter_map
   *   (optional) An array of parameter names and values.
   *
   * @return \Drupal\Core\Routing\RouteMatchInterface
   *   A mocked route match.
   */
  protected function getMockRouteMatch($route_name = NULL, array $parameter_map = []) {
    $route_match = $this->createMock('Drupal\Core\Routing\RouteMatchInterface');
    $route_match->expects($this->any())
      ->method('getRouteName')
      ->will($this->returnValue($route_name));
    $route_match->expects($this->any())
      ->method('getParameter')
      ->will($this->returnValueMap($parameter_map));

    /** @var \Drupal\Core\Routing\RouteMatchInterface $route_match */
    return $route_match;
  }

  /**
   * Setup mock webform and webform submission entities.
   *
   * This is called before every test is setup and provider initialization.
   */
  protected function setUpMockEntities() {
    // Only initial mock entities once.
    if (isset($this->node)) {
      return;
    }

    /* node entities */

    $this->node = $this->createMock('Drupal\node\NodeInterface');
    $this->node->expects($this->any())
      ->method('label')
      ->will($this->returnValue('{node}'));
    $this->node->expects($this->any())
      ->method('getEntityTypeId')
      ->will($this->returnValue('node'));
    $this->node->expects($this->any())
      ->method('id')
      ->will($this->returnValue('1'));
    $this->node->expects($this->any())
      ->method('toLink')
      ->will($this->returnValue(Link::createFromRoute('{node}', 'entity.node.canonical', ['node' => 1])));

    $this->nodeAccess = clone $this->node;
    $this->nodeAccess->expects($this->any())
      ->method('access')
      ->will($this->returnValue(TRUE));

    /* webform entities */

    $this->webform = $this->createMock('Drupal\webform\WebformInterface');
    $this->webform->expects($this->any())
      ->method('label')
      ->will($this->returnValue('{webform}'));
    $this->webform->expects($this->any())
      ->method('id')
      ->will($this->returnValue(1));

    $this->webformAccess = clone $this->webform;
    $this->webformAccess->expects($this->any())
      ->method('access')
      ->will($this->returnValue(TRUE));

    $this->webformTemplate = clone $this->webformAccess;
    $this->webformTemplate->expects($this->any())
      ->method('isTemplate')
      ->will($this->returnValue(TRUE));

    /* webform submission entities */

    $this->webformSubmission = $this->createMock('Drupal\webform\WebformSubmissionInterface');
    $this->webformSubmission->expects($this->any())
      ->method('getWebform')
      ->will($this->returnValue($this->webform));
    $this->webformSubmission->expects($this->any())
      ->method('label')
      ->will($this->returnValue('{webform_submission}'));
    $this->webformSubmission->expects($this->any())
      ->method('id')
      ->will($this->returnValue(1));

    $this->webformSubmissionAccess = clone $this->webformSubmission;
    $this->webformSubmissionAccess->expects($this->any())
      ->method('access')
      ->will($this->returnValue(TRUE));
  }

}

if (!function_exists('base_path')) {

  /**
   * Mock base path function.
   *
   * @return string
   *   A base path.
   */
  function base_path() {
    return '/';
  }

}
