<?php

declare(strict_types=1);

namespace Drupal\Tests\google_tag\Kernel\Form;

use Drupal\Core\Url;
use Drupal\Tests\google_tag\Kernel\GoogleTagTestCase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Symfony\Component\HttpFoundation\Request;

/**
 * @coversDefaultClass \Drupal\google_tag\Form\TagContainerForm
 * @group google_tag
 */
final class TagContainerFormTest extends GoogleTagTestCase {
  use UserCreationTrait;

  /**
   * Tests the form.
   */
  public function testForm(): void {
    $user = $this->createUser(['administer google_tag_container']);
    $this->container->get('current_user')->setAccount($user);

    $uri = Url::fromRoute('entity.google_tag_container.single_form')->toString();
    $response = $this->doRequest(Request::create($uri));
    self::assertEquals(200, $response->getStatusCode());

    $form_data = [
      'accounts' => [
        ['value' => 'GT-XXXXXX', 'weight' => 0],
        ['value' => 'UA-XXXXXX', 'weight' => 1],
        ['value' => 'GTM-XX3XXX', 'weight' => 1],
      ],
      'status' => TRUE,
      'form_build_id' => (string) $this->cssSelect('input[name="form_build_id"]')[0]->attributes()->value[0],
      'form_token' => (string) $this->cssSelect('input[name="form_token"]')[0]->attributes()->value[0],
      'form_id' => (string) $this->cssSelect('input[name="form_id"]')[0]->attributes()->value[0],
      'op' => (string) $this->cssSelect('#edit-submit')[0]->attributes()->value[0],
    ];

    $request = Request::create($uri, 'POST', $form_data);
    $response = $this->doRequest($request);
    self::assertEquals(303, $response->getStatusCode());
    $request = Request::create($response->headers->get('Location'));
    $this->doRequest($request);
    self::assertStringContainsString(
      'The configuration options have been saved.',
      $this->getRawContent()
    );

    $config = $this->config('google_tag.settings');
    self::assertNotEmpty($config->get('default_google_tag_entity'));
    $google_tag_id = $config->get('default_google_tag_entity');
    /** @var \Drupal\google_tag\Entity\TagContainer $google_tag */
    $google_tag = $this->container->get('entity_type.manager')->getStorage('google_tag_container')->load($google_tag_id);
    self::assertEmpty($google_tag->get('conditions'));
    self::assertTrue($google_tag->status());
  }

}
