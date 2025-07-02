<?php

namespace Drupal\Tests\search_exclude\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests content is appropriately excluded from search results.
 *
 * @group search_exclude
 */
class ExcludedContentTest extends BrowserTestBase {
  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['node', 'search_exclude'];

  /**
   * Default theme.
   *
   * @var string
   */
  protected $defaultTheme = 'stark';

  /**
   * Set to FALSE to avoid SchemaIncompleteException when saving config.
   *
   * @var bool
   */
  protected $strictConfigSchema = FALSE;

  /**
   * Test exclusion of appropriate content.
   *
   * @throws \Behat\Mink\Exception\ResponseTextException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testExcludingContent() {
    $this->drupalCreateContentType([
      'type' => 'included',
      'name' => 'Included Content',
    ]);
    $this->drupalCreateContentType([
      'type' => 'excluded',
      'name' => 'Excluded Content',
    ]);

    $this->drupalCreateNode([
      'type' => 'included',
      'title' => 'Included Content',
    ]);
    $this->drupalCreateNode([
      'type' => 'excluded',
      'title' => 'Excluded Content',
    ]);

    $account = $this->drupalCreateUser(['search content']);
    $this->drupalLogin($account);

    // Configure the search exclude to exclude content of type excluded.
    $config = \Drupal::configFactory()->getEditable('search.page.content_exclude_');
    $config->set('langcode', 'en');
    $config->set('status', TRUE);
    $config->set('dependencies', [
      "module" => [
        "search_exclude",
      ],
    ]);
    $config->set('id', 'content_exclude_');
    $config->set('label', 'Content (Exclude)');
    $config->set('path', 'exclude');
    $config->set('weight', 0);
    $config->set('plugin', 'search_exclude_node_search');
    $config->set('configuration', [
      'rankings' => [],
      "excluded_bundles" => [
        "excluded" => "excluded",
      ],
    ]);
    $config->save();

    \Drupal::service("router.builder")->rebuild();

    // Update the search index for the search exclude plugin.
    $searchPluginManager = $this->container->get('plugin.manager.search');
    $searchPluginManager->clearCachedDefinitions();
    $searchPluginManager->createInstance('search_exclude_node_search', $config->get('configuration'))->updateIndex();
    $this->drupalGet('search/exclude');

    // Finally search for the content types to verify inclusion/exclusion
    // appropriately.
    $this->submitForm(['keys' => '"Included Content"'], t('Search'));
    $this->assertSession()->pageTextNotContains('Your search yielded no results');
    $this->drupalGet('search/exclude');
    $this->submitForm(['keys' => '"Excluded Content"'], t('Search'));
    $this->assertSession()->pageTextContains('Your search yielded no results');
  }

}
