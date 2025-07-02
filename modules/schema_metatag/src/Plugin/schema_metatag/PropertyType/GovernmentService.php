<?php

namespace Drupal\schema_metatag\Plugin\schema_metatag\PropertyType;

use Drupal\schema_metatag\Plugin\schema_metatag\PropertyTypeBase;

/**
 * Provides a plugin for the 'GovernmentService' Schema.org property type.
 *
 * @SchemaPropertyType(
 *   id = "government_service",
 *   label = @Translation("GovernmentService"),
 *   tree_parent = {
 *     "GovernmentService",
 *   },
 *   tree_depth = -1,
 *   property_type = "GovernmentService",
 *   sub_properties = {
 *     "@type" = {
 *       "id" = "type",
 *       "label" = @Translation("@type"),
 *       "description" = "",
 *     },
 *     "name" = {
 *       "id" = "text",
 *       "label" = @Translation("name"),
 *       "description" = @Translation("The name of the government benefits."),
 *     },
 *     "url" = {
 *       "id" = "url",
 *       "label" = @Translation("url"),
 *       "description" = @Translation("The URL to more information about the government benefits."),
 *     },
 *     "serviceType" = {
 *       "id" = "text",
 *       "label" = @Translation("serviceType"),
 *       "description" = @Translation("Service type, one of http://schema.org/BasicIncome, http://schema.org/BusinessSupport, http://schema.org/DisabilitySupport, http://schema.org/HealthCare, http://schema.org/OneTimePayments, http://schema.org/PaidLeave, http://schema.org/ParentalSupport, http://schema.org/UnemploymentSupport."),
 *     },
 *     "audience" = {
 *       "id" = "text",
 *       "label" = @Translation("audience"),
 *       "description" = @Translation("The audience that is eligible to receive the government benefits. For example, small businesses."),
 *     },
 *     "provider" = {
 *       "id" = "organization",
 *       "description" = @Translation("The government organization that is providing the benefits."),
 *       "label" = @Translation("provider"),
 *       "tree_parent" = {
 *         "GovernmentOrganization",
 *         "GovernmentOffice",
 *       },
 *       "tree_depth" = -1,
 *     },
 *   },
 * )
 */
class GovernmentService extends PropertyTypeBase {

}
