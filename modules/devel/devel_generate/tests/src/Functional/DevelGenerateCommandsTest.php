<?php

namespace Drupal\Tests\devel_generate\Functional;

use Drupal\comment\Entity\Comment;
use Drupal\devel_generate\Drush\Commands\DevelGenerateCommands;
use Drupal\media\Entity\Media;
use Drupal\menu_link_content\Entity\MenuLinkContent;
use Drupal\node\Entity\Node;
use Drupal\system\Entity\Menu;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\devel_generate\Traits\DevelGenerateSetupTrait;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;
use Drupal\user\Entity\User;
use Drush\TestTraits\DrushTestTrait;

/**
 * Test class for the Devel Generate drush commands.
 *
 * Note: Drush must be in the Composer project.
 *
 * @coversDefaultClass \Drupal\devel_generate\Drush\Commands\DevelGenerateCommands
 * @group devel_generate
 */
class DevelGenerateCommandsTest extends BrowserTestBase {

  use DrushTestTrait;
  use DevelGenerateSetupTrait;
  use MediaTypeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'comment',
    'content_translation',
    'devel',
    'devel_generate',
    'devel_generate_fields',
    'language',
    'media',
    'menu_ui',
    'node',
    'path',
    'taxonomy',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Prepares the testing environment.
   */
  public function setUp(): void {
    parent::setUp();
    $this->setUpData();
  }

  /**
   * Tests generating users.
   */
  public function testDrushGenerateUsers(): void {
    // Make sure users get created, and with correct roles.
    $this->drush(DevelGenerateCommands::USERS, ['55'], [
      'kill' => NULL,
      'roles' => 'administrator',
    ]);
    $user = User::load(55);
    $this->assertTrue($user->hasRole('administrator'));
  }

  /**
   * Tests generating terms.
   */
  public function testDrushGenerateTerms(): void {
    // Make sure terms get created, and with correct vocab.
    $this->drush(DevelGenerateCommands::TERMS, ['55'], [
      'kill' => NULL,
      'bundles' => $this->vocabulary->id(),
    ]);
    $term = Term::load(55);
    $this->assertEquals($this->vocabulary->id(), $term->bundle());

    // Make sure terms get created, with proper language.
    $this->drush(DevelGenerateCommands::TERMS, ['10'], [
      'kill' => NULL,
      'bundles' => $this->vocabulary->id(),
      'languages' => 'fr',
    ]);
    $term = Term::load(60);
    $this->assertEquals('fr', $term->language()->getId());

    // Make sure terms gets created, with proper translation.
    $this->drush(DevelGenerateCommands::TERMS, ['10'], [
      'kill' => NULL,
      'bundles' => $this->vocabulary->id(),
      'languages' => 'fr',
      'translations' => 'de',
    ]);
    $term = Term::load(70);
    $this->assertTrue($term->hasTranslation('de'));
    $this->assertTrue($term->hasTranslation('fr'));
  }

  /**
   * Tests generating vocabularies.
   */
  public function testDrushGenerateVocabs(): void {
    // Make sure vocabs get created.
    $this->drush(DevelGenerateCommands::VOCABS, ['5'], ['kill' => NULL]);
    $vocabs = Vocabulary::loadMultiple();
    $this->assertGreaterThan(4, count($vocabs));
    $vocab = array_pop($vocabs);
    $this->assertNotEmpty($vocab);
  }

  /**
   * Tests generating menus.
   */
  public function testDrushGenerateMenus(): void {
    $generatedMenu = NULL;

    // Make sure menus, and with correct properties.
    $this->drush(DevelGenerateCommands::MENUS, ['1', '5'], ['kill' => NULL]);
    $menus = Menu::loadMultiple();
    foreach ($menus as $menu) {
      if (str_contains($menu->id(), 'devel-')) {
        // We have a menu that we created.
        $generatedMenu = $menu;
        break;
      }
    }

    $link = MenuLinkContent::load(5);

    $this->assertNotNull($generatedMenu, 'Generated menu successfully.');
    $this->assertNotNull($link, 'Generated link successfully.');
    $this->assertEquals($generatedMenu->id(), $link->getMenuName(), 'Generated menu ID matches link menu name.');
  }

