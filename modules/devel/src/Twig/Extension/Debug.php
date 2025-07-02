<?php

namespace Drupal\devel\Twig\Extension;

use Drupal\devel\DevelDumperManagerInterface;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\Template;
use Twig\TwigFunction;

/**
 * Provides the Devel debugging function within Twig templates.
 *
 * NOTE: This extension doesn't do anything unless twig_debug is enabled.
 * The twig_debug setting is read from the Twig environment, not Drupal
 * Settings, so a container rebuild is necessary when toggling twig_debug on
 * and off.
 */
class Debug extends AbstractExtension {

  /**
   * The devel dumper service.
   */
  protected DevelDumperManagerInterface $dumper;

  /**
   * Constructs a Debug object.
   *
   * @param \Drupal\devel\DevelDumperManagerInterface $dumper
   *   The devel dumper service.
   */
  public function __construct(DevelDumperManagerInterface $dumper) {
    $this->dumper = $dumper;
  }

  /**
   * {@inheritdoc}
   */
  public function getName(): string {
    return 'devel_debug';
  }

  /**
   * {@inheritdoc}
   */
  public function getFunctions() {
    $options = [
      'is_safe' => ['html'],
      'needs_environment' => TRUE,
      'needs_context' => TRUE,
      'is_variadic' => TRUE,
    ];

    return [
      new TwigFunction('devel_dump', [$this, 'dump'], $options),
      new TwigFunction('kpr', [$this, 'dump'], $options),
      // Preserve familiar kint() function for dumping.
      new TwigFunction('kint', [$this, 'kint'], $options),
      new TwigFunction('devel_message', [$this, 'message'], $options),
      new TwigFunction('dpm', [$this, 'message'], $options),
      new TwigFunction('dsm', [$this, 'message'], $options),
      new TwigFunction('devel_breakpoint', [$this, 'breakpoint'], [
        'needs_environment' => TRUE,
        'needs_context' => TRUE,
        'is_variadic' => TRUE,
      ]),
    ];
  }

  /**
   * Provides debug function to Twig templates.
   *
   * Handles 0, 1, or multiple arguments.
   *
   * @param \Twig\Environment $env
   *   The twig environment instance.
   * @param array $context
   *   An array of parameters passed to the template.
   * @param array $args
   *   An array of parameters passed the function.
   *
   * @return string
   *   String representation of the input variables.
   *
   * @see \Drupal\devel\DevelDumperManager::dump()
   */
  public function dump(Environment $env, array $context, array $args = []): string|false|null {
    return $this->doDump($env, $context, $args);
  }

  /**
   * Writes the debug information for Twig templates.
   *
   * @param \Twig\Environment $env
   *   The twig environment instance.
   * @param array $context
   *   An array of parameters passed to the template.
   * @param array $args
   *   An array of parameters passed the function.
   * @param string $plugin_id
   *   The plugin id. Defaults to null.
   *
   * @return false|string|null
   *   String representation of the input variables, or null if twig_debug mode
   *   is tunred off.
   */
  private function doDump(Environment $env, array $context, array $args = [], $plugin_id = NULL): false|string|null {
    if (!$env->isDebug()) {
      return NULL;
    }

    ob_start();

    // No arguments passed, display full Twig context.
    if ($args === []) {
      $context_variables = $this->getContextVariables($context);
      $this->dumper->dump($context_variables, 'Twig context', $plugin_id);
    }
    else {
      $parameters = $this->guessTwigFunctionParameters();

      foreach ($args as $index => $variable) {
        $name = empty($parameters[$index]) ? NULL : $parameters[$index];
        $this->dumper->dump($variable, $name, $plugin_id);
      }
    }

    return ob_get_clean();
  }

