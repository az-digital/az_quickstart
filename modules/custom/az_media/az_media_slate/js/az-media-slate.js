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
   */
  function loadEmbed(container) {
    const wrapper = container.closest('.az-media-slate');
    const src = container.getAttribute('data-az-slate-embed-src');
    if (!src) {
      return;
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
    script.src = src;
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
        document.documentElement.setAttribute('data-az-slate-loaded', 'true');
        loadEmbed(container);
      });
    },
  };
})(Drupal, once);
