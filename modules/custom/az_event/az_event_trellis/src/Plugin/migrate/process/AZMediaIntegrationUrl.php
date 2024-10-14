<?php

namespace Drupal\az_event_trellis\Plugin\migrate\process;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\media\Entity\Media;
use Drupal\migrate\Attribute\MigrateProcess;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns a media id based on external URL.
 *
 * @see \Drupal\migrate\Plugin\MigrateProcessInterface
 */
#[MigrateProcess('az_media_integration_url')]
class AZMediaIntegrationUrl extends ProcessPluginBase implements ContainerFactoryPluginInterface {

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
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {

    $id_field = $this->configuration['integration_id'];
    $integration_id = $row->get($id_field);
    $media = NULL;
    try {
      // Fetch the URL.
      $response = $this->httpClient->get($value);
      $contents = $response->getBody()->getContents();
      $node = $this->entityTypeManager->getStorage('node')->loadByProperties(['field_az_trellis_id' => $integration_id]);
      $node = array_shift($node);
      if (!empty($node)) {
        $media = $node->field_az_photos->entity;
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
      // We don't know what this is. Bail out.
      if (!isset($mimemap[$mimetype])) {
        throw new \Exception('Unsure of mime type.');
      }
      // Set the file extension.
      $extension = $mimemap[$mimetype];
      $filename = 'public://trellis_event' . $extension;

      // Create a media entity, if necessary.
      if (empty($media)) {
        $media = Media::create([
          'bundle' => 'az_image',
        ]);
      }
      else {
        // If we already have a file, we need to check if it has changed.
        /** @var \Drupal\Core\Entity\ContentEntityBase $media */
        /** @var \Drupal\file\Entity\File $file */
        $file = $media->field_media_az_image->entity;
        if ($file) {
          // Compare the files via hash.
          $current_hash = Crypt::hashBase64(file_get_contents($file->getFileUri()));
          $hash = Crypt::hashBase64($contents);
          // File is unchanged. Nothing to do.
          if ($hash === $current_hash) {
            return $media->id();
          }
        }
      }
      // Write the data to a file.
      $file = $this->fileRepository->writeData($contents, $filename);
      /** @var \Drupal\Core\Entity\ContentEntityBase $media */
      $media->set('field_media_az_image', [
        'target_id' => $file->id(),
        'alt' => 'image for Trellis event',
      ]);
      $media->save();
      return $media->id();
    }
    catch (\Exception $e) {
    }

    return $media ? $media->id() : NULL;
  }

}
