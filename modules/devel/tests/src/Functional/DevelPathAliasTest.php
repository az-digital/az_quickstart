<?php

namespace Drupal\Tests\devel\Functional;

use Drupal\path_alias\Entity\PathAlias;

/**
 * Tests the path alias devel page.
 *
 * @group devel
 */
class DevelPathAliasTest extends DevelBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['devel', 'node', 'path_alias'];

  public function testPathAliasDevelPage() {
    $this->drupalGet('devel/path-alias/node/999');
    $this->assertSession()->statusCodeEquals(404);

    $node = $this->drupalCreateNode();
    $node_id = $node->id();

    $this->drupalGet('devel/path-alias/node/' . $node_id);
    $this->assertSession()->statusCodeEquals(403);

    $this->drupalLogin($this->develUser);

    $this->drupalGet('devel/path-alias/node/' . $node_id);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Found no aliases with path "/node/' . $node_id . '".');

    PathAlias::create([
      'path' => '/node/' . $node_id,
      'alias' => '/custom-path-1',
    ])->save();
    PathAlias::create([
      'path' => '/node/' . $node_id,
      'alias' => '/custom-path-2',
    ])->save();

    $this->drupalGet('devel/path-alias/node/' . $node_id);
    $this->assertSession()->pageTextContains('Found 2 aliases with path "/node/' . $node_id . '".');
  }

}
