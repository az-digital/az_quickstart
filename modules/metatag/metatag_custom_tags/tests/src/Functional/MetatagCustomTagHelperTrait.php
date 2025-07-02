<?php

namespace Drupal\Tests\metatag_custom_tags\Functional;

/**
 * Custom tag helper functions for the automated tests.
 */
trait MetatagCustomTagHelperTrait {

  /**
   * Create Custom tag.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   * @throws \Behat\Mink\Exception\ExpectationException
   * @throws \Behat\Mink\Exception\ResponseTextException
   */
  protected function createCustomMetaTag($htmlElement, $htmlNameAttribute, $htmlValueAttribute) {
    // Access custom meta add page.
    $this->drupalGet('admin/config/search/metatag/custom-tags/add');
    $this->assertSession()->statusCodeEquals(200);
    $edit = [];
    $edit['id'] = 'foo';
    $edit['label'] = 'foo label';
    $edit['description'] = 'foo description';
    $edit['htmlElement'] = $htmlElement;
    $edit['htmlNameAttribute'] = $htmlNameAttribute;
    $edit['htmlValueAttribute'] = $htmlValueAttribute;
    $this->submitForm($edit, 'Save');
    $this->assertSession()->addressEquals('/admin/config/search/metatag/custom-tags');
    $this->assertSession()->pageTextContains('Created foo label Custom tag.');
  }

  /**
   * Update Custom Metatag.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   * @throws \Behat\Mink\Exception\ExpectationException
   * @throws \Behat\Mink\Exception\ResponseTextException
   */
  protected function updateCustomMetaTag() {
    $this->drupalGet('admin/config/search/metatag/custom-tags/foo/edit');
    $this->assertSession()->statusCodeEquals(200);
    $this->submitForm(['description' => 'foo description updated'], 'Save');
    $this->assertSession()->addressEquals('/admin/config/search/metatag/custom-tags');
    $this->assertSession()->pageTextContains('Updated foo label Custom tag.');
  }

  /**
   * Delete Custom Metatag.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   * @throws \Behat\Mink\Exception\ExpectationException
   * @throws \Behat\Mink\Exception\ResponseTextException
   */
  protected function deleteCustomMetaTag() {
    $this->drupalGet('admin/config/search/metatag/custom-tags/foo/delete');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Are you sure you want to delete the Custom tag foo label?');
    $this->submitForm([], 'Delete');
    $this->assertSession()->addressEquals('/admin/config/search/metatag/custom-tags');
  }

  /**
   * Remove default custom tags.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   * @throws \Behat\Mink\Exception\ExpectationException
   * @throws \Behat\Mink\Exception\ResponseTextException
   */
  protected function removeDefaultCustomTags() {
    $this->drupalGet('admin/config/search/metatag/custom-tags/sitename/delete');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Are you sure you want to delete the Custom tag Sitename?');
    $this->submitForm([], 'Delete');
    $this->assertSession()->addressEquals('/admin/config/search/metatag/custom-tags');
  }

  /**
   * Check metatag custom tag listing page empty text.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   * @throws \Behat\Mink\Exception\ExpectationException
   * @throws \Behat\Mink\Exception\ResponseTextException
   */
  protected function metatagCustomTagListingEmptyText() {
    $this->drupalGet('admin/config/search/metatag/custom-tags');
    $this->assertSession()->statusCodeEquals(200);
    // Check that the Add tag link exists.
    $this->assertSession()->linkByHrefExists('admin/config/search/metatag/custom-tags/add');
    // Check that empty message exists.
    $this->assertSession()->pageTextContains('There are no Custom tags yet.');
  }

}
