<?php declare(strict_types=1);
namespace Nevay\SPI\Composer;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Package\Package;
use Composer\Package\PackageInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;
use Composer\Util\Filesystem;
use Composer\Util\Platform;
use Nevay\SPI\ServiceLoader;
use Nevay\SPI\ServiceProviderRequirementRuntimeValidated;
use ReflectionAttribute;
use ReflectionClass;
use RuntimeException;
use function array_diff;
use function array_fill_keys;
use function array_unique;
use function class_exists;
use function getcwd;
use function implode;
use function is_string;
use function json_encode;
use function preg_match;
use function realpath;
use function sprintf;
use function var_export;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

final class Plugin implements PluginInterface, EventSubscriberInterface {

    private const FQCN_REGEX = '/^\\\\?[a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*(?:\\\\[a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*)*$/';

    public static function getSubscribedEvents(): array {
        return [
            ScriptEvents::PRE_AUTOLOAD_DUMP => 'preAutoloadDump',
        ];
    }

    public function activate(Composer $composer, IOInterface $io): void {
        // no-op
    }

    public function deactivate(Composer $composer, IOInterface $io): void {
        // no-op
    }

    public function uninstall(Composer $composer, IOInterface $io): void {
        $filesystem = new Filesystem();
        $vendorDir = $this->vendorDir($composer, $filesystem);
        $filesystem->remove($vendorDir . '/composer/GeneratedServiceProviderData.php');
    }

    public function preAutoloadDump(Event $event): void {
        // ClassLoader creation based on EventDispatcher::getScriptListeners()
        $package = $event->getComposer()->getPackage();
        $packages = $event->getComposer()->getRepositoryManager()->getLocalRepository()->getCanonicalPackages();
        $packageMap = $event->getComposer()->getAutoloadGenerator()->buildPackageMap($event->getComposer()->getInstallationManager(), $package, $packages);
        $map = $event->getComposer()->getAutoloadGenerator()->parseAutoloads($packageMap, $package);
        $loader = $event->getComposer()->getAutoloadGenerator()->createLoader($map);

        $loader->register();
        try {
            $this->dumpGeneratedServiceProviderData($event);
        } finally {
            $loader->unregister();
        }
    }

    private function dumpGeneratedServiceProviderData(Event $event): void {
        $match = '';
        foreach ($this->serviceProviders($event->getComposer(), $event->getIO()) as $service => $providers) {
            if (!preg_match(self::FQCN_REGEX, $service)) {
                $event->getIO()->warning(sprintf('Invalid extra.spi configuration, expected class name, got "%s" (%s)', $service, implode(', ', array_unique($providers))));
                continue;
            }
            if (!ServiceLoader::serviceAvailable($service)) {
                $event->getIO()->info(sprintf('Skipping extra.spi service "%s", service not available (%s)', $service, implode(', ', array_unique($providers))));
                continue;
            }

            if ($service[0] !== '\\') {
                $service = '\\' . $service;
            }

            $match .= "\n            $service::class => [";
            foreach ($providers as $provider => $package) {
                if (!preg_match(self::FQCN_REGEX, $provider)) {
                    $event->getIO()->warning(sprintf('Invalid extra.spi configuration, expected class name, got "%s" for "%s" (%s)', $provider, $service, $package));
                    continue;
                }
                if (!class_exists($provider)) {
                    $event->getIO()->info(sprintf('Skipping extra.spi configuration, provider class "%s" for "%s" does not exist (%s)', $provider, $service, $package));
                    continue;
                }
                if (!ServiceLoader::providerAvailable($provider, skipRuntimeValidatedRequirements: true)) {
                    $event->getIO()->info(sprintf('Skipping extra.spi provider "%s" for "%s", provider not available (%s)', $provider, $service, $package));
                    continue;
                }

                if ($provider[0] !== '\\') {
                    $provider = '\\' . $provider;
                }

                if ($condition = self::providerRuntimeValidatedRequirements($provider)) {
                    $match .= "\n                ...(($condition) ? [";
                    $match .= "\n                $provider::class, // $package";
                    $match .= "\n                ] : []),";
                } else {
                    $match .= "\n                $provider::class, // $package";
                }
            }
            $match .= "\n            ],";
        }
        $code = <<<PHP
            <?php declare(strict_types=1);
            namespace Nevay\SPI;
            
            /**
             * @internal 
             */
            final class GeneratedServiceProviderData {
            
                public const VERSION = 1;
            
                /**
                 * @param class-string \$service
                 * @return list<class-string>
                 */
                public static function providers(string \$service): array {
                    return match (\$service) {
                        default => [],$match
                    };
                }
            }
            PHP;

        $filesystem = new Filesystem();
        $vendorDir = $this->vendorDir($event->getComposer(), $filesystem);
        $filesystem->ensureDirectoryExists($vendorDir . '/composer');
        $filesystem->filePutContentsIfModified($vendorDir . '/composer/GeneratedServiceProviderData.php', $code);

        $package = $event->getComposer()->getPackage();
        $autoload = $package->getAutoload();
        $autoload['classmap'][] = $vendorDir . '/composer/GeneratedServiceProviderData.php';
        $package->setAutoload($autoload);
    }

