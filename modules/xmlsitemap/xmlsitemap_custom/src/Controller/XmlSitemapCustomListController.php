<?php

namespace Drupal\xmlsitemap_custom\Controller;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\PagerSelectExtender;
use Drupal\Core\Database\Query\TableSortExtender;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Builds the list table for all custom links.
 */
class XmlSitemapCustomListController extends ControllerBase {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * XmlSitemapCustomListController constructor.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   */
  public function __construct(Connection $connection) {
    $this->connection = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database')
    );
  }

  /**
   * Renders a list with all custom links.
   *
   * @return array
   *   The list to be rendered.
   */
  public function render() {
    $build['xmlsitemap_add_custom'] = [
      '#type' => 'link',
      '#title' => $this->t('Add custom link'),
      '#href' => 'admin/config/search/xmlsitemap/custom/add',
    ];
    $header = [
      'loc' => [
        'data' => $this->t('Location'),
        'field' => 'loc',
        'sort' => 'asc',
      ],
      'priority' => [
        'data' => $this->t('Priority'),
        'field' => 'priority',
      ],
      'changefreq' => [
        'data' => $this->t('Change frequency'),
        'field' => 'changefreq',
      ],
      'language' => [
        'data' => $this->t('Language'),
        'field' => 'language',
      ],
      'operations' => [
        'data' => $this->t('Operations'),
      ],
    ];

    $rows = [];

    $query = $this->connection->select('xmlsitemap');
    $query->fields('xmlsitemap');
    $query->condition('type', 'custom');
    $query->extend(PagerSelectExtender::class)->limit(50);
    $query->extend(TableSortExtender::class)->orderByHeader($header);
    $result = $query->execute();

    foreach ($result as $link) {
      $language = $this->languageManager()->getLanguage($link->language);
      $row = [];
      $row['loc'] = Link::fromTextAndUrl($link->loc, Url::fromUri('internal:' . $link->loc));
      $row['priority'] = number_format($link->priority, 1);
      $row['changefreq'] = $link->changefreq ? Unicode::ucfirst(xmlsitemap_get_changefreq($link->changefreq, TRUE)) : $this->t('None');
      if (isset($header['language'])) {
        $row['language'] = $language->getName();
      }
      $operations['edit'] = [
        'title' => $this->t('Edit'),
        'url' => Url::fromRoute('xmlsitemap_custom.edit', ['link' => $link->id]),
      ];
      $operations['delete'] = [
        'title' => $this->t('Delete'),
        'url' => Url::fromRoute('xmlsitemap_custom.delete', ['link' => $link->id]),
      ];
      $row['operations'] = [
        'data' => [
          '#type' => 'operations',
          '#links' => $operations,
        ],
      ];
      $rows[] = $row;
    }

    // @todo Convert to tableselect
    $build['xmlsitemap_custom_table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No custom links available. <a href="@custom_link">Add custom link</a>', [
        '@custom_link' => Url::fromRoute('xmlsitemap_custom.add', [], [
          'query' => $this->getDestinationArray(),
        ])->toString(),
      ]),
    ];
    $build['xmlsitemap_custom_pager'] = ['#type' => 'pager'];

    return $build;
  }

}
