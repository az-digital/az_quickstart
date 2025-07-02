<?php

namespace Drupal\Tests\devel_generate\Functional;

use Drupal\media\Entity\Media;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;

/**
 * Tests the logic to generate data.
 *
 * @group devel_generate
 */
class DevelGenerateBrowserTest extends DevelGenerateBrowserTestBase {

  use MediaTypeCreationTrait;

  /**
   * Tests generating users.
   */
  public function testDevelGenerateUsers(): void {
    $this->drupalGet('admin/config/development/generate/user');
    $edit = [
      'num' => 4,
    ];
    $this->submitForm($edit, 'Generate');
    $this->assertSession()->pageTextContains('4 users created.');
    $this->assertSession()->pageTextContains('Generate process complete.');

  }

  /**
   * Tests that if no content types are selected an error message is shown.
   */
  public function testDevelGenerateContent(): void {
    $this->drupalGet('admin/config/development/generate/content');
    $edit = [
      'num' => 4,
      'title_length' => 4,
    ];
    $this->submitForm($edit, 'Generate');
    $this->assertSession()->pageTextContains('Please select at least one content type');

    // Create a node in order to test the Delete content checkbox.
    $this->drupalCreateNode(['type' => 'article']);

    // Generate articles with comments and aliases.
    $this->drupalGet('admin/config/development/generate/content');
    $edit = [
      'num' => 4,
      'kill' => TRUE,
      'node_types[article]' => TRUE,
      'time_range' => 604800,
      'max_comments' => 3,
      'title_length' => 4,
      'add_alias' => 1,
    ];
    $this->submitForm($edit, 'Generate');
    $this->assertSession()->pageTextContains('Deleted 1 node');
    $this->assertSession()->pageTextContains('Created 4 nodes');
    $this->assertSession()->pageTextContains('Generate process complete.');
    $this->assertSession()->pageTextNotContains('translations');

    // Tests that nodes have been created in the generation process.
    $nodes = Node::loadMultiple();
    $this->assertEquals(4, count($nodes), 'Nodes generated successfully.');

    // Tests url alias for the generated nodes.
    foreach ($nodes as $node) {
      $alias = 'node-' . $node->id() . '-' . $node->bundle();
      $this->drupalGet($alias);
      $this->assertSession()->statusCodeEquals(200);
      $this->assertSession()->pageTextContains($node->getTitle());
    }

    // Generate articles with translations.
    $this->drupalGet('admin/config/development/generate/content');
    $edit = [
      'num' => 3,
      'kill' => TRUE,
      'node_types[article]' => TRUE,
      'add_language[]' => ['en'],
      'translate_language[]' => ['de', 'ca'],
      'add_alias' => TRUE,
    ];
    $this->submitForm($edit, 'Generate');
    $this->assertSession()->pageTextContains('Deleted 4 nodes');
    $this->assertSession()->pageTextContains('Created 3 nodes');
    // Two translations for each node makes six.
    $this->assertSession()->pageTextContains('Created 6 node translations');
    $articles = \Drupal::entityQuery('node')->accessCheck(FALSE)->execute();
    $this->assertCount(3, $articles);
    $node = Node::load(end($articles));
    $this->assertTrue($node->hasTranslation('de'));
    $this->assertTrue($node->hasTranslation('ca'));
    $this->assertFalse($node->hasTranslation('fr'));

    // Check url alias for each of the translations.
    foreach (Node::loadMultiple($articles) as $node) {
      foreach (['de', 'ca'] as $langcode) {
        $translation_node = $node->getTranslation($langcode);
        $alias = 'node-' . $translation_node->id() . '-' . $translation_node->bundle() . '-' . $langcode;
        $this->drupalGet($langcode . '/' . $alias);
        $this->assertSession()->statusCodeEquals(200);
        $this->assertSession()->pageTextContains($translation_node->getTitle());
      }
    }

    // Create article to make sure it is not deleted when only killing pages.
    $article = $this->drupalCreateNode(['type' => 'article', 'title' => 'Alive']);
    // The 'page' content type is not enabled for translation.
    $edit = [
      'num' => 2,
      'kill' => TRUE,
      'node_types[page]' => TRUE,
      'add_language[]' => ['en'],
      'translate_language[]' => ['fr'],
    ];
    $this->drupalGet('admin/config/development/generate/content');
    $this->submitForm($edit, 'Generate');
    $this->assertSession()->pageTextNotContains('Deleted');
    $this->assertSession()->pageTextContains('Created 2 nodes');
    $this->assertSession()->pageTextNotContains('node translations');
    // Check that 'kill' has not deleted the article.
    $this->assertNotEmpty(Node::load($article->id()));
    $pages = \Drupal::entityQuery('node')->condition('type', 'page')->accessCheck(FALSE)->execute();
    $this->assertCount(2, $pages);
    $node = Node::load(end($pages));
    $this->assertFalse($node->hasTranslation('fr'));

    // Create articles with add-type-label option.
    $edit = [
      'num' => 5,
      'kill' => TRUE,
      'node_types[article]' => TRUE,
      'add_type_label' => TRUE,
    ];
    $this->drupalGet('admin/config/development/generate/content');
    $this->submitForm($edit, 'Generate');
    $this->assertSession()->pageTextContains('Created 5 nodes');
    $this->assertSession()->pageTextContains('Generate process complete');

    // Count the articles created in the generation process.
    $nodes = \Drupal::entityQuery('node')->accessCheck(FALSE)->condition('type', 'article')->execute();
    $this->assertCount(5, $nodes);

    // Load the final node and verify that the title starts with the label.
    $node = Node::load(end($nodes));
    $this->assertEquals('Article - ', substr($node->title->value, 0, 10));

    // Test creating content with specified authors. First create 15 more users
    // making 18 in total, to make the test much stronger.
    for ($i = 0; $i < 15; ++$i) {
      $this->drupalCreateUser();
    }

    $edit = [
      'num' => 10,
      'kill' => TRUE,
      'node_types[article]' => TRUE,
      'authors[3]' => TRUE,
      'authors[4]' => TRUE,
    ];
    $this->drupalGet('admin/config/development/generate/content');
    $this->submitForm($edit, 'Generate');

    // Display the full content list for information and debug only.
    $this->drupalGet('admin/content');

    // Count all the articles by user 3 and 4 and by others. We count the two
    // users nodes separately to ensure that there are some by each user.
    $nodes_by_user_3 = \Drupal::entityQuery('node')->accessCheck(FALSE)->condition('type', 'article')->condition('uid', ['3'], 'IN')->execute();
    $nodes_by_user_4 = \Drupal::entityQuery('node')->accessCheck(FALSE)->condition('type', 'article')->condition('uid', ['4'], 'IN')->execute();
    $nodes_by_others = \Drupal::entityQuery('node')->accessCheck(FALSE)->condition('type', 'article')->condition('uid', ['3', '4'], 'NOT IN')->execute();

    // If the user option was not working correctly and users were assigned at
    // random, then the chance that these assertions will correctly detect the
    // error is 1 - (2/18 ** 10) = 99.99%.
    $this->assertEquals(10, count($nodes_by_user_3) + count($nodes_by_user_4));
    $this->assertCount(0, $nodes_by_others);

    // If the user option is coded correctly the chance of either of these
    // assertions giving a false failure is 1/2 ** 10 = 0.097%.
    $this->assertGreaterThan(0, count($nodes_by_user_3));
    $this->assertGreaterThan(0, count($nodes_by_user_4));
  }