    private static function providerRuntimeValidatedRequirements(string $provider): ?string {
        // The current implementation is suboptimal if multiple runtime validated requirements are specified
        // - should check all hashes of not satisfied requirements before calling ::isSatisfied()
        // - should deduplicate requirements

        $condition = var_export(true, true);
        /** @var ReflectionAttribute<ServiceProviderRequirementRuntimeValidated> $attribute */
        foreach ((new ReflectionClass($provider))->getAttributes(ServiceProviderRequirementRuntimeValidated::class, ReflectionAttribute::IS_INSTANCEOF) as $attribute) {
            $requirement = $attribute->newInstance();
            $class = '\\' . $requirement::class;
            $args = '';
            foreach ($attribute->getArguments() as $key => $value) {
                $args and $args .= ', ';
                if (is_string($key)) {
                    $args .= $key;
                    $args .= ': ';
                }
                $args .= var_export($value, true);
            }

            $hash = var_export($requirement->hash(), true);
            $condition .= $requirement->isSatisfied()
                ? /** @lang PHP */ " && ((\$r = new $class($args))->hash() === $hash || \$r->isSatisfied())"
                : /** @lang PHP */ " && ((\$r = new $class($args))->hash() !== $hash && \$r->isSatisfied())";
        }

        if (!isset($requirement)) {
            return null;
        }

        return $condition;
    }

    private function vendorDir(Composer $composer, Filesystem $filesystem): string {
        return $filesystem->normalizePath($composer->getConfig()->get('vendor-dir'));
    }

    /**
     * @return array<class-string, array<class-string, string>>
     */
    private function serviceProviders(Composer $composer, IOInterface $io): array {
        $mappings = [];
        $this->serviceProvidersFromExtraSpi($composer->getPackage(), $mappings);
        $this->serviceProvidersFromAutoloadFiles($composer->getPackage(), $mappings, self::getCwd(), $io);
        foreach ($composer->getRepositoryManager()->getLocalRepository()->getPackages() as $package) {
            $this->serviceProvidersFromExtraSpi($package, $mappings);

            if (($installPath = $composer->getInstallationManager()->getInstallPath($package)) !== null) {
                $this->serviceProvidersFromAutoloadFiles($package, $mappings, $installPath, $io);
            }
        }

        return $mappings;
    }

    private function serviceProvidersFromExtraSpi(PackageInterface $package, array &$mappings): void {
        foreach ($package->getExtra()['spi'] ?? [] as $service => $providers) {
            $providers = (array) $providers;
            $mappings[$service] ??= [];
            $mappings[$service] += array_fill_keys($providers, $package->getPrettyString() . ' (extra.spi)');
        }
    }

    private function serviceProvidersFromAutoloadFiles(PackageInterface $package, array &$mappings, string $installPath, IOInterface $io): void {
        $autoloadFiles = $package->getAutoload()['files'] ?? [];
        $spiAutoloadFiles = $package->getExtra()['spi-config']['autoload-files'] ?? false ?: [];
        $spiPruneAutoloadFiles = $package->getExtra()['spi-config']['prune-autoload-files'] ?? null;

        if ($spiAutoloadFiles === true) {
            $spiAutoloadFiles = $autoloadFiles;
        }
        if ($spiPruneAutoloadFiles === true) {
            $spiPruneAutoloadFiles = $spiAutoloadFiles;
        }

        $includeFile = (static fn(string $file) => require $file)->bindTo(null, null);
        foreach ($spiAutoloadFiles as $index => $file) {
            $io->debug(sprintf('Loading service providers from "%s" (%s)', $file, $package->getPrettyString()));

            if (!$includedProviders = ServiceLoader::collectProviders($includeFile, $installPath . '/' . $file)) {
                unset($spiAutoloadFiles[$index]);
                continue;
            }

            foreach ($includedProviders as $service => $providers) {
                $mappings[$service] ??= [];
                $mappings[$service] += array_fill_keys($providers, $package->getPrettyString() . ' (' . json_encode($file, flags: JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ')');
            }
        }

        $spiPruneAutoloadFiles ??= $spiAutoloadFiles;
        if ($spiPruneAutoloadFiles && $autoloadFiles && $package instanceof Package) {
            $io->debug(sprintf('Pruning autoload.files (%s): %s', $package->getPrettyString(), implode(', ', $spiPruneAutoloadFiles)));

            $autoload = $package->getAutoload();
            $autoload['files'] = array_diff($autoloadFiles, $spiPruneAutoloadFiles);
            $package->setAutoload($autoload);
        }
    }

    /**
     * @see Platform::getCwd()
     */
    private static function getCwd(): string {
        $cwd = getcwd();

        if ($cwd === false) {
            $cwd = realpath('');
        }

        if ($cwd === false) {
            throw new RuntimeException('Could not determine the current working directory');
        }

        return $cwd;
    }
}
