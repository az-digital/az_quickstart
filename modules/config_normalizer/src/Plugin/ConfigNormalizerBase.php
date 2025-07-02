<?php

namespace Drupal\config_normalizer\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\config_normalizer\Config\NormalizedReadOnlyStorageInterface;
use Drupal\Core\DependencyInjection\ContainerNotInitializedException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for Config normalizer plugins.
 */
abstract class ConfigNormalizerBase extends PluginBase implements ConfigNormalizerInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Creates a new config normalizer plugin.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * Determines whether the context has a default normalization mode.
   *
   * @param array $context
   *   An array of key-value pairs to pass additional context when needed.
   *
   * @return bool
   *   TRUE if the context normalization mode is default. Otherwise, FALSE.
   */
  protected function isDefaultModeContext(array $context) {
    return !empty($context['normalization_mode']) && ($context['normalization_mode'] === NormalizedReadOnlyStorageInterface::DEFAULT_NORMALIZATION_MODE);
  }

  /**
   * Determines whether the context reference storage is the active storage.
   *
   * @param array $context
   *   An array of key-value pairs to pass additional context when needed.
   *
   * @return bool
   *   TRUE if the context normalization mode is default. Otherwise, FALSE.
   */
  protected function isActiveStorageContext(array $context) {
    // Reverse lookup the storage service to determine if it's active storage.
    if (!empty($context['reference_storage_service'])) {
      $service = $context['reference_storage_service'];
      $serviceId = NULL;
      // @phpstan-ignore-next-line
      $kernel = \Drupal::service('kernel');

      // ReverseContainer available in 9.5.1+ is preferred for reverse lookup.
      // @phpstan-ignore-next-line
      if (\Drupal::hasService('Drupal\Component\DependencyInjection\ReverseContainer')) {
        // ReverseContainer specifically not injected because of support scope.
        // @phpstan-ignore-next-line
        $serviceId = \Drupal::service('Drupal\Component\DependencyInjection\ReverseContainer')->getId($service);
      }
      // getServiceIdMapping() first available in 9.5.0, deprecated.
      // This should be removed when 9.5.0 compatibility is no longer required.
      elseif (method_exists($kernel, 'getServiceIdMapping')) {
        // @phpstan-ignore-next-line
        $mapping = $kernel->getServiceIdMapping();
        try {
          // @phpstan-ignore-next-line
          $container = \Drupal::getContainer();
          // Deprecated function included only for 9.5.0 compatibility.
          // @phpstan-ignore-next-line
          $serviceId = $mapping[$container->generateServiceIdHash($service)] ?? NULL;
        }
        catch (ContainerNotInitializedException $e) {
        }

      }
      // Fallback to dynamic property before 9.5.
      // This should be removed when < 9.5 compatibility is no longer required.
      elseif (!empty($service->_serviceId)) {
        $serviceId = $service->_serviceId;
      }
      // TRUE if the service is the active storage service.
      if ($serviceId === 'config.storage') {
        return TRUE;
      }
    }

    return FALSE;
  }

}
