<?php

/**
 * @file
 * Hooks provided by the XML Sitemap module.
 *
 * @ingroup xmlsitemap
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Provide information on the type of links this module provides.
 *
 * @see hook_entity_info()
 * @see hook_entity_info_alter()
 */
function hook_xmlsitemap_link_info() {
  return [
    'mymodule' => [
      'label' => 'My module items',
      // If your items can be grouped into unique "bundles", add the following
      // information.
      'bundle label' => t('Subtype name'),
      'bundles' => [
        'mysubtype1' => [
          'label' => t('My subtype 1'),
          'xmlsitemap' => [
            'status' => XMLSITEMAP_STATUS_DEFAULT,
            'priority' => XMLSITEMAP_PRIORITY_DEFAULT,
          ],
        ],
      ],
      'xmlsitemap' => [
        // Callback function to take an array of IDs and save them as sitemap
        // links.
        'process callback' => 'mymodule_xmlsitemap_process_links',
        // Callback function used in batch API for rebuilding all links.
        'rebuild callback' => 'mymodule_xmlsitemap_rebuild_links',
        // Callback function called from the XML Sitemap settings page.
        'settings callback' => 'mymodule_xmlsitemap_settings',
      ],
    ],
  ];
}

/**
 * Alter the data of a sitemap link before the link is saved.
 *
 * @param array $link
 *   An array with the data of the sitemap link.
 * @param array $context
 *   An optional context array containing data related to the link.
 */
function hook_xmlsitemap_link_alter(array &$link, array $context) {
  if ($link['type'] == 'mymodule') {
    $link['priority'] += 0.5;
  }
}

/**
 * Inform modules that an XML Sitemap link has been created.
 *
 * @param array $link
 *   Associative array defining an XML Sitemap link as passed into
 *   \Drupal\xmlsitemap\XmlSitemapLinkStorageInterface::save().
 * @param array $context
 *   An optional context array containing data related to the link.
 *
 * @see hook_xmlsitemap_link_update()
 */
function hook_xmlsitemap_link_insert(array $link, array $context) {
  \Drupal::database()->insert('mytable')
    ->fields([
      'link_type' => $link['type'],
      'link_id' => $link['id'],
      'link_status' => $link['status'],
    ])
    ->execute();
}

/**
 * Inform modules that an XML Sitemap link has been updated.
 *
 * @param array $link
 *   Associative array defining an XML Sitemap link as passed into
 *   \Drupal\xmlsitemap\XmlSitemapLinkStorageInterface::save().
 * @param array $context
 *   An optional context array containing data related to the link.
 *
 * @see hook_xmlsitemap_link_insert()
 */
function hook_xmlsitemap_link_update(array $link, array $context) {
  \Drupal::database()->update('mytable')
    ->fields([
      'link_type' => $link['type'],
      'link_id' => $link['id'],
      'link_status' => $link['status'],
    ])
    ->execute();
}

/**
 * Respond to XML Sitemap link clearing and rebuilding.
 *
 * @param array $entity_type_ids
 *   An array of entity type IDs that are being rebuilt.
 * @param bool $save_custom
 *   If links with overridden status and/or priority are being removed or not.
 */
function hook_xmlsitemap_rebuild_clear(array $entity_type_ids, $save_custom) {
  \Drupal::database()->delete('mytable')
    ->condition('link_type', $entity_type_ids, 'IN')
    ->execute();
}

/**
 * Index links for the XML Sitemaps.
 */
function hook_xmlsitemap_index_links($limit) {

}

/**
 * Provide information about contexts available to XML Sitemap.
 *
 * @see hook_xmlsitemap_context_info_alter()
 */
function hook_xmlsitemap_context_info() {
  $info['vocabulary'] = [
    'label' => t('Vocabulary'),
    'summary callback' => 'mymodule_xmlsitemap_vocabulary_context_summary',
    'default' => 0,
  ];
  return $info;
}

/**
 * Alter XML Sitemap context info.
 *
 * @see hook_xmlsitemap_context_info()
 */
function hook_xmlsitemap_context_info_alter(&$info) {
  $info['vocabulary']['label'] = t('Site vocabularies');
}

/**
 * Provide information about the current context on the site.
 *
 * @see hook_xmlsitemap_context_alter()
 */
function hook_xmlsitemap_context() {
  $context = [];
  if ($vid = mymodule_get_current_vocabulary()) {
    $context['vocabulary'] = $vid;
  }
  return $context;
}

