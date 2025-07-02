<?php

declare(strict_types=1);

namespace Drupal\migmag_process\Plugin\migrate\process;

use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Process plugin which returns a generated UUID.
 *
 * If a string value is provided, and it contains a valid UUID, then the plugin
 * will extract the UUID substring and return that. If the provided value is not
 * a string, or it does not contain a valid UUID, then the plugin will generate
 * a new UUID.
 *
 * Example:
 *
 * @code
 * process:
 *   uuid:
 *      plugin: migmag_uuid_generate
 *      source: foo
 * @endcode
 *
 * @MigrateProcessPlugin(
 *   id = "migmag_uuid_generate"
 * )
 */
class MigMagUuidGenerate extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The UUID service.
   *
   * @var \Drupal\Component\Uuid\UuidInterface
   */
  protected $uuidGenerator;

  /**
   * Constructs a new UuidGenerate instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Component\Uuid\UuidInterface $uuid_generator
   *   The UUID service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, UuidInterface $uuid_generator) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->uuidGenerator = $uuid_generator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('uuid')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    return is_string($value) && preg_match('/([0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[0-9a-f]{4}-[0-9a-f]{12})/', $value, $matches)
      ? $matches[1]
      : $this->uuidGenerator->generate();
  }

}
