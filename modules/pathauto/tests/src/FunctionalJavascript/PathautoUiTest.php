<?php

namespace Drupal\Tests\pathauto\FunctionalJavascript;

use Drupal\Core\Url;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\pathauto\Entity\PathautoPattern;
use Drupal\Tests\pathauto\Functional\PathautoTestHelperTrait;

/**
 * Test basic pathauto functionality.
 *
 * @group pathauto
 */
class PathautoUiTest extends WebDriverTestBase {

  use PathautoTestHelperTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['pathauto', 'node', 'block'];

  /**
   * Admin user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->drupalCreateContentType(['type' => 'page', 'name' => 'Basic page']);
    $this->drupalCreateContentType(['type' => 'article']);

    // Allow other modules to add additional permissions for the admin user.
    $permissions = [
      'administer pathauto',
      'administer url aliases',
      'bulk delete aliases',
      'bulk update aliases',
      'create url aliases',
      'administer nodes',
      'bypass node access',
      'access content overview',
    ];
    $this->adminUser = $this->drupalCreateUser($permissions);
    $this->drupalLogin($this->adminUser);
  }

  public function testSettingsValidation() {
    $this->drupalGet('/admin/config/search/path/settings');

    $this->assertSession()->fieldExists('max_length');
    $this->assertSession()->elementAttributeContains('css', '#edit-max-length', 'min', '1');

    $this->assertSession()->fieldExists('max_component_length');
    $this->assertSession()->elementAttributeContains('css', '#edit-max-component-length', 'min', '1');
  }

  public function testPatternsWorkflow() {
    $this->drupalPlaceBlock('local_tasks_block', ['id' => 'local-tasks-block']);
    $this->drupalPlaceBlock('local_actions_block');
    $this->drupalPlaceBlock('page_title_block');

    $this->drupalGet('admin/config/search/path');
    $this->assertSession()->elementContains('css', '#block-local-tasks-block', 'Patterns');
    $this->assertSession()->elementContains('css', '#block-local-tasks-block', 'Settings');
    $this->assertSession()->elementContains('css', '#block-local-tasks-block', 'Bulk generate');
    $this->assertSession()->elementContains('css', '#block-local-tasks-block', 'Delete aliases');

    $this->drupalGet('admin/config/search/path/patterns');
    $this->clickLink('Add Pathauto pattern');

    $session = $this->getSession();
    $session->getPage()->fillField('type', 'canonical_entities:node');
    $this->assertSession()->assertWaitOnAjaxRequest();

    $edit = [
      'type' => 'canonical_entities:node',
      'bundles[page]' => TRUE,
      'label' => 'Page pattern',
      'pattern' => '[node:title]/[user:name]/[term:name]',
    ];
    $this->submitForm($edit, 'Save');

    $this->assertSession()->waitForElementVisible('css', '[name="id"]');
    if (version_compare(\Drupal::VERSION, '10.1', '<')) {
      $edit += [
        'id' => 'page_pattern',
      ];
      $this->submitForm($edit, 'Save');
    }

    $this->assertSession()->pageTextContains('Path pattern is using the following invalid tokens: [user:name], [term:name].');
    $this->assertSession()->pageTextNotContains('The configuration options have been saved.');

    // We do not need ID anymore, it is already set in previous step and made a label by browser.
    unset($edit['id']);
    $edit['pattern'] = '#[node:title]';
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('The Path pattern is using the following invalid characters: #.');
    $this->assertSession()->pageTextNotContains('The configuration options have been saved.');

    // Checking whitespace ending of the string.
    $edit['pattern'] = '[node:title] ';
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains("The Path pattern doesn't allow the patterns ending with whitespace.");
    $this->assertSession()->pageTextNotContains('The configuration options have been saved.');

    // Fix the pattern, then check that it gets saved successfully.
    $edit['pattern'] = '[node:title]';
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('Pattern Page pattern saved.');

    \Drupal::service('pathauto.generator')->resetCaches();

    // Create a node with pattern enabled and check if the pattern applies.
    $title = 'Page Pattern enabled';
    $alias = '/page-pattern-enabled';
    $node = $this->createNode(['title' => $title, 'type' => 'page']);
    $this->drupalGet($alias);
    $this->assertSession()->pageTextContains($title);
    $this->assertEntityAlias($node, $alias);

    // Edit workflow, set a new label and weight for the pattern.
    $this->drupalGet('/admin/config/search/path/patterns');
    $session->getPage()->pressButton('Show row weights');
    $this->submitForm(['entities[page_pattern][weight]' => '4'], 'Save');

    $session->getPage()->find('css', '.dropbutton-toggle > button')->press();
    $this->clickLink('Edit');
    $destination_query = ['query' => ['destination' => Url::fromRoute('entity.pathauto_pattern.collection')->toString()]];
    $address = Url::fromRoute('entity.pathauto_pattern.edit_form', ['pathauto_pattern' => 'page_pattern'], [$destination_query]);
    $this->assertSession()->addressEquals($address);
    $this->assertSession()->fieldValueEquals('pattern', '[node:title]');
    $this->assertSession()->fieldValueEquals('label', 'Page pattern');
    $this->assertSession()->checkboxChecked('edit-status');
    $this->assertSession()->linkExists('Delete');

    $edit = ['label' => 'Test'];
    $this->drupalGet('/admin/config/search/path/patterns/page_pattern');
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('Pattern Test saved.');
    // Check that the pattern weight did not change.
    $this->assertSession()->optionExists('edit-entities-page-pattern-weight', '4');

    $this->drupalGet('/admin/config/search/path/patterns/page_pattern/duplicate');
    $session->getPage()->pressButton('Edit');
    $edit = ['label' => 'Test Duplicate', 'id' => 'page_pattern_test_duplicate'];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('Pattern Test Duplicate saved.');

    PathautoPattern::load('page_pattern_test_duplicate')->delete();

    // Disable workflow.
    $this->drupalGet('/admin/config/search/path/patterns');
    $session->getPage()->find('css', '.dropbutton-toggle > button')->press();
    $this->assertSession()->linkNotExists('Enable');
    $this->clickLink('Disable');
    $this->assertSession()->addressEquals('/admin/config/search/path/patterns/page_pattern/disable');
    $this->submitForm([], 'Disable');
    $this->assertSession()->pageTextContains('Disabled pattern Test.');

    // Load the pattern from storage and check if its disabled.
    $pattern = PathautoPattern::load('page_pattern');
    $this->assertFalse($pattern->status());

    \Drupal::service('pathauto.generator')->resetCaches();

    // Create a node with pattern disabled and check that we have no new alias.
    $title = 'Page Pattern disabled';
    $node = $this->createNode(['title' => $title, 'type' => 'page']);
    $this->assertNoEntityAlias($node);

    // Enable workflow.
    $this->drupalGet('/admin/config/search/path/patterns');
    $this->assertSession()->linkNotExists('Disable');
    $this->clickLink('Enable');
    $address = Url::fromRoute('entity.pathauto_pattern.enable', ['pathauto_pattern' => 'page_pattern'], [$destination_query]);
    $this->assertSession()->addressEquals($address);
    $this->submitForm([], 'Enable');
    $this->assertSession()->pageTextContains('Enabled pattern Test.');

    // Reload pattern from storage and check if its enabled.
    $pattern = PathautoPattern::load('page_pattern');
    $this->assertTrue($pattern->status());

    // Delete workflow.
    $this->drupalGet('/admin/config/search/path/patterns');
    $session->getPage()->find('css', '.dropbutton-toggle > button')->press();
    $this->clickLink('Delete');
    $this->assertSession()->assertWaitOnAjaxRequest();
    if (version_compare(\Drupal::VERSION, '10.1', '>=')) {
      $this->assertNotEmpty($this->assertSession()->waitForElementVisible('css', '#drupal-modal'));
      $this->assertSession()->elementContains('css', '#drupal-modal', 'This action cannot be undone.');
      $this->assertSession()->elementExists('css', '.ui-dialog-buttonpane')->pressButton('Delete');
    }
    else {
      $address = Url::fromRoute('entity.pathauto_pattern.delete_form', ['pathauto_pattern' => 'page_pattern'], [$destination_query]);
      $this->assertSession()->addressEquals($address);
      $this->submitForm([], 'Delete');
    }
    $this->assertSession()->pageTextContains('The pathauto pattern Test has been deleted.');

    $this->assertEmpty(PathautoPattern::load('page_pattern'));
  }

}
