/**
 * @file
 * JS to redirect user to CAS for gateway auth check.
 */
((cookies, drupalSettings) => {
  // We rely on cookies for checking if we've already performed the gateway redirect
  // and for logging into Drupal in general. Do nothing if Cookies are disabled on
  // the user agent.
  if (!navigator.cookieEnabled) {
    return;
  }

  if (drupalSettings.cas && drupalSettings.cas.gatewayRedirectUrl) {
    // Don't perform gateway check if request is from a crawler.
    if (drupalSettings.cas.knownCrawlers) {
      const uaRegex = new RegExp(drupalSettings.cas.knownCrawlers, 'i');
      if (uaRegex.test(navigator.userAgent)) {
        return;
      }
    }

    // Don't perform gateway check if there's a cookie indicating we already have.
    // This cookie will automatically expire after the recheck time has passed. Until
    // then, we do nothing.
    if (cookies.get('cas_gateway_checked_cs')) {
      return;
    }

    // OK to redirect to CAS for gateway check. Set cookie indicating we've done this
    // so we don't redirect again until the recheck time passed.
    // The recheck time is a value in minutes.
    // Note that we don't offer a "once per browsing session" option because that would
    // indicate we use a session cookie, which is supposed to go away when user closes
    // their browser. But most browsers don't respect that and keep session cookies around
    // to support their "pick up where I left off" features.
    const expiresDate = new Date(
      new Date().getTime() + drupalSettings.cas.recheckTime * 60 * 1000,
    );
    cookies.set('cas_gateway_checked_cs', 1, { expires: expiresDate });
    window.location.replace(drupalSettings.cas.gatewayRedirectUrl);
  }
})(window.Cookies, drupalSettings);
