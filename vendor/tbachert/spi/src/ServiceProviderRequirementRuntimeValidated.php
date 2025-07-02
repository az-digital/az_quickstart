<?php declare(strict_types=1);
namespace Nevay\SPI;

/**
 * Specifies requirements for a service provider.
 *
 * Service providers will only be registered if all {@link ServiceProviderRequirement}s are satisfied.
 *
 * Requirements implementing this interface will be re-validated during runtime instead of during composer
 * autoload dumping.
 */
interface ServiceProviderRequirementRuntimeValidated extends ServiceProviderRequirement {

    /**
     * Returns a hash representing the state that lead to the {@link self::isSatisfied()} decision.
     *
     * @return mixed a primitive value representing the state
     */
    public function hash(): mixed;
}
