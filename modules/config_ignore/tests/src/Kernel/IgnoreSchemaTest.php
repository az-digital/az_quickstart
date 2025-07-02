<?php

declare(strict_types=1);

namespace Drupal\Tests\config_ignore\Kernel;

use Drupal\Core\Config\Schema\SchemaCheckTrait;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\KernelTests\KernelTestBase;
use Symfony\Component\Validator\ConstraintViolation;

/**
 * Test the schema.
 *
 * @group config_ignore
 */
class IgnoreSchemaTest extends KernelTestBase {

  use SchemaCheckTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'system',
    'config_ignore',
  ];

  /**
   * Test the different configuration options.
   *
   * @param array $config
   *   The configuration.
   * @param bool $valid
   *   Whether the config is valid.
   *
   * @dataProvider ignoreConfigValidationProvider
   */
  public function testConfigSchemaValidation(array $config, $valid): void {
    $check = $this->checkConfigSchema($this->container->get('config.typed'), 'config_ignore.settings', $config);
    if (is_array($check)) {
      if ($valid === TRUE) {
        // This helps with debugging because it prints the violation.
        self::assertEquals($check, []);
      }
      else {
        // We don't care about how the config is not valid.
        self::assertNotEmpty($check);
      }
    }
    else {
      self::assertEquals($check, $valid);
    }
  }

  /**
   * Provide different configurations to test.
   *
   * @return \Generator
   *   The test scenario.
   */
  public function ignoreConfigValidationProvider() {
    yield 'default' => [
      'config' => [
        'mode' => 'simple',
        'ignored_config_entities' => [],
      ],
      'valid' => TRUE,
    ];

    yield 'no mode' => [
      'config' => [
        'mode' => 'too simple',
        'ignored_config_entities' => [],
      ],
      'valid' => FALSE,
    ];

    yield 'with simple data' => [
      'config' => [
        'mode' => 'simple',
        'ignored_config_entities' => [
          'hello',
          'config_ignore.*',
        ],
      ],
      'valid' => TRUE,
    ];

    yield 'wrong format simple' => [
      'config' => [
        'mode' => 'simple',
        'ignored_config_entities' => [
          'import' => [],
          'export' => [],
        ],
      ],
      'valid' => FALSE,
    ];

    yield 'empty intermediate' => [
      'config' => [
        'mode' => 'intermediate',
        'ignored_config_entities' => [
          'import' => [],
          'export' => [],
        ],
      ],
      'valid' => TRUE,
    ];

  }

  /**
   * Checks the TypedConfigManager has a valid schema for the configuration.
   *
   * We override this method from the trait because we also do checks not done
   * in Drupal < 10.2
   *
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typed_config
   *   The TypedConfigManager.
   * @param string $config_name
   *   The configuration name.
   * @param array $config_data
   *   The configuration data, assumed to be data for a top-level config object.
   *
   * @return array|bool
   *   FALSE if no schema found. List of errors if any found. TRUE if fully
   *   valid.
   */
  public function checkConfigSchema(TypedConfigManagerInterface $typed_config, $config_name, $config_data) {
    // We'd like to verify that the top-level type is either config_base,
    // config_entity, or a derivative. The only thing we can really test though
    // is that the schema supports having langcode in it. So add 'langcode' to
    // the data if it doesn't already exist.
    if (!isset($config_data['langcode'])) {
      $config_data['langcode'] = 'en';
    }
    $this->configName = $config_name;
    if (!$typed_config->hasConfigSchema($config_name)) {
      return FALSE;
    }
    $this->schema = $typed_config->createFromNameAndData($config_name, $config_data);
    $errors = [];
    foreach ($config_data as $key => $value) {
      $errors[] = $this->checkValue($key, $value);
    }
    $errors = array_merge(...$errors);
    // Also perform explicit validation. Note this does NOT require every node
    // in the config schema tree to have validation constraints defined.
    $violations = $this->schema->validate();
    $ignored_validation_constraint_messages = [
      // @see \Drupal\Core\Config\Plugin\Validation\Constraint\ConfigExistsConstraint::$message
      // @todo Remove this in https://www.drupal.org/project/drupal/issues/3362453
      "The '.*' config does not exist.",
      // @see \Drupal\Core\Extension\Plugin\Validation\Constraint\ExtensionExistsConstraint::$moduleMessage
      // @see \Drupal\Core\Extension\Plugin\Validation\Constraint\ExtensionExistsConstraint::$themeMessage
      // @todo Remove this in https://www.drupal.org/project/drupal/issues/3362456
      "Module '.*' is not installed.",
      "Theme '.*' is not installed.",
      // @see \Drupal\Core\Plugin\Plugin\Validation\Constraint\PluginExistsConstraint::$unknownPluginMessage
      // @todo Remove this in https://www.drupal.org/project/drupal/issues/3362457
      "The '.*' plugin does not exist.",
      // @see "machine_name" in core.data_types.schema.yml
      // @todo Remove this in https://www.drupal.org/project/drupal/issues/3372972
      "The <em class=\"placeholder\">.*<\/em> machine name is not valid.",
    ];
    $filtered_violations = array_filter(
      iterator_to_array($violations),
      fn (ConstraintViolation $v) => preg_match(sprintf("/^(%s)$/", implode('|', $ignored_validation_constraint_messages)), (string) $v->getMessage()) !== 1
    );
    $validation_errors = array_map(
      fn (ConstraintViolation $v) => sprintf("[%s] %s", $v->getPropertyPath(), (string) $v->getMessage()),
      $filtered_violations
    );
    $errors = array_merge($errors, $validation_errors);
    if (empty($errors)) {
      return TRUE;
    }
    return $errors;
  }

}
