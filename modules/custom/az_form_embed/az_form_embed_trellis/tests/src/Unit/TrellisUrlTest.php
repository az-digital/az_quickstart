<?php

declare(strict_types=1);

namespace Drupal\Tests\az_form_embed_trellis\Unit;

use Drupal\az_form_embed_trellis\TrellisUrl;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests how Trellis form links are checked and rebuilt.
 */
#[CoversClass(TrellisUrl::class)]
#[Group('az_form_embed')]
class TrellisUrlTest extends UnitTestCase {

  /**
   * Links that are accepted, with the URLs they become.
   *
   * @return array<string, array{string, string, string}>
   *   The link, then its embed URL, then its canonical URL.
   */
  public static function validUrlProvider(): array {
    return [
      'number' => [
        'https://forms-a.trellis.arizona.edu/207',
        'https://forms-a.trellis.arizona.edu/publish/207',
        'https://forms-a.trellis.arizona.edu/207',
      ],
      'long form link' => [
        'https://forms-a.trellis.arizona.edu/forms/view/207',
        'https://forms-a.trellis.arizona.edu/publish/207',
        'https://forms-a.trellis.arizona.edu/207',
      ],
      'review link names its form' => [
        'https://forms-a.trellis.arizona.edu/forms/legacyView/207/8a3753932ce1de86cf2371a11d1d1b50/219983',
        'https://forms-a.trellis.arizona.edu/publish/207',
        'https://forms-a.trellis.arizona.edu/207',
      ],
      'tfaforms host' => [
        'https://trellis.tfaforms.net/72',
        'https://trellis.tfaforms.net/publish/72',
        'https://trellis.tfaforms.net/72',
      ],
      'prefill kept, re-encoded' => [
        'https://forms-a.trellis.arizona.edu/185?tfa_4=701V400000r8xdx&tfa_7=CESL+Newsletter&tfa_14=https%3A%2F%2Fcesl.arizona.edu%2Fsubscribe-thank-you',
        'https://forms-a.trellis.arizona.edu/publish/185?tfa_4=701V400000r8xdx&tfa_7=CESL%20Newsletter&tfa_14=https%3A%2F%2Fcesl.arizona.edu%2Fsubscribe-thank-you',
        'https://forms-a.trellis.arizona.edu/185?tfa_4=701V400000r8xdx&tfa_7=CESL%20Newsletter&tfa_14=https%3A%2F%2Fcesl.arizona.edu%2Fsubscribe-thank-you',
      ],
      'tracking keys dropped' => [
        'https://forms-a.trellis.arizona.edu/72?tfa_4=701V400000liWCQ&_gl=1*qjk09m*_gcl_au*Mjg4&utm_source=x',
        'https://forms-a.trellis.arizona.edu/publish/72?tfa_4=701V400000liWCQ',
        'https://forms-a.trellis.arizona.edu/72?tfa_4=701V400000liWCQ',
      ],
      'repeated key keeps its last copy, as FormAssembly does' => [
        'https://forms-a.trellis.arizona.edu/185?tfa_5=Red&tfa_5=Red;Blue',
        'https://forms-a.trellis.arizona.edu/publish/185?tfa_5=Red%3BBlue',
        'https://forms-a.trellis.arizona.edu/185?tfa_5=Red%3BBlue',
      ],
      'host case ignored' => [
        'https://Forms-A.Trellis.Arizona.edu/185/',
        'https://forms-a.trellis.arizona.edu/publish/185',
        'https://forms-a.trellis.arizona.edu/185',
      ],
    ];
  }

  /**
   * Tests that a valid link becomes the expected embed and canonical URLs.
   */
  #[DataProvider('validUrlProvider')]
  public function testValidUrl(string $url, string $embed, string $canonical): void {
    $trellis_url = TrellisUrl::parse($url, $reason);
    $this->assertNotNull($trellis_url, "Refused with $reason");
    $this->assertSame($embed, $trellis_url->getEmbedUrl());
    $this->assertSame($canonical, $trellis_url->getCanonicalUrl());
  }

  /**
   * Links that are refused, with the reason code.
   *
   * @return array<string, array{string, string}>
   *   The link, then the reason code.
   */
  public static function invalidUrlProvider(): array {
    return [
      'http' => ['http://forms-a.trellis.arizona.edu/185', 'bad_scheme'],
      'user info' => ['https://a:b@forms-a.trellis.arizona.edu/185', 'has_userinfo'],
      'port' => ['https://forms-a.trellis.arizona.edu:8443/185', 'has_port'],
      'fragment' => ['https://forms-a.trellis.arizona.edu/185#x', 'has_fragment'],
      'other host' => ['https://forms.example.com/185', 'bad_host'],
      'lookalike host' => ['https://forms-a.trellis.arizona.edu.example.com/185', 'bad_host'],
      'named form' => ['https://forms-a.trellis.arizona.edu/f/CampaignSubscription?tfa_4=701V400000liWCQ', 'named_form'],
      'publish link' => ['https://forms-a.trellis.arizona.edu/publish/185', 'bad_path'],
      'dot segments' => ['https://forms-a.trellis.arizona.edu/185/../admin', 'bad_path'],
      'long value' => ['https://forms-a.trellis.arizona.edu/185?tfa_7=' . str_repeat('a', 513), 'param_too_long'],
    ];
  }

  /**
   * Tests that an invalid link is refused with the expected reason.
   */
  #[DataProvider('invalidUrlProvider')]
  public function testInvalidUrl(string $url, string $expected_reason): void {
    $this->assertNull(TrellisUrl::parse($url, $reason));
    $this->assertSame($expected_reason, $reason);
  }

  /**
   * Tests which links count as Trellis's, even when they're refused.
   */
  public function testLooksLikeTrellis(): void {
    $this->assertTrue(TrellisUrl::looksLikeTrellis('https://forms-a.trellis.arizona.edu/f/CampaignSubscription'));
    $this->assertTrue(TrellisUrl::looksLikeTrellis('http://trellis.tfaforms.net/72'));
    $this->assertFalse(TrellisUrl::looksLikeTrellis('https://uaz.test.technolutions.net/register/referawildcat'));
    $this->assertFalse(TrellisUrl::looksLikeTrellis('not a link'));
  }

}
