/**
 * @file
 * Loads a Slate form into the page and styles it with Arizona Bootstrap.
 *
 * This runs on any page that shows a Slate Form media item. The formatter
 * renders an empty div whose data-az-slate-embed-src holds the embed URL,
 * such as uaz.test.technolutions.net/register/?id=<guid>&output=embed.
 * This script checks that URL, adds it as a script tag, and shows a spinner.
 * Slate's script then fetches the form over a few more requests and writes it
 * into the div.
 *
 * Some of the page's own query parameters travel with that address, which is
 * how someone arriving from a personal link gets fields filled in. Slate
 * calls that a dynamic embed, and forwardedParams() decides which may go.
 *
 * Once the form is in, this adds Arizona Bootstrap classes to its fields. If
 * the form never arrives, it shows an alert instead. Slate allows one
 * form per page, so only the first Slate embed on a page loads.
 *
 * @see https://knowledge.technolutions.net/docs/embedding-forms
 */

((Drupal, once) => {
  /**
   * How long to wait for Slate's form before showing the fallback alert.
   *
   * The script's error event only fires when the script fails to download.
   * Slate's troubleshooting page also describes a form stuck on "Loading...",
   * for example from a conditional logic error, or custom JavaScript or CSS on
   * the form. Nothing errors when that happens, so we also watch the clock.
   *
   * @see https://knowledge.technolutions.net/docs/troubleshooting-forms
   */
  const INIT_TIMEOUT_MS = 15000;

  /**
   * The domains we'll load an embed script from.
   *
   * Slate serves its own sites from technolutions.net, and UA's production
   * forms come from arizona.edu vanity domains. Keep this in step with
   * SlateUrl::HOST_SUFFIXES in PHP. See loadEmbed() for why the browser
   * checks the URL again.
   */
  const SLATE_HOST_SUFFIXES = ['.technolutions.net', '.arizona.edu'];

  /**
   * Input types that get form-control, and the Drupal class to add with it.
   *
   * The second class is what a native Drupal field of that type carries, for
   * example form-email on an email input. az_barrio sizes form fields by those
   * classes (see its css/style.css), so matching them makes Slate's fields
   * size like any other form on the site.
   *
   * Text and password inputs get no second class. Don't add form-text to them:
   * the base theme, bootstrap_barrio, strips it, because in Bootstrap
   * form-text is the small grey help-text style.
   *
   * Types not listed here, apart from checkbox, radio, range, and buttons, keep
   * Slate's styling. That includes hidden inputs, which only carry values.
   */
  const FORM_CONTROL_TYPES = {
    date: 'form-date',
    'datetime-local': null,
    email: 'form-email',
    file: 'form-file',
    month: null,
    number: 'form-number',
    password: null,
    search: 'form-search',
    tel: 'form-tel',
    text: null,
    time: 'form-time',
    url: 'form-url',
    week: null,
  };

  /**
   * Adds classes to an element, skipping any it already has.
   *
   * @param {Element|null} element The element to add classes to.
   * @param {string[]} classes The classes to add.
   */
  function addClasses(element, classes) {
    if (!element) {
      return;
    }
    classes.forEach((name) => {
      if (!element.classList.contains(name)) {
        element.classList.add(name);
      }
    });
  }

  /**
   * Gives a checkbox or radio Bootstrap's form-check layout.
   *
   * Slate already renders each option the way form-check expects: a wrapper
   * holding the input, then its label. So this only adds classes.
   *
   * Likert questions (a grid of options per row) are skipped. Rationale: Slate
   * lays their options out as a table, and form-check's block display and left
   * padding would likely pull that apart. The test form has no likert
   * question, so this is untested.
   *
   * @param {HTMLInputElement} input The checkbox or radio.
   */
  function styleCheck(input) {
    if (input.closest('.form_question[data-type="likert"]')) {
      return;
    }
    addClasses(input, ['form-check-input']);
    const response = input.parentElement;
    if (!response || !response.classList.contains('form_response')) {
      return;
    }
    addClasses(response, ['form-check']);
    if (input.id) {
      addClasses(
        response.querySelector(`label[for="${CSS.escape(input.id)}"]`),
        ['form-check-label'],
      );
    }
  }

  /**
   * Adds Arizona Bootstrap classes to the form Slate rendered.
   *
   * Careful: only ever add classes here. Don't move, wrap, or remove Slate's
   * elements, or give them inline styles. Rationale: Slate's conditional logic
   * shows and hides questions by toggling a "hidden" class on elements it
   * already rendered, and its event handlers are attached to those elements.
   * Rebuilding them risks breaking both, and nothing would error. Adding a
   * class leaves both alone.
   *
   * Don't block Slate's stylesheets either. In testing, the fields looked the
   * same without them, because they look plain from lacking Bootstrap's
   * classes. And blocking them showed labels Slate hides, such as each
   * fieldset's legend.
   *
   * Safe to run more than once on the same form.
   *
   * @param {HTMLElement} container The element Slate filled.
   */
  function applyBootstrapClasses(container) {
    container
      .querySelectorAll('input, select, textarea, button')
      .forEach((control) => {
        const { tagName, type } = control;
        if (tagName === 'SELECT') {
          addClasses(control, ['form-select']);
        } else if (tagName === 'TEXTAREA') {
          addClasses(control, ['form-control']);
        } else if (
          tagName === 'BUTTON' ||
          type === 'submit' ||
          type === 'button'
        ) {
          // Slate marks its main action with a "default" class. Give it
          // btn-primary, like Quickstart's own submit buttons, such as Log in.
          const primary = control.matches('.default, .form_button_submit');
          addClasses(control, [
            'btn',
            primary ? 'btn-primary' : 'btn-outline-secondary',
          ]);
        } else if (type === 'checkbox' || type === 'radio') {
          styleCheck(control);
        } else if (type === 'range') {
          addClasses(control, ['form-range']);
        } else if (Object.hasOwn(FORM_CONTROL_TYPES, type)) {
          const drupalClass = FORM_CONTROL_TYPES[type];
          addClasses(
            control,
            drupalClass ? ['form-control', drupalClass] : ['form-control'],
          );
        }
      });

    container.querySelectorAll('.form_question').forEach((question) => {
      // Only label questions someone fills in. Slate's section headers and
      // paragraphs also use .form_label, and shouldn't get form-label's bold.
      if (
        question.querySelector(
          'input:not([type="hidden"]), select, textarea, .form_signature_editable',
        )
      ) {
        // Label every field in the question, not just the first. Slate's
        // address question holds six labels: one for the question, then one
        // each for country, street, city, state, and postal code.
        question.querySelectorAll('.form_label').forEach((label) => {
          // Skip a label that belongs to a question nested inside this one.
          // Rationale: this question has a field, but a nested one might not,
          // such as a section header, which shouldn't get form-label's bold.
          if (label.closest('.form_question') === question) {
            addClasses(label, ['form-label']);
          }
        });
      }
    });

    container.querySelectorAll('.form_responses').forEach((responses) => {
      // Bootstrap's form-control and form-select are display: block, so fields
      // Slate puts side by side, like a birthdate's month, day, and year, would
      // stack. Mark those rows so the stylesheet keeps them on one line.
      const controls = responses.querySelectorAll(
        ':scope > .form-control, :scope > .form-select',
      );
      if (controls.length > 1) {
        addClasses(responses, ['az-media-slate__inline-controls']);
      }
    });
  }

  /**
   * What to say when the page already holds a Slate form.
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
   * @param {HTMLElement} wrapper The .az-media-slate element being refused.
   * @return {object} reason, the clause to show, and other, the name to put
   *   where its @other placeholder sits. other is undefined when there's no
   *   name to give.
   */
  function alreadyEmbeddedMessage(wrapper) {
    // The name sits in the em.placeholder Drupal's placeholder filter writes.
    // There's no class of our own to aim at, because that element is Drupal's.
    const nameInside = '.az-media-slate__message .placeholder';
    const loaded = document.querySelector(
      `.az-media-slate--js:not(.az-media-slate--failed) ${nameInside}`,
    );
    const other = loaded ? loaded.textContent.trim() : '';
    const mine = wrapper.querySelector(nameInside);
    if (other !== '' && mine && other === mine.textContent.trim()) {
      return {
        reason: Drupal.t(
          'because this page already embeds this same form. Slate allows only one embedded form per page. Remove the duplicate.',
        ),
      };
    }
    if (other === '') {
      return {
        reason: Drupal.t(
          'because this page already embeds another Slate form. Slate allows only one embedded form per page. Remove one of them, or move it to its own page.',
        ),
      };
    }
    // Leave @other in the string for showFallback() to replace with an
    // element. Rationale: Drupal.t() would escape the name for HTML, and this
    // clause is written into the page as text nodes, so an ampersand in a
    // form's name would arrive as "&amp;".
    return {
      reason: Drupal.t(
        'because this page already embeds @other. Slate allows only one embedded form per page. Remove one of them, or move it to its own page.',
      ),
      other,
    };
  }

  /**
   * An id for the container that no element on the page is already using.
   *
   * Slate finds the element with document.getElementById(), so the name has
   * to be unique. It's chosen here rather than in PHP because only the
   * browser can see the whole page: the formatter renders each media item on
   * its own and can't tell that the same one sits further down.
   *
   * Only one Slate form loads per page, so the first name is normally free.
   * The loop is for a page that already holds something called
   * az-media-slate.
   *
   * @return {string} An id no element on the page is using.
   */
  function uniqueContainerId() {
    const base = 'az-media-slate';
    let id = base;
    let n = 2;
    while (document.getElementById(id)) {
      id = `${base}-${n}`;
      n += 1;
    }
    return id;
  }

  /**
   * Picks which of the page's own query parameters may travel to Slate.
   *
   * Forwarding them is what makes an embed dynamic: a visitor who arrives
   * from a link carrying their details, such as one Slate emailed them, finds
   * those fields already filled in.
   *
   * Careful: don't forward the query string as it stands, the way Slate's own
   * dynamic snippet does. The embed URL sets output, div and id for itself,
   * and Slate honors the last copy of a parameter it is given, so an
   * appended one wins. For example, anyone can hand out a link ending ?div=x.
   * Slate then writes the form into an element that doesn't exist, so the
   * form never appears and nothing errors.
   *
   * The rules come from SlateUrl::getForwardingRules() in PHP, so there's one
   * source of truth for what a Slate parameter may look like.
   *
   * @param {URL} embedUrl The embed URL built from the link the editor saved.
   * @param {object} settings Drupal's settings for this page.
   * @return {string} Parameters to append, or an empty string.
   */
  function forwardedParams(embedUrl, settings) {
    const rules = settings && settings.azMediaSlate;
    if (!rules || window.location.search === '') {
      return '';
    }
    const keyPattern = new RegExp(rules.keyPattern);
    const allowed = new URLSearchParams();
    new URLSearchParams(window.location.search).forEach((value, key) => {
      if (
        keyPattern.test(key) &&
        !rules.blockedKeys.includes(key) &&
        key.length <= rules.maxKeyLength &&
        value.length <= rules.maxValueLength &&
        // What the editor saved beats what the visitor asked for.
        !embedUrl.searchParams.has(key)
      ) {
        allowed.append(key, value);
      }
    });
    return allowed.toString();
  }

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
   * Removes the spinner, shows the fallback alert, and says why.
   *
   * The template writes the first half of the sentence, up to "was not
   * embedded", because that half holds the form's name. So every reason
   * passed here is the clause that finishes it, starting with "because".
   *
   * @param {HTMLElement} wrapper The .az-media-slate element.
   * @param {string} reason The clause finishing the alert's sentence.
   * @param {string} [named] A form's name to stand in for @other in the
   *   reason. It goes in as an element of its own, so the stylesheet can
   *   find it.
   */
  function showFallback(wrapper, reason, named) {
    // Show the alert before writing into it. Rationale: it's display: none
    // until this class lands, and a live region (an element screen readers
    // watch for new text) that's hidden when its text arrives isn't reliably
    // announced.
    wrapper.classList.add('az-media-slate--failed');
    const spinner = wrapper.querySelector('.az-media-slate__spinner');
    if (spinner) {
      spinner.remove();
    }
    const target = wrapper.querySelector('.az-media-slate__reason');
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
   * Builds the spinner shown until Slate's form arrives.
   *
   * It's Arizona Bootstrap's spinner, the same one az_media_trellis shows for a
   * Trellis form. Slate's form arrives over several requests to Slate's
   * servers, one after another, so it can take a second or more.
   *
   * @return {HTMLElement} The spinner, ready to insert.
   */
  function buildSpinner() {
    const spinnerWrapper = document.createElement('div');
    spinnerWrapper.className = 'az-media-slate__spinner';
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
   * Adds Slate's script for one container and watches how it goes.
   *
   * @param {HTMLElement} container The element Slate was told to fill.
   * @param {object} settings Drupal's settings for this page.
   * @return {boolean} True if a script was appended, false if the address was
   *   missing or refused. The caller uses this to decide whether the page has
   *   spent its one embed.
   */
  function loadEmbed(container, settings) {
    const wrapper = container.closest('.az-media-slate');
    const src = container.getAttribute('data-az-slate-embed-src');
    if (!src) {
      return false;
    }

    // If the URL isn't https on a technolutions.net host, show the fallback
    // and stop. Rationale: SlateUrl already checked it in PHP, but the code
    // below is what turns a string into a script tag, so check again right
    // here. That still holds if this markup ever comes from somewhere other
    // than our formatter.
    let embedUrl;
    try {
      embedUrl = new URL(src, window.location.href);
    } catch (e) {
      embedUrl = null;
    }
    if (
      embedUrl === null ||
      embedUrl.protocol !== 'https:' ||
      !SLATE_HOST_SUFFIXES.some((suffix) => embedUrl.hostname.endsWith(suffix))
    ) {
      showFallback(wrapper, Drupal.t('because its web address is not valid.'));
      return false;
    }

    // Hide the alert while the embed loads. It's visible in the markup so a
    // browser with no JavaScript still gets the link inside it.
    wrapper.classList.add('az-media-slate--js');

    // Put the spinner inside the container. When the form arrives, Slate
    // replaces everything in the container, which removes the spinner at
    // exactly that moment. If the form never arrives, showFallback() removes
    // it.
    container.appendChild(buildSpinner());

    const timer = window.setTimeout(() => {
      if (!hasRendered(container)) {
        showFallback(wrapper, Drupal.t('because it took too long to respond.'));
      }
    }, INIT_TIMEOUT_MS);

    // Slate writes the form in a little after its script runs, so watch for it
    // instead of styling once. Keep watching afterward in case Slate adds
    // fields later. This only listens for added and removed elements, and
    // adding a class is neither, so styling can't set it off again.
    const observer = new MutationObserver(() => {
      // Once the form has appeared, cancel the timeout. Rationale: when a
      // visitor submits, Slate replaces the form with its confirmation, so a
      // timeout still waiting would find no form and wrongly show the
      // fallback. For example, a visitor who submits 3 seconds after the page
      // loads would otherwise see "The form did not load" 12 seconds later.
      if (hasRendered(container)) {
        window.clearTimeout(timer);
      }
      applyBootstrapClasses(container);
    });
    observer.observe(container, { childList: true, subtree: true });

    // Give the container its id, and point Slate at it. The embed URL
    // arrives without a div parameter, because PHP can't see what other ids
    // the page holds.
    container.id = uniqueContainerId();
    embedUrl.searchParams.set('div', container.id);

    // Add the page's own parameters, filtered. Only the query string changes,
    // so the scheme and host checked above still hold.
    const forwarded = forwardedParams(embedUrl, settings);
    if (forwarded !== '') {
      embedUrl.search = embedUrl.search
        ? `${embedUrl.search}&${forwarded}`
        : forwarded;
    }

    const script = document.createElement('script');
    script.async = true;
    // Use the parsed URL, not the raw attribute, so the script tag gets the
    // address checked above plus only the parameters the filter allowed.
    script.src = embedUrl.href;
    script.addEventListener('error', () => {
      window.clearTimeout(timer);
      showFallback(
        wrapper,
        Drupal.t('because there was a problem reaching it.'),
      );
    });
    document.head.appendChild(script);
    return true;
  }

  Drupal.behaviors.azMediaSlate = {
    attach(context, settings) {
      const containers = once(
        'az-media-slate',
        '[data-az-slate-embed-src]',
        context,
      );

      containers.forEach((container) => {
        // If a Slate form already loaded on this page, show this one's alert
        // instead. Rationale: Slate's docs say only one Slate form can be
        // embedded on a page. The flag is on <html>, not in a variable here,
        // so a container added later by AJAX or Layout Builder counts against
        // the same page.
        if (document.documentElement.hasAttribute('data-az-slate-loaded')) {
          const wrapper = container.closest('.az-media-slate');
          // Name Slate and the form for this one. Rationale: it's a mistake
          // in how the page was built, so the person who can act on it needs
          // to know which form and why. Every other failure is something a
          // visitor hits, and neither word would mean anything to them.
          wrapper.classList.add('az-media-slate--names-form');
          const { reason, other } = alreadyEmbeddedMessage(wrapper);
          showFallback(wrapper, reason, other);
          return;
        }
        // Mark the page only once a script is on its way. A container we
        // refused hasn't used up the one form, so a later valid one still
        // loads.
        if (loadEmbed(container, settings)) {
          document.documentElement.setAttribute('data-az-slate-loaded', 'true');
        }
      });
    },
  };
})(Drupal, once);
