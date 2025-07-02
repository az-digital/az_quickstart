<?php

namespace Drupal\Tests\ctools\Functional;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests basic wizard functionality.
 *
 * @group ctools
 */
class CToolsWizardTest extends BrowserTestBase {

  use StringTranslationTrait;
  protected static $modules = ['ctools', 'ctools_wizard_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Test wizard Multistep form.
   */
  public function testWizardSteps() {
    $this->drupalGet('ctools/wizard');
    $this->assertSession()->pageTextContains('Form One');
    // Check that $operations['one']['values'] worked.
    $this->assertSession()->pageTextContains('Xylophone');
    // Submit first step in the wizard.
    $edit = [
      'one' => 'test',
    ];
    $this->drupalGet('ctools/wizard');
    $this->submitForm($edit, 'Next');
    // Redirected to the second step.
    $this->assertSession()->pageTextContains('Form Two');
    $this->assertSession()->pageTextContains('Dynamic value submitted: Xylophone');
    // Check that $operations['two']['values'] worked.
    $this->assertSession()->pageTextContains('Zebra');
    // Hit previous to make sure our form value are preserved.
    $this->submitForm([], 'Previous');
    // Check the known form values.
    $this->assertSession()->fieldValueEquals('one', 'test');
    $this->assertSession()->pageTextContains('Xylophone');
    // Goto next step again and finish this wizard.
    $this->submitForm([], 'Next');
    $edit = [
      'two' => 'Second test',
    ];
    $this->submitForm($edit, 'Finish');
    // Check that the wizard finished properly.
    $this->assertSession()->pageTextContains('Value One: test');
    $this->assertSession()->pageTextContains('Value Two: Second test');
  }

  /**
   * Test wizard validate and submit.
   */
  public function testStepValidateAndSubmit() {
    $this->drupalGet('ctools/wizard');
    $this->assertSession()->pageTextContains('Form One');
    // Submit first step in the wizard.
    $edit = [
      'one' => 'wrong',
    ];
    $this->drupalGet('ctools/wizard');
    $this->submitForm($edit, 'Next');
    // We're still on the first form and the error is present.
    $this->assertSession()->pageTextContains('Form One');
    $this->assertSession()->pageTextContains('Cannot set the value to "wrong".');
    // Try again with the magic value.
    $edit = [
      'one' => 'magic',
    ];
    $this->drupalGet('ctools/wizard');
    $this->submitForm($edit, 'Next');
    // Redirected to the second step.
    $this->assertSession()->pageTextContains('Form Two');
    $edit = [
      'two' => 'Second test',
    ];
    $this->submitForm($edit, 'Finish');
    // Check that the magic value triggered our submit callback.
    $this->assertSession()->pageTextContains('Value One: Abraham');
    $this->assertSession()->pageTextContains('Value Two: Second test');
  }

  /**
   * Test wizard entity config update.
   */
  public function testEntityWizard() {
    $this->drupalLogin($this->drupalCreateUser(['administer site configuration']));

    // Start adding a new config entity.
    $this->drupalGet('admin/structure/ctools_wizard_test_config_entity/add');
    $this->assertSession()->pageTextContains('Example entity');
    $this->assertSession()->pageTextNotContains('Existing entity');

    // Submit the general step.
    $edit = [
      'id' => 'test123',
      'label' => 'Test Config Entity 123',
    ];
    $this->submitForm($edit, 'Next');

    // Submit the first step.
    $edit = [
      'one' => 'The first bit',
    ];
    $this->submitForm($edit, 'Next');

    // Submit the second step.
    $edit = [
      'two' => 'The second bit',
    ];
    $this->submitForm($edit, 'Finish');

    // Now we should be looking at the list of entities.
    $this->assertSession()->addressEquals('admin/structure/ctools_wizard_test_config_entity');
    $this->assertSession()->pageTextContains('Test Config Entity 123');

    // Edit the entity again and make sure the values are what we expect.
    $this->clickLink($this->t('Edit'));
    $this->assertSession()->pageTextContains('Existing entity');
    $this->assertSession()->fieldValueEquals('label', 'Test Config Entity 123');
    $this->clickLink($this->t('Form One'));
    $this->assertSession()->fieldValueEquals('one', 'The first bit');
    $previous = $this->getUrl();
    $this->clickLink($this->t('Show on dialog'));
    $this->assertSession()->responseContains('Value from one: The first bit');
    $this->drupalGet($previous);
    // Change the value for 'one'.
    $this->submitForm(['one' => 'New value'], 'Next');
    $this->assertSession()->fieldValueEquals('two', 'The second bit');
    $this->submitForm([], 'Next');
    // Make sure we get the additional step because the entity exists.
    $this->assertSession()->pageTextContains('This step only shows if the entity is already existing!');
    $this->submitForm([], 'Finish');

    // Edit the entity again and make sure the change stuck.
    $this->assertSession()->addressEquals('admin/structure/ctools_wizard_test_config_entity');
    $this->clickLink($this->t('Edit'));
    $this->clickLink($this->t('Form One'));
    $this->assertSession()->fieldValueEquals('one', 'New value');
  }

}
