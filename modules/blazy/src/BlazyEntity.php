<?php

namespace Drupal\blazy;

use Drupal\Core\Entity\EntityInterface;
use Drupal\blazy\Media\BlazyOEmbedInterface;
use Drupal\blazy\Utility\CheckItem;
use Drupal\blazy\internals\Internals;
use Drupal\media\MediaInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides common entity utilities to work with field details or vanilla.
 */
class BlazyEntity implements BlazyEntityInterface {

  /**
   * The blazy oembed service.
   *
   * @var \Drupal\blazy\Media\BlazyOEmbedInterface
   */
  protected $oembed;

  /**
   * The blazy manager service.
   *
   * @var \Drupal\blazy\BlazyManagerInterface
   */
  protected $blazyManager;

  /**
   * The blazy media service.
   *
   * @var \Drupal\blazy\Media\BlazyMediaInterface
   */
  protected $blazyMedia;

  /**
   * Constructs a BlazyEntity instance.
   */
  public function __construct(BlazyOEmbedInterface $oembed) {
    $this->oembed = $oembed;
    $this->blazyManager = $oembed->blazyManager();
    $this->blazyMedia = $oembed->blazyMedia();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('blazy.oembed')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function oembed() {
    return $this->oembed;
  }

  /**
   * {@inheritdoc}
   */
  public function blazyManager() {
    return $this->blazyManager;
  }

  /**
   * {@inheritdoc}
   */
  public function blazyMedia() {
    return $this->blazyMedia;
  }

  /**
   * {@inheritdoc}
   */
  public function build(array $data): array {
    $manager = $this->blazyManager;
    $manager->hashtag($data);

    $access   = $data['#access'] ?? FALSE;
    $entity   = $data['#entity'] ?? NULL;
    $settings = &$data['#settings'];

    if (!$entity instanceof EntityInterface) {
      return [];
    }

    if (!$access && $denied = $manager->denied($entity)) {
      return $denied;
    }

    // @todo remove $settings after sub-modules: gridstack, slick_browser.
    $data['#access'] = TRUE;
    $data['#delta']  = $data['#delta'] ?? ($settings['delta'] ?? -1);

    // Extract media data with translated one, dup required by self::prepare().
    if ($entity instanceof MediaInterface) {
      $entity = $this->blazyMedia->prepare($data);
    }

    // Prepare container settings.
    // @todo re-arrange, this needs media metadata from ::oembed() below.
    // Temporary, extracted separately via BlazyMedia::prepare() above.
    $this->prepare($data);

    // Individual entity settings.
    self::settings($settings, $entity);

    // Since 3.0.9, mimicking Blazy formatters so to swap settings once.
    // At most cases, this class is accessed from Views, or Entity Browser.
    // Should save many lightbox-related sub-modules from another hook_alter.
    // See \Drupal\blazy\Plugin\views\field\BLAH.
    // See \Drupal\io_browser\Plugin\EntityBrowser\BLAH.
    // See \Drupal\slick_browser\Plugin\EntityBrowser\BLAH.
    $view = $data['#view'] ?? NULL;
    $manager->moduleHandler()->alter('blazy_settings', $data, $view);
    $settings = &$data['#settings'];

    // $manager->toSettings($settings, $info);
    $manager->postSettingsAlter($settings, $entity);

    // Build the Media item.
    $this->oembed->build($data);

    // Only pass to Blazy for known entities related to File or Media.
    if (in_array($entity->getEntityTypeId(), ['file', 'media'])) {
      unset($data['fallback']);
      $build = $this->blazyMedia->build($data);
    }
    else {
      // Else entity.get.view or view builder aka vanilla.
      $build = $this->view($data);
    }

    $manager->moduleHandler()->alter('blazy_build_entity', $build, $entity, $settings);

    // Allows a standalone blazy layout media to have container for lightboxes.
    if ($config = $build['#build']['#settings'] ?? []) {
      if ($blazies = $config['blazies'] ?? NULL) {
        if ($blazies->use('container')) {
          $content = $build;
          $attrs = [];
          Blazy::containerAttributes($attrs, $config);

          $build = [
            '#type' => 'container',
            '#attributes' => $attrs,
            'content' => $content,
          ];
        }
      }
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function prepare(array &$data): void {
    $manager = $this->blazyManager;
    $manager->hashtag($data);

    $settings = &$data['#settings'];
    $blazies = $manager->verifySafely($settings);

    if ($blazies->was('entity_prepared')) {
      return;
    }

    $manager->preSettings($settings);
    $manager->prepareData($data);
    $manager->postSettings($settings);

    // Reset in case locked too early before enough data, yet lock it locally.
    // Seen the problem with GridStack Media player at LB, initialized was
    // flagged at ::preSettings() above.
    $blazies->set('was.initialized', FALSE)
      ->set('was.entity_prepared', TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function view(array $data): array {
    $manager  = $this->blazyManager;
    $settings = $manager->toHashtag($data);
    $entity   = $data['#entity'] ?? NULL;
    $build    = [];

    // Might be called independently from self::build().
    if (!$entity instanceof EntityInterface) {
      return [];
    }

    // Re-defined, needed downstream by local video, etc.
    $data['#settings']['view_mode'] = $settings['view_mode'] ?? 'default';

    // Provides a convenient one view call for any entities, mostly guess works,
    // if accessed outside self::build() which already took care of this.
    if (in_array($entity->getEntityTypeId(), ['file', 'media'])) {
      try {
        // @todo recheck if doable with BlazyMedia::build().
        unset($data['fallback']);
        $build = $this->blazyMedia->view($data);
      }
      catch (\Exception $ignore) {
        // Do nothing, no need to be chatty in mischievous deeds.
      }
    }

    // Provides an entity.get.view or view builder aka vanilla.
    return $build ?: $manager->view($data);
  }

  /**
   * Modifies the common settings extracted from the given entity.
   */
  public static function settings(array &$settings, $entity): void {
    // Might be accessed by tests, or anywhere outside the workflow.
    $blazies  = Internals::verify($settings);
    $langcode = $blazies->get('language.current');

    if ($info = CheckItem::entity($entity, $langcode)) {
      $data = $info['data'];
      $id   = $data['id'];
      $rid  = $data['rid'];

      $blazies->set('cache.metadata.keys', [$id, $rid], TRUE)
        ->set('entity', $data, TRUE);
    }
  }

}
