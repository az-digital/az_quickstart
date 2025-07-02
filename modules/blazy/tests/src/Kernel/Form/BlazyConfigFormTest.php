<?php

namespace Drupal\Tests\blazy\Kernel\Form;

use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormState;
use Drupal\KernelTests\KernelTestBase;
use Drupal\blazy\BlazyDefault;
use Drupal\blazy_ui\Form\BlazyConfigForm;

/**
 * Tests the Blazy UI settings form.
 *
 * @coversDefaultClass \Drupal\blazy_ui\Form\BlazyConfigForm
 *
 * @group blazy
 */
class BlazyConfigFormTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The blazy manager service.
   *
   * @var \Drupal\blazy\BlazyManagerInterface
   */
  protected $blazyManager;

  /**
   * The Blazy form object under test.
   *
   * @var \Drupal\blazy_ui\Form\BlazyConfigForm
   */
  protected $blazySettingsForm;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'system',
    'file',
    'image',
    'media',
    'blazy',
    'blazy_ui',
  ];

  /**
   * {@inheritdoc}
   *
   * @covers ::__construct
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installConfig(static::$modules);

    $this->blazyManager = $this->container->get('blazy.manager');

    $this->blazySettingsForm = BlazyConfigForm::create($this->container);
  }

  /**
   * Tests for \Drupal\blazy_ui\Form\BlazyConfigForm.
   *
   * @covers ::getFormId
   * @covers ::getEditableConfigNames
   * @covers ::buildForm
   * @covers ::submitForm
   */
  public function testBlazyConfigForm() {
    $nojs = BlazyDefault::nojs();
    // Emulate a form state of a submitted form.
    $form_state = (new FormState())->setValues([
      'admin_css' => TRUE,
      'nojs' => array_combine($nojs, $nojs),
    ]);

    $this->assertInstanceOf(FormInterface::class, $this->blazySettingsForm);
    $this->assertTrue($this->blazyManager->configFactory()->get('blazy.settings')->get('admin_css'));

    $id = $this->blazySettingsForm->getFormId();
    $this->assertEquals('blazy_settings_form', $id);

    $method = new \ReflectionMethod(BlazyConfigForm::class, 'getEditableConfigNames');
    $method->setAccessible(TRUE);

    $name = $method->invoke($this->blazySettingsForm);
    $this->assertEquals(['blazy.settings'], $name);

    $form = $this->blazySettingsForm->buildForm([], $form_state);
    $this->blazySettingsForm->submitForm($form, $form_state);
  }

}
