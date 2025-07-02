<?php

/**
 * @file
 * Views integration for xmlsitemap.
 */

/**
 * Implements hook_views_data().
 */
function xmlsitemap_views_data() {
  return [
    'xmlsitemap' => [
      'table' => [
        'group' => t('XML Sitemap'),
        'provider' => 'xmlsitemap',
      ],
      'id' => [
        'title' => t('ID'),
      ],
      'type' => [
        'title' => t('Type'),
      ],
      'subtype' => [
        'title' => t('Sub-type'),
      ],
      'loc' => [
        'title' => t('URL'),
      ],
      'language' => [
        'title' => t('Language'),
      ],
      'access' => [
        'title' => t('Access'),
      ],
      'status' => [
        'title' => t('Status'),
        'filter' => [
          'id' => 'boolean',
          'label' => t('Status'),
        ],
      ],
      'status_override' => [
        'title' => t('Status Override'),
        'filter' => [
          'id' => 'boolean',
          'label' => t('Status Override'),
        ],
      ],
      'lastmod' => [
        'title' => t('Last modified'),
      ],
      'priority' => [
        'title' => t('Priority'),
      ],
      'priority_override' => [
        'title' => t('Priority Override'),
      ],
      'changefreq' => [
        'title' => t('Change Frequency'),
      ],
      'changecount' => [
        'title' => t('Change Count'),
      ],
    ],
  ];
}

/**
 * Implements hook_views_data_alter().
 */
function xmlsitemap_views_data_alter(array &$data) {
  foreach (\Drupal::entityTypeManager()->getDefinitions() as $id => $definition) {
    if (!xmlsitemap_link_entity_check_enabled($id) && !empty($data[$definition->getBaseTable()])) {
      continue;
    }
    $data[$definition->getBaseTable()]['xmlsitemap_settings'] = [
      'title' => t('XML Sitemap Settings'),
      'relationship' => [
        'id' => 'standard',
        'base' => 'xmlsitemap',
        'base field' => 'id',
        'field' => $definition->getKey('id'),
        'label' => t('XML Sitemap Settings'),
        'extra' => [
          ['field' => 'type', 'value' => $id],
        ],
      ],
    ];
  }
}
