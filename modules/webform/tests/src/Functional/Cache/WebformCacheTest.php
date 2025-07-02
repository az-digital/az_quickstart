<?php

namespace Drupal\Tests\webform\Functional\Cache;

use Drupal\Tests\webform\Functional\WebformBrowserTestBase;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;

/**
 * Tests for #cache properties.
 *
 * @group webform
 */
class WebformCacheTest extends WebformBrowserTestBase {

  /**
   * Test cache.
   */
  public function testCache() {
    /** @var \Drupal\Core\Entity\EntityFormBuilder $entity_form_builder */
    $entity_form_builder = \Drupal::service('entity.form_builder');

    $account = $this->createUser();

    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = Webform::load('contact');
    /** @var \Drupal\webform\WebformSubmissionInterface $webform_submission */
    $webform_submission = WebformSubmission::create(['webform_id' => 'contact']);

    /* ********************************************************************** */

    $form = $entity_form_builder->getForm($webform_submission, 'add');

    // Check that the form includes 'user.roles:authenticated' because the
    // '[current-user:mail]' token.
    $expected = [
      'contexts' => [
        'user.roles:authenticated',
      ],
      'tags' => [
        'CACHE_MISS_IF_UNCACHEABLE_HTTP_METHOD:form',
        'config:core.entity_form_display.webform_submission.contact.add',
        'config:webform.settings',
        'config:webform.webform.contact',
        'webform:contact',
      ],
      'max-age' => -1,
    ];
    if (version_compare(\Drupal::VERSION, '10.3', '<')) {
      array_shift($expected['tags']);
    }
    $this->assertEqualsCanonicalizing($expected, $form['#cache']);

    // Check that the name element does not have #cache because the
    // '[current-user:mail]' is set via
    // \Drupal\webform\WebformSubmissionForm::setEntity.
    $this->assertFalse(isset($form['elements']['email']['#cache']));
    $this->assertEquals($form['elements']['email']['#default_value'], '');

    // Login and check the #cache property.
    $this->drupalLogin($account);
    $webform_submission->setOwnerId($account);
    \Drupal::currentUser()->setAccount($account);

    // Must create a new submission with new data which is set via
    // WebformSubmissionForm::setEntity.
    // @see \Drupal\webform\WebformSubmissionForm::setEntity
    $webform_submission = WebformSubmission::create(['webform_id' => 'contact']);

    $form = $entity_form_builder->getForm($webform_submission, 'add');

    // Check that the form includes 'user.roles:authenticated' because the
    // '[current-user:mail]' token.
    $expected = [
      'contexts' => [
        'user',
        'user.roles:authenticated',
      ],
      'tags' => [
        'CACHE_MISS_IF_UNCACHEABLE_HTTP_METHOD:form',
        'config:core.entity_form_display.webform_submission.contact.add',
        'config:webform.settings',
        'config:webform.webform.contact',
        'user:2',
        'webform:contact',
      ],
      'max-age' => -1,
    ];
    if (version_compare(\Drupal::VERSION, '10.3', '<')) {
      array_shift($expected['tags']);
    }
    $this->assertEqualsCanonicalizing($expected, $form['#cache']);
    $this->assertFalse(isset($form['elements']['email']['#cache']));
    $this->assertEquals($form['elements']['email']['#default_value'], $account->getEmail());

    // Add the '[current-user:mail]' to the name elements' description.
    $element = $webform->getElementDecoded('email')
      + ['#description' => '[current-user:mail]']; // phpcs:ignore
    $webform
      ->setElementProperties('email', $element)
      ->save();

    $form = $entity_form_builder->getForm($webform_submission, 'add');

    // Check that the 'email' element does have '#cache' property because the
    // '#description' is using the '[current-user:mail]' token.
    $expected = [
      'contexts' => [
        'user',
      ],
      'tags' => [
        'config:webform.settings',
        'config:webform.webform.contact',
        'user:2',
        'webform:contact',
      ],
      'max-age' => -1,
    ];
    $this->assertEqualsCanonicalizing($expected, $form['elements']['email']['#cache']);
    $this->assertEquals($form['elements']['email']['#default_value'], $account->getEmail());
    $this->assertEquals($form['elements']['email']['#description']['#markup'], $account->getEmail());
  }

}
