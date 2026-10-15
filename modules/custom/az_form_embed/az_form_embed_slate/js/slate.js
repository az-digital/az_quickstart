/**
 * @file
 * Slate's handler for the Form embed component's loader.
 *
 * The loader, in the profile's components/form-embed/form-embed.js, does what
 * every vendor shares: the spinner, the timeout, the alert and the retry. This
 * file tells it what's particular to Slate: which addresses Slate's scripts
 * come from, how to point Slate's script at the container, when Slate's form
 * has arrived, and how to add Arizona Bootstrap classes to it.
 *
 * Some of the page's own query parameters travel with the script's address,
 * which is how someone arriving from a personal link gets fields filled in.
 * Slate calls that a dynamic embed, and forwardedParams() decides which may
 * go.
 *
 * @see https://knowledge.technolutions.net/docs/embedding-forms
 */

((Drupal, drupalSettings) => {
  /**
   * Returns the hosts we'll load Slate's script from.
   *
   * They come from SlateUrl::getHosts() in PHP, through drupalSettings, so
   * there's only one list. If they're missing, return none, so nothing loads.
   *
   * @return {string[]} The hosts, lowercase.
   */
  function slateHosts() {
    const hosts =
      drupalSettings.azFormEmbed &&
      drupalSettings.azFormEmbed.hosts &&
      drupalSettings.azFormEmbed.hosts.slate;
    return Array.isArray(hosts) ? hosts : [];
  }

  /**
   * Returns the pattern for paths we'll load Slate's script from.
   *
   * That's /register/, optionally followed by a form's name, as in
   * /register/referawildcat. The pattern comes from SlateUrl::getPathPattern()
   * in PHP, through drupalSettings, so there's only one copy. If it's missing,
   * or doesn't compile here, return null, so nothing loads.
   *
   * @return {RegExp|null} The pattern, or null.
   */
  function slatePathPattern() {
    const body =
      drupalSettings.azFormEmbed &&
      drupalSettings.azFormEmbed.pathPattern &&
      drupalSettings.azFormEmbed.pathPattern.slate;
    if (typeof body !== 'string') {
      return null;
    }
    // If the pattern doesn't compile, refuse every path. For example, PHP's
    // possessive a++ is a syntax error in a browser.
    try {
      return new RegExp(body);
    } catch (e) {
      return null;
    }
  }

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
        addClasses(responses, ['az-form-embed-slate__inline-controls']);
      }
    });
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
   * az_form_embed_slate adds them to this file's library. Without them,
   * nothing is forwarded.
   *
   * @param {URL} embedUrl The embed URL built from the link the editor saved.
   * @param {object|undefined} rules The forwarding rules.
   * @return {string} Parameters to append, or an empty string.
   */
  function forwardedParams(embedUrl, rules) {
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
   * Gives Slate's own scripts the global $ they expect.
   *
   * Slate loads its own copy of jQuery as FW.$ (FW is Slate's framework
   * object), and on Slate's own pages $ points at that copy too. Some of
   * Slate's scripts call $ directly. For example, the signature question's
   * dialog runs a short script that sets up its six signature styles with
   * $(...). Drupal doesn't define a global $, so on our pages that script
   * fails with "$ is not a function", and a visitor can type a name but
   * can't pick a style.
   *
   * If $ is already set, leave it. Rationale: another script on the page may
   * rely on its own $.
   */
  function provideSlateDollar() {
    if (
      typeof window.$ !== 'function' &&
      window.FW &&
      typeof window.FW.$ === 'function'
    ) {
      window.$ = window.FW.$;
    }
  }

  Drupal.azFormEmbed = Drupal.azFormEmbed || {};
  Drupal.azFormEmbed.vendors = Drupal.azFormEmbed.vendors || {};

  Drupal.azFormEmbed.vendors.slate = {
    label: 'Slate',
    // Slate's docs say only one Slate form can be embedded on a page.
    onePerPage: true,

    // Check the scheme, port, host and path the way SlateUrl::parse() does.
    // A URL's port is '' when the URL names none.
    isAllowedUrl(url) {
      const pathPattern = slatePathPattern();
      return (
        url.protocol === 'https:' &&
        url.port === '' &&
        slateHosts().includes(url.hostname) &&
        pathPattern !== null &&
        pathPattern.test(url.pathname)
      );
    },

    scriptUrl(embedUrl, containerId, settings) {
      const url = new URL(embedUrl.href);
      // Add the page's own parameters, filtered. Only the query string
      // changes, so the scheme and host the loader checked still hold.
      const rules =
        settings && settings.azFormEmbed && settings.azFormEmbed.forwarding
          ? settings.azFormEmbed.forwarding.slate
          : undefined;
      const forwarded = forwardedParams(url, rules);
      if (forwarded !== '') {
        url.search = url.search ? `${url.search}&${forwarded}` : forwarded;
      }
      // Point Slate at the container. The embed URL arrives without a div
      // parameter, because PHP can't see what other ids the page holds.
      url.searchParams.set('div', containerId);
      return url;
    },

    hasRendered(container) {
      return container.querySelector('form') !== null;
    },

    decorate(container) {
      // Set $ here, because Slate's framework, and with it FW.$, is on the
      // page by the time its form arrives.
      provideSlateDollar();
      applyBootstrapClasses(container);
    },
  };
})(Drupal, drupalSettings);
