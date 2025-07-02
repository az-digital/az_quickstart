<?php declare(strict_types=1);
namespace Nevay\SPI;

use Closure;
use Iterator;
use IteratorAggregate;
use ReflectionAttribute;
use ReflectionClass;
use function class_exists;
use function in_array;
use function interface_exists;

/**
 * Service provider loading facility.
 *
 * @template-covariant S of object service type
 * @implements IteratorAggregate<class-string, S>
 */
final class ServiceLoader implements IteratorAggregate {

    /** @var array<class-string, list<class-string>> */
    private static array $mappings = [];
    private static bool $skipChecks = false;

    /** @var class-string<S> */
    private readonly string $service;
    /** @var list<class-string> */
    private array $providers;
    /** @var array<int, S|false> */
    private array $cache = [];

    /**
     * @param class-string<S> $service
     * @param list<class-string> $providers
     */
    private function __construct(string $service, array $providers) {
        $this->service = $service;
        $this->providers = $providers;
    }

    /**
     * Registers a service provider implementation for the given service type.
     *
     * Equivalent to `composer.json` configuration:
     * ```
     * "extra": {
     *     "spi": {
     *         $service: [
     *             $provider
     *         ]
     *     }
     * }
     * ```
     *
     * @template S_ of object service type
     * @template P_ of S_ service provider
     * @param class-string<S_> $service service to provide
     * @param class-string<P_> $provider provider class, must have a public
     *        zero-argument constructor
     * @return bool whether the provider is available
     */
    public static function register(string $service, string $provider): bool {
        if (in_array($provider, self::providers($service), true)) {
            return true;
        }
        if (!self::$skipChecks && (!self::serviceAvailable($service) || !self::providerAvailable($provider))) {
            return false;
        }

        self::$mappings[$service][] = $provider;

        return true;
    }

    /**
     * Lazy loads service providers for the given service.
     *
     * @template S_ of object service type
     * @param class-string<S_> $service service to load
     * @return ServiceLoader<S_> service loader for the given service
     */
    public static function load(string $service): ServiceLoader {
        return new self($service, self::providers($service));
    }

    public function getIterator(): Iterator {
        return new ServiceLoaderIterator($this->service, $this->providers, $this->cache);
    }

    /**
     * Reloads this service loader, clearing all cached instances.
     */
    public function reload(): void {
        unset($this->cache);
        $this->cache = [];
        $this->providers = self::providers($this->service);
    }

    /**
     * @param class-string $service
     * @return list<class-string>
     */
    private static function providers(string $service): array {
        if (($providers = self::$mappings[$service] ?? null) !== null) {
            return $providers;
        }

        $providers = class_exists(GeneratedServiceProviderData::class) && GeneratedServiceProviderData::VERSION === 1
            ? GeneratedServiceProviderData::providers($service)
            : [];

        return self::$mappings[$service] ??= $providers;
    }

    /**
     * @internal
     */
    public static function serviceAvailable(string $service): bool {
        return interface_exists($service) || class_exists($service);
    }

    /**
     * @internal
     */
    public static function providerAvailable(string $provider, bool $skipRuntimeValidatedRequirements = false): bool {
        if (!class_exists($provider)) {
            return false;
        }

        $reflection = new ReflectionClass($provider);
        /** @var ReflectionAttribute<ServiceProviderRequirement> $attribute */
        foreach ($reflection->getAttributes(ServiceProviderRequirement::class, ReflectionAttribute::IS_INSTANCEOF) as $attribute) {
            $requirement = $attribute->newInstance();
            if ($skipRuntimeValidatedRequirements && $requirement instanceof ServiceProviderRequirementRuntimeValidated) {
                continue;
            }
            if (!$requirement->isSatisfied()) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array<class-string, list<class-string>>
     *
     * @internal
     */
    public static function collectProviders(Closure $closure, mixed ...$args): array {
        $skipChecks = self::$skipChecks;
        $mappings = self::$mappings;
        self::$skipChecks = true;
        self::$mappings = [];

        try {
            $closure(...$args);

            return self::$mappings;
        } finally {
            self::$skipChecks = $skipChecks;
            self::$mappings = $mappings;
        }
    }
}
