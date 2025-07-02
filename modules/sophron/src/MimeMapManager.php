<?php

declare(strict_types=1);

namespace Drupal\sophron;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\sophron\Event\MapEvent;
use Drupal\sophron\Map\DrupalMap;
use FileEye\MimeMap\Extension;
use FileEye\MimeMap\Map\AbstractMap;
use FileEye\MimeMap\Map\DefaultMap;
use FileEye\MimeMap\MapHandler;
use FileEye\MimeMap\MappingException;
use FileEye\MimeMap\Type;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Provides a sensible mapping between filename extensions and MIME types.
 */
class MimeMapManager implements MimeMapManagerInterface {

  use StringTranslationTrait;

  /**
   * The module configuration settings.
   */
  protected ImmutableConfig $sophronSettings;

  /**
   * The FQCN of the map currently in use.
   */
  protected string $currentMapClass;

  /**
   * The array of initialized map classes.
   *
   * Keyed by FQCN, each value stores the array of initialization errors.
   *
   * @var array
   */
  protected array $initializedMapClasses = [];

  public function __construct(
    protected ConfigFactoryInterface $configFactory,
    protected EventDispatcherInterface $eventDispatcher,
    protected ModuleHandlerInterface $moduleHandler,
  ) {
    $this->sophronSettings = $this->configFactory->get('sophron.settings');
  }

  /**
   * {@inheritdoc}
   */
  public function isMapClassValid(string $mapClass): bool {
    if (class_exists($mapClass) && in_array(AbstractMap::class, class_parents($mapClass))) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getMapClass(): string {
    if (!isset($this->currentMapClass)) {
      switch ($this->sophronSettings->get('map_option')) {
        case static::DRUPAL_MAP:
          $this->setMapClass(DrupalMap::class);
          break;

        case static::DEFAULT_MAP:
          $this->setMapClass(DefaultMap::class);
          break;

        case static::CUSTOM_MAP:
          $mapClass = $this->sophronSettings->get('map_class');
          $this->setMapClass($this->isMapClassValid($mapClass) ? $mapClass : DrupalMap::class);
          break;

      }
    }
    return $this->currentMapClass;
  }

  /**
   * {@inheritdoc}
   */
  public function setMapClass(string $mapClass): MimeMapManagerInterface {
    $this->currentMapClass = $mapClass;
    if (!isset($this->initializedMapClasses[$mapClass])) {
      $event = new MapEvent($mapClass);
      $this->eventDispatcher->dispatch($event, MapEvent::INIT);
      $this->initializedMapClasses[$mapClass] = $event->getErrors();
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getMappingErrors(string $mapClass): array {
    $this->setMapClass($mapClass);
    return $this->initializedMapClasses[$mapClass] ?? [];
  }

  /**
   * {@inheritdoc}
   */
  public function listTypes(): array {
    return MapHandler::map($this->getMapClass())->listTypes();
  }

  /**
   * {@inheritdoc}
   */
  public function getType(string $type): Type {
    return new Type($type, $this->getMapClass());
  }

  /**
   * {@inheritdoc}
   */
  public function listExtensions(): array {
    return MapHandler::map($this->getMapClass())->listExtensions();
  }

  /**
   * {@inheritdoc}
   */
  public function getExtension(string $extension): Extension {
    return new Extension($extension, $this->getMapClass());
  }

  /**
   * {@inheritdoc}
   */
  public function requirements(string $phase): array {
    $is_sophron_guessing = $this->moduleHandler->moduleExists('sophron_guesser');
    return [
      'mime_type_guessing_sophron' => [
        'title' => $this->t('MIME type guessing'),
        'value' => $is_sophron_guessing ? $this->t('Sophron') : $this->t('Drupal core'),
        'description' => $is_sophron_guessing ? $this->t('The <strong>Sophron guesser</strong> module is providing MIME type guessing. <a href=":url">Uninstall the module</a> to revert to Drupal core guessing.', [':url' => Url::fromRoute('system.modules_uninstall')->toString()]) : $this->t('Drupal core is providing MIME type guessing. <a href=":url">Install the <strong>Sophron guesser</strong> module</a> to allow the enhanced guessing provided by Sophron.', [':url' => Url::fromRoute('system.modules_list')->toString()]),
      ],
    ];
  }

  /**
   * Returns an array of gaps of a map vs Drupal's core mapping.
   *
   * @param class-string<\FileEye\MimeMap\Map\MimeMapInterface> $mapClass
   *   A FQCN.
   *
   * @return array
   *   An array of simple arrays, each having a file extension, its Drupal MIME
   *   type guess, and a gap information.
   *
   * @todo add to interface in sophron:3.0.0
   */
  public function determineMapGaps(string $mapClass): array {
    $currentMapClass = $this->getMapClass();
    $this->setMapClass($mapClass);

    $core_extended_guesser = new CoreExtensionMimeTypeGuesserExtended();

    $extensions = $core_extended_guesser->listExtensions();
    sort($extensions);

    $rows = [];
    foreach ($extensions as $ext) {
      $drupal_mime_type = $core_extended_guesser->guessMimeType('a.' . (string) $ext);

      $extension = $this->getExtension((string) $ext);
      try {
        $mimemap_mime_type = $extension->getDefaultType();
      }
      catch (MappingException $e) {
        $mimemap_mime_type = '';
      }

      $gap = '';
      if ($mimemap_mime_type === '') {
        $gap = $this->t('No MIME type mapped to this file extension.');
      }
      elseif (mb_strtolower($drupal_mime_type) != mb_strtolower($mimemap_mime_type)) {
        $gap = $this->t("File extension mapped to '@type' instead.", ['@type' => $mimemap_mime_type]);
      }

      if ($gap !== '') {
        $rows[] = [(string) $ext, $drupal_mime_type, $gap];
      }
    }

    $this->setMapClass($currentMapClass);
    return $rows;
  }

}
