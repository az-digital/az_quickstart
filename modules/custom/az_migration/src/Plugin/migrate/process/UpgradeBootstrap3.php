<?php

namespace Drupal\az_migration\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Process plugin to upgrade ua-bootstrap styles to arizona-bootstrap styles.
 *
 * Replace CSS classes available in ua-bootstrap with coresponding class in
 * arizona-bootstrap.
 *
 * Example:
 *
 * @code
 * process:
 *   'body/value':
 *     -
 *       plugin: upgrade_ua_bootstrap_to_arizona_bootstrap
 *       source: 'body/0/value'
 * @endcode
 *
 * @MigrateProcessPlugin(
 *   id = "upgrade_ua_bootstrap_to_arizona_bootstrap"
 * )
 */
class UpgradeBootstrap3 extends ProcessPluginBase {

  /**
   * If warnings should be logged as migrate messages.
   *
   * @var bool
   */
  protected $logMessages = TRUE;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configuration += $this->defaultValues();
    $this->logMessages = (bool) $this->configuration['log_messages'];
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {

    if (!is_string($value)) {
      throw new MigrateException('Cannot transform on non-string value.');
    }

    if ($this->logMessages) {
      set_error_handler(static function ($errno, $errstr) use ($migrate_executable) {
        $migrate_executable->saveMessage($errstr, MigrationInterface::MESSAGE_WARNING);
      });
    }

    $uaBootstrapToArizonaBootstrapRules = self::uaBootstrapToArizonaBootstrapMap();

    $upgraded_string = $value;

    foreach ($uaBootstrapToArizonaBootstrapRules as $key => $rule) {
      $upgraded_string = preg_replace($rule['regex'], $rule['replacement'], $upgraded_string);
    }

    if ($this->logMessages) {
      restore_error_handler();
    }

    return $upgraded_string;

  }

  /**
   * Supply default values of all optional parameters.
   *
   * @return array
   *   An array with keys the optional parameters and values the corresponding
   *   defaults.
   */
  protected function defaultValues() {
    return [
      'log_messages' => TRUE,
    ];
  }

  /**
   * @return array
   *   An array with all required settings.
   */
  public static function uaBootstrapToArizonaBootstrapMap() {
    return [
      'container' => [
        'regex' => '/container-fluid/',
        'replacement' => 'container',
      ],
      'row' => [
        'regex' => '/row-fluid/',
        'replacement' => 'row',
      ],
      'span' => [
        'regex' => '/span(?=[1-9|10|11|12])/',
        'replacement' => 'col-md-',
      ],
      'offset' => [
        'regex' => '/offset(?=[1-9|10|11|12])/',
        'replacement' => 'col-lg-offset-',
      ],
      'btn' => [
        'regex' => '/(?!class=\")btn(?=[\s\"][^\-|btn])/',
        'replacement' => 'btn btn-default',
      ],
      'btn-mini' => [
        'regex' => '/btn-mini/',
        'replacement' => 'btn-xs',
      ],
      'btn-hollow' => [
        'regex' => '/btn-hollow/',
        'replacement' => 'btn-outline-red',
      ],
      'btn-lg' => [
        'regex' => '/btn-large/',
        'replacement' => 'btn-lg',
      ],
      'btn-small' => [
        'regex' => '/btn-small/',
        'replacement' => 'btn-sm',
      ],
      'input' => [
        'regex' => '/input-large/',
        'replacement' => 'input-lg',
      ],
      'input' => [
        'regex' => '/input-small/',
        'replacement' => 'input-sm',
      ],
      'input' => [
        'regex' => '/input-append/',
        'replacement' => 'input-group',
      ],
      'input' => [
        'regex' => '/input-prepend/',
        'replacement' => 'input-group',
      ],
      'add-on' => [
        'regex' => '/add-on/',
        'replacement' => 'input-group-addon',
      ],
      'label' => [
        'regex' => '/(?!class=\")label(?=[\s\"][^\-|label])/',
        'replacement' => 'label label-default',
      ],
      'hero' => [
        'regex' => '/hero-unit/',
        'replacement' => 'jumbotron',
      ],
      'nav list' => [
        'regex' => '/nav-list/',
        'replacement' => '',
      ],
      'affix' => [
        'regex' => '/nav-fixed-sidebar/',
        'replacement' => 'affix',
      ],
      'icons' => [
        'regex' => '/(="\bicon-)/',
        'replacement' => 'glyphicon glyphicon-',
      ],
      'icons' => [
        'regex' => '/(="\bicon-)/',
        'replacement' => '=\"glyphicon glyphicon-',
      ],
      'icons' => [
        'regex' => '/(=\bicon-)/',
        'replacement' => '=glyphicon glyphicon-',
      ],
      'icons' => [
        'regex' => '/\bclass+(\sicon-)/',
        'replacement' => '=\"glyphicon glyphicon-',
      ],
      'brand' => [
        'regex' => '/(?!class=\")brand/',
        'replacement' => 'navbar-brand',
      ],
      'btn' => [
        'regex' => '/(?!class=\")btn btn-navbar/',
        'replacement' => 'navbar-toggle',
      ],
      'nav' => [
        'regex' => '/nav-collapse/',
        'replacement' => 'navbar-collapse',
      ],
      'nav' => [
        'regex' => '/navbar-search/',
        'replacement' => 'navbar-form',
      ],
      'toggle' => [
        'regex' => '/nav-toggle/',
        'replacement' => 'navbar-toggle',
      ],
      'util' => [
        'regex' => '/(?!class=\")-phone/',
        'replacement' => '-sm',
      ],
      'util' => [
        'regex' => '/(?!class=\")-tablet/',
        'replacement' => '-md',
      ],
      'util' => [
        'regex' => '/(?!class=\")-desktop/',
        'replacement' => '-lg',
      ],
      'nav' => [
        'regex' => '/navbar-inner/',
        'replacement' => 'container',
      ],
      'nav' => [
        'regex' => '/navbar\s.nav/',
        'replacement' => 'navbar-nav',
      ],
    ];
  }

}
