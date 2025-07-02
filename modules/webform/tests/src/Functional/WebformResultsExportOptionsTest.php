<?php

namespace Drupal\Tests\webform\Functional;

use Drupal\webform\Entity\Webform;

/**
 * Tests for webform results export.
 *
 * @group webform
 */
class WebformResultsExportOptionsTest extends WebformBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['node', 'locale', 'webform', 'webform_test_submissions'];

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_submissions'];

  /**
   * Tests export options.
   */
  public function testExportOptions() {
    $assert_session = $this->assertSession();

    $admin_submission_user = $this->drupalCreateUser([
      'administer webform submission',
    ]);

    /* ********************************************************************** */

    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = Webform::load('test_submissions');
    /** @var \Drupal\webform\WebformSubmissionInterface[] $submissions */
    $submissions = array_values(\Drupal::entityTypeManager()->getStorage('webform_submission')->loadByProperties(['webform_id' => 'test_submissions']));
    /** @var \Drupal\node\NodeInterface[] $node */
    $nodes = array_values(\Drupal::entityTypeManager()->getStorage('node')->loadByProperties(['type' => 'webform_test_submissions']));

    $this->drupalLogin($admin_submission_user);

    // Check default options.
    $this->getExport($webform);
    $assert_session->responseContains('"First name","Last name"');
    $assert_session->responseContains('George,Washington');
    $assert_session->responseContains('Abraham,Lincoln');
    $assert_session->responseContains('Hillary,Clinton');

    // Check special characters.
    $assert_session->responseContains("quotes' \"\"", "html <markup>");

    // Check delimiter.
    $this->getExport($webform, ['delimiter' => '|']);
    $assert_session->responseContains('"First name"|"Last name"');
    $assert_session->responseContains('George|Washington');

    // Check header keys = label.
    $this->getExport($webform, ['header_format' => 'label']);
    $assert_session->responseContains('"First name","Last name"');

    // Check header keys = key.
    $this->getExport($webform, ['header_format' => 'key']);
    $assert_session->responseContains('first_name,last_name');

    // Check options format compact.
    $this->getExport($webform, ['options_single_format' => 'compact', 'options_multiple_format' => 'compact']);
    $assert_session->responseContains('"Flag colors"');
    $assert_session->responseContains('Red;White;Blue');

    // Check options format separate.
    $this->getExport($webform, ['options_single_format' => 'separate', 'options_multiple_format' => 'separate']);
    $assert_session->responseContains('"Flag colors: Red","Flag colors: White","Flag colors: Blue"');
    $assert_session->responseNotContains('"Flag colors"');
    $assert_session->responseContains('X,X,X');
    $assert_session->responseNotContains('Red;White;Blue');

    // Check options item format label.
    $this->getExport($webform, ['options_item_format' => 'label']);
    $assert_session->responseContains('Red;White;Blue');

    // Check options item format key.
    $this->getExport($webform, ['options_item_format' => 'key']);
    $assert_session->responseNotMatches('/Red;White;Blue/');
    $assert_session->responseMatches('/red;white;blue/');

    // Check multiple delimiter.
    $this->getExport($webform, ['multiple_delimiter' => '|']);
    $assert_session->responseContains('Red|White|Blue');
    $this->getExport($webform, ['multiple_delimiter' => ',']);
    $assert_session->responseContains('"Red,White,Blue"');

    // Check entity reference format link.
    $this->getExport($webform, ['entity_reference_items' => 'id,title,url']);
    $assert_session->responseContains('"Favorite node: ID","Favorite node: Title","Favorite node: URL"');
    $assert_session->responseContains('' . $nodes[0]->id() . ',"' . $nodes[0]->label() . '",' . $nodes[0]->toUrl('canonical', ['absolute' => TRUE])->toString());

    // Check entity reference format title and url.
    $this->getExport($webform, ['entity_reference_items' => 'id']);
    $this->getExport($webform, ['entity_reference_items' => 'title,url']);
    $assert_session->responseNotContains('"Favorite node: ID","Favorite node: Title","Favorite node: URL"');
    $assert_session->responseNotContains('' . $nodes[0]->id() . ',"' . $nodes[0]->label() . '",' . $nodes[0]->toUrl('canonical', ['absolute' => TRUE])->toString());
    $assert_session->responseContains('"Favorite node: Title","Favorite node: URL"');
    $assert_session->responseContains('"' . $nodes[0]->label() . '",' . $nodes[0]->toUrl('canonical', ['absolute' => TRUE])->toString());

    // Check likert questions format label.
    $this->getExport($webform, ['header_format' => 'label']);
    $assert_session->responseContains('"Likert: Question 1","Likert: Question 2","Likert: Question 3"');

    // Check likert questions format key.
    $this->getExport($webform, ['header_format' => 'key']);
    $assert_session->responseNotContains('"Likert: Question 1","Likert: Question 2","Likert: Question 3"');
    $assert_session->responseContains('likert__q1,likert__q2,likert__q3');

    // Check likert answers format label.
    $this->getExport($webform, ['likert_answers_format' => 'label']);
    $assert_session->responseContains('"Answer 1","Answer 1","Answer 1"');

    // Check likert answers format key.
    $this->getExport($webform, ['likert_answers_format' => 'key']);
    $assert_session->responseNotContains('"Option 1","Option 1","Option 1"');
    $assert_session->responseContains('1,1,1');

    // Check composite w/o header prefix.
    $this->getExport($webform, ['header_format' => 'label', 'header_prefix' => TRUE]);
    $assert_session->responseContains('"Address: Address","Address: Address 2","Address: City/Town","Address: State/Province","Address: ZIP/Postal Code","Address: Country"');

    // Check composite w header prefix.
    $this->getExport($webform, ['header_format' => 'label', 'header_prefix' => FALSE]);
    $assert_session->responseContains('Address,"Address 2",City/Town,State/Province,"ZIP/Postal Code",Country');

    // Check limit.
    $this->getExport($webform, ['range_type' => 'latest', 'range_latest' => 2]);
    $assert_session->responseContains('Hillary,Clinton');
    $assert_session->responseNotContains('George,Washington');
    $assert_session->responseNotContains('Abraham,Lincoln');

    // Check sort ASC.
    $this->getExport($webform, ['order' => 'asc']);
    $assert_session->responseMatches('/George.*Abraham.*Hillary/ms');

    // Check sort DESC.
    $this->getExport($webform, ['order' => 'desc']);
    $assert_session->responseMatches('/Hillary.*Abraham.*George/ms');

    // Check sid start.
    $this->getExport($webform, ['range_type' => 'sid', 'range_start' => $submissions[1]->id()]);
    $assert_session->responseNotContains('George,Washington');
    $assert_session->responseContains('Abraham,Lincoln');
    $assert_session->responseContains('Hillary,Clinton');

    // Check sid range.
    $this->getExport($webform, ['range_type' => 'sid', 'range_start' => $submissions[1]->id(), 'range_end' => $submissions[1]->id()]);
    $assert_session->responseNotContains('George,Washington');
    $assert_session->responseContains('Abraham,Lincoln');
    $assert_session->responseNotContains('Hillary,Clinton');

    // Check uid.
    $submissions[0]->setOwner($admin_submission_user)->save();
    $this->getExport($webform, ['uid' => $admin_submission_user->id()]);
    $assert_session->responseContains('George,Washington');
    $assert_session->responseNotContains('Abraham,Lincoln');
    $assert_session->responseNotContains('Hillary,Clinton');

    // Check langcode.
    $submissions[0]->setOwner($admin_submission_user)->save();
    $this->getExport($webform, ['langcode' => 'es']);
    $assert_session->responseNotContains('George,Washington');
    $assert_session->responseNotContains('Abraham,Lincoln');
    $assert_session->responseNotContains('Hillary,Clinton');

    // Check date range.
    $this->getExport($webform, ['range_type' => 'date', 'range_start' => '2000-01-01', 'range_end' => '2001-01-01']);
    $assert_session->responseContains('George,Washington');
    $assert_session->responseContains('Abraham,Lincoln');
    $assert_session->responseNotContains('Hillary,Clinton');

    // Check entity type and id hidden.
    $this->drupalGet('/admin/structure/webform/manage/' . $webform->id() . '/results/download');
    $assert_session->fieldNotExists('edit-entity-type');

    // Change submission 0 & 1 to be submitted user account.
    $submissions[0]->set('entity_type', 'user')->set('entity_id', '1')->save();
    $submissions[1]->set('entity_type', 'user')->set('entity_id', '2')->save();

    // Check entity type and id visible.
    $this->drupalGet('/admin/structure/webform/manage/' . $webform->id() . '/results/download');
    $assert_session->fieldExists('edit-entity-type');

    // Check entity type limit.
    $this->getExport($webform, ['entity_type' => 'user']);
    $assert_session->responseContains('George,Washington');
    $assert_session->responseContains('Abraham,Lincoln');
    $assert_session->responseNotContains('Hillary,Clinton');

    // Check entity type and id limit.
    $this->getExport($webform, ['entity_type' => 'user', 'entity_id' => '1']);
    $assert_session->responseContains('George,Washington');
    $assert_session->responseNotContains('Abraham,Lincoln');
    $assert_session->responseNotContains('Hillary,Clinton');

    $this->drupalLogin($this->rootUser);

    // Check changing default exporter to 'table' settings.
    $this->drupalGet('/admin/structure/webform/manage/' . $webform->id() . '/results/download');
    $edit = ['exporter' => 'table'];
    $this->submitForm($edit, 'Download');
    $assert_session->responseContains('<body><table border="1"><thead><tr bgcolor="#cccccc" valign="top"><th>Serial number</th>');
    $assert_session->responseMatches('#<td>George</td>\s+<td>Washington</td>\s+<td>Male</td>#ms');

    // Check changing default export (delimiter) settings.
    $this->drupalGet('/admin/structure/webform/config/exporters');
    $edit = [
      'exporter' => 'delimited',
      'exporters[delimited][delimiter]' => '|',
    ];
    $this->submitForm($edit, 'Save configuration');
    $this->drupalGet('/admin/structure/webform/manage/' . $webform->id() . '/results/download');
    $this->submitForm([], 'Download');
    $assert_session->responseContains('"Submission ID"|"Submission URI"');

    // Check saved webform export (delimiter) settings.
    $this->drupalGet('/admin/structure/webform/manage/' . $webform->id() . '/results/download');
    $edit = [
      'exporter' => 'delimited',
      'exporters[delimited][delimiter]' => '.',
    ];
    $this->submitForm($edit, 'Save settings');
    $this->drupalGet('/admin/structure/webform/manage/' . $webform->id() . '/results/download');
    $this->submitForm([], 'Download');
    $assert_session->responseContains('"Submission ID"."Submission URI"');

    // Check delete webform export (delimiter) settings.
    $this->drupalGet('/admin/structure/webform/manage/' . $webform->id() . '/results/download');
    $this->submitForm([], 'Reset settings');
    $this->drupalGet('/admin/structure/webform/manage/' . $webform->id() . '/results/download');
    $this->submitForm([], 'Download');
    $assert_session->responseContains('"Submission ID"|"Submission URI"');
  }

}