/**
 * Alter the current context information.
 *
 * @see hook_xmlsitemap_context()
 */
function hook_xmlsitemap_context_alter(&$context) {
  $currentUser = \Drupal::currentUser();
  if ($currentUser->hasPermission('administer taxonomy')) {
    unset($context['vocabulary']);
  }
}

/**
 * Provide options for the url() function based on an XML Sitemap context.
 */
function hook_xmlsitemap_context_url_options(array $context) {

}

/**
 * Alter the url() options based on an XML Sitemap context.
 */
function hook_xmlsitemap_context_url_options_alter(array &$options, array $context) {

}

/**
 * Alter the content added to an XML Sitemap for an individual element.
 *
 * This hooks is called when the module is generating the XML content for the
 * sitemap and allows other modules to alter existing or add additional XML data
 * for any element by adding additional key value paris to the $element array.
 *
 * The key in the element array is then used as the name of the XML child
 * element to add and the value is the value of that element. For example:
 *
 * @code $element['video:title'] = 'Big Ponycorn'; @endcode
 *
 * Would result in a child element like <video:title>Big Ponycorn</video:title>
 * being added to the sitemap for this particular link.
 *
 * @param array $element
 *   The element that will be converted to XML for the link.
 * @param array $link
 *   An array of properties providing context about the link that we are
 *   generating an XML element for.
 * @param \Drupal\xmlsitemap\XmlSitemapInterface $sitemap
 *   The sitemap that is currently being generated.
 */
function hook_xmlsitemap_element_alter(array &$element, array $link, \Drupal\xmlsitemap\XmlSitemapInterface $sitemap) {
  if ($link['subtype'] === 'video') {
    $video = video_load($link['id']);
    $element['video:video'] = [
      'video:title' => \Drupal\Component\Utility\Html::escape($video->title),
      'video:description' => \Drupal\Component\Utility\Html::escape($video->description),
      'video:live' => 'no',
    ];
  }
}

/**
 * Alter the attributes used for the root element of the XML Sitemap.
 *
 * For example add an xmlns:video attribute:
 *
 * @code
 * <urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:video="https://www.google.com/schemas/sitemap-video/1.1">
 * @endcode
 *
 * @param array $attributes
 *   An associative array of attributes to use in the root element of an XML
 *   sitemap.
 * @param \Drupal\xmlsitemap\XmlSitemapInterface $sitemap
 *   The sitemap that is currently being generated.
 */
function hook_xmlsitemap_root_attributes_alter(array &$attributes, \Drupal\xmlsitemap\XmlSitemapInterface $sitemap) {
  $attributes['xmlns:video'] = 'https://www.google.com/schemas/sitemap-video/1.1';
}

/**
 * Alter the query selecting data from {xmlsitemap} during sitemap generation.
 *
 * @param QueryAlterableInterface $query
 *   A Query object describing the composite parts of a SQL query.
 *
 * @see hook_query_TAG_alter()
 */
function hook_query_xmlsitemap_generate_alter(QueryAlterableInterface $query) {
  $sitemap = $query->getMetaData('sitemap');
  if (!empty($sitemap->context['vocabulary'])) {
    $node_condition = $query->andConditionGroup();
    $node_condition->condition('type', 'taxonomy_term');
    $node_condition->condition('subtype', $sitemap->context['vocabulary']);
    $normal_condition = $query->andConditionGroup();
    $normal_condition->condition('type', 'taxonomy_term', '<>');
    $condition = $query->orConditionGroup();
    $condition->condition($node_condition);
    $condition->condition($normal_condition);
    $query->condition($condition);
  }
}

/**
 * Provide information about XML Sitemap bulk operations.
 */
function hook_xmlsitemap_sitemap_operations() {

}

/**
 * Respond to XML Sitemap deletion.
 *
 * This hook is invoked from xmlsitemap_sitemap_delete_multiple() after the XML
 * sitemap has been removed from the table in the database.
 *
 * @param \Drupal\xmlsitemap\XmlSitemapInterface $sitemap
 *   The XML Sitemap object that was deleted.
 */
function hook_xmlsitemap_sitemap_delete(\Drupal\xmlsitemap\XmlSitemapInterface $sitemap) {
  \Drupal::database()->query("DELETE FROM {mytable} WHERE smid = '%s'", $sitemap->smid);
}

/**
 * @} End of "addtogroup hooks".
 */
