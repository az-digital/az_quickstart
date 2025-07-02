<?php

declare(strict_types=1);

namespace Drupal\Tests\google_tag\Kernel\Events;

use Drupal\Core\Url;
use Drupal\google_tag\Entity\TagContainer;
use Drupal\Tests\google_tag\Kernel\GoogleTagTestCase;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\Request;

/**
 * Login event test.
 *
 * @group google_tag
 */
final class LoginEventTest extends GoogleTagTestCase {

  /**
   * Tests login event.
   *
   * @testWith [""]
   *           ["Drupal"]
   *           ["CMS"]
   *           ["[site:url]"]
   *           [null]
   */
  public function testEvent(?string $method): void {
    $event_config = $method !== NULL ? ['method' => $method] : [];
    TagContainer::create([
      'id' => 'foo',
      'weight' => 10,
      'events' => [
        'login' => $event_config,
      ],
    ])->save();
    $user = User::create([
      'mail' => 'foo@example.com',
      'name' => 'foo',
      'pass' => 'barbaz',
      'status' => 1,
    ]);
    $user->save();

    $uri = Url::fromRoute('user.login')->toString();

    $this->doRequest(Request::create($uri));

    $form_data = [
      'name' => 'foo',
      'pass' => 'barbaz',
      'form_build_id' => (string) $this->cssSelect('input[name="form_build_id"]')[0]->attributes()->value[0],
      'form_id' => (string) $this->cssSelect('input[name="form_id"]')[0]->attributes()->value[0],
      'op' => (string) $this->cssSelect('input[name="op"]')[0]->attributes()->value[0],
    ];

    $request = Request::create($uri, 'POST', $form_data);
    $response = $this->doRequest($request);
    self::assertEquals(303, $response->getStatusCode());
    $request = Request::create($response->headers->get('Location'));
    $this->doRequest($request);

    if ($method === '') {
      $event_data = [];
    }
    else {
      if ($method && str_starts_with($method, '[')) {
        $method = $this->container->get('token')->replace($method, [], ['clear' => TRUE]);
      }
      $event_data = [
        'method' => $method ?? 'CMS',
      ];
    }
    $this->assertGoogleTagEvents([
      [
        'name' => 'login',
        'data' => $event_data,
      ],
    ]);
  }

}
