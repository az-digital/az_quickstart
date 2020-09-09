<?php

namespace Drupal\az_content_chunks;

use Drupal\Core\Asset\LibraryDiscoveryInterface;

/**
 * Class AZParagraphCustomizations. Used to preprocess paragraphs.
 */
class AZParagraphCustomizations {

  /**
   * Drupal\Core\Asset\LibraryDiscoveryInterface definition.
   *
   * @var \Drupal\Core\Asset\LibraryDiscoveryInterface
   */
  protected $libraryDiscovery;

  /**
   * Constructs a new AZParagraphCustomizations object.
   */
  public function __construct(LibraryDiscoveryInterface $library_discovery) {
    $this->libraryDiscovery = $library_discovery;
  }

  /**
   * Counts the number of items in the provided array.
   *
   * @param mixed[] $variables
   *   Array variables from hook_preprocess_paragraph.
   */
  public function preprocessParagraph(array &$variables) {

    /** @var \Drupal\paragraphs\Entity\Paragraph $paragraph */
    $paragraph = $variables['paragraph'];
    // Get the parent bundle.
    $bundle = $paragraph->bundle();
    $name = 'az_content_chunks.' . $bundle;

    // Check if az_content_chunks implements library for the  paragraph bundle.
    $library = $this->libraryDiscovery->getLibraryByName('az_content_chunks', $name);

    // If we found a library, attach it to the block.
    if ($library) {
      $variables['#attached']['library'][] = 'az_content_chunks/' . $name;
    }

  }

}
