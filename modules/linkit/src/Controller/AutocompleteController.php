<?php

namespace Drupal\linkit\Controller;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\linkit\ProfileInterface;
use Drupal\linkit\SuggestionManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Returns responses for linkit autocomplete routes.
 */
class AutocompleteController implements ContainerInjectionInterface {

  /**
   * The suggestion manager.
   *
   * @var \Drupal\linkit\SuggestionManager
   */
  protected $suggestionManager;

  /**
   * Constructs a AutocompleteController object.
   *
   * @param \Drupal\linkit\SuggestionManager $suggestionManager
   *   The suggestion service.
   */
  public function __construct(SuggestionManager $suggestionManager) {
    $this->suggestionManager = $suggestionManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('linkit.suggestion_manager')
    );
  }

  /**
   * Menu callback for linkit search autocompletion.
   *
   * Like other autocomplete functions, this function inspects the 'q' query
   * parameter for the string to use to search for suggestions.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   * @param \Drupal\linkit\ProfileInterface $linkit_profile_id
   *   The linkit profile.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JSON response containing the autocomplete suggestions.
   */
  public function autocomplete(Request $request, ProfileInterface $linkit_profile_id) {
    // We do not need to load the entity, since it is upcaste automatically,
    // per https://www.drupal.org/project/linkit/issues/3212820.
    // The erroneous variable name $linkit_profile_id is to avoid BC breaks.
    $string = $request->query->get('q');

    $suggestionCollection = $this->suggestionManager->getSuggestions($linkit_profile_id, mb_strtolower($string));

    /*
     * If there are no suggestions from the matcher plugins, we have to add a
     * special suggestion that have the same path as the given string so users
     * can select it and use it anyway. This is a common use case with external
     * links.
     */
    if (!count($suggestionCollection->getSuggestions()) && !empty($string)) {
      $suggestionCollection = $this->suggestionManager->addUnscathedSuggestion($suggestionCollection, $string);
    }

    return new JsonResponse($suggestionCollection);
  }

}
