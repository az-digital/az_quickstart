<?php

/**
 * @file
 * Document all supported APIs.
 */

/**
 * Adjust Schema Metatags tags.
 *
 * Use hook_metatag_tags_alter() to change tag information.
 *
 * Schema Metatag adds new information to the standard Metatag definition.
 * Use this hook to alter that information.
 *
 * @param array $definitions
 *   An array of the tag definitions.
 */
function hook_metatag_tags_alter(array &$definitions) {
  // Set up the Schema Service tags to only display "GovernmentService" in the
  // `@type` option list.
  $definitions['schema_service_type']['property_type'] = 'type';
  $definitions['schema_service_type']['tree_parent'] = ['GovernmentService'];
  $definitions['schema_service_type']['tree_depth'] = 0;

  // Set up the Schema Organization tags to display every "LocalBusiness" option
  // in the @type option list.
  $definitions['schema_organization_type']['property_type'] = 'type';
  $definitions['schema_organization_type']['tree_parent'] = ['LocalBusiness'];
  $definitions['schema_organization_type']['tree_depth'] = -1;

  // Use a different PropertyType plugin for Schema Recipe instructions, the
  // HowToStep instead of the Text type used by Schema Recipe. Make it a
  // multiple value too since there will be more than one step.
  $definitions['schema_recipe_recipe_instructions']['property_type'] = 'how_to_step';
  $definitions['schema_recipe_recipe_instructions']['tree_parent'] = ['HowToStep'];
  $definitions['schema_recipe_recipe_instructions']['tree_depth'] = -1;
  $definitions['schema_recipe_recipe_instructions']['multiple'] = TRUE;
}

/**
 * Adjust Schema Metatags property types.
 *
 * Use hook_schema_metatag_property_type_plugins_alter() to change property
 * type information.
 *
 * @param array $definitions
 *   An array of the property type definitions.
 */
function hook_schema_metatag_property_type_plugins_alter(array &$definitions) {
  // Change the Place property type to use plain text instead of the structured
  // PostalAddress and GeoCoordinates used as a default.
  $definitions['place']['property_type'] = 'Text';
  $definitions['place']['tree_parent'] = [];
  $definitions['place']['tree_depth'] = -1;
  $definitions['place']['sub_properties'] = [];

  // Update the CreativeWork property type to add an author sub-property to all
  // properties that use this property type.
  $definitions['creative_work']['sub_properties'] += [
    'author' => [
      'id' => 'person',
      'label' => t('author'),
      'description' => t('The person who created this.'),
      'tree_parent' => ['Person'],
      'tree_depth' => 0,
    ],
  ];

}
