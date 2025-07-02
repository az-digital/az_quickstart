<?php

declare(strict_types=1);

namespace Drupal\Tests\google_tag\Kernel\Events;

use Drupal\Core\Url;
use Drupal\google_tag\Entity\TagContainer;
use Drupal\Tests\google_tag\Kernel\GoogleTagTestCase;
use Drupal\user\RoleInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Search event test.
 *
 * @group google_tag
 */
final class SearchEventTest extends GoogleTagTestCase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['search', 'search_extra_type', 'test_page_test'];

  /**
   * Tests search event.
   */
  public function testEvent(): void {
    TagContainer::create([
      'id' => 'foo',
      'weight' => 10,
      'events' => ['search' => []],
    ])->save();

    $this->installConfig(['search_extra_type']);
    user_role_grant_permissions(RoleInterface::ANONYMOUS_ID, ['search content']);

    $uri = Url::fromRoute('search.view_dummy_search_type')->toString();
    $this->doRequest(Request::create($uri));

    $form_data = [
      'keys' => 'test search',
      'form_build_id' => (string) $this->cssSelect('input[name="form_build_id"]')[0]->attributes()->value[0],
      'form_id' => (string) $this->cssSelect('input[name="form_id"]')[0]->attributes()->value[0],
      'op' => (string) $this->cssSelect('input[name="op"]')[0]->attributes()->value[0],
    ];

    $request = Request::create($uri, 'POST', $form_data);
    $response = $this->doRequest($request);
    self::assertEquals(303, $response->getStatusCode());
    $request = Request::create($response->headers->get('Location'));
    $this->doRequest($request);

    $this->assertGoogleTagEvents([
      [
        'name' => 'search',
        'data' => [
          'search_term' => $form_data['keys'],
        ],
      ],
    ]);
  }

}
