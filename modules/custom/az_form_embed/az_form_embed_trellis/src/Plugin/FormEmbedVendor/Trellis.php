<?php

declare(strict_types=1);

namespace Drupal\az_form_embed_trellis\Plugin\FormEmbedVendor;

use Drupal\az_form_embed\Attribute\FormEmbedVendor;
use Drupal\az_form_embed\FormEmbedTarget;
use Drupal\az_form_embed\FormEmbedVendorBase;
use Drupal\az_form_embed_trellis\TrellisUrl;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Trellis, whose forms are built in FormAssembly.
 *
 * TrellisUrl does the checking. This class only connects it to the Form
 * embed module.
 */
#[FormEmbedVendor(
  id: 'trellis',
  label: new TranslatableMarkup('Trellis'),
  description: new TranslatableMarkup('Forms built in Trellis (FormAssembly), such as newsletter sign-up forms.'),
  examples: [
    'https://forms-a.trellis.arizona.edu/185?tfa_4=701V400000r8xdx',
    'https://trellis.tfaforms.net/72',
  ],
)]
class Trellis extends FormEmbedVendorBase {

  /**
   * {@inheritdoc}
   */
  public function claims(string $url): bool {
    return TrellisUrl::looksLikeTrellis($url);
  }

  /**
   * {@inheritdoc}
   */
  public function resolve(string $url, ?string &$reason = NULL): ?FormEmbedTarget {
    $trellis_url = TrellisUrl::parse($url, $reason);
    if ($trellis_url === NULL) {
      return NULL;
    }
    return new FormEmbedTarget($trellis_url->getEmbedUrl(), $trellis_url->getCanonicalUrl());
  }

  /**
   * {@inheritdoc}
   *
   * Each case is one of TrellisUrl::parse()'s codes.
   */
  public function explain(string $reason): string {
    return (string) match ($reason) {
      'bad_scheme' => $this->t('it must start with https://'),
      'has_userinfo' => $this->t('it contains a user name or password'),
      'has_port' => $this->t('it contains a port number'),
      'has_fragment' => $this->t('it contains a # section'),
      'bad_host' => $this->t('it is not on a Trellis form address'),
      'named_form' => $this->t("it names the form instead of giving its number, and Trellis can only embed a form by its number. Ask your Trellis team for the form's number link, such as https://forms-a.trellis.arizona.edu/72"),
      'bad_path' => $this->t('it does not point to a Trellis form. Use a link like https://forms-a.trellis.arizona.edu/185'),
      'param_too_long' => $this->t('one of its parameters is too long'),
      default => $this->t('it is not a valid Trellis link'),
    };
  }

}
