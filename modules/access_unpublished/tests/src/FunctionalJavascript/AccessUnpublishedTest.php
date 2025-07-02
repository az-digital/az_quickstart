<?php

namespace Drupal\Tests\access_unpublished\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\user\RoleInterface;

/**
 * Test for access unpublished integration.
 *
 * @group access_unpublished
 *
 * @package Drupal\Tests\thunder\FunctionalJavascript\Integration
 */
class AccessUnpublishedTest extends WebDriverTestBase {

  use NodeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'classy';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'access_unpublished',
    'node',
  ];

  /**
   * Permissions for user that will be logged-in for test.
   *
   * @var array
   */
  protected static $userPermissions = [
    'create page content',
    'edit any page content',
    'delete any page content',
    'access content',
    'access_unpublished node page',
    'delete token',
    'renew token',
    'administer nodes',
  ];

  /**
   * Node entity that is used in all tests.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    NodeType::create(['type' => 'page', 'name' => 'page'])->save();

    user_role_grant_permissions(RoleInterface::ANONYMOUS_ID, [
      'access content',
      'access_unpublished node page',
    ]);

    // Create an unpublished entity.
    $this->entity = $this->createNode(['status' => FALSE]);

    $assert_session = $this->assertSession();

    // Verify that the entity is not accessible.
    $this->drupalGet($this->entity->toUrl());
    $assert_session->pageTextContains('Access denied');

    $account = $this->drupalCreateUser(static::$userPermissions);
    $this->drupalLogin($account);
  }

  /**
   * Testing integration of "access_unpublished" module.
   */
  public function testAccessUnpublished() {

    // Edit node and generate access unpubplished token.
    $this->drupalGet($this->entity->toUrl('edit-form'));

    $page = $this->getSession()->getPage();

    $page->clickLink('Temporary unpublished access');
    $page->find('xpath', '//*[@data-drupal-selector="edit-generate-token"]')->click();
    $this->assertSession()->assertWaitOnAjaxRequest();
    $copyToClipboard = $page->find('xpath', '//*[@data-drupal-selector="access-token-list"]//a[contains(@class, "clipboard-button")]');
    $tokenUrl = $copyToClipboard->getAttribute('data-unpublished-access-url');

    // Log-Out and check that URL with token works, but not URL without it.
    $loggedInUser = $this->loggedInUser;
    $this->drupalLogout();
    $this->drupalGet($tokenUrl);
    $this->assertSession()->pageTextContains($this->entity->label());
    $this->drupalGet($this->entity->toUrl());
    $this->assertSession()->pageTextContains('Access denied');

    // Log-In and delete token -> check page can't be accessed.
    $this->drupalLogin($loggedInUser);

    // Check that access unpublished form is not on the delete form.
    $this->drupalGet($this->entity->toUrl('delete-form'));
    $this->assertSession()->pageTextContains('Are you sure you want to delete the content item');
    $this->assertSession()->elementNotExists('css', '[data-drupal-selector="access-token-list"]');

    $this->drupalGet($this->entity->toUrl('edit-form'));
    $page->clickLink('Temporary unpublished access');
    $page->find('css', '[data-drupal-selector="access-token-list"] li.dropbutton-toggle > button')->click();
    $page->find('css', '[data-drupal-selector="access-token-list"] li.delete > a')->click();
    $this->assertSession()->assertWaitOnAjaxRequest();

    // Log-Out and check that URL with token doesn't work anymore.
    $this->drupalLogout();
    $this->drupalGet($tokenUrl);
    $this->assertSession()->pageTextContains('Access denied');

    // Log-In and publish node.
    $this->drupalLogin($loggedInUser);
    $this->drupalGet($this->entity->toUrl('edit-form'));
    $page->checkField('Published');
    $page->pressButton('Save');

    // Log-Out and check that URL to node works.
    $this->drupalLogout();
    $this->drupalGet($this->entity->toUrl());
    $this->assertSession()->pageTextContains($this->entity->label());
  }

}
