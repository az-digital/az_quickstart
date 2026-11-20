<?php

declare(strict_types=1);

namespace Drupal\az_publication;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;

/**
 * Provides a listing of Quickstart Citation Style entities.
 */
class AZQuickstartCitationStyleListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Style name');
    $header['id'] = $this->t('Machine name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $entity->label();
    $row['id'] = $entity->id();
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $parent_build = parent::render();

    $build = [
      'description' => [
        '#type' => 'markup',
        '#markup' => $this->t('Tailor citation styles to meet specific display requirements. For full documentation on how to use this feature, visit the <a href=":link" target="_blank">Quickstart documentation</a>.', [
          ':link' => 'https://quickstart.arizona.edu/create-content/adding-publications',
        ]),
        '#prefix' => '<div class="az-publication-style-description">',
        '#suffix' => '</div>',
      ],
    ];

    $build += $parent_build;

    if (isset($build['table'])) {
      $build['table']['#empty'] = $this->t('No citation styles available. <a href=":url">Add citation style</a>.', [
        ':url' => Url::fromRoute('entity.az_citation_style.add_form')->toString(),
      ]);
    }

    return $build;
  }

}
