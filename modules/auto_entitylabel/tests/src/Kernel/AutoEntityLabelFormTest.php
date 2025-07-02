<?php

namespace Drupal\Tests\auto_entitylabel\Kernel;

use Drupal\auto_entitylabel\AutoEntityLabelManager;
use Drupal\auto_entitylabel\Form\AutoEntityLabelForm;
use Drupal\Core\Form\FormState;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Symfony\Component\Routing\Route;

/**
 * Tests auto entity label form.
 *
 * @group auto_entitylabel
 *
 * @requires module token
 */
class AutoEntityLabelFormTest extends EntityKernelTestBase {

  use ContentTypeCreationTrait;

  /**
   * Form builder service.
   *
   * @var object|null
   */
  protected $formBuilder;

  /**
   * Mocked RouteMatch service.
   *
   * @var \PHPUnit\Framework\MockObject\MockObject
   */
  protected $mockRouteMatch;

  /**
   * Node type.
   *
   * @var \Drupal\node\Entity\NodeType
   */
  protected $nodeType;

  /**
   * Config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'node',
    'filter',
    'token',
    'auto_entitylabel',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->configFactory = $this->container->get('config.factory');

    $this->installEntitySchema('node');

    $this->installSchema('user', 'users_data');
    $this->installSchema('node', ['node_access']);

    $this->installConfig(self::$modules);

    $this->mockRouteMatch = $this->createMock(RouteMatchInterface::class);
    $this->mockRouteMatch->method('getRouteObject')->willReturn(new Route(
      '/admin/structure/types/manage/{node_type}/auto-label',
      [
        '_form' => '\Drupal\auto_entitylabel\Form\AutoEntityLabelForm',
        '_title' => 'Automatic entity label',
      ],
      [
        '_permission' => 'administer node_type labels',
      ],
      [
        'compiler_class' => '',
        '_admin_route' => TRUE,
        'parameters' => [
          'node_type' => [
            'type' => 'entity:node_type',
            'converter' => 'drupal.proxy_original_service.paramconverter.configentity_admin',
          ],
        ],
        '_access_checks' => [
          0 => 'access_check.permission',
          1 => 'access_check.domain',
        ],
        'utf8' => TRUE,
      ],
      '',
      [],
      [],
      '',
    ));
    $this->nodeType = $this->createContentType(['type' => 'page']);
    $this->mockRouteMatch->method('getParameter')->willReturn($this->nodeType);
    $this->container->set('current_route_match', $this->mockRouteMatch);
    $this->formBuilder = $this->container->get('form_builder');
  }

  /**
   * Tests that form is built correctly.
   */
  public function testFormBuild() {
    $this->setConfiguration([
      'status' => AutoEntityLabelManager::ENABLED,
      'pattern' => 'Testing title',
      'escape' => TRUE,
      'preserve_titles' => TRUE,
    ]);
    $formState = new FormState();
    $form = $this->formBuilder->buildForm(AutoEntityLabelForm::class, $formState);

    $this->assertNotNull($form);
    $this->assertCount(0, $formState->getErrors());
    $this->assertArrayHasKey('auto_entitylabel', $form);
    $this->assertArrayHasKey('status', $form['auto_entitylabel']);
    $this->assertEquals(1, $form['auto_entitylabel']['status']['#default_value']);
    $this->assertArrayHasKey('pattern', $form['auto_entitylabel']);
    $this->assertEquals('Testing title', $form['auto_entitylabel']['pattern']['#default_value']);

    $this->assertArrayHasKey('token_help', $form['auto_entitylabel']);
    $this->assertArrayHasKey('escape', $form['auto_entitylabel']);
    $this->assertTrue($form['auto_entitylabel']['escape']['#default_value']);
    $this->assertArrayHasKey('preserve_titles', $form['auto_entitylabel']);
    $this->assertTrue($form['auto_entitylabel']['preserve_titles']['#default_value']);
    $this->assertArrayHasKey('save', $form['auto_entitylabel']);
    $this->assertArrayHasKey('chunk', $form['auto_entitylabel']);
  }

  /**
   * Tests that submitForm() works correctly.
   */
  public function testFormSubmit() {
    $this->setConfiguration([
      'status' => AutoEntityLabelManager::DISABLED,
      'pattern' => 'Testing title',
      'escape' => FALSE,
      'preserve_titles' => FALSE,
    ]);
    $formState = (new FormState())
      ->setValues([
        'status' => 1,
        'pattern' => 'Testing Node',
        'escape' => TRUE,
        'preserve_titles' => TRUE,
        'save' => FALSE,
        'chunk' => 49,
      ]);
    $this->formBuilder->submitForm(AutoEntityLabelForm::class, $formState);
    $this->assertCount(0, $formState->getErrors());
    $entityLabelConfiguration = $this->configFactory
      ->getEditable("auto_entitylabel.settings.node.{$this->nodeType->id()}");
    $this->assertEquals(1, $entityLabelConfiguration->get('status'));
    $this->assertEquals('Testing Node', $entityLabelConfiguration->get('pattern'));
    $this->assertTrue($entityLabelConfiguration->get('escape'));
    $this->assertTrue($entityLabelConfiguration->get('preserve_titles'));
    $this->assertNotTrue($entityLabelConfiguration->get('save'));
    $this->assertEquals(49, $entityLabelConfiguration->get('chunk'));
  }

  /**
   * Sets the configuration values.
   *
   * @param array $params
   *   Array of values to be configured.
   */
  public function setConfiguration(array $params) {
    $autoEntityLabelSettings = $this->configFactory
      ->getEditable("auto_entitylabel.settings.node.{$this->nodeType->id()}");
    foreach ($params as $key => $value) {
      $autoEntityLabelSettings
        ->set($key, $value);
    }
    $autoEntityLabelSettings->save();
  }

}
