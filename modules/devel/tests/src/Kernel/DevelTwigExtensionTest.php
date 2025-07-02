<?php

namespace Drupal\Tests\devel\Kernel;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\devel\Twig\Extension\Debug;
use Drupal\KernelTests\KernelTestBase;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;

/**
 * Tests Twig extensions.
 *
 * @group devel
 */
class DevelTwigExtensionTest extends KernelTestBase {

  use DevelDumperTestTrait;
  use MessengerTrait;

  /**
   * The user used in test.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $develUser;

  /**
   * Modules to enable.
   *
   * @var string[]
   */
  protected static $modules = ['devel', 'user', 'system'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installSchema('system', 'sequences');

    $devel_role = Role::create([
      'id' => 'admin',
      'label' => 'Admin',
      'permissions' => ['access devel information'],
    ]);
    $devel_role->save();

    $this->develUser = User::create([
      'name' => $this->randomMachineName(),
      'roles' => [$devel_role->id()],
    ]);
    $this->develUser->save();
  }

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container): void {
    parent::register($container);

    $parameters = $container->getParameter('twig.config');
    $parameters['debug'] = TRUE;
    $container->setParameter('twig.config', $parameters);
  }

  /**
   * Tests that Twig extension loads appropriately.
   */
  public function testTwigExtensionLoaded(): void {
    $twig_service = \Drupal::service('twig');
    $extension = $twig_service->getExtension(Debug::class);
    $this->assertEquals($extension::class, Debug::class, 'Debug Extension loaded successfully.');
  }

  /**
   * Tests that the Twig dump functions are registered properly.
   */
  public function testDumpFunctionsRegistered(): void {
    /** @var \Drupal\Core\Template\TwigEnvironment $environment */
    $environment = $this->container->get('twig');
    $functions = $environment->getFunctions();

    $dump_functions = ['devel_dump', 'kpr'];
    $message_functions = ['devel_message', 'dpm', 'dsm'];
    $registered_functions = $dump_functions + $message_functions;
    foreach ($registered_functions as $name) {
      $this->assertArrayHasKey($name, $functions);
      $function = $functions[$name];
      $this->assertEquals($function->getName(), $name);
      $this->assertTrue($function->needsContext());
      $this->assertTrue($function->needsEnvironment());
      $this->assertTrue($function->isVariadic());

      is_callable($function->getCallable(), TRUE, $callable);
      if (in_array($name, $dump_functions)) {
        $this->assertEquals($callable, Debug::class . '::dump');
      }
      else {
        $this->assertEquals($callable, Debug::class . '::message');
      }
    }
  }

  /**
   * Tests that the Twig function for XDebug integration is registered properly.
   */
  public function testXdebugIntegrationFunctionsRegistered(): void {
    /** @var \Drupal\Core\Template\TwigEnvironment $environment */
    $environment = $this->container->get('twig');
    $function = $environment->getFunction('devel_breakpoint');
    $this->assertNotNull($function);
    $this->assertTrue($function->needsContext());
    $this->assertTrue($function->needsEnvironment());
    $this->assertTrue($function->isVariadic());
    is_callable($function->getCallable(), TRUE, $callable);
    $this->assertEquals($callable, Debug::class . '::breakpoint');
  }

  /**
   * Tests that the Twig extension's dump functions produce the expected output.
   */
  public function testDumpFunctions(): void {
    $template = 'test-with-context {{ twig_string }} {{ twig_array.first }} {{ twig_array.second }}{{ devel_dump() }}';
    $expected_template_output = 'test-with-context context! first value second value';

    $context = [
      'twig_string' => 'context!',
      'twig_array' => [
        'first' => 'first value',
        'second' => 'second value',
      ],
      'twig_object' => new \stdClass(),
    ];

    /** @var \Drupal\Core\Template\TwigEnvironment $environment */
    $environment = \Drupal::service('twig');

    // Ensures that the twig extension does nothing if the current
    // user has not the adequate permission.
    $this->assertTrue($environment->isDebug());
    $this->assertEquals($environment->renderInline($template, $context), $expected_template_output);

    \Drupal::currentUser()->setAccount($this->develUser);

    // Ensures that if no argument is passed to the function the twig context is
    // dumped.
    $output = (string) $environment->renderInline($template, $context);
    $this->assertStringContainsString($expected_template_output, $output, 'When no argument passed');
    $this->assertContainsDump($output, $context, 'Twig context');

    // Ensures that if an argument is passed to the function it is dumped.
    $template = 'test-with-context {{ twig_string }} {{ twig_array.first }} {{ twig_array.second }}{{ devel_dump(twig_array) }}';
    $output = (string) $environment->renderInline($template, $context);
    $this->assertStringContainsString($expected_template_output, $output, 'When one argument is passed');
    $this->assertContainsDump($output, $context['twig_array']);

    // Ensures that if more than one argument is passed the function works
    // properly and every argument is dumped separately.
    $template = 'test-with-context {{ twig_string }} {{ twig_array.first }} {{ twig_array.second }}{{ devel_dump(twig_string, twig_array.first, twig_array, twig_object) }}';
    $output = (string) $environment->renderInline($template, $context);
    $this->assertStringContainsString($expected_template_output, $output, 'When multiple arguments are passed');
    $this->assertContainsDump($output, $context['twig_string']);
    $this->assertContainsDump($output, $context['twig_array']['first']);
    $this->assertContainsDump($output, $context['twig_array']);
    $this->assertContainsDump($output, $context['twig_object']);

    // Clear messages.
    $this->messenger()->deleteAll();

    $retrieve_message = static fn($messages, $index): ?string => isset($messages['status'][$index]) ? (string) $messages['status'][$index] : NULL;

    // Ensures that if no argument is passed to the function the twig context is
    // dumped.
    $template = 'test-with-context {{ twig_string }} {{ twig_array.first }} {{ twig_array.second }}{{ devel_message() }}';
    $output = (string) $environment->renderInline($template, $context);
    $this->assertStringContainsString($expected_template_output, $output, 'When no argument passed');
    $messages = \Drupal::messenger()->deleteAll();
    $this->assertDumpExportEquals($retrieve_message($messages, 0), $context, 'Twig context');

    // Ensures that if an argument is passed to the function it is dumped.
    $template = 'test-with-context {{ twig_string }} {{ twig_array.first }} {{ twig_array.second }}{{ devel_message(twig_array) }}';
    $output = (string) $environment->renderInline($template, $context);
    $this->assertStringContainsString($expected_template_output, $output, 'When one argument is passed');
    $messages = $this->messenger()->deleteAll();
    $this->assertDumpExportEquals($retrieve_message($messages, 0), $context['twig_array']);

    // Ensures that if more than one argument is passed to the function works
    // properly and every argument is dumped separately.
    $template = 'test-with-context {{ twig_string }} {{ twig_array.first }} {{ twig_array.second }}{{ devel_message(twig_string, twig_array.first, twig_array, twig_object) }}';
    $output = (string) $environment->renderInline($template, $context);
    $this->assertStringContainsString($expected_template_output, $output, 'When multiple arguments are passed');
    $messages = $this->messenger()->deleteAll();
    $this->assertDumpExportEquals($retrieve_message($messages, 0), $context['twig_string']);
    $this->assertDumpExportEquals($retrieve_message($messages, 1), $context['twig_array']['first']);
    $this->assertDumpExportEquals($retrieve_message($messages, 2), $context['twig_array']);
    $this->assertDumpExportEquals($retrieve_message($messages, 3), $context['twig_object']);
  }

}
