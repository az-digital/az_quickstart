<?php

namespace Drupal\externalauth\Form;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Confirm the user wants to delete an authmap entry.
 */
class AuthmapDeleteForm extends ConfirmFormBase {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The EntityTypeManager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Temporary storage for the current authmap entry.
   *
   * @var array
   */
  protected $authmapEntry;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Constructs a router for Drupal with access check and upcasting.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection to get authmap entries.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The EntityTypeManager service.
   */
  public function __construct(Connection $connection, EntityTypeManagerInterface $entity_type_manager) {
    $this->connection = $connection;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'authmap_delete';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion(): TranslatableMarkup {
    if (!empty($this->authmapEntry['uid'])) {
      /** @var \Drupal\user\Entity\User $user */
      $user = $this->entityTypeManager->getStorage('user')->load($this->authmapEntry['uid']);
    }
    // We don't display the provider name; in most use cases it's implicit.
    return $this->t('Are you sure you want to delete the link between authentication name %id and Drupal user %user?', [
      '%id' => $this->authmapEntry['authname'],
      '%user' => isset($user) ? $user->getAccountName() : "<unknown> ({$this->authmapEntry['uid']})",
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl(): Url {
    // We want to return a URL object pointing to admin/people/authmap/PROVIDER.
    // Url('view.authmap.page', ['provider' => PROVIDER]) instead returns
    // admin/people/authmap?provider=PROVIDER, which is not recognized as
    // contextual filter value.
    return Url::fromUri('internal:/admin/people/authmap/' . $this->authmapEntry['provider']);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    // This form has uid + provider in its URL, not authname + provider, to not
    // expose authnames externally in e.g. HTTP referrer logs.
    $authname = FALSE;
    $provider = $this->getRouteMatch()->getParameter('provider');
    $uid = $this->getRouteMatch()->getParameter('uid');
    if ($provider && $uid && filter_var($uid, FILTER_VALIDATE_INT)) {
      $authname = $this->connection->select('authmap', 'm')
        ->fields('m', ['authname'])
        ->condition('m.uid', (int) $uid)
        ->condition('m.provider', $provider)
        ->execute()->fetchField();
    }
    if ($authname === FALSE) {
      // Display same error for either illegal UID or no record.
      $this->messenger()->addError($this->t('No authmap record found for provider @provider / uid @uid.', [
        '@provider' => $provider,
        '@uid' => $uid,
      ]));
      return [];
    }

    $this->authmapEntry = [
      'provider' => $provider,
      'authname' => $authname,
      'uid' => $uid,
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $provider = $this->getRouteMatch()->getParameter('provider');
    $uid = $this->getRouteMatch()->getParameter('uid');
    if (!$provider || !$uid || filter_var($uid, FILTER_VALIDATE_INT) === FALSE) {
      throw new \LogicException('It should be impossible to submit this form without valid provider/uid parameters.');
    }
    $this->connection->delete('authmap')
      ->condition('uid', (int) $uid)
      ->condition('provider', $provider)
      ->execute();
    $this->messenger()->addStatus($this->t('The link has been deleted.'));
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
