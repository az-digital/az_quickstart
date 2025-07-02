<?php

namespace Drupal\easy_breadcrumb;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Unicode;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Access\AccessManagerInterface;
use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\TitleResolverInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Menu\MenuLinkManager;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\ParamConverter\ParamNotConvertedException;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Path\PathMatcherInterface;
use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Drupal\Core\Routing\RequestContext;
use Drupal\Core\Routing\RouteMatch;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Routing\RouteObjectInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Matcher\RequestMatcherInterface;

/**
 * Primary implementation for the Easy Breadcrumb builder.
 */
class EasyBreadcrumbBuilder implements BreadcrumbBuilderInterface {
  use StringTranslationTrait;

  /**
   * The router request context.
   *
   * @var \Drupal\Core\Routing\RequestContext
   */
  protected $context;

  /**
   * The access manager service.
   *
   * @var \Drupal\Core\Access\AccessManagerInterface
   */
  protected $accessManager;
  /**
   * The request stack service.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The dynamic router service.
   *
   * @var \Symfony\Component\Routing\Matcher\RequestMatcherInterface
   */
  protected $router;

  /**
   * The path processor service.
   *
   * @var \Drupal\Core\PathProcessor\InboundPathProcessorInterface
   */
  protected $pathProcessor;

  /**
   * Site config object.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $siteConfig;

  /**
   * Breadcrumb config object.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * Language negotiation config object.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $languageNegotiationConfig;

  /**
   * The title resolver.
   *
   * @var \Drupal\easy_breadcrumb\TitleResolver
   */
  protected $titleResolver;

  /**
   * The current user object.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The current path object.
   *
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  protected $currentPath;

  /**
   * The menu link manager.
   *
   * @var \Drupal\Core\Menu\MenuLinkManager
   */
  protected $menuLinkManager;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The logger service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $logger;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The path matcher.
   *
   * @var \Drupal\Core\Path\PathMatcherInterface
   */
  protected $pathMatcher;

