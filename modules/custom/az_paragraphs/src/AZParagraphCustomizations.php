<?php

namespace Drupal\az_paragraphs;

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
   * Applies modifications to a paragraph being rendered.
   *
   * @param mixed[] $variables
   *   Array variables from hook_preprocess_paragraph.
   */
  public function preprocessParagraph(array &$variables) {

    /** @var \Drupal\paragraphs\Entity\Paragraph $paragraph */
    $paragraph = $variables['paragraph'];
    // Get the parent bundle.
    $bundle = $paragraph->bundle();
    $name = 'az_paragraphs.' . $bundle;

    // Check if az_paragraphs implements library for the  paragraph bundle.
    $library = $this->libraryDiscovery->getLibraryByName('az_paragraphs', $name);

    // If we found a library, attach it to the block.
    if ($library) {
      $variables['#attached']['library'][] = 'az_paragraphs/' . $name;
    }

  }

}
