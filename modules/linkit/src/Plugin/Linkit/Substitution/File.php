<?php

namespace Drupal\linkit\Plugin\Linkit\Substitution;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\file\FileInterface;
use Drupal\linkit\SubstitutionInterface;

/**
 * A substitution plugin for the URL to a file.
 *
 * @Substitution(
 *   id = "file",
 *   label = @Translation("Direct File URL"),
 * )
 */
class File extends PluginBase implements SubstitutionInterface {

  /**
   * {@inheritdoc}
   */
  public function getUrl(EntityInterface $entity) {
    if (!($entity instanceof FileInterface)) {
      return NULL;
    }

    /** @var \Drupal\Core\File\FileUrlGeneratorInterface $file_url_generator */
    $file_url_generator = \Drupal::service('file_url_generator');
    return $file_url_generator->generate($entity->getFileUri());
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(EntityTypeInterface $entity_type) {
    return $entity_type->entityClassImplements('Drupal\file\FileInterface');
  }

}
