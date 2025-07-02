<?php

declare(strict_types=1);

namespace Drupal\Tests\google_tag\Kernel\Form;

use Drupal\Core\Url;
use Drupal\Tests\google_tag\Kernel\GoogleTagTestCase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Symfony\Component\HttpFoundation\Request;

/**
 * @coversDefaultClass \Drupal\google_tag\Form\SettingsForm
 * @group google_tag
 */
final class SettingsFormTest extends GoogleTagTestCase {

  use UserCreationTrait;

  /**
   * Tests the form.
   */
  public function testForm(): void {
    $user = $this->createUser(['administer google_tag_container']);
    $this->container->get('current_user')->setAccount($user);

    $uri = Url::fromRoute('google_tag.settings_form')->toString();
    $this->doRequest(Request::create($uri));

    $form_data = [
      'use_collection' => '1',
      'form_build_id' => (string) $this->cssSelect('input[name="form_build_id"]')[0]->attributes()->value[0],
      'form_token' => (string) $this->cssSelect('input[name="form_token"]')[0]->attributes()->value[0],
      'form_id' => (string) $this->cssSelect('input[name="form_id"]')[0]->attributes()->value[0],
      'op' => (string) $this->cssSelect('input[name="op"]')[0]->attributes()->value[0],
    ];

    $request = Request::create($uri, 'POST', $form_data);
    $response = $this->doRequest($request);
    self::assertEquals(303, $response->getStatusCode());
    $request = Request::create($response->headers->get('Location'));
    $this->doRequest($request);

    $config = $this->config('google_tag.settings');
    self::assertTrue($config->get('use_collection'));
  }

}
