<?php

/**
 * @file
 * Provides views data for the az_opportunity_trellis module.
 */

/**
 * Implements hook_views_data().
 */
function az_opportunity_trellis_views_data(): array {
  $data = [];

  $data['az_opportunity_trellis_data']['table']['group'] = 'az_opportunity_trellis_data';
  $data['az_opportunity_trellis_data']['table']['base'] = [
    'title' => t('Trellis Opportunities'),
    'query_id' => 'views_remote_data_query',
  ];
  $data['az_opportunity_trellis_data']['Id'] = [
    'title' => t('Trellis Opportunity ID'),
    'help' => t('Unique key of Trellis opportunity.'),
    'field' => [
      'id' => 'az_opportunity_trellis_views_field',
    ],
  ];
  $data['az_opportunity_trellis_data']['Start_Options__c'] = [
    'title' => t('Trellis Opportunity Start Options'),
    'help' => t('When the opportunity usually begins'),
    'field' => [
      'id' => 'az_opportunity_trellis_views_start_options',
    ],
  ];
  $data['az_opportunity_trellis_data']['Program_Format__c'] = [
    'title' => t('Trellis Opportunity Program Format'),
    'help' => t('Modality for the program attendance'),
    'field' => [
      'id' => 'az_opportunity_trellis_views_modality',
    ],
  ];
  $data['az_opportunity_trellis_data']['Length_Of_Program__c'] = [
    'title' => t('Trellis Opportunity Length of Program'),
    'help' => t('How long does this program typically last'),
    'field' => [
      'id' => 'az_opportunity_trellis_views_length_of_program',
    ],
  ];
  $data['az_opportunity_trellis_data']['Incentives__c'] = [
    'title' => t('Trellis Opportunity Incentives'),
    'help' => t('Does this program have incentives?'),
    'field' => [
      'id' => 'az_opportunity_trellis_views_incentives',
    ],
  ];
  $data['az_opportunity_trellis_data']['Credit_Type__c'] = [
    'title' => t('Trellis Opportunity Credits'),
    'help' => t('What credits does this opportunity offer'),
    'field' => [
      'id' => 'az_opportunity_trellis_views_credits',
    ],
  ];
  $data['az_opportunity_trellis_data']['Location__c'] = [
    'title' => t('Trellis Opportunity Location'),
    'help' => t('Where this opportunity takes place'),
    'field' => [
      'id' => 'az_opportunity_trellis_views_location',
    ],
  ];
  $data['az_opportunity_trellis_data']['Program_Website__c'] = [
    'title' => t('Trellis Opportunity Program Website'),
    'help' => t('Link to Program website'),
    'field' => [
      'id' => 'az_opportunity_trellis_views_program_website',
    ],
  ];
  $data['az_opportunity_trellis_data']['Program_Description__c'] = [
    'title' => t('Trellis Opportunity Description'),
    'help' => t('Description'),
    'field' => [
      'id' => 'az_opportunity_trellis_views_description',
    ],
  ];
  $data['az_opportunity_trellis_data']['Eligibility__c'] = [
    'title' => t('Trellis Opportunity Eligibility'),
    'help' => t('Eligibility'),
    'field' => [
      'id' => 'az_opportunity_trellis_views_eligibility',
    ],
  ];
  $data['az_opportunity_trellis_data']['Topic__c'] = [
    'title' => t('Trellis Opportunity Topic'),
    'help' => t('Topics'),
    'field' => [
      'id' => 'az_opportunity_trellis_views_topic',
    ],
  ];
  $data['az_opportunity_trellis_data']['Audiences__c'] = [
    'title' => t('Trellis Opportunity Topic'),
    'help' => t('Topics'),
    'field' => [
      'id' => 'az_opportunity_trellis_views_audiences',
    ],
  ];
  $data['az_opportunity_trellis_data']['Application_Open__c'] = [
    'title' => t('Trellis Opportunity Application Open Date'),
    'help' => t('When the applications are open'),
    'field' => [
      'id' => 'az_opportunity_trellis_views_application_open',
    ],
  ];
  $data['az_opportunity_trellis_data']['Application_Closed__c'] = [
    'title' => t('Trellis Opportunity Application Closed Date'),
    'help' => t('When the applications are closed'),
    'field' => [
      'id' => 'az_opportunity_trellis_views_application_closed',
    ],
  ];
  $data['az_opportunity_trellis_data']['Start_Date__c'] = [
    'title' => t('Trellis Opportunity Program Start Date'),
    'help' => t('When the Program starts'),
    'field' => [
      'id' => 'az_opportunity_trellis_views_program_start_date',
    ],
  ];
  $data['az_opportunity_trellis_data']['End_Date__c'] = [
    'title' => t('Trellis Opportunity Program End Date'),
    'help' => t('When the Program ends'),
    'field' => [
      'id' => 'az_opportunity_trellis_views_program_end_date',
    ],
  ];
  $data['az_opportunity_trellis_data']['Ongoing__c'] = [
    'title' => t('Trellis Opportunity Ongoing'),
    'help' => t('Does this opportunity repeat'),
    'field' => [
      'id' => 'az_opportunity_trellis_views_ongoing',
    ],
  ];
  $data['az_opportunity_trellis_data']['Last_Modified_Date'] = [
    'title' => t('Trellis Opportunity Last Modified Date'),
    'help' => t('When the opportunity was last edited'),
    'field' => [
      'id' => 'az_opportunity_trellis_views_last_modified',
    ],
  ];
  $data['az_opportunity_trellis_data']['Parent_Account_Name'] = [
    'title' => t('Trellis Opportunity Parent Account Name'),
    'help' => t('Parent Opportunity Name'),
    'field' => [
      'id' => 'az_opportunity_trellis_views_parent_name',
    ],
  ];
  $data['az_opportunity_trellis_data']['Parent_Id'] = [
    'title' => t('Trellis Opportunity Parent Id'),
    'help' => t('Parent Opportunity ID'),
    'field' => [
      'id' => 'az_opportunity_trellis_views_parent_id',
    ],
  ];
  $data['az_opportunity_trellis_data']['Owner_NetID'] = [
    'title' => t('Trellis Opportunity Owner NetID'),
    'help' => t('Parent Opportunity Owner Netid'),
    'field' => [
      'id' => 'az_opportunity_trellis_views_owner_netid',
    ],
  ];
  $data['az_opportunity_trellis_data']['Attributes'] = [
    'title' => t('Enterprise Attribute'),
    'help' => t('Trellis opportunity search API enterprise attributes'),
    'filter' => [
      'id' => 'az_opportunity_trellis_views_attribute_filter',
    ],
  ];
  return $data;
}