  /**
   * Tests generating terms.
   */
  public function testDevelGenerateTerms(): void {
    // Generate terms.
    $edit = [
      'vids[]' => $this->vocabulary->id(),
      'num' => 5,
      'title_length' => 12,
    ];
    $this->drupalGet('admin/config/development/generate/term');
    $this->submitForm($edit, 'Generate');
    $this->assertSession()->pageTextContains('Created 5 new terms');
    $this->assertSession()->pageTextContains('In vocabulary ' . $this->vocabulary->label());
    $this->assertSession()->pageTextNotContains('translations');
    $this->assertSession()->pageTextContains('Generate process complete.');
    $this->assertCount(5, \Drupal::entityQuery('taxonomy_term')->accessCheck(FALSE)->execute());

    // Generate terms with translations.
    $edit = [
      'vids[]' => $this->vocabulary->id(),
      'num' => 3,
      'add_language[]' => ['en'],
      'translate_language[]' => ['ca'],
    ];
    $this->drupalGet('admin/config/development/generate/term');
    $this->submitForm($edit, 'Generate');
    $this->assertSession()->pageTextNotContains('Deleted');
    $this->assertSession()->pageTextContains('Created 3 new terms');
    $this->assertSession()->pageTextContains('Created 3 term translations');
    // Not using 'kill' so there should be 8 terms.
    $terms = \Drupal::entityQuery('taxonomy_term')->accessCheck(FALSE)->execute();
    $this->assertCount(8, $terms);
    // Check the translations created (and not created).
    $term = Term::load(end($terms));
    $this->assertTrue($term->hasTranslation('ca'));
    $this->assertFalse($term->hasTranslation('de'));
    $this->assertFalse($term->hasTranslation('fr'));

    // Generate terms in vocabulary 2 only.
    $edit = [
      'vids[]' => $this->vocabulary2->id(),
      'num' => 4,
    ];
    $this->drupalGet('admin/config/development/generate/term');
    $this->submitForm($edit, 'Generate');
    $this->assertSession()->pageTextContains('Created 4 new terms');
    $this->assertSession()->pageTextNotContains('In vocabulary ' . $this->vocabulary->label());
    $this->assertSession()->pageTextContains('In vocabulary ' . $this->vocabulary2->label());
    // Check the term count in each vocabulary.
    $terms1 = \Drupal::entityQuery('taxonomy_term')->accessCheck(FALSE)->condition('vid', $this->vocabulary->id())->execute();
    $this->assertCount(8, $terms1);
    $terms2 = \Drupal::entityQuery('taxonomy_term')->accessCheck(FALSE)->condition('vid', $this->vocabulary2->id())->execute();
    $this->assertCount(4, $terms2);

    // Generate in vocabulary 2 with 'kill' to remove the existing vocab2 terms.
    $edit = [
      'vids[]' => $this->vocabulary2->id(),
      'num' => 6,
      'kill' => TRUE,
    ];
    $this->drupalGet('admin/config/development/generate/term');
    $this->submitForm($edit, 'Generate');
    $this->assertSession()->pageTextContains('Deleted 4 existing terms');
    $this->assertSession()->pageTextContains('Created 6 new terms');
    // Check the term count in vocabulary 1 has not changed.
    $terms1 = \Drupal::entityQuery('taxonomy_term')->accessCheck(FALSE)->condition('vid', $this->vocabulary->id())->execute();
    $this->assertCount(8, $terms1);
    // Check the term count in vocabulary 2 is just from the second call.
    $terms2 = \Drupal::entityQuery('taxonomy_term')->accessCheck(FALSE)->condition('vid', $this->vocabulary2->id())->execute();
    $this->assertCount(6, $terms2);

    // Generate in both vocabularies and specify minimum and maximum depth.
    $edit = [
      'vids[]' => [$this->vocabulary->id(), $this->vocabulary2->id()],
      'num' => 9,
      'minimum_depth' => 2,
      'maximum_depth' => 6,
    ];
    $this->drupalGet('admin/config/development/generate/term');
    $this->submitForm($edit, 'Generate');
    $this->assertSession()->pageTextContains('Created 9 new terms');
    // Check the total term count is 8 + 6 + 9 = 23.
    $terms1 = \Drupal::entityQuery('taxonomy_term')->accessCheck(FALSE)->condition('vid', $this->vocabulary->id())->execute();
    $terms2 = \Drupal::entityQuery('taxonomy_term')->accessCheck(FALSE)->condition('vid', $this->vocabulary2->id())->execute();
    $this->assertCount(23, $terms1 + $terms2);

  }

