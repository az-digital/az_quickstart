<?php

declare(strict_types=1);

namespace Drupal\Tests\linkit\Kernel;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Language\LanguageInterface;

/**
 * Provides helper methods for assertions.
 */
trait AssertLinkitFilterTrait {

  /**
   * The linkit filter.
   *
   * @var \Drupal\filter\Plugin\FilterInterface
   */
  protected $filter;

  /**
   * Asserts that Linkit filter correctly processes the content.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity object to check.
   * @param string $langcode
   *   The language code of the text to be filtered.
   */
  protected function assertLinkitFilter(EntityInterface $entity, $langcode = LanguageInterface::LANGCODE_SITE_DEFAULT) {
    if ($entity->getEntityTypeId() === "file") {
      /** @var \Drupal\file\Entity\File $entity */
      $href = \Drupal::service('file_url_generator')->generateString($entity->getFileUri());
    }
    else {
      $href = $entity->toUrl()->toString();
    }

    $input = '<a data-entity-type="' . $entity->getEntityTypeId() . '" data-entity-uuid="' . $entity->uuid() . '">Link text</a>';
    $expected = '<a data-entity-type="' . $entity->getEntityTypeId() . '" data-entity-uuid="' . $entity->uuid() . '" href="' . $href . '">Link text</a>';
    $actual = $this->process($input, $langcode)->getProcessedText();
    $actual = urldecode($actual);
    $this->assertSame($expected, $actual);
    $canonical_url_aka_not_path_alias = '/entity_test_mul/manage/1';
    $this->assertStringNotContainsString($canonical_url_aka_not_path_alias, $this->process($input, $langcode)->getProcessedText());
  }

  /**
   * Asserts that Linkit filter correctly processes the content titles.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity object to check.
   * @param string $langcode
   *   The language code of the text to be filtered.
   */
  protected function assertLinkitFilterWithTitle(EntityInterface $entity, $langcode = LanguageInterface::LANGCODE_SITE_DEFAULT) {
    if ($entity->getEntityTypeId() === "file") {
      /** @var \Drupal\file\Entity\File $entity */
      $href = \Drupal::service('file_url_generator')->generateString($entity->getFileUri());
    }
    else {
      $href = $entity->toUrl()->toString();
    }

    $input = '<a data-entity-type="' . $entity->getEntityTypeId() . '" data-entity-uuid="' . $entity->uuid() . '">Link text</a>';
    $expected = '<a data-entity-type="' . $entity->getEntityTypeId() . '" data-entity-uuid="' . $entity->uuid() . '" href="' . $href . '" title="' . Html::decodeEntities($entity->label()) . '">Link text</a>';
    $actual = $this->process($input, $langcode)->getProcessedText();
    $actual = urldecode($actual);
    $this->assertSame($expected, $actual);
  }

  /**
   * Test helper method that wraps the filter process method.
   *
   * @param string $input
   *   The text string to be filtered.
   * @param string $langcode
   *   The language code of the text to be filtered.
   *
   * @return \Drupal\filter\FilterProcessResult
   *   The filtered text, wrapped in a FilterProcessResult object, and possibly
   *   with associated assets, cacheability metadata and placeholders.
   *
   * @see \Drupal\filter\Plugin\FilterInterface::process
   */
  protected function process($input, $langcode = LanguageInterface::LANGCODE_SITE_DEFAULT) {
    return $this->filter->process($input, $langcode);
  }

}