  /**
   * Tests generating content.
   */
  public function testDrushGenerateContent(): void {
    // Generate content using the minimum parameters.
    $this->drush(DevelGenerateCommands::CONTENT, ['21']);
    $node = Node::load(21);
    $this->assertNotEmpty($node);

    // Make sure articles get comments. Only one third of articles will have
    // comment status 'open' and therefore the ability to receive a comment.
    // However, generating 30 articles will give the likelihood of test failure
    // (i.e. no article gets a comment) as 2/3 ^ 30 = 0.00052% or 1 in 191751.
    $this->drush(DevelGenerateCommands::CONTENT, ['30', '9'], [
      'kill' => NULL,
      'bundles' => 'article',
    ]);
    $comment = Comment::load(1);
    $this->assertNotEmpty($comment);

    // Generate content with a higher number that triggers batch running.
    $this->drush(DevelGenerateCommands::CONTENT, ['55'], ['kill' => NULL]);
    $nodes = \Drupal::entityQuery('node')->accessCheck(FALSE)->execute();
    $this->assertCount(55, $nodes);
    $messages = $this->getErrorOutput();
    $this->assertStringContainsStringIgnoringCase('Finished 55 elements created successfully.', $messages, 'devel-generate-content batch ending message not found');

    // Generate specified language. Verify base field is populated.
    $this->drush(DevelGenerateCommands::CONTENT, ['10'], [
      'kill' => NULL,
      'languages' => 'fr',
      'base-fields' => 'phish',
    ]);
    $nodes = \Drupal::entityQuery('node')->accessCheck(FALSE)->execute();
    $node = Node::load(end($nodes));
    $this->assertEquals('fr', $node->language()->getId());
    $this->assertNotEmpty($node->get('phish')->getString());

    // Generate content with translations.
    $this->drush(DevelGenerateCommands::CONTENT, ['18'], [
      'kill' => NULL,
      'languages' => 'fr',
      'translations' => 'de',
    ]);
    // Only articles are enabled for translations.
    $articles = \Drupal::entityQuery('node')
      ->accessCheck(FALSE)
      ->condition('type', 'article')
      ->execute();
    $pages = \Drupal::entityQuery('node')
      ->accessCheck(FALSE)
      ->condition('type', 'page')
      ->execute();
    $this->assertCount(18, $articles + $pages);
    // Check that the last article has 'de' and 'fr' but no 'ca' translation.
    $node = Node::load(end($articles));
    $this->assertTrue($node->hasTranslation('de'));
    $this->assertTrue($node->hasTranslation('fr'));
    $this->assertFalse($node->hasTranslation('ca'));

    // Generate just page content with option --add-type-label.
    // Note: Use the -v verbose option to get the ending message shown when not
    // generating enough to trigger batch mode.
    // @todo Remove -v when the messages are shown for both run types.
    $this->drush(DevelGenerateCommands::CONTENT . ' -v', ['9'], [
      'kill' => NULL,
      'bundles' => 'page',
      'add-type-label' => NULL,
    ]);
    // Count the page nodes.
    $nodes = \Drupal::entityQuery('node')
      ->accessCheck(FALSE)
      ->condition('type', 'page')
      ->execute();
    $this->assertCount(9, $nodes);
    $messages = $this->getErrorOutput();
    $this->assertStringContainsStringIgnoringCase('Created 9 nodes', $messages, 'batch end message not found');
    // Load the final node and verify that the title starts with the label.
    $node = Node::load(end($nodes));
    $this->assertEquals('Basic Page - ', substr($node->title->value, 0, 13));

    // Generate articles with a specified users.
    $this->drush(DevelGenerateCommands::CONTENT . ' -v', ['10'], [
      'kill' => NULL,
      'bundles' => 'article',
      'authors' => '2',
    ]);
    // Count the nodes assigned to user 2. We have two other users (0 and 1) so
    // if the code was broken and users were assigned randomly the chance that
    // this fauly would be detected is 1 - (1/3 ** 10) = 99.998%.
    $nodes = \Drupal::entityQuery('node')
      ->accessCheck(FALSE)
      ->condition('type', 'article')
      ->condition('uid', ['2'], 'IN')
      ->execute();
    $this->assertCount(10, $nodes);

    // Generate page content using the 'roles' option to select authors based
    // on the roles that the user has. For this we need a new user with a
    // distinct role.
    $userA = $this->drupalCreateUser(['access content']);
    $roleA = $userA->getRoles()[1];
    $this->drush(DevelGenerateCommands::CONTENT . ' -v', ['8'], [
      'kill' => NULL,
      'bundles' => 'page',
      'roles' => $roleA,
    ]);
    // Count the number of nodes assigned to User A. There are three other users
    // so if the code was broken and authors assigned randomly, the chance that
    // this test would detect the fault is 1 - (1/4 ^ 8) = 99.998%.
    $nodesA = \Drupal::entityQuery('node')
      ->accessCheck(FALSE)
      ->condition('type', 'page')
      ->condition('uid', $userA->id())
      ->execute();
    $this->assertCount(8, $nodesA, 'User A should have all the generated content');

    // Repeat the above using two roles and two users.
    $userB = $this->drupalCreateUser(['create page content']);
    $roleB = $userB->getRoles()[1];
    $this->drush(DevelGenerateCommands::CONTENT . ' -v', ['20'], [
      'kill' => NULL,
      'bundles' => 'page',
      'roles' => sprintf('%s, %s', $roleA, $roleB),
    ]);
    // Count the nodes assigned to users A and B.  There are three other users
    // so if the code was broken and users were assigned randomly the chance
    // that the test would detect the fault is 1 - (2/5 ^ 20) = 99.999%.
    $nodesA = \Drupal::entityQuery('node')
      ->accessCheck(FALSE)
      ->condition('type', 'page')
      ->condition('uid', $userA->id())
      ->execute();
    $nodesB = \Drupal::entityQuery('node')
      ->accessCheck(FALSE)
      ->condition('type', 'page')
      ->condition('uid', $userB->id())
      ->execute();
    $this->assertGreaterThan(0, count($nodesA), 'User A should have some content');
    $this->assertGreaterThan(0, count($nodesB), 'User B should have some content');
    $this->assertCount(20, $nodesA + $nodesB);
  }

