<?php

namespace Drupal\az_event_trellis\Plugin\QueueWorker;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\media\Entity\Media;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Queue worker to lazily download media from integrations.
 *
 * @QueueWorker(
 *   id = "az_deferred_media",
 *   title = @Translation("Quickstart Deferred Media"),
 *   cron = {
 *     "time" = 30,
 *   },
 * )
 */
class AZDeferredMedia extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\file\FileRepository
   */
  protected $fileRepository;

  /**
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = new static(
      $configuration,
      $plugin_id,
      $plugin_definition
    );

    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->fileRepository = $container->get('file.repository');
    $instance->httpClient = $container->get('http_client');
    $instance->logger = $container->get('logger.factory')->get('az_event_trellis');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    $id = $data['id'];
    $entity_type = $data['entity_type'] ?? 'node';
    $media_field = $data['media_field'] ?? 'field_az_photos';
    $file_field = $data['file_field'] ?? 'field_media_az_image';
    $id_field = $data['id_field'] ?? 'nid';
    $url = $data['url'] ?? '';
    $alt = $data['alt'] ?? '';
    $media = NULL;
    try {
      // Fetch the URL.
      $response = $this->httpClient->get($url);
      $contents = $response->getBody()->getContents();
      $entity = $this->entityTypeManager->getStorage($entity_type)->loadByProperties([$id_field => $id]);
      /** @var \Drupal\Core\Entity\ContentEntityBase $entity */
      $entity = array_shift($entity);
      if (empty($url)) {
        throw new \Exception('Missing url for remote media');
      }
      if (is_null($entity)) {
        throw new \Exception('Could not look up destination entity.');
      }
      if ($entity->hasField($media_field)) {
        $media = $entity->get($media_field)->entity;
      }
      // Prepare to guess type of file.
      $finfo = finfo_open();
      if (empty($contents)) {
        throw new \Exception('Empty response when fetching remote media.');
      }
      // Attempt to retrieve the mime type from the response.
      $mimetype = finfo_buffer($finfo, $contents, FILEINFO_MIME_TYPE);
      $mimemap = [
        'image/webp' => '.webp',
        'image/png' => '.png',
        'image/jpeg' => '.jpg',
      ];
      // We don't know what the file is. Abort the attempt.
      if (!isset($mimemap[$mimetype])) {
        throw new \Exception('Could not determine media mime type.');
      }
      // Set the file extension.
      $extension = $mimemap[$mimetype];
      $file_base = $data['filename'] ?? 'deferred_image';
      $filename = 'public://' . $file_base . $extension;

      // Create a media entity, if necessary.
      if (empty($media)) {
        $media = Media::create([
          'bundle' => ($data['media_type'] ?? 'az_image'),
        ]);
      }
      else {
        // If we already have a file, we need to check if it has changed.
        /** @var \Drupal\Core\Entity\ContentEntityBase $media */
        if ($media->hasField($file_field)) {
          /** @var \Drupal\file\Entity\File $file */
          $file = $media->get($file_field)->entity;
          if ($file) {
            // Compare the files via hash.
            $current_hash = Crypt::hashBase64(file_get_contents($file->getFileUri()));
            $hash = Crypt::hashBase64($contents);
            // File is unchanged. Nothing to do.
            if ($hash === $current_hash) {
              throw new \Exception('Remote image is unchanged');
            }
          }
        }

      }
      // Write the data to a file.
      $file = $this->fileRepository->writeData($contents, $filename);
      /** @var \Drupal\Core\Entity\ContentEntityBase $media */
      if ($media->hasField($file_field)) {
        $media->set($file_field, [
          'target_id' => $file->id(),
          'alt' => $alt,
        ]);
      }
      $media->save();
      if ($entity->hasField($media_field)) {
        $entity->set($media_field, [
          'target_id' => $media->id(),
        ]);
        $entity->save();
      }
    }
    catch (\Exception $e) {
      // Emit log messages.
      $message = $e->getMessage();
      $this->logger->info("%entity_type @id @url %message.", [
        '%entity_type' => $entity_type,
        '@id' => $id,
        '@url' => $url,
        '%message' => $message,
      ]);
    }
  }

}