  /**
   * Tests generating vocabularies.
   */
  public function testDevelGenerateVocabs(): void {
    $edit = [
      'num' => 5,
      'title_length' => 12,
      'kill' => TRUE,
    ];
    $this->drupalGet('admin/config/development/generate/vocabs');
    $this->submitForm($edit, 'Generate');
    $this->assertSession()->pageTextContains('Created the following new vocabularies: ');
    $this->assertSession()->pageTextContains('Generate process complete.');
  }

  /**
   * Tests generating menus.
   *
   * @todo Add test coverage to check:
   *   - title_length is not exceeded.
   *   - max_depth and max_width work as designed.
   *   - generating links in existing menus, and then deleting them with kill.
   *   - using specific link_types settings only create those links.
   */
  public function testDevelGenerateMenus(): void {
    $edit = [
      'num_menus' => 5,
      'num_links' => 7,
    ];
    $this->drupalGet('admin/config/development/generate/menu');
    $this->submitForm($edit, 'Generate');
    $this->assertSession()->pageTextContains('Created the following 5 new menus: ');
    $this->assertSession()->pageTextContains('Created 7 new menu links');
    $this->assertSession()->pageTextContains('Generate process complete.');

    // Use big numbers for menus and links, but short text, to test for clashes.
    // Also verify the kill option.
    $edit = [
      'num_menus' => 160,
      'num_links' => 380,
      'title_length' => 3,
      'kill' => 1,
    ];
    $this->drupalGet('admin/config/development/generate/menu');
    $this->submitForm($edit, 'Generate');
    $this->assertSession()->pageTextContains('Deleted 5 menu(s) and 0 other link(s).');
    $this->assertSession()->pageTextContains('Created the following 160 new menus: ');
    $this->assertSession()->pageTextContains('Created 380 new menu links');
    $this->assertSession()->pageTextContains('Generate process complete.');
  }

