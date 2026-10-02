/**
 * @file
 * Loads an embedded form into the page, using its vendor's handler.
 *
 * This runs on any page that shows the Form embed component. Its template
 * renders an empty div whose data attributes hold the vendor, such as slate,
 * and the embed address. This finds that vendor's handler, checks the
 * address, adds the vendor's script, and shows a spinner. The vendor's script
 * then fetches the form and writes it into the div.
 *
 * Each vendor's submodule adds its handler to this component's library, and
 * the handler adds itself to Drupal.azFormEmbed.vendors. A handler is an
 * object with these members:
 * - label: The vendor's name, such as Slate, for the alert's messages.
 * - onePerPage: Whether the vendor allows only one of its forms per page.
 * - isAllowedUrl(url): Whether a URL object is an address the vendor's
 *   scripts come from.
 * - scriptUrl(embedUrl, containerId, settings): The URL object to load the
 *   vendor's script from, for one attempt that writes into containerId.
 * - hasRendered(container): Whether the vendor's form has arrived.
 * - decorate(container): Adds Arizona Bootstrap classes to the vendor's form.
 *   It must be safe to run many times.
 *
 * If the form never arrives, this shows an alert instead, with a retry
 * button when the form was only slow.
 */

((Drupal, once) => {
  /**
   * How long to wait for the form before showing the fallback alert.
   *
   * The script's error event only fires when the script fails to download.
   * A vendor's form can also stall with nothing erroring, for example Slate's
   * on a conditional logic error, so we also watch the clock.
   *
   * @see https://knowledge.technolutions.net/docs/troubleshooting-forms
   */
  const INIT_TIMEOUT_MS = 15000;

  /**
   * The shape a vendor id must have: a Drupal machine name.
   *
   * Rationale: the id ends up in an attribute name and a CSS selector, and
   * this keeps anything odd out of both.
   */
  const VENDOR_ID_PATTERN = /^[a-z0-9_]+$/;

  // Where vendor handlers add themselves. A handler's library loads before
  // this one, so keep what's already here.
  Drupal.azFormEmbed = Drupal.azFormEmbed || {};
  Drupal.azFormEmbed.vendors = Drupal.azFormEmbed.vendors || {};

  /**
   * What to say when the page already holds a form from a one-per-page vendor.
   *
   * It names the other form wherever it can. That's the part worth having:
   * whoever fixes this gets both names and has nothing to hunt for. Every
   * embed renders its own name, so the one that loaded still carries its name
   * in the markup even though its alert is hidden.
   *
   * One media item placed twice needs different words. Naming the other form
   * would print the same name twice in a row, and the fix there is to delete
   * a copy rather than to choose between two forms.
   *
   * @param {HTMLElement} wrapper The .az-form-embed element being refused.
   * @param {string} vendor The vendor's id.
   * @param {object} handler The vendor's handler.
   * @return {object} reason, the clause to show, and other, the name to put
   *   where its @other placeholder sits. other is undefined when there's no
   *   name to give.
   */
  function alreadyEmbeddedMessage(wrapper, vendor, handler) {
    // The name sits in the em.placeholder Drupal's placeholder filter writes.
    // There's no class of our own to aim at, because that element is Drupal's.
    const nameInside = '.az-form-embed__message .placeholder';
    const loaded = document.querySelector(
      `.az-form-embed--${CSS.escape(vendor)}.az-form-embed--js:not(.az-form-embed--failed) ${nameInside}`,
    );
    const other = loaded ? loaded.textContent.trim() : '';
    const mine = wrapper.querySelector(nameInside);
    const args = { '@vendor': handler.label };
    if (other !== '' && mine && other === mine.textContent.trim()) {
      return {
        reason: Drupal.t(
          'because this page already embeds this same form. @vendor allows only one embedded form per page. Remove the duplicate.',
          args,
        ),
      };
    }
    if (other === '') {
      return {
        reason: Drupal.t(
          'because this page already embeds another @vendor form. @vendor allows only one embedded form per page. Remove one of them, or move it to its own page.',
          args,
        ),
      };
    }
    // Leave @other in the string for showFallback() to replace with an
    // element. Rationale: Drupal.t() would escape the name for HTML, and this
    // clause is written into the page as text nodes, so an ampersand in a
    // form's name would arrive as "&amp;".
    return {
      reason: Drupal.t(
        'because this page already embeds @other. @vendor allows only one embedded form per page. Remove one of them, or move it to its own page.',
        args,
      ),
      other,
    };
  }

  /**
   * An id for the container that no element on the page is already using.
   *
   * A vendor's script finds the element by its id, so the name has to be
   * unique. It's chosen here rather than in PHP because only the browser can
   * see the whole page: Drupal renders each component on its own and can't
   * tell that another form sits further down.
   *
   * @param {string[]} [skip] Ids to pass over even though they're free.
   * @return {string} An id no element on the page is using.
   */
  function uniqueContainerId(skip = []) {
    const base = 'az-form-embed';
    let id = base;
    let n = 2;
    while (document.getElementById(id) || skip.includes(id)) {
      id = `${base}-${n}`;
      n += 1;
    }
    return id;
  }

  /**
   * Removes the spinner, shows the fallback alert, and says why.
   *
   * The template writes the first half of the sentence, up to "was not
   * embedded", because that half holds the form's name. So every reason
   * passed here is the clause that finishes it, starting with "because".
   *
   * @param {HTMLElement} wrapper The .az-form-embed element.
   * @param {string} reason The clause finishing the alert's sentence.
   * @param {object} [options] Extra parts of the alert to fill in.
   * @param {string} [options.named] A form's name to stand in for @other in
   *   the reason. It goes in as an element of its own, so the stylesheet can
   *   find it.
   * @param {boolean} [options.retry] Whether to show the retry button.
   */
  function showFallback(wrapper, reason, { named, retry = false } = {}) {
    // Show the alert before writing into it. Rationale: it's display: none
    // until this class lands, and a live region (an element screen readers
    // watch for new text) that's hidden when its text arrives isn't reliably
    // announced.
    wrapper.classList.add('az-form-embed--failed');
    const spinner = wrapper.querySelector('.az-form-embed__spinner');
    if (spinner) {
      spinner.remove();
    }
    // Set the retry button every time, hiding it as well as showing it. For
    // example, a retry can end in a download error instead of a timeout, and
    // that alert shouldn't still offer the retry.
    const button = wrapper.querySelector('.az-form-embed__retry');
    if (button) {
      button.hidden = !retry;
    }
    const target = wrapper.querySelector('.az-form-embed__reason');
    if (!target) {
      return;
    }
    if (named === undefined) {
      target.textContent = reason;
      return;
    }
    // Build the clause from nodes rather than substituting into the string.
    // Rationale: the name has to sit in an element of its own to be styled,
    // and setting each node's textContent keeps a name that looks like markup
    // as plain text.
    const [before, after] = reason.split('@other');
    const name = document.createElement('em');
    name.className = 'placeholder';
    name.textContent = named;
    target.textContent = before;
    target.appendChild(name);
    target.appendChild(document.createTextNode(after || ''));
  }

  /**
   * Builds the spinner shown until the form arrives.
   *
   * @return {HTMLElement} The spinner, ready to insert.
   */
  function buildSpinner() {
    const spinnerWrapper = document.createElement('div');
    spinnerWrapper.className = 'az-form-embed__spinner';
    const spinner = document.createElement('div');
    spinner.className = 'spinner-border text-primary';
    spinner.setAttribute('role', 'status');
    const label = document.createElement('span');
    label.className = 'visually-hidden';
    label.textContent = Drupal.t('Loading form…');
    spinner.appendChild(label);
    spinnerWrapper.appendChild(spinner);
    return spinnerWrapper;
  }

  /**
   * Adds the vendor's script for one container and watches how it goes.
   *
   * @param {HTMLElement} container The element the vendor fills.
   * @param {object} handler The vendor's handler.
   * @param {object} settings Drupal's settings for this page.
   */
  function loadEmbed(container, handler, settings) {
    const wrapper = container.closest('.az-form-embed');
    const src = container.getAttribute('data-az-form-embed-src');
    if (!src) {
      return;
    }

    // If the vendor doesn't recognize the address, show the fallback and
    // stop. Rationale: the vendor already checked it in PHP, but the code
    // below is what turns a string into a script tag, so check again right
    // here. That still holds if this markup ever comes from somewhere other
    // than the vendor's check.
    let embedUrl;
    try {
      embedUrl = new URL(src, window.location.href);
    } catch (e) {
      embedUrl = null;
    }
    if (embedUrl === null || !handler.isAllowedUrl(embedUrl)) {
      showFallback(wrapper, Drupal.t('because its web address is not valid.'));
      return;
    }

    // Hide the alert while the form loads. It's visible in the markup so a
    // browser with no JavaScript still gets the link inside it.
    wrapper.classList.add('az-form-embed--js');

    // What every attempt shares: the timeout now running, how many attempts
    // there have been, and each container id used so far. A handler compares
    // its own attempt number to the count to tell whether it's out of date.
    let timer = null;
    let attempt = 0;
    const usedIds = [];

    // The vendor writes the form in a little after its script runs, so watch
    // for it instead of styling once. Keep watching afterward in case the
    // vendor adds fields later. This only listens for added and removed
    // elements, and adding a class is neither, so styling can't set it off
    // again.
    const observer = new MutationObserver(() => {
      // Once the form has appeared, cancel the timeout. Rationale: when a
      // visitor submits, the vendor can replace the form with a confirmation,
      // so a timeout still waiting would find no form and wrongly show the
      // fallback.
      if (handler.hasRendered(container)) {
        window.clearTimeout(timer);
        // Hide the alert too, in case the form arrived after the timeout
        // showed it. For example, Slate's test server can answer slowly while
        // it wakes up, so its form can arrive after the alert.
        wrapper.classList.remove('az-form-embed--failed');
      }
      handler.decorate(container);
    });
    observer.observe(container, { childList: true, subtree: true });

    /**
     * Adds the vendor's script, pointed at a new container id each time.
     *
     * The new id keeps a late script from an earlier attempt out of the
     * container. For example, the first attempt times out, someone clicks
     * retry, and then the first script finally arrives. Removing its script
     * tag wouldn't stop it running. But Slate's script starts by looking up
     * the id it was given, and does nothing if that id isn't on the page.
     *
     * Careful: this relies on that check in the vendor's script. Without it,
     * a late script writes a second copy of the form into the container, and
     * nothing errors.
     */
    const start = () => {
      attempt += 1;
      const thisAttempt = attempt;

      // Skip every id an earlier attempt used. For example, on a second retry
      // az-form-embed is free again, and the first attempt's script, if it
      // finally arrives, is looking for it.
      container.id = uniqueContainerId(usedIds);
      usedIds.push(container.id);

      window.clearTimeout(timer);
      timer = window.setTimeout(() => {
        if (thisAttempt === attempt && !handler.hasRendered(container)) {
          showFallback(
            wrapper,
            Drupal.t('because it took too long to respond.'),
            { retry: true },
          );
        }
      }, INIT_TIMEOUT_MS);

      const script = document.createElement('script');
      script.async = true;
      // Use the handler's URL object, not the raw attribute, so the script
      // tag gets the address checked above plus only what the handler added.
      script.src = handler.scriptUrl(embedUrl, container.id, settings).href;
      script.addEventListener('error', () => {
        if (thisAttempt !== attempt) {
          return;
        }
        window.clearTimeout(timer);
        showFallback(
          wrapper,
          Drupal.t('because there was a problem reaching it.'),
        );
      });
      document.head.appendChild(script);
    };

    // Put the spinner inside the container. When the form arrives, the
    // vendor replaces everything in the container, which removes the spinner
    // at exactly that moment. If the form never arrives, showFallback()
    // removes it.
    container.appendChild(buildSpinner());

    // Try again the way the page first loaded: alert hidden, spinner showing.
    // Move focus to the container. Rationale: the clicked button is inside
    // the alert, and hiding the alert would drop focus to the top of the
    // page. tabindex="-1" lets the container take focus from a script
    // without adding it to the Tab order.
    const retry = wrapper.querySelector('.az-form-embed__retry');
    if (retry) {
      retry.addEventListener('click', () => {
        wrapper.classList.remove('az-form-embed--failed');
        container.replaceChildren(buildSpinner());
        container.setAttribute('tabindex', '-1');
        container.focus();
        start();
      });
    }

    start();
  }

  Drupal.behaviors.azFormEmbed = {
    attach(context, settings) {
      const containers = once(
        'az-form-embed',
        '[data-az-form-embed-src]',
        context,
      );

      containers.forEach((container) => {
        const wrapper = container.closest('.az-form-embed');
        const vendor = container.getAttribute('data-az-form-embed-vendor');
        const handler =
          vendor && VENDOR_ID_PATTERN.test(vendor)
            ? Drupal.azFormEmbed.vendors[vendor]
            : undefined;
        if (!handler) {
          showFallback(
            wrapper,
            Drupal.t('because there was a problem reaching it.'),
          );
          return;
        }

        // If a form from a one-per-page vendor is already on this page, show
        // this one's alert instead. Look in the page rather than keeping a
        // flag. Rationale: Canvas's editor redraws a component each time its
        // settings change, which removes the old form, and a flag would
        // outlive it. The redrawn copy would then wrongly show the alert.
        // The loader adds --js only once a script is on its way, so a
        // container it refused doesn't count, and one added later by AJAX or
        // Layout Builder still finds the first.
        if (
          handler.onePerPage &&
          document.querySelector(
            `.az-form-embed--${CSS.escape(vendor)}.az-form-embed--js`,
          )
        ) {
          // Name the form for this one. Rationale: it's a mistake in how the
          // page was built, so the person who can act on it needs to know
          // which form and why.
          wrapper.classList.add('az-form-embed--names-form');
          const { reason, other } = alreadyEmbeddedMessage(
            wrapper,
            vendor,
            handler,
          );
          showFallback(wrapper, reason, { named: other });
          return;
        }
        loadEmbed(container, handler, settings);
      });
    },
  };
})(Drupal, once);
