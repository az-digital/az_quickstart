<?php declare(strict_types=1);
// @codingStandardsIgnoreFile

/**
 * This file was generated via php core/scripts/generate-proxy-class.php 'Drupal\sophron_guesser\SophronMimeTypeGuesser' "modules/contrib/sophron/sophron_guesser/src".
 */

namespace Drupal\sophron_guesser\ProxyClass {

    /**
     * Provides a proxy class for \Drupal\sophron_guesser\SophronMimeTypeGuesser.
     *
     * @see \Drupal\Component\ProxyBuilder
     */
    class SophronMimeTypeGuesser implements \Symfony\Component\Mime\MimeTypeGuesserInterface
    {

        use \Drupal\Core\DependencyInjection\DependencySerializationTrait;

        /**
         * The id of the original proxied service.
         *
         * @var string
         */
        protected $drupalProxyOriginalServiceId;

        /**
         * The real proxied service, after it was lazy loaded.
         *
         * @var \Drupal\sophron_guesser\SophronMimeTypeGuesser
         */
        protected $service;

        /**
         * The service container.
         *
         * @var \Symfony\Component\DependencyInjection\ContainerInterface
         */
        protected $container;

        /**
         * Constructs a ProxyClass Drupal proxy object.
         *
         * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
         *   The container.
         * @param string $drupal_proxy_original_service_id
         *   The service ID of the original service.
         */
        public function __construct(\Symfony\Component\DependencyInjection\ContainerInterface $container, $drupal_proxy_original_service_id)
        {
            $this->container = $container;
            $this->drupalProxyOriginalServiceId = $drupal_proxy_original_service_id;
        }

        /**
         * Lazy loads the real service from the container.
         *
         * @return object
         *   Returns the constructed real service.
         */
        protected function lazyLoadItself()
        {
            if (!isset($this->service)) {
                $this->service = $this->container->get($this->drupalProxyOriginalServiceId);
            }

            return $this->service;
        }

        /**
         * {@inheritdoc}
         */
        public function guessMimeType(string $path): string
        {
            return $this->lazyLoadItself()->guessMimeType($path);
        }

        /**
         * {@inheritdoc}
         */
        public function isGuesserSupported(): bool
        {
            return $this->lazyLoadItself()->isGuesserSupported();
        }

        /**
         * {@inheritdoc}
         */
        public function setMapping(?array $mapping = NULL)
        {
            return $this->lazyLoadItself()->setMapping($mapping);
        }

    }

}
