<?php

namespace Drupal\Tests\token\Functional;

use Drupal\block_content\Entity\BlockContent;
use Drupal\block_content\Entity\BlockContentType;

/**
 * Tests block tokens.
 *
 * @group token
 */
class TokenBlockTest extends TokenTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['block', 'node', 'views', 'block_content'];

  /**
   * Admin user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();
    $this->adminUser = $this->drupalCreateUser(['access content', 'administer blocks']);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * {@inheritdoc}
   */
  public function testBlockTitleTokens(): void {
    $label = 'tokenblock';
    $bundle = BlockContentType::create([
      'id' => $label,
      'label' => $label,
      'revision' => FALSE
    ]);
    $bundle->save();

    $block_content = BlockContent::create([
      'type' => $label,
      'label' => '[current-page:title] block title',
      'info' => 'Test token title block',
      'body[value]' => 'This is the test token title block.',
    ]);
    $block_content->save();

    $block = $this->drupalPlaceBlock('block_content:' . $block_content->uuid(), [
      'label' => '[user:name]',
    ]);
    $this->drupalGet($block->toUrl());
    // Ensure that the link to available tokens is present and correctly
    // positioned.
    $this->assertSession()->linkExists('Browse available tokens.');
    $this->assertSession()->pageTextContains('This field supports tokens. Browse available tokens.');
    $this->submitForm([], 'Save block');
    // Ensure token validation is working on the block.
    $this->assertSession()->pageTextContains('Title is using the following invalid tokens: [user:name].');

    // Create the block for real now with a valid title.
    $settings = $block->get('settings');
    $settings['label'] = '[current-page:title] block title';
    $block->set('settings', $settings);
    $block->save();

    // Ensure that tokens are not double-escaped when output as a block title.
    $this->drupalCreateContentType(['type' => 'page']);
    $node = $this->drupalCreateNode(['title' => "Site's first node"]);
    $this->drupalGet('node/' . $node->id());
    // The apostrophe should only be escaped once.
    $this->assertSession()->responseContains("Site&#039;s first node block title");
  }
}