  /**
   * Similar to dump() but always uses the kint dumper if available.
   *
   * Handles 0, 1, or multiple arguments.
   *
   * @param \Twig\Environment $env
   *   The twig environment instance.
   * @param array $context
   *   An array of parameters passed to the template.
   * @param array $args
   *   An array of parameters passed the function.
   *
   * @return string
   *   String representation of the input variables.
   *
   * @see \Drupal\devel\DevelDumperManager::dump()
   */
  public function kint(Environment $env, array $context, array $args = []): string|false|null {
    return $this->doDump($env, $context, $args, 'kint');
  }

  /**
   * Provides debug function to Twig templates.
   *
   * Handles 0, 1, or multiple arguments.
   *
   * @param \Twig\Environment $env
   *   The twig environment instance.
   * @param array $context
   *   An array of parameters passed to the template.
   * @param array $args
   *   An array of parameters passed the function.
   *
   * @see \Drupal\devel\DevelDumperManager::message()
   */
  public function message(Environment $env, array $context, array $args = []): void {
    if (!$env->isDebug()) {
      return;
    }

    // No arguments passed, display full Twig context.
    if ($args === []) {
      $context_variables = $this->getContextVariables($context);
      $this->dumper->message($context_variables, 'Twig context');
    }
    else {
      $parameters = $this->guessTwigFunctionParameters();

      foreach ($args as $index => $variable) {
        $name = empty($parameters[$index]) ? NULL : $parameters[$index];
        $this->dumper->message($variable, $name);
      }
    }

  }

  /**
   * Provides XDebug integration for Twig templates.
   *
   * To use this features simply put the following statement in the template
   * of interest:
   *
   * @code
   * {{ devel_breakpoint() }}
   * @endcode
   *
   * When the template is evaluated is made a call to a dedicated method in
   * devel twig debug extension in which is used xdebug_break(), that emits a
   * breakpoint to the debug client (the debugger break on the specific line as
   * if a normal file/line breakpoint was set on this line).
   * In this way you'll be able to inspect any variables available in the
   * template (environment, context, specific variables etc..) in your IDE.
   *
   * @param \Twig\Environment $env
   *   The twig environment instance.
   * @param array $context
   *   An array of parameters passed to the template.
   * @param array $args
   *   An array of parameters passed the function.
   */
  public function breakpoint(Environment $env, array $context, array $args = []): void {
    if (!$env->isDebug()) {
      return;
    }

    if (function_exists('xdebug_break')) {
      xdebug_break();
    }
  }

  /**
   * Filters the Twig context variable.
   *
   * @param array $context
   *   The Twig context.
   *
   * @return array
   *   An array Twig context variables.
   */
  protected function getContextVariables(array $context): array {
    $context_variables = [];
    foreach ($context as $key => $value) {
      if (!$value instanceof Template) {
        $context_variables[$key] = $value;
      }
    }

    return $context_variables;
  }

  /**
   * Gets the twig function parameters for the current invocation.
   *
   * @return array
   *   The detected twig function parameters.
   */
  protected function guessTwigFunctionParameters(): array {
    $callee = NULL;
    $template = NULL;

    $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS | DEBUG_BACKTRACE_PROVIDE_OBJECT);

    foreach ($backtrace as $index => $trace) {
      if (isset($trace['object']) && $trace['object'] instanceof Template) {
        $template = $trace['object'];
        $callee = $backtrace[$index - 1];
        break;
      }
    }

    $parameters = [];
    if ($template !== NULL && $callee !== NULL) {
      $line_number = $callee['line'];
      $debug_infos = $template->getDebugInfo();

      if (isset($debug_infos[$line_number])) {
        $source_line = $debug_infos[$line_number];
        $source_file_name = $template->getTemplateName();

        if (is_readable($source_file_name)) {
          $source = file($source_file_name, FILE_IGNORE_NEW_LINES);
          $line = $source[$source_line - 1];

          preg_match('/\((.+)\)/', $line, $matches);
          if (isset($matches[1])) {
            $parameters = array_map('trim', explode(',', $matches[1]));
          }
        }
      }
    }

    return $parameters;
  }

}
