<?php declare(strict_types=1);
namespace Nevay\SPI\ServiceProviderDependency;

use Attribute;
use Composer\InstalledVersions;
use Composer\Semver\VersionParser;
use Nevay\SPI\ServiceProviderRequirement;

/**
 * Specifies composer dependencies required by a service provider.
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final class PackageDependency implements ServiceProviderRequirement {

    /**
     * @param string $package composer package
     * @param string $version version constraint
     */
    public function __construct(
        private readonly string $package,
        private readonly string $version,
    ) {}

    public function isSatisfied(): bool {
        return InstalledVersions::isInstalled($this->package)
            && InstalledVersions::satisfies(new VersionParser(), $this->package, $this->version);
    }
}
