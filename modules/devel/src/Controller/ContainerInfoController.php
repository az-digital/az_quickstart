<?php

namespace Drupal\devel\Controller;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DrupalKernelInterface;
use Drupal\Core\Url;
use Drupal\devel\DevelDumperManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Exception\ParameterNotFoundException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides route responses for the container info pages.
 */
class ContainerInfoController extends ControllerBase {

  /**
   * The drupal kernel.
   */
  protected DrupalKernelInterface $kernel;

  /**
   * The dumper manager service.
   */
  protected DevelDumperManagerInterface $dumper;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    $instance = parent::create($container);
    $instance->kernel = $container->get('kernel');
    $instance->dumper = $container->get('devel.dumper');
    $instance->stringTranslation = $container->get('string_translation');

    return $instance;
  }

  /**
   * Builds the services overview page.
   *
   * @return array
   *   A render array as expected by the renderer.
   */
  public function serviceList(): array {
    $headers = [
      $this->t('ID'),
      $this->t('Class'),
      $this->t('Alias'),
      $this->t('Operations'),
    ];

    $rows = [];

    if ($cached_definitions = $this->kernel->getCachedContainerDefinition()) {
      foreach ($cached_definitions['services'] as $service_id => $definition) {
        $service = unserialize($definition);

        $row['id'] = [
          'data' => $service_id,
          'filter' => TRUE,
        ];
        $row['class'] = [
          'data' => $service['class'] ?? '',
          'filter' => TRUE,
        ];
        $row['alias'] = [
          'data' => array_search($service_id, $cached_definitions['aliases'], TRUE) ?: '',
          'filter' => TRUE,
        ];
        $row['operations']['data'] = [
          '#type' => 'operations',
          '#links' => [
            'devel' => [
              'title' => $this->t('Devel'),
              'url' => Url::fromRoute('devel.container_info.service.detail', ['service_id' => $service_id]),
              'attributes' => [
                'class' => ['use-ajax'],
                'data-dialog-type' => 'modal',
                'data-dialog-options' => Json::encode([
                  'width' => 700,
                  'minHeight' => 500,
                ]),
              ],
            ],
          ],
        ];

        $rows[$service_id] = $row;
      }

      ksort($rows);
    }

    $output['services'] = [
      '#type' => 'devel_table_filter',
      '#filter_label' => $this->t('Search'),
      '#filter_placeholder' => $this->t('Enter service id, alias or class'),
      '#filter_description' => $this->t('Enter a part of the service id, service alias or class to filter by.'),
      '#header' => $headers,
      '#rows' => $rows,
      '#empty' => $this->t('No services found.'),
      '#sticky' => TRUE,
      '#attributes' => [
        'class' => ['devel-service-list'],
      ],
    ];

    return $output;
  }

  /**
   * Returns a render array representation of the service.
   *
   * @param string $service_id
   *   The ID of the service to retrieve.
   *
   * @return array
   *   A render array containing the service detail.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   *   If the requested service is not defined.
   */
  public function serviceDetail(string $service_id): array {
    $container = $this->kernel->getContainer();
    /** @var object|null $instance */
    $instance = $container->get($service_id, ContainerInterface::NULL_ON_INVALID_REFERENCE);
    if ($instance === NULL) {
      throw new NotFoundHttpException();
    }

    $output = [];

    // Tries to retrieve the service definition from the kernel's cached
    // container definition.
    $cached_definitions = $this->kernel->getCachedContainerDefinition();
    if ($cached_definitions && isset($cached_definitions['services'][$service_id])) {
      $definition = unserialize($cached_definitions['services'][$service_id]);

      // If the service has an alias add it to the definition.
      if ($alias = array_search($service_id, $cached_definitions['aliases'], TRUE)) {
        $definition['alias'] = $alias;
      }

      $output['definition'] = $this->dumper->exportAsRenderable($definition, $this->t('Computed Definition'));
    }

    $output['instance'] = $this->dumper->exportAsRenderable($instance, $this->t('Instance'));

    return $output;
  }

  /**
   * Builds the parameters overview page.
   *
   * @return array
   *   A render array as expected by the renderer.
   */
  public function parameterList(): array {
    $headers = [
      $this->t('Name'),
      $this->t('Operations'),
    ];

    $rows = [];

    if ($cached_definitions = $this->kernel->getCachedContainerDefinition()) {
      foreach ($cached_definitions['parameters'] as $parameter_name => $definition) {
        $row['name'] = [
          'data' => $parameter_name,
          'filter' => TRUE,
        ];
        $row['operations']['data'] = [
          '#type' => 'operations',
          '#links' => [
            'devel' => [
              'title' => $this->t('Devel'),
              'url' => Url::fromRoute('devel.container_info.parameter.detail', ['parameter_name' => $parameter_name]),
              'attributes' => [
                'class' => ['use-ajax'],
                'data-dialog-type' => 'modal',
                'data-dialog-options' => Json::encode([
                  'width' => 700,
                  'minHeight' => 500,
                ]),
              ],
            ],
          ],
        ];

        $rows[$parameter_name] = $row;
      }

      ksort($rows);
    }

    $output['parameters'] = [
      '#type' => 'devel_table_filter',
      '#filter_label' => $this->t('Search'),
      '#filter_placeholder' => $this->t('Enter parameter name'),
      '#filter_description' => $this->t('Enter a part of the parameter name to filter by.'),
      '#header' => $headers,
      '#rows' => $rows,
      '#empty' => $this->t('No parameters found.'),
      '#sticky' => TRUE,
      '#attributes' => [
        'class' => ['devel-parameter-list'],
      ],
    ];

    return $output;
  }

  /**
   * Returns a render array representation of the parameter value.
   *
   * @param string $parameter_name
   *   The name of the parameter to retrieve.
   *
   * @return array
   *   A render array containing the parameter value.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   *   If the requested parameter is not defined.
   */
  public function parameterDetail(string $parameter_name): array {
    $container = $this->kernel->getContainer();
    try {
      $parameter = $container->getParameter($parameter_name);
    }
    catch (ParameterNotFoundException) {
      throw new NotFoundHttpException();
    }

    return $this->dumper->exportAsRenderable($parameter);
  }

}
