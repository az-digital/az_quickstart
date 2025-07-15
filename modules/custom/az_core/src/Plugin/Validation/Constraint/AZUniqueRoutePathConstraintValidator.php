<?php

namespace Drupal\az_core\Plugin\Validation\Constraint;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Routing\RouteProviderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Constraint validator for validating unique route paths.
 */
class AZUniqueRoutePathConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The route provider.
   *
   * @var \Drupal\Core\Routing\RouteProviderInterface
   */
  protected $routeProvider;

  /**
   * Creates a new UniqueRoutePathConstraintValidator instance.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Routing\RouteProviderInterface $route_provider
   *   The route provider.
   */
  public function __construct(ConfigFactoryInterface $config_factory, RouteProviderInterface $route_provider) {
    $this->configFactory = $config_factory;
    $this->routeProvider = $route_provider;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('router.route_provider')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint): void {
    if (empty($value)) {
      return;
    }
    $config_name = $this->context->getRoot();
    $property_path = $this->context->getPropertyPath();
    $existing_value = $this->configFactory()->get($root)->get($property_path);
    $path = strtolower(trim(trim($value), " \\/"));

    if (!empty($path) && $value !== $existing_value) {
      if ($this->routeProvider->getRoutesByPattern($path)->count()) {
        $this->context->addViolation($constraint->message);
      }
    }
  }

}
