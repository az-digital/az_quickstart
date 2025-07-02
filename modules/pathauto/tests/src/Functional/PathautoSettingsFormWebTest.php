<?php

namespace Drupal\Tests\pathauto\Functional;

use Drupal\pathauto\PathautoGeneratorInterface;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests pathauto settings form.
 *
 * @group pathauto
 */
class PathautoSettingsFormWebTest extends BrowserTestBase {

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
  protected static $modules = ['node', 'pathauto'];

  /**
   * Admin user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * Form values that are set by default.
   *
   * @var array
   */
  protected $defaultFormValues = [
    'verbose' => FALSE,
    'separator' => '-',
    'case' => '1',
    'max_length' => '100',
    'max_component_length' => '100',
    'update_action' => '2',
    'transliterate' => '1',
    'reduce_ascii' => FALSE,
    'ignore_words' => 'a, an, as, at, before, but, by, for, from, is, in, into, like, of, off, on, onto, per, since, than, the, this, that, to, up, via, with',
  ];

  /**
   * Punctuation form items with default values.
   *
   * @var array
   */
  protected $defaultPunctuations = [
    'punctuation[double_quotes]' => '0',
    'punctuation[quotes]' => '0',
    'punctuation[backtick]' => '0',
    'punctuation[comma]' => '0',
    'punctuation[period]' => '0',
    'punctuation[hyphen]' => '1',
    'punctuation[underscore]' => '0',
    'punctuation[colon]' => '0',
    'punctuation[semicolon]' => '0',
    'punctuation[pipe]' => '0',
    'punctuation[left_curly]' => '0',
    'punctuation[left_square]' => '0',
    'punctuation[right_curly]' => '0',
    'punctuation[right_square]' => '0',
    'punctuation[plus]' => '0',
    'punctuation[equal]' => '0',
    'punctuation[asterisk]' => '0',
    'punctuation[ampersand]' => '0',
    'punctuation[percent]' => '0',
    'punctuation[caret]' => '0',
    'punctuation[dollar]' => '0',
    'punctuation[hash]' => '0',
    'punctuation[exclamation]' => '0',
    'punctuation[tilde]' => '0',
    'punctuation[left_parenthesis]' => '0',
    'punctuation[right_parenthesis]' => '0',
    'punctuation[question_mark]' => '0',
    'punctuation[less_than]' => '0',
    'punctuation[greater_than]' => '0',
    'punctuation[slash]' => '0',
    'punctuation[back_slash]' => '0',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->drupalCreateContentType(['type' => 'article']);

    $permissions = [
      'administer pathauto',
      'notify of path changes',
      'administer url aliases',
      'bulk delete aliases',
      'bulk update aliases',
      'create url aliases',
      'bypass node access',
    ];
    $this->adminUser = $this->drupalCreateUser($permissions);
    $this->drupalLogin($this->adminUser);
    $this->createPattern('node', '/content/[node:title]');
  }

  /**
   * Test if the default values are shown correctly in the form.
   */
  public function testDefaultFormValues() {
    $this->drupalGet('/admin/config/search/path/settings');
    $this->assertSession()->checkboxNotChecked('edit-verbose');
    $this->assertSession()->fieldExists('edit-separator');
    $this->assertSession()->checkboxChecked('edit-case');
    $this->assertSession()->fieldExists('edit-max-length');
    $this->assertSession()->fieldExists('edit-max-component-length');
    $this->assertSession()->checkboxChecked('edit-update-action-2');
    $this->assertSession()->checkboxChecked('edit-transliterate');
    $this->assertSession()->checkboxNotChecked('edit-reduce-ascii');
    $this->assertSession()->fieldExists('edit-ignore-words');
  }

  /**
   * Test the verbose option.
   */
  public function testVerboseOption() {
    $edit = ['verbose' => '1'];
    $this->drupalGet('/admin/config/search/path/settings');
    $this->submitForm($edit, 'Save configuration');
    $this->assertSession()->pageTextContains('The configuration options have been saved.');
    $this->assertSession()->checkboxChecked('edit-verbose');

    $title = 'Verbose settings test';
    $this->drupalGet('/node/add/article');
    $this->assertSession()->checkboxChecked('edit-path-0-pathauto');
    $this->submitForm(['title[0][value]' => $title], 'Save');
    $this->assertSession()->pageTextContains('Created new alias /content/verbose-settings-test for');

    $node = $this->drupalGetNodeByTitle($title);
    $this->drupalGet('/node/' . $node->id() . '/edit');
    $this->submitForm(['title[0][value]' => 'Updated title'], 'Save');
    $this->assertSession()->pageTextContains('Created new alias /content/updated-title for');
    $this->assertSession()->pageTextContains('replacing /content/verbose-settings-test.');
  }

