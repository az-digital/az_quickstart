<?php

namespace Drupal\schema_metatag\Plugin\schema_metatag\PropertyType;

use Drupal\schema_metatag\Plugin\schema_metatag\PropertyTypeBase;

/**
 * Provides a plugin for the 'ContactPoint' Schema.org property type.
 *
 * @SchemaPropertyType(
 *   id = "contact_point",
 *   label = @Translation("ContactPoint"),
 *   tree_parent = {
 *     "ContactPoint",
 *   },
 *   tree_depth = 0,
 *   property_type = "ContactPoint",
 *   sub_properties = {
 *     "@type" = {
 *       "id" = "type",
 *       "label" = @Translation("@type"),
 *       "description" = "",
 *     },
 *     "telephone" = {
 *       "id" = "text",
 *       "label" = @Translation("telephone"),
 *       "description" = @Translation("An internationalized version of the phone number, starting with the ""+"" symbol and country code (+1 in the US and Canada). Examples: ""+1-800-555-1212"", ""+44-2078225951""."),
 *     },
 *     "email" = {
 *       "id" = "text",
 *       "label" = @Translation("email"),
 *       "description" = @Translation("Email address."),
 *     },
 *     "faxnumber" = {
 *       "id" = "text",
 *       "label" = @Translation("faxnumber"),
 *       "description" = @Translation("The fax number."),
 *     },
 *     "url" = {
 *       "id" = "url",
 *       "label" = @Translation("url"),
 *       "description" = @Translation("URL of place, organization."),
 *     },
 *     "availableLanguage" = {
 *       "id" = "text",
 *       "label" = @Translation("availableLanguage"),
 *       "description" = @Translation("Details about the language spoken. Languages may be specified by their common English name. If omitted, the language defaults to English. Examples: ""English, Spanish""."),
 *     },
 *     "contactType" = {
 *       "id" = "text",
 *       "label" = @Translation("contactType"),
 *       "description" = @Translation("One of the following: customer service, technical support, billing support, bill payment, sales, reservations, credit card support, emergency, baggage tracking, roadside assistance, package tracking."),
 *     },
 *     "contactOption" = {
 *       "id" = "text",
 *       "label" = @Translation("contactOption"),
 *       "description" = @Translation("One of the following: HearingImpairedSupported, TollFree."),
 *     },
 *     "productSupported" = {
 *       "id" = "text",
 *       "label" = @Translation("productSupported"),
 *       "description" = @Translation("The product or service this support contact point is related to (such as product support for a particular product line). This can be a specific product or product line (e.g. ""iPhone"") or a general category of products or services (e.g. ""smartphones"")."),
 *     },
 *     "areaServed" = {
 *       "id" = "place",
 *       "label" = @Translation("areaServed"),
 *       "description" = @Translation("The geographical region served by the number, specified as a AdministrativeArea. If omitted, the number is assumed to be global."),
 *       "tree_parent" = {
 *         "AdministrativeArea",
 *       },
 *       "tree_depth" = -1,
 *     },
 *   },
 * )
 */
class ContactPoint extends PropertyTypeBase {

}
