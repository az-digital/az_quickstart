<?php declare(strict_types=1);
namespace Nevay\SPI;

/**
 * @internal 
 */
final class GeneratedServiceProviderData {

    public const VERSION = 1;

    /**
     * @param class-string $service
     * @return list<class-string>
     */
    public static function providers(string $service): array {
        return match ($service) {
            default => [],
            \OpenTelemetry\API\Instrumentation\AutoInstrumentation\HookManagerInterface::class => [
                ...((true && (($r = new \Nevay\SPI\ServiceProviderDependency\ExtensionDependency('opentelemetry', '^1.0'))->hash() !== false && $r->isSatisfied())) ? [
                \OpenTelemetry\API\Instrumentation\AutoInstrumentation\ExtensionHookManager::class, // open-telemetry/api 1.4.0 (extra.spi)
                ] : []),
            ],
            \OpenTelemetry\API\Configuration\ConfigEnv\EnvComponentLoader::class => [
                \OpenTelemetry\API\Instrumentation\Configuration\General\ConfigEnv\EnvComponentLoaderHttpConfig::class, // open-telemetry/sdk 1.6.0 (extra.spi)
                \OpenTelemetry\API\Instrumentation\Configuration\General\ConfigEnv\EnvComponentLoaderPeerConfig::class, // open-telemetry/sdk 1.6.0 (extra.spi)
            ],
        };
    }
}