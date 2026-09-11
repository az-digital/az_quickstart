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
   * Input types that take form-control, and the class Drupal adds beside it.
   *
   * The second class is the one a native Drupal field of that type carries in
   * Quickstart's theme, such as form-email on an email input. The theme sizes
   * fields using those classes, so giving Slate's fields the same ones makes
   * the theme treat them like any other form on the site. Text and password
   * inputs get none: the theme strips Drupal's form-text from them, because in
   * Bootstrap form-text is the small grey help-text style.
   *
   * Anything not listed here, and not a checkbox, radio, range, or button, is
   * left with Slate's own styling. That includes hidden inputs, which Slate
   * uses to carry values and must never be made visible.
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
   * Adds a set of classes to an element, skipping any it already has.
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
   * Slate already renders each option the way Bootstrap's form-check expects -
   * a wrapper holding the input and then its label - so this only has to add
   * classes to what is there. Likert grids are skipped: they lay their options
   * out as a table, which form-check's padding and block display would break.
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
   * This only ever adds classes. Slate's own classes and markup stay exactly as
   * they are - nothing is moved, wrapped, removed, or given an inline style.
   * Slate's conditional logic works by toggling "hidden" on the questions it
   * already rendered, and its event handlers are bound to those same elements,
   * so changing the structure would break both. Adding classes does neither.
   *
   * Slate's stylesheets stay loaded too. Blocking them does not make the inputs
   * look any more like Bootstrap - they look plain because they lack
   * Bootstrap's classes - and it uncovers labels Slate means to hide.
   *
   * Safe to run again on the same form: anything already styled is skipped.
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
          // Slate marks its main action with "default". Match it to the
          // btn-primary that Quickstart's own form submit buttons use.
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
      // A question label only gets form-label when it labels something a
      // person fills in. Section headers and paragraphs also use .form_label
      // in Slate's markup, and are left alone.
      if (
        question.querySelector(
          'input:not([type="hidden"]), select, textarea, .form_signature_editable',
        )
      ) {
        addClasses(question.querySelector('.form_label'), ['form-label']);
      }
    });

    container.querySelectorAll('.form_responses').forEach((responses) => {
      // Bootstrap makes every control full width, which would stack controls
      // Slate places side by side, like a birthdate's month, day, and year.
      // Mark those rows so the stylesheet can keep them on one line.
      const controls = responses.querySelectorAll(
        ':scope > .form-control, :scope > .form-select',
      );
      if (controls.length > 1) {
        addClasses(responses, ['az-media-slate__inline-controls']);
      }
    });
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
   * Brings the fallback link back and says why the form is not there.
   *
   * @param {HTMLElement} wrapper The .az-media-slate element.
   * @param {string} message Text for the status region.
   */
  function showFallback(wrapper, message) {
    wrapper.classList.add('az-media-slate--failed');
    const spinner = wrapper.querySelector('.az-media-slate__spinner');
    if (spinner) {
      spinner.remove();
    }
    const status = wrapper.querySelector('.az-media-slate__status');
    if (status) {
      status.textContent = message;
    }
  }

  /**
   * Builds the loading spinner shown until Slate's form arrives.
   *
   * This is Arizona Bootstrap's spinner, the same one az_media_trellis shows
   * while a Trellis form loads, so both embeds look alike. Slate's form takes
   * a second or more to appear, because it arrives over several requests to
   * Slate's servers one after another, and without this the space stays blank.
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

    // The spinner goes inside the container on purpose. When the form arrives,
    // Slate replaces everything in the container with it, which takes the
    // spinner away at exactly that moment. If the form never arrives,
    // showFallback() removes it instead.
    container.appendChild(buildSpinner());

    const timer = window.setTimeout(() => {
      if (!hasRendered(container)) {
        showFallback(
          wrapper,
          Drupal.t('The form did not load. Use the link to open it directly.'),
        );
      }
    }, INIT_TIMEOUT_MS);

    // Slate fills the container some time after its script runs, so watch for
    // markup arriving rather than styling once. This keeps watching after the
    // first render in case Slate adds controls later. It only listens for
    // added and removed nodes, and adding a class is neither, so the styling
    // does not set the observer off again.
    const observer = new MutationObserver(() => {
      applyBootstrapClasses(container);
    });
    observer.observe(container, { childList: true, subtree: true });

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
