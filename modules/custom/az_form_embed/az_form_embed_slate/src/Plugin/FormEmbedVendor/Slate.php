<?php

declare(strict_types=1);

namespace Drupal\az_form_embed_slate\Plugin\FormEmbedVendor;

use Drupal\az_form_embed\Attribute\FormEmbedVendor;
use Drupal\az_form_embed\FormEmbedTarget;
use Drupal\az_form_embed\FormEmbedVendorBase;
use Drupal\az_form_embed_slate\SlateUrl;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Slate, the admissions system the university's Slate teams build forms in.
 *
 * SlateUrl does the checking. This class only connects it to the Form embed
 * module.
 */
#[FormEmbedVendor(
  id: 'slate',
  label: new TranslatableMarkup('Slate'),
  description: new TranslatableMarkup('Forms built in Slate, such as admissions request-for-information forms.'),
  examples: [
    'https://slate.admissions.arizona.edu/register/?id=dbfabd84-d348-4bf9-88ef-1832b354fcb0',
    'https://slate.grad.arizona.edu/register/?id=dbfabd84-d348-4bf9-88ef-1832b354fcb0&sys:first=Wilbur',
    'https://uaz.test.technolutions.net/register/referawildcat',
  ],
)]
class Slate extends FormEmbedVendorBase {

  /**
   * {@inheritdoc}
   */
  public function claims(string $url): bool {
    return SlateUrl::looksLikeSlate($url);
  }

  /**
   * {@inheritdoc}
   */
  public function resolve(string $url, ?string &$reason = NULL): ?FormEmbedTarget {
    $slate_url = SlateUrl::parse($url, $reason);
    if ($slate_url === NULL) {
      return NULL;
    }
    return new FormEmbedTarget($slate_url->getEmbedUrl(), $slate_url->getCanonicalUrl());
  }

  /**
   * {@inheritdoc}
   *
   * Each case is one of SlateUrl::parse()'s codes.
   */
  public function explain(string $reason): string {
    return (string) match ($reason) {
      'bad_scheme' => $this->t('it must start with https://'),
      'has_userinfo' => $this->t('it contains a user name or password'),
      'has_port' => $this->t('it contains a port number'),
      'has_fragment' => $this->t('it contains a # section'),
      'bad_host' => $this->t("it is not on one of the university's Slate addresses"),
      'bad_path' => $this->t('it does not point to a Slate form page, under /register/'),
      'missing_id' => $this->t('it does not say which form to show. Use the link with ?id= that Slate gives you'),
      'bad_id' => $this->t('its form id is not valid'),
      'person_param' => $this->t("it contains a person parameter, which would show one person's details to everyone"),
      'unknown_param' => $this->t('it contains a parameter Slate forms do not use'),
      'param_too_long' => $this->t('one of its parameters is too long'),
      default => $this->t('it is not a valid Slate link'),
    };
  }

}
