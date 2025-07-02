<?php

namespace Drupal\Tests\field_group_migrate\Functional;

use Drupal\Core\Url;
use Drupal\Tests\field_group_migrate\Traits\FieldGroupMigrationAssertionsTrait;
use Drupal\Tests\migrate_drupal_ui\Functional\MigrateUpgradeTestBase;

/**
 * Tests migration of field groups with Migrate Drupal UI.
 *
 * @group field_group_migrate
 */
class MigrateUiFieldGroupTest extends MigrateUpgradeTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  use FieldGroupMigrationAssertionsTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'field_group_migrate',
    'field_ui',
    'migrate_drupal_ui',
    'telephone',
  ];

  /**
   * {@inheritdoc}
   */
  protected function getSourceBasePath() {
    return \Drupal::service('extension.list.module')
      ->getPath('migrate_drupal_ui') . '/tests/src/Functional/d7/files';
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $extension_list_module = \Drupal::service('extension.list.module');

    $migrate_drupal_path = $extension_list_module
      ->getPath('migrate_drupal') . '/tests/fixtures/drupal7.php';
    $field_group_migrate = $extension_list_module
      ->getPath('field_group_migrate') . '/tests/fixtures/drupal7.php';

    // Field Group's migration database fixture extends Drupal core's fixture.
    $this->loadFixture($migrate_drupal_path);
    $this->loadFixture($field_group_migrate);
  }

  /**
   * Tests the result of the field group migration.
   */
  public function testFieldGroupMigrate() {
    $page = $this->getSession()->getPage();
    $this->executeMigrateUpgradeViaUi();

    $this->assertNodeArticleDefaultForm();
    $this->assertNodePageDefaultForm();
    $this->assertNodeArticleTeaserDisplay();
    $this->assertNodePageDefaultDisplay();
    $this->assertUserDefaultDisplay();

    // Re-save every field group's configuration to ensure that the migrated
    // settings aren't changed.
    $this->drupalGet(Url::fromRoute('entity.entity_form_display.node.default', [
      'node_type' => 'article',
    ]));
    $page->findButton('group_article_htabs_group_settings_edit')->click();
    $page->findButton('Update')->click();
    $page->findButton('group_article_group_settings_edit')->click();
    $this->submitForm([], 'Save');

    $this->drupalGet(Url::fromRoute('entity.entity_form_display.node.default', [
      'node_type' => 'page',
    ]));
    $page->findButton('group_page_group_settings_edit')->click();
    $page->findButton('Update')->click();
    $page->findButton('group_page_tab_group_settings_edit')->click();
    $this->submitForm([], 'Save');

    $this->drupalGet(Url::fromRoute('entity.entity_view_display.node.view_mode', [
      'node_type' => 'article',
      'view_mode_name' => 'teaser',
    ]));
    $page->findButton('group_article_htabs_group_settings_edit')->click();
    $page->findButton('Update')->click();
    $page->findButton('group_article_group_settings_edit')->click();
    $this->submitForm([], 'Save');

    $this->drupalGet(Url::fromRoute('entity.entity_view_display.node.default', [
      'node_type' => 'page',
    ]));
    $page->findButton('group_page_group_settings_edit')->click();
    $this->submitForm([], 'Save');

    $this->drupalGet(Url::fromRoute('entity.entity_view_display.user.default'));
    $page->findButton('group_user_group_settings_edit')->click();
    $page->findButton('Update')->click();
    $page->findButton('group_user_child_group_settings_edit')->click();
    $page->findButton('Update')->click();
    $page->findButton('group_user_tab1_group_settings_edit')->click();
    $page->findButton('Update')->click();
    $page->findButton('group_user_tab2_group_settings_edit')->click();
    $this->submitForm([], 'Save');

    // Re-test the migrated field group configurations.
    $this->assertNodeArticleDefaultForm();
    $this->assertNodePageDefaultForm();
    $this->assertNodeArticleTeaserDisplay();
    $this->assertNodePageDefaultDisplay();
    $this->assertUserDefaultDisplay();
  }

  /**
   * Submits the Migrate Upgrade source connection and files form.
   */
  protected function submitMigrateUpgradeSourceConnectionForm() {
    $connection_options = $this->sourceDatabase->getConnectionOptions();
    $this->drupalGet('/upgrade');
    $session = $this->assertSession();
    $session->responseContains("Upgrade a site by importing its files and the data from its database into a clean and empty new install of Drupal");

    $this->submitForm([], 'Continue');
    $session->pageTextContains('Provide credentials for the database of the Drupal site you want to upgrade.');

    $connectionDriver = $connection_options['driver'];

    // Use the driver connection form to get the correct options out of the
    // database settings. This supports all of the databases we test against.
    if (\Drupal::hasService('extension.list.database_driver')) {
      $drivers = [];
      foreach (\Drupal::service('extension.list.database_driver')->getInstallableList() as $driver) {
        $drivers[$driver->getNameSpace()] = $driver->getInstallTasks();
      }
    }
    else {
      // Introduce database driver extensions and
      // autoload database drivers' dependencies.
      // @see https://www.drupal.org/node/3258175
      // @phpstan-ignore-next-line
      $drivers = drupal_get_database_types();
    }

    $form = $drivers[$connectionDriver]->getFormOptions($connection_options);
    $connection_options = array_intersect_key($connection_options, $form + $form['advanced_options']);
    $version = $this->getLegacyDrupalVersion($this->sourceDatabase);
    $edit = [
      $connectionDriver => $connection_options,
      'source_private_file_path' => $this->getSourceBasePath(),
      'version' => $version,
      'source_base_path' => $this->getSourceBasePath(),
    ];

    if (count($drivers) !== 1) {
      $edit['driver'] = $connectionDriver;
    }
    $edits = $this->translatePostValues($edit);

    $this->submitForm($edits, 'Review upgrade');
  }

  /**
   * Executes the upgrade process with Migrate Drupal UI.
   */
  protected function executeMigrateUpgradeViaUi() {
    $this->submitMigrateUpgradeSourceConnectionForm();
    $assert_session = $this->assertSession();
    $assert_session->pageTextNotContains('Resolve all issues below to continue the upgrade.');

    // When complete node migration is executed, Drupal 8.9 and above (even 9.x)
    // will complain about content id conflicts. Drupal 8.8 and below won't.
    // @see https://www.drupal.org/node/2928118
    // @see https://www.drupal.org/node/3105503
    if ($this->getSession()->getPage()->findButton('I acknowledge I may lose data. Continue anyway.')) {
      $this->submitForm([], 'I acknowledge I may lose data. Continue anyway.');
      $assert_session->statusCodeEquals(200);
    }

    // Perform the upgrade.
    $this->submitForm([], 'Perform upgrade');
    $this->assertSession()->pageTextContains('Congratulations, you upgraded Drupal!');

    // Have to reset all the statics after migration to ensure entities are
    // loadable.
    $this->resetAll();
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityCounts() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  protected function getAvailablePaths() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  protected function getMissingPaths() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityCountsIncremental() {
    return [];
  }

}