  /**
   * Tests generating aliases with different settings.
   */
  public function testSettingsForm() {
    // Ensure the separator settings apply correctly.
    $this->checkAlias('My awesome content', '/content/my.awesome.content', ['separator' => '.']);

    // Ensure the character case setting works correctly.
    // Leave case the same as source token values.
    $this->checkAlias('My awesome Content', '/content/My-awesome-Content', ['case' => FALSE]);
    $this->checkAlias('Change Lower', '/content/change-lower', ['case' => '1']);

    // Ensure the maximum alias length is working.
    $this->checkAlias('My awesome Content', '/content/my-awesome', ['max_length' => '23']);

    // Ensure the maximum component length is working.
    $this->checkAlias('My awesome Content', '/content/my', ['max_component_length' => '2']);

    // Ensure transliteration option is working.
    $this->checkAlias('è é àl ö äl ü', '/content/e-e-al-o-al-u', ['transliterate' => '1']);
    $this->checkAlias('è é àl äl ö ü', '/content/è-é-àl-äl-ö-ü', ['transliterate' => FALSE]);

    $ignore_words = 'a, new, very, should';
    $this->checkAlias('a very new alias to test', '/content/alias-to-test', ['ignore_words' => $ignore_words]);
  }

  /**
   * Test the punctuation setting form items.
   */
  public function testPunctuationSettings() {
    // Test the replacement of punctuations.
    $settings = [];
    foreach ($this->defaultPunctuations as $key => $punctuation) {
      $settings[$key] = PathautoGeneratorInterface::PUNCTUATION_REPLACE;
    }

    $title = 'aa"b`c,d.e-f_g:h;i|j{k[l}m]n+o=p*q%r^s$t#u!v~w(x)y?z>1/2\3';
    $alias = '/content/aa-b-c-d-e-f-g-h-i-j-k-l-m-n-o-p-q-r-s-t-u-v-w-x-y-z-1-2-3';
    $this->checkAlias($title, $alias, $settings);

    // Test the removal of punctuations.
    $settings = [];
    foreach ($this->defaultPunctuations as $key => $punctuation) {
      $settings[$key] = PathautoGeneratorInterface::PUNCTUATION_REMOVE;
    }

    $title = 'a"b`c,d.e-f_g:h;i|j{k[l}m]n+o=p*q%r^s$t#u!v~w(x)y?z>1/2\3';
    $alias = '/content/abcdefghijklmnopqrstuvwxyz123';
    $this->checkAlias($title, $alias, $settings);

    // Keep all punctuations in alias.
    $settings = [];
    foreach ($this->defaultPunctuations as $key => $punctuation) {
      $settings[$key] = PathautoGeneratorInterface::PUNCTUATION_DO_NOTHING;
    }

    $title = 'al"b`c,d.e-f_g:h;i|j{k[l}m]n+o=p*q%r^s$t#u!v~w(x)y?z>1/2\3';
    $alias = '/content/al"b`c,d.e-f_g:h;i|j{k[l}m]n+o=p*q%r^s$t#u!v~w(x)y?z>1/2\3';
    $this->checkAlias($title, $alias, $settings);
  }

  /**
   * Helper method to check the an aliases.
   *
   * @param string $title
   *   The node title to build the aliases from.
   * @param string $alias
   *   The expected alias.
   * @param array $settings
   *   The form values the alias should be generated with.
   */
  protected function checkAlias($title, $alias, $settings = []) {
    // Submit the settings form.
    $edit = array_merge($this->defaultFormValues + $this->defaultPunctuations, $settings);
    $this->drupalGet('/admin/config/search/path/settings');
    $this->submitForm($edit, 'Save configuration');
    $this->assertSession()->pageTextContains('The configuration options have been saved.');

    // If we do not clear the caches here, AliasCleaner will use its
    // cleanStringCache instance variable. Due to that the creation of aliases
    // with $this->createNode() will only work correctly on the first call.
    \Drupal::service('pathauto.generator')->resetCaches();

    // Create a node and check if the settings applied.
    $node = $this->createNode(
      [
        'title' => $title,
        'type' => 'article',
      ]
    );

    $this->drupalGet($alias);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertEntityAlias($node, $alias);
  }

}