  /**
   * Tests generating media.
   */
  public function testDrushGenerateMedia(): void {
    // Create two media types.
    $media_type1 = $this->createMediaType('image');
    $media_type2 = $this->createMediaType('audio_file');
    // Make sure media items gets created with batch process.
    $this->drush(DevelGenerateCommands::MEDIA, ['53'], [
      'kill' => NULL,
      'base-fields' => 'phish',
    ]);
    $this->assertCount(53, \Drupal::entityQuery('media')
      ->accessCheck(FALSE)
      ->execute());
    $messages = $this->getErrorOutput();
    $this->assertStringContainsStringIgnoringCase('Finished 53 elements created successfully.', $messages, 'devel-generate-media batch ending message not found');
    $medias = \Drupal::entityQuery('media')->accessCheck(FALSE)->execute();
    $media = Media::load(end($medias));
    // Verify that base field populates.
    $this->assertNotEmpty($media->get('phish')->getString());

    // Test also with a non-batch process. We're testing also --kill here.
    $this->drush(DevelGenerateCommands::MEDIA, ['7'], [
      'media-types' => $media_type1->id() . ',' . $media_type2->id(),
      'kill' => NULL,
    ]);
    $this->assertCount(7, \Drupal::entityQuery('media')
      ->accessCheck(FALSE)
      ->execute());
  }

}
