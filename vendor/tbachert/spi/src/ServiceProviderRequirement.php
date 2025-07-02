<?php declare(strict_types=1);
namespace Nevay\SPI;

/**
 * Specifies requirements for a service provider.
 *
 * Service providers will only be registered if all {@link ServiceProviderRequirement}s are satisfied.
 */
interface ServiceProviderRequirement {

    /**
     * Returns whether this requirement is satisfied.
     *
     * @return bool true if this requirement is satisfied, false otherwise
     */
    public function isSatisfied(): bool;
}