  /**
   * Tests generating media.
   */
  public function testDevelGenerateMedia(): void {
    // As the 'media' plugin has a dependency on 'media' module, the plugin is
    // not generating a route to the plugin form.
    $this->drupalGet('admin/config/development/generate/media');
    $this->assertSession()->statusCodeEquals(404);
    // Enable the module and retry.
    \Drupal::service('module_installer')->install(['media']);
    $this->getSession()->reload();
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Generate media');

    // Create two media types.
    $media_type1 = $this->createMediaType('image');
    $media_type2 = $this->createMediaType('audio_file');

    // Creating media items (non-batch mode).
    $edit = [
      'num' => 5,
      'name_length' => 12,
      sprintf('media_types[%s]', $media_type1->id()) => 1,
      sprintf('media_types[%s]', $media_type2->id()) => 1,
      'base_fields' => 'phish',
      'kill' => 1,
    ];
    $this->drupalGet('admin/config/development/generate/media');
    $this->submitForm($edit, 'Generate');
    $this->assertSession()->pageTextContains('Finished creating 5 media items.');
    $this->assertSession()->pageTextContains('Generate process complete.');
    $medias = \Drupal::entityQuery('media')->accessCheck(FALSE)->execute();
    $this->assertCount(5, $medias);
    $media = Media::load(end($medias));
    $this->assertNotEmpty($media->get('phish')->getString());

    // Creating media items (batch mode).
    $edit = [
      'num' => 56,
      'name_length' => 6,
      sprintf('media_types[%s]', $media_type1->id()) => 1,
      sprintf('media_types[%s]', $media_type2->id()) => 1,
      'base_fields' => 'phish',
      'kill' => 1,
    ];
    $this->drupalGet('admin/config/development/generate/media');
    $this->submitForm($edit, 'Generate');
    $this->assertSession()->pageTextContains('Finished 56 elements created successfully.');
    $this->assertSession()->pageTextContains('Generate process complete.');
    $this->assertCount(56, \Drupal::entityQuery('media')->accessCheck(FALSE)->execute());
  }

  /**
   * Tests generating content in batch mode.
   */
  public function testDevelGenerateBatchContent(): void {
    // For 50 or more nodes, the processing will be done via batch.
    $edit = [
      'num' => 55,
      'kill' => TRUE,
      'node_types[article]' => TRUE,
      'node_types[page]' => TRUE,
    ];
    $this->drupalGet('admin/config/development/generate/content');
    $this->submitForm($edit, 'Generate');
    $this->assertSession()->pageTextContains('Finished 55 elements created successfully.');
    $this->assertSession()->pageTextContains('Generate process complete.');

    // Tests that the expected number of nodes have been created.
    $count = count(Node::loadMultiple());
    $this->assertEquals(55, $count, sprintf('The expected total number of nodes is %s, found %s', 55, $count));

    // Create nodes with translations via batch.
    $edit = [
      'num' => 52,
      'kill' => TRUE,
      'node_types[article]' => TRUE,
      'node_types[page]' => TRUE,
      'add_language[]' => ['en'],
      'translate_language[]' => ['de', 'ca'],
    ];
    $this->drupalGet('admin/config/development/generate/content');
    $this->submitForm($edit, 'Generate');
    $this->assertCount(52, \Drupal::entityQuery('node')->accessCheck(FALSE)->execute());
    // Only articles will have translations so get that number.
    $articles = \Drupal::entityQuery('node')->accessCheck(FALSE)->condition('type', 'article')->execute();
    $this->assertSession()->pageTextContains(sprintf('Finished 52 elements and %s translations created successfully.', 2 * count($articles)));

    // Generate only articles.
    $edit = [
      'num' => 60,
      'kill' => TRUE,
      'node_types[article]' => TRUE,
      'node_types[page]' => FALSE,
    ];
    $this->drupalGet('admin/config/development/generate/content');
    $this->submitForm($edit, 'Generate');

    // Tests that all the created nodes were of the node type selected.
    $nodeStorage = $this->container->get('entity_type.manager')->getStorage('node');
    $type = 'article';
    $count = $nodeStorage->getQuery()
      ->condition('type', $type)
      ->accessCheck(FALSE)
      ->count()
      ->execute();
    $this->assertEquals(60, $count, sprintf('The expected number of %s is %s, found %s', $type, 60, $count));

  }

}
