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
 * Once the form is in, this adds Arizona Bootstrap classes to its fields. If
 * the form never arrives, it shows a fallback link instead. Slate allows one
 * form per page, so only the first Slate embed on a page loads.
 *
 * @see https://knowledge.technolutions.net/docs/embedding-forms
 */

((Drupal, once) => {
  /**
   * How long to wait for Slate's form before showing the fallback link.
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
   * The only domain we'll load an embed script from.
   *
   * Keep this in step with SlateUrl::HOST_SUFFIX in PHP. See loadEmbed() for
   * why the browser checks the URL again.
   */
  const SLATE_HOST_SUFFIX = '.technolutions.net';

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
        addClasses(question.querySelector('.form_label'), ['form-label']);
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
   * Whether Slate has put a form into the container yet.
   *
   * @param {HTMLElement} container The element Slate was told to fill.
   * @return {boolean} True once a form element is present.
   */
  function hasRendered(container) {
    return container.querySelector('form') !== null;
  }

  /**
   * Removes the spinner, shows the fallback link, and says why.
   *
   * @param {HTMLElement} wrapper The .az-media-slate element.
   * @param {string} message Text for the status message.
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
      !embedUrl.hostname.endsWith(SLATE_HOST_SUFFIX)
    ) {
      showFallback(
        wrapper,
        Drupal.t('The form could not be loaded. Use the link to open it.'),
      );
      return false;
    }

    // Hide the fallback link while the embed loads. It's visible in the
    // markup so a browser with no JavaScript still gets it.
    wrapper.classList.add('az-media-slate--js');

    // Put the spinner inside the container. When the form arrives, Slate
    // replaces everything in the container, which removes the spinner at
    // exactly that moment. If the form never arrives, showFallback() removes
    // it.
    container.appendChild(buildSpinner());

    const timer = window.setTimeout(() => {
      if (!hasRendered(container)) {
        showFallback(
          wrapper,
          Drupal.t('The form did not load. Use the link to open it directly.'),
        );
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

    const script = document.createElement('script');
    script.async = true;
    // Use the parsed URL, not the raw attribute, so the script tag gets exactly
    // the value checked above.
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
        // If a Slate form already loaded on this page, show this one's
        // fallback link instead. Rationale: Slate's docs say only one Slate
        // form can be embedded on a page. The flag is on <html>, not in a
        // variable here, so a container added later by AJAX or Layout Builder
        // counts against the same page.
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
        // Mark the page only once a script is on its way. A container we
        // refused hasn't used up the one form, so a later valid one still
        // loads.
        if (loadEmbed(container)) {
          document.documentElement.setAttribute('data-az-slate-loaded', 'true');
        }
      });
    },
  };
})(Drupal, once);
