<?php

declare(strict_types=1);

namespace Drupal\upgrade_status;

use Drupal\Core\Cache\NullBackend;
use Drupal\Core\Extension\Extension;
use Drupal\Core\Theme\Registry;
use PhpParser\Error;
use PhpParser\Node;
use PhpParser\NodeFinder;
use PhpParser\ParserFactory;
use PhpParser\PhpVersion;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Function_;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A theme function deprecation analyzer.
 *
 * @todo Remove once Drupal 8 to 9 deprecations are not a focus anymore.
 *   This is not dependent on Drupal 8 core itself though, so we can keep
 *   it in Drupal 9 to 10 for the sake of exposing extremely outdated code.
 */
final class ThemeFunctionDeprecationAnalyzer {

  /**
   * The service container.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerInterface
   */
  private $container;

  /**
   * Constructs a new theme function deprecation analyzer.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $this->container
   *   The service container.
   */
  public function __construct(ContainerInterface $container) {
    $this->container = $container;
  }

  /**
   * Analyzes theme functions in an extension.
   *
   * @param \Drupal\Core\Extension\Extension $extension
   *   The extension to be analyzed.
   *
   * @return \Drupal\upgrade_status\DeprecationMessage[]
   */
  public function analyze(Extension $extension): array {
    $deprecation_messages = [];
    // Analyze hook_theme and hook_theme_registry_alter functions.
    $deprecation_messages = array_merge($deprecation_messages, $this->analyzeFunction($extension->getName() . '_' . 'theme', $extension));
    $deprecation_messages = array_merge($deprecation_messages, $this->analyzeFunction($extension->getName() . '_' . 'theme_registry_alter', $extension));

    // If a theme is being analyzed, theme function overrides need to be
    // analyzed.
    if ($extension->getType() === 'theme') {
      // Create new instance of theme registry to ensure that we have the most
      // recent data without having to make changes to the production theme
      // registry.
      $theme_registry = new Registry($this->container->get('app.root'), new NullBackend('null'), $this->container->get('lock'), $this->container->get('module_handler'), $this->container->get('theme_handler'), $this->container->get('theme.initialization'), $extension->getName());
      $theme_registry->setThemeManager($this->container->get('theme.manager'));
      $theme_hooks = $theme_registry->get();

      $theme_function_overrides = drupal_find_theme_functions($theme_hooks, [$extension->getName()]);
      foreach ($theme_function_overrides as $machine_name => $theme_function_override) {
        try {
          $function = new \ReflectionFunction($extension->getName() . '_' . $machine_name);
          $file = $function->getFileName();
          $line = $function->getStartLine();
          $deprecation_messages[$extension->getName() . '_' . $machine_name] = new DeprecationMessage(sprintf('The theme is overriding the "%s" theme function. Theme functions are deprecated. For more info, see https://www.drupal.org/node/2575445.', $machine_name), $file, $line, 'ThemeFunctionDeprecationAnalyzer');
        } catch (\ReflectionException $e) {
          // This should never happen because drupal_find_theme_functions()
          // ensures that the function exists.
        }
      }
    }

    return $deprecation_messages;
  }

  /**
   * Analyzes function for definition of theme functions.
   *
   * This doesn't recognize functions in all edge cases. For example, theme
   * functions could be generated dynamically in a number of different ways.
   * However, this will be useful in most use cases.
   *
   * @param $function
   *   The function to be analyzed.
   * @param \Drupal\Core\Extension\Extension $extension
   *   The extension that is being tested.
   *
   * @return \Drupal\upgrade_status\DeprecationMessage[]
   */
  private function analyzeFunction(string $function, Extension $extension): array {
    $deprecation_messages = [];

    try {
      $function_reflection = new \ReflectionFunction($function);
    } catch (\ReflectionException $e) {
      // Not all extensions implement theme hooks.
      return [];
    }

    $parser_factory = new ParserFactory();
    if (method_exists($parser_factory, 'create')) {
      $parser = $parser_factory->create(ParserFactory::PREFER_PHP7);
    }
    else {
      $parser = $parser_factory->createForVersion(PhpVersion::fromString("7.4"));
    }
    try {
      $ast = $parser->parse(file_get_contents($function_reflection->getFileName()));
    } catch (Error $error) {
      // The function cannot be evaluated because of a syntax error.
      $deprecation_messages[] = new DeprecationMessage(sprintf('Parse error while processing the %s hook implementation.', $function), $function_reflection->getFileName(), $function_reflection->getStartLine(), 'ThemeFunctionDeprecationAnalyzer');
    }

    if (!is_iterable($ast)) {
      return [];
    }
    $finder = new NodeFinder();
    // Find the node for the function that is being analyzed.
    $function_node = $finder->findFirst($ast, function (Node $node) use ($function) {
      return ($node instanceof Function_ && isset($node->name) && $node->name->name === $function);
    });

    if (!$function_node) {
      // This should never happen because the file has been loaded based on the
      // existence of the function.
      return [];
    }

    // Find theme functions that have been defined using the array syntax.
    // @code
    // function hook_theme() {
    //   return [
    //     'theme_hook' => ['function' => theme_function'],
    //   ];
    // }
    // @endcode
    $theme_function_nodes = $finder->find([$function_node], function(Node $node) {
      return (isset($node->key) && $node->key instanceof String_ && $node->key->value === 'function');
    });
    foreach ($theme_function_nodes as $node) {
      $theme_function = $node->value instanceof String_ ? sprintf('"%s"', $node->value->value) : 'an unknown';
      $deprecation_messages[] = new DeprecationMessage(sprintf('The %s is defining %s theme function. Theme functions are deprecated. For more info, see https://www.drupal.org/node/2575445.', $extension->getType(), $theme_function), $function_reflection->getFileName(), $node->getStartLine(), 'ThemeFunctionDeprecationAnalyzer');
    }

    // Find theme functions that are being added to an existing array using
    // the array square bracket syntax.
    // @code
    // function hook_theme_registry_alter(&$theme_registry) {
    //   $theme_registry['theme_hook']['function'] = 'another_theme_function';
    // }
    // @endcode
    $theme_function_dim_nodes = $finder->find([$function_node], function(Node $node) {
      return $node instanceof Assign && $node->var instanceof ArrayDimFetch && $node->var->dim instanceof String_ && $node->var->dim->value === 'function';
    });
    foreach ($theme_function_dim_nodes as $node) {
      $theme_function = $node->expr instanceof String_ ? sprintf('"%s"', $node->expr->value) : 'an unknown';
      $deprecation_messages[] = new DeprecationMessage(sprintf('The %s is defining %s theme function. Theme functions are deprecated. For more info, see https://www.drupal.org/node/2575445.', $extension->getType(), $theme_function), $function_reflection->getFileName(), $node->getStartLine(), 'ThemeFunctionDeprecationAnalyzer');
    }

    return $deprecation_messages;
  }

}
