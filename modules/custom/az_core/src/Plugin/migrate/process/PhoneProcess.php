<?php

namespace Drupal\az_core\Plugin\migrate\process;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\Attribute\MigrateProcess;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use libphonenumber\PhoneNumberUtil;
use libphonenumber\PhoneNumberFormat;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Attempts to convert a string to a phone number.
 *
 * Example:
 *
 * @code
 * process:
 *   field_az_phone:
 *     plugin: az_phone
 *     source: phone
 * @endcode
 */
#[MigrateProcess(
  id: 'az_phone',
  handle_multiples: TRUE,
)]
class PhoneProcess extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $pluginId, $pluginDefinition, ?MigrationInterface $migration = NULL) {
    $instance = new static(
      $configuration,
      $pluginId,
      $pluginDefinition
    );
    $instance->configFactory = $container->get('config.factory');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $phones = [];

    if (empty($value)) {
      return $phones;
    }
    $was_array = FALSE;
    if (!is_array($value)) {
      $was_array = TRUE;
      $value = [$value];
    }
    foreach ($value as $phone) {
      $formatted = [];
      // Profiles API phones are nested.
      $phone = $phone['number'] ?? $phone;
      // We'll attempt to do some formatting on the phone number.
      try {
        $util = PhoneNumberUtil::getInstance();
        // Get default region.
        $region = $this->configFactory->get('system.date')->get('country.default') ?? 'US';
        $number = $util->parse($phone, $region);
        $phone = $util->format($number, PhoneNumberFormat::NATIONAL);
      }
      catch (\Exception $e) {
        // If we couldn't match, just continue with the number as-is.
      }
      $phones[] = $phone;
    }

    if (!$was_array) {
      $phones = reset($phones);
    }
    return $phones;
  }

}
