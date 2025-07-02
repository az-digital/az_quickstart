<?php

/**
 * @file
 * A database agnostic dump for testing purposes.
 */

use Drupal\Core\Database\Database;
use Symfony\Component\Yaml\Yaml;

$database = Database::getConnection();

foreach (['test_gtm_1', 'test_gtm_2'] as $entity_id) {
  $database->insert('config')
    ->fields([
      'name',
      'data',
    ])
    ->values([
      'google_tag.container.' . $entity_id,
      get_google_tag_entity_config($entity_id),
    ])->execute();
}

/**
 * Returns config for google tag entity.
 *
 * @param string $entity_id
 *   Entity id.
 *
 * @return string
 *   Config string.
 */
function get_google_tag_entity_config(string $entity_id): string {
  /** @var \Drupal\Component\Uuid\UuidInterface $uuid_generator */
  $uuid_generator = \Drupal::service('uuid');
  $uuid = $uuid_generator->generate();
  $entity_string = "uuid: $uuid
langcode: en
status: true
dependencies:
  module:
    - node
    - taxonomy
id: $entity_id
label: $entity_id
weight: 0
container_id: GTM-T26VRML
data_layer: dataLayer
include_classes: true
whitelist_classes: |-
  google
  nonGooglePixels
  nonGoogleScripts
  nonGoogleIframes
blacklist_classes: |-
  customScripts
  customPixels
include_environment: false
environment_id: ''
environment_token: ''
path_toggle: 'exclude listed'
path_list: |-
  /admin
  /admin/*
  /batch
  /batch/*
  /node/add*
  /node/*/edit
  /node/*/delete
  /node/*/layout
  /taxonomy/term/*/edit
  /taxonomy/term/*/layout
  /user/*/edit*
  /user/*/cancel*
  /user/*/layout
role_toggle: 'include listed'
role_list:
  content_editor: content_editor
  administrator: administrator
status_toggle: 'exclude listed'
status_list: |-
    403
    404
conditions:
  'entity_bundle:node':
    id: 'entity_bundle:node'
    negate: false
    context_mapping:
      node: '@node.node_route_context:node'
    bundles:
      article: article
  'entity_bundle:taxonomy_term':
    id: 'entity_bundle:taxonomy_term'
    negate: false
    context_mapping:
      taxonomy_term: '@taxonomy_term.taxonomy_term_route_context:taxonomy_term'
    bundles:
      tags: tags
  gtag_language:
    id: gtag_language
    context_mapping:
      language: '@language.current_language_context:language_interface'
    language_toggle: 'exclude listed'
    language_list:
      es: es
";
  return serialize(Yaml::parse($entity_string));
}
