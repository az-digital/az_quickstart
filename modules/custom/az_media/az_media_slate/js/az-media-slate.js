/**
 * @file
 * Loads the Slate form embed script.
 *
 * Slate serves an embed as a script that fetches the form and injects it into
 * a container we name. This behavior appends that script, keeps a page to the
 * one form Slate supports, and makes sure a form that never appears leaves a
 * usable link behind instead of an empty box.
 *
 * @see https://knowledge.technolutions.net/docs/embedding-forms
 */

((Drupal, once) => {
  /**
   * How long to wait for Slate to render before showing the fallback.
   *
   * A script.onerror handler only catches a script that failed to download.
   * It does not catch the failure Slate documents, where the script loads and
   * runs but the form never appears because something on the host page got in
   * its way - the container just sits there. So we also watch the clock.
   *
   * @see https://knowledge.technolutions.net/docs/troubleshooting-forms
   */
  const INIT_TIMEOUT_MS = 15000;

  /**
   * The only domain we will load an embed script from.
   *
   * This repeats SlateUrl::HOST_SUFFIX on purpose. See loadEmbed() for why the
   * browser checks the address again rather than trusting the markup.
   */
  const SLATE_HOST_SUFFIX = '.technolutions.net';

  /**
   * Whether Slate has put a form into the container yet.
   *
   * @param {HTMLElement} container The element Slate was told to fill.
   * @return {boolean} True once a form element is present.
   */
  function hasRendered(container) {
    return container.querySelector('form') !== null;
  }

  /**
   * Brings the fallback link back and says why the form is not there.
   *
   * @param {HTMLElement} wrapper The .az-media-slate element.
   * @param {string} message Text for the status region.
   */
  function showFallback(wrapper, message) {
    wrapper.classList.add('az-media-slate--failed');
    const status = wrapper.querySelector('.az-media-slate__status');
    if (status) {
      status.textContent = message;
    }
  }

  /**
   * Appends the Slate script for one container and watches how it goes.
   *
   * @param {HTMLElement} container The element Slate was told to fill.
   * @return {boolean} True if a script was appended, false if the address was
   *   missing or refused. The caller uses this to decide whether the page has
   *   spent its one embed.
   */
  function loadEmbed(container) {
    const wrapper = container.closest('.az-media-slate');
    const src = container.getAttribute('data-az-slate-embed-src');
    if (!src) {
      return false;
    }

    // Check the address again here, in the browser. SlateUrl has already
    // refused anything that is not a Slate form URL, but that check is in PHP
    // and this is the line that turns a string into a script tag. A guard at
    // the point of use still holds if this markup is ever produced by
    // something other than our own formatter. The suffix matches
    // SlateUrl::HOST_SUFFIX; the two have to stay in step.
    let embedUrl;
    try {
      embedUrl = new URL(src, window.location.href);
    } catch (e) {
      embedUrl = null;
    }
    if (
      embedUrl === null ||
      embedUrl.protocol !== 'https:' ||
      !embedUrl.hostname.endsWith(SLATE_HOST_SUFFIX)
    ) {
      showFallback(
        wrapper,
        Drupal.t('The form could not be loaded. Use the link to open it.'),
      );
      return false;
    }

    // Hide the fallback link now that we are driving the embed. It ships
    // visible so a browser with no JavaScript still gets a way to reach the
    // form.
    wrapper.classList.add('az-media-slate--js');

    const timer = window.setTimeout(() => {
      if (!hasRendered(container)) {
        showFallback(
          wrapper,
          Drupal.t('The form did not load. Use the link to open it directly.'),
        );
      }
    }, INIT_TIMEOUT_MS);

    const script = document.createElement('script');
    script.async = true;
    // Assign the parsed URL rather than the raw attribute, so the value that
    // reaches the script tag is the one the check above accepted.
    script.src = embedUrl.href;
    script.addEventListener('error', () => {
      window.clearTimeout(timer);
      showFallback(
        wrapper,
        Drupal.t(
          'The form could not be reached. Use the link to open it directly.',
        ),
      );
    });
    document.head.appendChild(script);
    return true;
  }

  Drupal.behaviors.azMediaSlate = {
    attach(context) {
      const containers = once(
        'az-media-slate',
        '[data-az-slate-embed-src]',
        context,
      );

      containers.forEach((container) => {
        // Slate supports one embedded form per page. Two live embeds break
        // each other, so only the first container on the page gets a script;
        // any other keeps its fallback link. The flag lives on <html> rather
        // than in this closure so that a container arriving later through
        // AJAX or Layout Builder is measured against the same page.
        if (document.documentElement.hasAttribute('data-az-slate-loaded')) {
          const wrapper = container.closest('.az-media-slate');
          showFallback(
            wrapper,
            Drupal.t(
              'Only one Slate form can be shown per page. Use the link to open this one.',
            ),
          );
          return;
        }
        // Mark the page as spent only once a script is actually on its way.
        // A container we refused has not used up the one embed Slate allows,
        // so a later valid one on the same page still gets its turn.
        if (loadEmbed(container)) {
          document.documentElement.setAttribute('data-az-slate-loaded', 'true');
        }
      });
    },
  };
})(Drupal, once);
