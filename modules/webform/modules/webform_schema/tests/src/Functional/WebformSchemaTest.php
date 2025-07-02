<?php

namespace Drupal\Tests\webform_schema\Functional;

use Drupal\Tests\webform\Functional\WebformBrowserTestBase;

/**
 * Webform schema test.
 *
 * @group webform_schema
 */
class WebformSchemaTest extends WebformBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'webform',
    'webform_schema',
    'webform_schema_test',
  ];

  /**
   * Test schema.
   */
  public function testSchema() {
    $assert_session = $this->assertSession();

    // Check that access is denied.
    $this->drupalGet('/admin/structure/webform/manage/test_webform_schema/schema');
    $assert_session->statusCodeEquals(403);

    // Create a user who has 'access webform schema' permission.
    $account = $this->createUser(['access webform schema']);
    $this->drupalLogin($account);

    // Check that access is allowed.
    $this->drupalGet('/admin/structure/webform/manage/test_webform_schema/schema');
    $assert_session->statusCodeEquals(200);

    // Check that the options text is rendered as expected.
    $assert_session->responseContains('<td>One; Two; Three</td>');
    $assert_session->responseContains('<td>one; two; three</td>');

    // Check the entire schema CSV export.
    $this->drupalGet('/admin/structure/webform/manage/test_webform_schema/schema/export');
    $assert_session->responseContains('Name,Title,Type,Datatype,Maxlength,Required,Multiple,"Options text","Options value",Notes/Comments
textfield,textfield,textfield,Text,255,No,1,,,"This is a note"
textarea,textarea,textarea,Blob,Unlimited,No,1,,,
checkbox,checkbox,checkbox,Boolean,,No,1,,,
checkboxes,checkboxes,checkboxes,Text,5,No,1,One;Two;Three,one;two;three,
file,file,managed_file,Number,,No,1,,,
likert,likert,webform_likert,Text,,No,1,,,
composite,composite,webform_link,Composite,,No,1,,,
composite.title,"Link Title",textfield,Text,255,No,1,,,
composite.url,"Link URL",url,Text,255,No,1,,,
entity_reference,entity_reference,webform_entity_select,Number,,No,1,,,
serial,serial,integer,Number,,,1,,,
sid,sid,integer,Number,,,1,,,
uuid,uuid,uuid,Text,128,,1,,,
token,token,string,Text,255,,1,,,
uri,uri,string,Text,2000,,1,,,
created,created,created,Timestamp,,,1,,,
completed,completed,timestamp,Timestamp,,,1,,,
changed,changed,changed,Timestamp,,,1,,,
in_draft,in_draft,boolean,Boolean,,,1,,,
current_page,current_page,string,Text,128,,1,,,
remote_addr,remote_addr,string,Text,128,,1,,,
uid,uid,entity_reference,Text,,,1,,,
langcode,langcode,language,Text,,,1,,,
webform_id,webform_id,entity_reference,Text,,,1,,,
entity_type,entity_type,string,Text,32,,1,,,
entity_id,entity_id,string,Text,255,,1,,,
locked,locked,boolean,Boolean,,,1,,,
sticky,sticky,boolean,Boolean,,,1,,,
notes,notes,string_long,Blob,Unlimited,,1,,,');
  }

}