  /**
   * Constructs the EasyBreadcrumbBuilder.
   *
   * @param \Drupal\Core\Routing\RequestContext $context
   *   The router request context.
   * @param \Drupal\Core\Access\AccessManagerInterface $access_manager
   *   The access manager service.
   * @param \Symfony\Component\Routing\Matcher\RequestMatcherInterface $router
   *   The dynamic router service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack service.
   * @param \Drupal\Core\PathProcessor\InboundPathProcessorInterface $path_processor
   *   The inbound path processor.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\Core\Controller\TitleResolverInterface $title_resolver
   *   The title resolver service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user object.
   * @param \Drupal\Core\Path\CurrentPathStack $current_path
   *   The current path.
   * @param \Drupal\Core\Menu\MenuLinkManager $menu_link_manager
   *   The menu link manager.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger
   *   The logger service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Path\PathMatcherInterface $path_matcher
   *   The path matcher.
   */
  public function __construct(RequestContext $context, AccessManagerInterface $access_manager, RequestMatcherInterface $router, RequestStack $request_stack, InboundPathProcessorInterface $path_processor, ConfigFactoryInterface $config_factory, TitleResolverInterface $title_resolver, AccountInterface $current_user, CurrentPathStack $current_path, MenuLinkManager $menu_link_manager, LanguageManagerInterface $language_manager, EntityTypeManagerInterface $entity_type_manager, EntityRepositoryInterface $entity_repository, LoggerChannelFactoryInterface $logger, MessengerInterface $messenger, ModuleHandlerInterface $module_handler, PathMatcherInterface $path_matcher) {
    $this->context = $context;
    $this->accessManager = $access_manager;
    $this->router = $router;
    $this->requestStack = $request_stack;
    $this->pathProcessor = $path_processor;
    $this->siteConfig = $config_factory->get('system.site');
    $this->config = $config_factory->get(EasyBreadcrumbConstants::MODULE_SETTINGS);
    $this->languageNegotiationConfig = $config_factory->get('language.negotiation');
    $this->titleResolver = $title_resolver;
    $this->currentUser = $current_user;
    $this->currentPath = $current_path;
    $this->menuLinkManager = $menu_link_manager;
    $this->languageManager = $language_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityRepository = $entity_repository;
    $this->logger = $logger;
    $this->messenger = $messenger;
    $this->moduleHandler = $module_handler;
    $this->pathMatcher = $path_matcher;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    $applies_admin_routes = $this->config->get(EasyBreadcrumbConstants::APPLIES_ADMIN_ROUTES);

    // If never set before ensure Applies to administration pages is on.
    if (!isset($applies_admin_routes)) {

      return TRUE;
    }
    $request = $this->requestStack->getCurrentRequest();
    $route = $request->attributes->get(RouteObjectInterface::ROUTE_OBJECT);
    if ($route && $route->getOption('_admin_route') && $applies_admin_routes == FALSE) {

      return FALSE;
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function build(RouteMatchInterface $route_match) {
    $breadcrumb = new Breadcrumb();
    $links = [];
    $exclude = [];
    $curr_lang = $this->languageManager->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)->getId();
    $replacedTitles = [];
    $configTitles = $this->config->get(EasyBreadcrumbConstants::REPLACED_TITLES);
    $mapValues = !empty($configTitles) ? preg_split('/[\r\n]+/', $configTitles) : [];
    $limit_display = $this->config->get(EasyBreadcrumbConstants::LIMIT_SEGMENT_DISPLAY);
    $segment_limit = $this->config->get(EasyBreadcrumbConstants::SEGMENT_DISPLAY_LIMIT);
    foreach ($mapValues as $mapValue) {
      $values = explode("::", $mapValue);
      if (count($values) == 2) {
        $replacedTitles[$values[0]] = $values[1];
      }
    }

    // Set request context from the $route_match if route is available.
    $this->setRouteContextFromRouteMatch($route_match);

    // General path-based breadcrumbs. Use the actual request path, prior to
    // resolving path aliases so the breadcrumb can be defined by creating a
    // hierarchy of path aliases.
    $path = trim($this->context->getPathInfo(), '/');

    // Ensure that Views AJAX requests do not seep into the breadcrumb.  This
    // can be a problem when the breadcrumb exists inside the view header.
    if ($route_match->getRouteName() == 'views.ajax') {
      $path = trim($this->currentPath->getPath(), '/');
    }

    $path = urldecode($path);
    $path_elements = explode('/', trim($path, '/'));
    $front = $this->siteConfig->get('page.front');

    // Give the option to keep the breadcrumb on the front page.
    $keep_front = !empty($this->config->get(EasyBreadcrumbConstants::HOME_SEGMENT_TITLE))
                  && $this->config->get(EasyBreadcrumbConstants::HOME_SEGMENT_KEEP);
    $exclude[$front] = !$keep_front;
    $exclude[''] = !$keep_front;
    $exclude['/user'] = TRUE;

    // See if we are doing a Custom Path override.
    $path_crumb_row = preg_split('/[\r\n]+/', (string) $this->config->get(EasyBreadcrumbConstants::CUSTOM_PATHS));
    $path_crumb_row = array_filter($path_crumb_row);
    foreach ($path_crumb_row as $path_crumb) {
      $values = explode("::", $path_crumb);

      // Shift path off array.
      $custom_path = array_shift($values);

      // Strip of leading/ending slashes and spaces/tabs (allows indenting
      // rows on config page).
      $custom_path = trim($custom_path, "/ \t");

      // Check if custom path includes the flag used to signify that the
      // path is expressed as a regular expression pattern.
      $regex_match = [];
      $is_regex = preg_match('/^regex\s*!\s*\/(.*)/', $custom_path, $regex_match);
      if ($is_regex) {
        $custom_path = $regex_match[1];
        $regex_group_matches = [];
      }

      $internal_path = $route_match->getRouteObject() ? Url::fromRouteMatch($route_match)->getInternalPath() : '';

      // If the path matches the current path, build the breadcrumbs.
      if (
        ($is_regex && preg_match("|" . $custom_path . "|", $path, $regex_group_matches))
        || ($is_regex && preg_match("|" . $custom_path . "|", $internal_path, $regex_group_matches))
        || (!$is_regex && $path == $custom_path)
        || (!$is_regex && $internal_path == $custom_path)
      ) {
        if ($this->config->get(EasyBreadcrumbConstants::INCLUDE_HOME_SEGMENT)) {
          $links[] = Link::createFromRoute($this->config->get(EasyBreadcrumbConstants::HOME_SEGMENT_TITLE), '<front>');
        }

        if ($is_regex && count($regex_group_matches) > 1) {
          // Discard first element as that's the full matched string
          // rather than a captured group.
          array_shift($regex_group_matches);
        }

        // Get $title|[$url] pairs from $values.
        foreach ($values as $pair) {
          $settings = explode("|", $pair);
          $use_current_page_title = strpos($settings[0], '<title>') !== FALSE;

          // If the custom title uses the current page title, fetch it.
          if ($use_current_page_title) {
            $route_request = $this->getRequestForPath($path, []);

            if ($route_request) {
              $route_match = RouteMatch::createFromRequest($route_request);
              $access = $this->accessManager->check($route_match, $this->currentUser, NULL, TRUE);
              $breadcrumb = $breadcrumb->addCacheableDependency($access);
              // The set of breadcrumb links depends on the access result,
              // so merge the access result's cacheability metadata.
              if ($access->isAllowed()) {
                if ($this->config->get(EasyBreadcrumbConstants::TITLE_FROM_PAGE_WHEN_AVAILABLE)) {
                  $normalized_title = $this->normalizeText($this->getTitleString($route_request, $route_match, $replacedTitles));
                  // Replace <title> tag in the text provided for the segment.
                  $settings[0] = str_replace('<title>', $normalized_title, $settings[0]);
                }
              }
            }
          }

          // If the custom title includes any regex match groups
          // (eg. "/foo/(\d*)/bar") then check if the urls for any segments
          // have matched group variables (eg. $1 or $3) and if they do
          // substitute them out for the corresponding matched strings.
          elseif ($is_regex) {
            foreach ($regex_group_matches as $group_num => $captured_str) {
              $settings[0] = str_replace('$' . ($group_num + 1), urlencode($captured_str), $settings[0]);
            }
          }

          $title = Html::decodeEntities(Xss::filter(trim($settings[0])));

          // Get URL if it is provided.
          $url = '';
          if (isset($settings[1])) {
            $url = trim($settings[1]);

            // If the custom path includes any regex match groups
            // (eg. "/foo/(\d*)/bar") then check if the urls for any segments
            // have matched group variables (eg. $1 or $3) and if they do
            // substitute them out for the corresponding matched strings.
            if ($is_regex) {
              foreach ($regex_group_matches as $group_num => $captured_str) {
                $url = str_replace('$' . ($group_num + 1), urlencode($captured_str), $url);
              }
            }

            // If URL is invalid, then display warning and disable the link.
            if (!UrlHelper::isValid($url)) {
              $this->messenger->addWarning($this->t(
                "EasyBreadcrumb: Custom crumb for @path URL '@url' is invalid.",
                ['@path' => $path, '@url' => $url]
              ));
              $url = '';
            }
            // If URL is not start with slash then display warning
            // and disable the link.
            if ($url[0] != '/') {
              $this->messenger->addWarning($this->t(
                "EasyBreadcrumb: Custom crumb for @path URL '@url' should start with slash(/).",
                ['@path' => $path, '@url' => $url]
              ));
              $url = '';
            }
          }

          if ($url) {
            $url_obj = Url::fromUserInput($url, ['absolute' => TRUE]);

            // If the URL is not accessible, skip the crumb.
            if (!$url_obj->access()) {
              continue;
            }

            $links[] = new Link($title, $url_obj);
          }
          else {
            $links[] = Link::createFromRoute($title, '<none>');
          }
        }

        // Handle views path expiration cache expiration.
        $parameters = $route_match->getParameters();
        foreach ($parameters as $key => $parameter) {
          if ($key === 'view_id') {
            $breadcrumb->addCacheTags(['config:views.view.' . $parameter]);
          }

          if ($parameter instanceof CacheableDependencyInterface) {
            $breadcrumb->addCacheableDependency($parameter);
          }
        }

        // Expire cache by languages and config changes.
        $breadcrumb->addCacheContexts(['route', 'url.path', 'languages']);

        // Expire cache context for config changes.
        $breadcrumb->addCacheableDependency($this->config);

        return $breadcrumb->setLinks($links);
      }
    }

    // Handle views path expiration cache expiration.
    $parameters = $route_match->getParameters();
    foreach ($parameters as $key => $parameter) {
      if ($key === 'view_id') {
        $breadcrumb->addCacheTags(['config:views.view.' . $parameter]);
      }

      if ($parameter instanceof CacheableDependencyInterface) {
        $breadcrumb->addCacheableDependency($parameter);
      }
    }

    // Expire cache by languages and config changes.
    $breadcrumb->addCacheContexts(['route', 'url.path', 'languages']);
    $breadcrumb->addCacheableDependency($this->config);
    $i = 0;
    $add_langcode = FALSE;

    // Remove the current page if it's not wanted.
    if (!$this->config->get(EasyBreadcrumbConstants::INCLUDE_TITLE_SEGMENT)) {
      array_pop($path_elements);
    }

    if (isset($path_elements[0])) {

      // Remove the first parameter if it matches the current language.
      if (!($this->config->get(EasyBreadcrumbConstants::LANGUAGE_PATH_PREFIX_AS_SEGMENT))) {
        $curr_lang_prefix = $curr_lang;

        if ($prefixes = $this->languageNegotiationConfig->get('url.prefixes')) {
          // Using null-coalescing to check for prefix existence for $curr_lang.
          $curr_lang_prefix = $prefixes[$curr_lang] ?? '';
        }
        if (mb_strtolower($path_elements[0]) == mb_strtolower($curr_lang_prefix)) {

          // Preserve case in language to allow path matching to work properly.
          $curr_lang = $path_elements[0];
          array_shift($path_elements);
          $add_langcode = TRUE;
        }
      }
    }

    while (count($path_elements) > 0) {
      $exclude_match_found = FALSE;
      $check_path = '/' . implode('/', $path_elements);
      if ($add_langcode) {
        $check_path = '/' . $curr_lang . $check_path;
      }

      // Copy the path elements for up-casting.
      $route_request = $this->getRequestForPath($check_path, $exclude);
      if ($this->config->get(EasyBreadcrumbConstants::EXCLUDED_PATHS)) {
        $config_textarea = $this->config->get(EasyBreadcrumbConstants::EXCLUDED_PATHS);
        $exclude_segments = preg_split('/[\r\n]+/', $config_textarea, -1, PREG_SPLIT_NO_EMPTY);

        // Loop through all exclude segments.
        foreach ($exclude_segments as $exclude_segment) {
          // Escape slashes that need escaping.
          $unescaped_slash_pattern = '/(?<!\\\\)\//';
          $slash_replacement = '\/';
          $escaped_exclude_segment = preg_replace($unescaped_slash_pattern,
            $slash_replacement, $exclude_segment);

          $regex_match_found = FALSE;
          $exclude_is_regex = !@preg_match($escaped_exclude_segment, NULL);

          // Check path against exclude segment.
          if ($exclude_is_regex === TRUE) {
            $regex_match_found = preg_match('/' . $escaped_exclude_segment . '/', $check_path, $matches);
          }

          // If the target segment should be excluded, set a flag.
          if ($regex_match_found || $escaped_exclude_segment == $check_path) {
            $exclude_match_found = TRUE;
            break;
          }
        }
      }

      // Stop processing if the segment on top of the stack is excluded.
      if ($exclude_match_found) {
        array_pop($path_elements);
        continue;
      }

      if ($route_request) {
        $route_match = RouteMatch::createFromRequest($route_request);
        $access = $this->accessManager->check($route_match, $this->currentUser, NULL, TRUE);
        $breadcrumb = $breadcrumb->addCacheableDependency($access);
        // The set of breadcrumb links depends on the access result, so merge
        // the access result's cacheability metadata.
        if ($access->isAllowed()) {
          if ($this->config->get(EasyBreadcrumbConstants::TITLE_FROM_PAGE_WHEN_AVAILABLE)) {
            // Get the title if the current route represents an entity.
            $title = FALSE;
            if (($route = $route_match->getRouteObject()) && ($parameters = $route->getOption('parameters'))) {
              foreach ($parameters as $name => $options) {
                if (isset($options['type']) && strpos($options['type'], 'entity:') === 0) {
                  $entity = $route_match->getParameter($name);
                  if ($entity instanceof EntityInterface && $entity->hasLinkTemplate('canonical')) {
                    $title = $this->normalizeText($this->getTitleString($route_request, $route_match, $replacedTitles));
                    // Add this entity's cacheability metadata.
                    $breadcrumb->addCacheableDependency($entity);
                    break;
                  }
                }
              }
            }

            if (!$title) {
              $title = $this->normalizeText($this->getTitleString($route_request, $route_match, $replacedTitles));
            }
          }
          // Set title based on alternative field.
          if ($this->config->get(EasyBreadcrumbConstants::ALTERNATIVE_TITLE_FIELD)) {
            $alternativeTitle = $this->normalizeText($this->getTitleString($route_request, $route_match, $replacedTitles));
            if ($this->config->get(EasyBreadcrumbConstants::TRUNCATOR_MODE)) {
              $alternativeTitle = $this->truncator($alternativeTitle);
            }
            if (!empty($alternativeTitle)) {
              $title = $alternativeTitle;
            }
          }
          if (!isset($title)) {

            if ($this->config->get(EasyBreadcrumbConstants::USE_MENU_TITLE_AS_FALLBACK)) {

              // Try resolve the menu title from the route.
              $route_name = $route_match->getRouteName();
              $route_parameters = $route_match->getRawParameters()->all();
              $menu_links = $this->menuLinkManager->loadLinksByRoute($route_name, $route_parameters);

              if (empty($menu_links)) {
                if ($this->config->get(EasyBreadcrumbConstants::USE_PAGE_TITLE_AS_MENU_TITLE_FALLBACK)) {
                  $title = $this->getTitleString($route_request, $route_match, $replacedTitles);
                }
              }
              else {
                $preferred_menu = $this->config->get(EasyBreadcrumbConstants::MENU_TITLE_PREFERRED_MENU);
                if ($preferred_menu) {
                  $preferred_found = FALSE;
                  foreach ($menu_links as $link) {
                    if ($link->getMenuName() == $preferred_menu) {
                      $menu_link = $link;
                      $preferred_found = TRUE;
                      break;
                    }
                  }
                  if (!$preferred_found) {
                    $menu_link = reset($menu_links);
                  }
                }
                else {
                  $menu_link = reset($menu_links);
                }
                $title = $this->normalizeText($menu_link->getTitle());
              }
            }

            // Fallback to using the raw path component as the title if the
            // route is missing a _title or _title_callback attribute.
            if (!isset($title)) {
              $title = $this->normalizeText(str_replace(['-', '_'], ' ', end($path_elements)));
            }
          }

          // Check if title needs to be replaced.
          if (!empty($title) && array_key_exists($title, $replacedTitles)) {
            $title = $replacedTitles[(string) $title];
          }
          // Check if title needs to be truncated.
          if ($title && $this->config->get(EasyBreadcrumbConstants::TRUNCATOR_MODE)) {
            $title = $this->truncator($title);
          }

          // Add a linked breadcrumb unless it's the current page.
          if ($i == 0
              && $this->config->get(EasyBreadcrumbConstants::INCLUDE_TITLE_SEGMENT)
              && !$this->config->get(EasyBreadcrumbConstants::TITLE_SEGMENT_AS_LINK)) {
            $links[] = Link::createFromRoute($title, '<none>');
          }
          elseif ($route_match->getRouteObject()) {
            $url = Url::fromRouteMatch($route_match);
            if ($this->config->get(EasyBreadcrumbConstants::ABSOLUTE_PATHS)) {
              $url->setOption('absolute', TRUE);
            }
            $links[] = new Link($title, $url);
          }

          // Add all term parents.
          if ($i == 0
              && $this->config->get(EasyBreadcrumbConstants::TERM_HIERARCHY)
              && $term = $route_match->getParameter('taxonomy_term')) {
            $parents = $this->entityTypeManager->getStorage('taxonomy_term')->loadAllParents($term->id());

            // Unset current term.
            array_shift($parents);
            foreach ($parents as $parent) {
              $parent = $this->entityRepository->getTranslationFromContext($parent);
              $links[] = $parent->toLink();
            }
          }
          unset($title);
          $i++;
        }
      }
      elseif ($this->config->get(EasyBreadcrumbConstants::INCLUDE_INVALID_PATHS) && empty($exclude[implode('/', $path_elements)])) {
        $title = $this->normalizeText(str_replace(['-', '_'], ' ', end($path_elements)));
        $this->applyTitleReplacement($title, $replacedTitles);
        $links[] = Link::createFromRoute($title, '<none>');
        unset($title);
      }
      array_pop($path_elements);
    }

    // Add the home link, if desired.
    if ($this->config->get(EasyBreadcrumbConstants::INCLUDE_HOME_SEGMENT)) {

      $home_route_name = '<front>';
      if ($this->pathMatcher->isFrontPage() && !$this->config->get(EasyBreadcrumbConstants::TITLE_SEGMENT_AS_LINK)) {
        $home_route_name = '<none>';
      }

      if (!$this->config->get(EasyBreadcrumbConstants::USE_SITE_TITLE)) {
        $links[] = Link::createFromRoute($this->normalizeText($this->config->get(EasyBreadcrumbConstants::HOME_SEGMENT_TITLE)), $home_route_name);
      }
      else {
        $links[] = Link::createFromRoute($this->siteConfig->get('name'), $home_route_name);
      }
      if ($this->config->get(EasyBreadcrumbConstants::HIDE_SINGLE_HOME_ITEM) && count($links) === 1) {
        return $breadcrumb->setLinks([]);
      }
    }
    $links = array_reverse($links);

    if ($this->config->get(EasyBreadcrumbConstants::REMOVE_REPEATED_SEGMENTS)) {
      $links = $this->removeRepeatedSegments($links);
    }

    if ($this->config->get(EasyBreadcrumbConstants::SEGMENT_DISPLAY_MINIMUM) > count($links)) {
      return $breadcrumb->setLinks([]);
    }

    // Remove leading breadcrumb segments.
    if ($limit_display && isset($segment_limit)) {
      if ($this->config->get(EasyBreadcrumbConstants::INCLUDE_HOME_SEGMENT)) {
        $home_segment = array_shift($links);
        $segment_limit--;
      }
      while (count($links) > $segment_limit) {
        array_shift($links);
      }
      if ($this->config->get(EasyBreadcrumbConstants::INCLUDE_HOME_SEGMENT)) {
        array_unshift($links, $home_segment);
      }
    }

    return $breadcrumb->setLinks($links);
  }

  /**
   * Set request context from passed in $route_match if route is available.
   *
   * @param Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match for the breadcrumb.
   */
  protected function setRouteContextFromRouteMatch(RouteMatchInterface $route_match) {
    try {
      $url = $route_match->getRouteObject() ? Url::fromRouteMatch($route_match) : NULL;
      if ($url) {
        $url_path = $url->toString(TRUE)->getGeneratedUrl();
        // Remove base path if drupal is installed in a subdirectory.
        $url = strpos($url_path, base_path()) === 0 ?
          preg_replace('/^' . str_replace('/', '\/', base_path()) . '/', '/', $url_path) :
          $url_path;
        if ($request = $this->getRequestForPath($url, [])) {
          $route_match_context = new RequestContext();
          $route_match_context->fromRequest($request);
          $this->context = $route_match_context;
        }
      }
    }
    catch (RouteNotFoundException $e) {

      // Ignore the exception.
    }
  }

  /**
   * Apply title replacements.
   *
   * @param string $title
   *   Page title.
   * @param array $replacements
   *   Replacement rules map.
   */
  public function applyTitleReplacement(&$title, array $replacements) {
    if (!is_string($title)) {

      return;
    }

    if (array_key_exists($title, $replacements)) {
      $title = $replacements[$title];
    }
  }

  /**
   * Get string title for route.
   *
   * @param \Symfony\Component\HttpFoundation\Request $route_request
   *   A request object.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   A RouteMatch object.
   * @param array $replacedTitles
   *   A array replaced titles.
   *
   * @return string|null
   *   Either the current title string or NULL if unable to determine it.
   */
  public function getTitleString(Request $route_request, RouteMatchInterface $route_match, array $replacedTitles) {
    try {
      $title = $this->titleResolver->getTitle($route_request, $route_match->getRouteObject());
    }
    catch (\InvalidArgumentException $exception) {
      $title = NULL;
    }
    $this->applyTitleReplacement($title, $replacedTitles);

    // Title resolver only returns title if route defines a _title or
    // _title_callback but some core routes like node.edit or block_content.edit
    // uses $main_content['#title'] to set a title. Add an special case to set a
    // title for {entity_type_id}.{operation} when it's possible.
    if (NULL === $title && $entityForm = $route_match->getRouteObject()->getDefault('_entity_form')) {
      $entityFormParts = explode('.', $entityForm);

      if (2 === count($entityFormParts)) {
        $entity_type_id = $entityFormParts[0];
        $operation      = $entityFormParts[1];

        // Operations that can be used as a title: add, edit or delete.
        if (in_array($operation, ['add', 'edit', 'delete'])) {
          $title = $operation;
        }
        // Operations used to show the entity: default, view or preview.
        elseif (in_array($operation, ['default', 'view', 'preview'])) {
          if ($entity = $route_match->getParameter($entity_type_id)) {
            if (is_object($entity)) {
              if (method_exists($entity, 'getTitle')) {
                $title = $entity->getTitle();
              }
              elseif (method_exists($entity, 'label')) {
                $title = $entity->label();
              }
            }
          }
        }
      }
    }

    // If title is object then try to render it.
    if ($title instanceof MarkupInterface) {
      $title = strip_tags(Html::decodeEntities($title));
    }
    // Other paths, such as admin/structure/menu/manage/main, will
    // return a render array suitable to render using core's XSS filter.
    elseif (is_array($title) && array_key_exists('#markup', $title)) {

      // If this render array has #allowed tags use that instead of default.
      $tags = array_key_exists('#allowed_tags', $title) ? $title['#allowed_tags'] : NULL;
      $title = Html::decodeEntities(Xss::filter($title['#markup'], $tags));
    }

    if (!is_string($title)) {

      return NULL;
    }

    return $title;
  }

  /**
   * Remove duplicate repeated segments.
   *
   * @param \Drupal\Core\Link[] $links
   *   The links.
   *
   * @return \Drupal\Core\Link[]
   *   The new links.
   */
  protected function removeRepeatedSegments(array $links) {
    $newLinks = [];

    /** @var \Drupal\Core\Link $last */
    $last = NULL;

    foreach ($links as $link) {
      if (empty($last) || (!$this->linksAreEqual($last, $link))) {
        $newLinks[] = $link;
      }

      $last = $link;
    }

    return $newLinks;
  }

  /**
   * Compares two breadcrumb links for equality.
   *
   * @param \Drupal\Core\Link $link1
   *   The first link.
   * @param \Drupal\Core\Link $link2
   *   The second link.
   *
   * @return bool
   *   TRUE if equal, FALSE otherwise.
   */
  protected function linksAreEqual(Link $link1, Link $link2) {
    $links_equal = TRUE;

    if ($link1->getText() instanceof TranslatableMarkup) {
      $link_one_text = (string) $link1->getText();
    }
    else {
      $link_one_text = $link1->getText();
    }

    if ($link2->getText() instanceof TranslatableMarkup) {
      $link_two_text = (string) $link2->getText();
    }
    else {
      $link_two_text = $link2->getText();
    }

    if ($link_one_text != $link_two_text) {
      $links_equal = FALSE;
    }

    $validate_urls = $this->config->get(EasyBreadcrumbConstants::REMOVE_REPEATED_SEGMENTS_TEXT_ONLY);
    if (!$validate_urls && ($link1->getUrl()->getInternalPath() != $link2->getUrl()->getInternalPath())) {
      $links_equal = FALSE;
    }

    return $links_equal;
  }

  /**
   * Matches a path in the router.
   *
   * @param string $path
   *   The request path with a leading slash.
   * @param array $exclude
   *   An array of paths or system paths to skip.
   *
   * @return \Symfony\Component\HttpFoundation\Request
   *   A populated request object or NULL if the path couldn't be matched.
   */
  protected function getRequestForPath($path, array $exclude) {
    if (!empty($exclude[$path])) {
      return NULL;
    }

    // Check to see if the path is actually a redirect, if it is, resolve it to
    // its source before we create the request.  Strip the starting slash,
    // redirect module doesn't include it.
    if ($this->moduleHandler->moduleExists('redirect') && $this->config->get(EasyBreadcrumbConstants::FOLLOW_REDIRECTS)) {
      $redirect_path = $path;
      if (!empty($redirect_path) && $redirect_path[0] === '/') {
        $redirect_path = substr($redirect_path, 1);
      }
      $language_prefix = $this->languageManager->getCurrentLanguage()->getId();
      if (strpos($redirect_path, "$language_prefix/") === 0) {
        $redirect_path = substr($redirect_path, strlen("$language_prefix/"));
      }

      // Get the site base path.
      $request = $this->requestStack->getCurrentRequest();
      $base_path = $request->getBasePath();

      // Adjust redirect_path to include base_path if not already included.
      // Prevent double slashes.
      if (!empty($base_path) && $base_path != '/') {
        // Ensure we don't add the base path twice if it's already there.
        if (strpos($redirect_path, $base_path) !== 0) {
          $redirect_path = rtrim($base_path, '/') . '/' . $redirect_path;
        }
      }

      /** @var \Drupal\redirect\Entity\Redirect $redirect */
      // Redirect can throw an exception, so catch it if it happens.
      $redirect = NULL;
      try {
        // Ignore DI recommendation as we want no dependency on redirect module.
        // @phpstan-ignore-next-line
        $redirect = \Drupal::service('redirect.repository')
          ->findMatchingRedirect($redirect_path, [], $this->languageManager->getCurrentLanguage()
            ->getId());
      }
      catch (\Exception $exception) {
        // Do nothing for now.
      }
      if ($redirect) {
        $path = $redirect->getRedirectUrl()->toString();
      }
    }

    // @todo Use the RequestHelper once https://www.drupal.org/node/2090293 is
    // fixed.
    // The path in the request should start with a slash.
    $request = Request::create('/' . ltrim($path, '/'));

    // Performance optimization: set a short accept header to reduce overhead in
    // AcceptHeaderMatcher when matching the request.
    $request->headers->set('Accept', 'text/html');

    // Find the system path by resolving aliases, language prefix, etc.
    $processed = $this->pathProcessor->processInbound($path, $request);
    if ($this->config->get(EasyBreadcrumbConstants::HOME_SEGMENT_VALIDATION_SKIP)) {
      unset($exclude[$this->siteConfig->get('page.front')]);
    }
    if (empty($processed) || !empty($exclude[$processed])) {

      // This resolves to the front page, which we already add.
      return NULL;
    }
    $this->currentPath->setPath($processed, $request);

    // Attempt to match this path to provide a fully built request.
    try {
      $request->attributes->add($this->router->matchRequest($request));
      return $request;
    }
    catch (ParamNotConvertedException $e) {
      return NULL;
    }
    catch (ResourceNotFoundException $e) {
      return NULL;
    }
    catch (MethodNotAllowedException $e) {
      return NULL;
    }
    catch (AccessDeniedHttpException $e) {
      return NULL;
    }
  }

  /**
   * Normalizes a text.
   *
   * E.g., transforms "about-us" to "About Us" or "About us", according to
   * parameters.
   *
   * @param string|null $raw_text
   *   Text to be normalized.
   *
   * @return string
   *   Normalized title.
   */
  private function normalizeText($raw_text) {
    if (empty($raw_text)) {
      return '';
    }

    // Transform '-hello--world_javascript-' to 'hello world javascript'.
    $normalized_text = trim($raw_text);
    $normalized_text = preg_replace('/\s{2,}/', ' ', $normalized_text);

    // Gets the flag saying the capitalizator mode.
    $capitalizator_mode = $this->config->get(EasyBreadcrumbConstants::CAPITALIZATOR_MODE);
    if ($capitalizator_mode === 'ucwords') {

      // Transforms the text 'once a time' to 'Once a Time'.
      // List of words to be ignored by the capitalizator.
      $ignored_words = $this->config->get(EasyBreadcrumbConstants::CAPITALIZATOR_IGNORED_WORDS) ?? [];
      if (!is_array($ignored_words)) {
        $ignored_words = explode(' ', $ignored_words ?? '');
      }
      $words = explode(' ', $normalized_text ?? '');

      // Transforms the non-ignored words of the segment.
      $words[0] = Unicode::ucfirst($words[0]);
      $words_quantity = count($words);
      for ($i = 1; $i < $words_quantity; ++$i) {

        // Transforms this word only if it is not in the list of ignored words.
        if (!in_array($words[$i], $ignored_words, TRUE)) {
          $words[$i] = Unicode::ucfirst($words[$i]);
        }
      }
      $normalized_text = implode(' ', $words);
    }
    elseif ($capitalizator_mode === 'ucall') {

      // Transforms the text 'once a time' to 'ONCE A TIME'.
      $normalized_text = mb_strtoupper($normalized_text);
    }
    elseif ($capitalizator_mode === 'ucforce') {

      // Transforms the text 'once a time' to 'once a TIME'.
      // List of words to be forced by the capitalizator.
      $forced_words = $this->config->get(EasyBreadcrumbConstants::CAPITALIZATOR_FORCED_WORDS);

      // If case sensitivity is false make all the forced words
      // uncapitalized by default.
      if ($forced_words && !$this->config->get(EasyBreadcrumbConstants::CAPITALIZATOR_FORCED_WORDS_CASE_SENSITIVITY)) {
        $forced_words = array_map('strtolower', $forced_words);
      }
      $words = explode(' ', $normalized_text ?? '');

      // Transforms the non-ignored words of the segment.
      if ($this->config->get(EasyBreadcrumbConstants::CAPITALIZATOR_FORCED_WORDS_FIRST_LETTER)) {
        $words[0] = Unicode::ucfirst($words[0]);
      }
      $words_quantity = count($words);
      for ($i = 0; $i < $words_quantity; ++$i) {

        // If case sensitivity is false make the compared word uncapitalized in
        // order to allow the comparison well.
        if (!$this->config->get(EasyBreadcrumbConstants::CAPITALIZATOR_FORCED_WORDS_CASE_SENSITIVITY)) {
          $selected_word = mb_strtolower($words[$i]);
        }
        else {
          $selected_word = $words[$i];
        }

        // Transforms this word only if it is in the list of forced words.
        if (is_array($forced_words) && in_array($selected_word, $forced_words)) {
          $words[$i] = mb_strtoupper($selected_word);
        }
      }
      $normalized_text = implode(' ', $words);
    }
    elseif ($capitalizator_mode === 'ucfirst') {

      // Transforms the text 'once a time' to 'Once a time' (ucfirst).
      $normalized_text = Unicode::ucfirst($normalized_text);
    }

    return $normalized_text;
  }

  /**
   * Truncate the title.
   *
   * @param string $title
   *   Text/title to be truncated.
   *
   * @return array|\Drupal\Core\StringTranslation\TranslatableMarkup|false|mixed|string|null
   *   Return truncated title.
   */
  public function truncator(string $title) {
    $title = mb_strimwidth(
      $title,
      0,
      $this->config->get(EasyBreadcrumbConstants::TRUNCATOR_LENGTH),
      $this->config->get(EasyBreadcrumbConstants::TRUNCATOR_DOTS) ? '...' : '',
      'utf8'
    );
    return $title;
  }

}
