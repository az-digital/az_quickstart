/**
 * @file
 * Trellis's handler for the Form embed component's loader.
 *
 * Trellis forms are FormAssembly forms, embedded with FormAssembly's Quick
 * Publish script. The loader, in the profile's
 * components/form-embed/form-embed.js, does what every vendor shares: the
 * spinner, the timeout, the alert and the retry. This file tells it what's
 * particular to FormAssembly: which addresses its script comes from, how to
 * point the script at the container and start it, when the form has
 * arrived, and how to add Arizona Bootstrap classes to it.
 *
 * Prefill comes from two places:
 * - The page's own query parameters. FormAssembly's prefill reads the page's
 *   address itself, so a link such as ?tfa_1=Wilbur works with nothing from
 *   us.
 * - The link the editor saved, such as ?tfa_4=701V400000r8xdx for a
 *   newsletter's campaign. FormAssembly ignores parameters on its script's
 *   address, so this handler fills those fields itself.
 *
 * @see https://help.formassembly.com/help/javascript-form-publishing
 */

((Drupal) => {
  /**
   * The hosts we'll load FormAssembly's script from.
   *
   * Keep this in step with TrellisUrl::HOSTS in PHP.
   */
  const TRELLIS_HOSTS = ['forms-a.trellis.arizona.edu', 'trellis.tfaforms.net'];

  /**
   * The only path we load: a form's Quick Publish script, such as
   * /publish/185.
   */
  const PUBLISH_PATH_PATTERN = /^\/publish\/[0-9]+$/;

  /**
   * Input types that get form-control, and the Drupal class to add with it.
   *
   * The second class is what a native Drupal field of that type carries, so
   * az_barrio sizes the field like any other form on the site. See
   * FORM_CONTROL_TYPES in az_form_embed_slate's js/slate.js, which explains
   * the choices.
   */
  const FORM_CONTROL_TYPES = {
    date: 'form-date',
    'datetime-local': null,
    email: 'form-email',
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
   * FormAssembly wraps each option in a span.oneChoice holding the input,
   * then its label, which is the order form-check expects. So this only adds
   * classes.
   *
   * @param {HTMLInputElement} input The checkbox or radio.
   */
  function styleCheck(input) {
    addClasses(input, ['form-check-input']);
    const choice = input.parentElement;
    if (!choice || !choice.classList.contains('oneChoice')) {
      return;
    }
    addClasses(choice, ['form-check']);
    if (input.id) {
      addClasses(choice.querySelector(`label[for="${CSS.escape(input.id)}"]`), [
        'form-check-label',
      ]);
    }
  }

  /**
   * Adds Arizona Bootstrap classes to the form FormAssembly rendered.
   *
   * Careful: only ever add classes here. Don't move, wrap, or remove
   * FormAssembly's elements, or give them inline styles. Rationale:
   * FormAssembly's scripts (wForms) run its conditional logic, repeatable
   * sections and validation on the elements it rendered. Rebuilding them
   * risks breaking those, and nothing would error.
   *
   * Safe to run more than once on the same form.
   *
   * @param {HTMLElement} container The element FormAssembly filled.
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
          // FormAssembly marks its submit button with primaryAction. Give it
          // btn-primary, like Quickstart's own submit buttons.
          const primary = control.matches('.primaryAction');
          addClasses(control, [
            'btn',
            primary ? 'btn-primary' : 'btn-outline-secondary',
          ]);
        } else if (type === 'checkbox' || type === 'radio') {
          styleCheck(control);
        } else if (Object.hasOwn(FORM_CONTROL_TYPES, type)) {
          const drupalClass = FORM_CONTROL_TYPES[type];
          addClasses(
            control,
            drupalClass ? ['form-control', drupalClass] : ['form-control'],
          );
        }
      });

    // A preField label sits above its field, which is where form-label goes.
    // Option labels are postField, and styleCheck() gives them
    // form-check-label instead.
    container.querySelectorAll('label.preField').forEach((label) => {
      addClasses(label, ['form-label']);
    });

    // Show a read-only text box as plain text. For example, a newsletter
    // form's campaign name is filled in by the link and locked.
    container
      .querySelectorAll(
        'textarea[readonly], input[readonly]:not([type="hidden"])',
      )
      .forEach((field) => {
        addClasses(field, ['form-control-plaintext']);
      });

    // Hide a text box that has no label while it's empty. For example, a
    // newsletter form's campaign description shows as an empty, unlabeled
    // box when the link doesn't fill it in, and a visitor can't tell what it
    // is for. A box with placeholder text explains itself, so it stays.
    container
      .querySelectorAll('.labelsRemoved textarea:not([placeholder])')
      .forEach((field) => {
        const wrapper = field.closest('.oneField');
        if (wrapper) {
          wrapper.classList.toggle(
            'az-form-embed-trellis__empty',
            field.value.trim() === '',
          );
        }
      });
  }

  /**
   * Removes the form's Trellis theme stylesheet, such as theme-26.css.
   *
   * FormAssembly loads three stylesheets with a form. Two, wforms-layout.css
   * and wforms-jsonly.css, lay the form out and hold the rules its scripts
   * show and hide fields with, such as .offstate for conditional logic. Keep
   * those. The third is the theme the form's author picked in Trellis, which
   * is only looks: colors, fonts, borders. Arizona Bootstrap's classes give
   * the form its looks instead, and the theme would override them, often
   * with !important.
   *
   * Remove the link rather than setting its disabled property. Rationale:
   * Chrome ignores disabled when it's set before the stylesheet loads, as it
   * is here, and applies the theme anyway.
   *
   * Call this right after loadFormAssemblyFormHeadAndBodyContents(). It adds
   * the stylesheets to the page's head before it returns, so the theme is
   * gone before the browser draws anything with it.
   */
  function removeThemeStylesheet() {
    document.head
      .querySelectorAll('link[rel~="stylesheet"]')
      .forEach((link) => {
        let url;
        try {
          url = new URL(link.href);
        } catch (e) {
          return;
        }
        if (
          TRELLIS_HOSTS.includes(url.hostname) &&
          url.pathname.startsWith('/uploads/themes/')
        ) {
          link.remove();
        }
      });
  }

  /**
   * Fills in and locks the fields the saved link prefills, such as tfa_4.
   *
   * Lock each one, because the editor chose that value for every visitor.
   * FormAssembly's own page locks a prefilled field the same way when the
   * form asks for it, as a newsletter form does for its campaign name. Quick
   * Publish doesn't say which fields ask, so lock them all.
   *
   * @param {HTMLElement} container The element FormAssembly filled.
   * @param {URL} embedUrl The embed URL built from the link the editor saved.
   */
  function applySavedPrefill(container, embedUrl) {
    embedUrl.searchParams.forEach((value, key) => {
      if (!key.startsWith('tfa_')) {
        return;
      }
      const field = container.querySelector(`[name="${CSS.escape(key)}"]`);
      if (!field) {
        return;
      }
      field.readOnly = true;
      if (field.value === value) {
        return;
      }
      field.value = value;
      // Tell FormAssembly's scripts, so its conditional logic sees the value.
      field.dispatchEvent(new Event('input', { bubbles: true }));
      field.dispatchEvent(new Event('change', { bubbles: true }));
    });
  }

  Drupal.azFormEmbed = Drupal.azFormEmbed || {};
  Drupal.azFormEmbed.vendors = Drupal.azFormEmbed.vendors || {};

  Drupal.azFormEmbed.vendors.trellis = {
    label: 'Trellis',
    // FormAssembly's script keeps a form in page-wide names: the global
    // variables it builds the form from, a wrapper with a fixed id
    // (FA__QP__BODYWRAPPERDIV), and its FA__DOMContentLoaded event. A second
    // form would share them with the first. For example, each form's setup
    // would run again when the other one finished loading, because both
    // listen for the same event.
    onePerPage: true,

    isAllowedUrl(url) {
      return (
        url.protocol === 'https:' &&
        TRELLIS_HOSTS.includes(url.hostname) &&
        PUBLISH_PATH_PATTERN.test(url.pathname)
      );
    },

    scriptUrl(embedUrl) {
      return new URL(embedUrl.href);
    },

    scriptAttributes(containerId) {
      return { 'data-qp-target-id': containerId };
    },

    afterScriptLoad(container, embedUrl) {
      // If the page is still loading, stop here. FormAssembly's script starts
      // itself on the page's DOMContentLoaded event, which is still to come.
      // Otherwise that event has passed, as it has whenever the loader adds
      // the script, so start the script here.
      if (
        document.readyState === 'loading' ||
        typeof window.loadFormAssemblyFormHeadAndBodyContents !== 'function'
      ) {
        return;
      }
      // Clear data-qp-target-id from every other script tag first. Rationale:
      // the call below reads it from the first script tag that has one, and
      // a tag from an earlier render can still be on the page. For example,
      // Canvas's editor redraws the component each time its settings change,
      // and the old render's tag names a container that's gone, so the form
      // would land at the bottom of the page instead.
      document
        .querySelectorAll('script[data-qp-target-id]')
        .forEach((script) => {
          if (script.getAttribute('data-qp-target-id') !== container.id) {
            script.removeAttribute('data-qp-target-id');
          }
        });
      window.loadFormAssemblyFormHeadAndBodyContents();
      removeThemeStylesheet();
      applySavedPrefill(container, embedUrl);
      applyBootstrapClasses(container);
      // Fill in the saved fields again after FormAssembly's own prefill, so
      // what the editor saved beats what the page's address asks for.
      //
      // FormAssembly prefills from the page's address twice, both on its
      // FA__DOMContentLoaded event: first from a listener the call above
      // added, then from wForms's own setup, whose listener wforms.js adds
      // once it loads. Listeners run in the order they were added, so this
      // one runs between the two. Turn wForms's prefill off here, so the
      // second pass can't undo ours. wForms has that switch, prefill.skip,
      // for forms its server has already prefilled.
      //
      // Then style the form again, because filling in a field doesn't add or
      // remove elements, so the loader's observer doesn't see it.
      document.addEventListener(
        'FA__DOMContentLoaded',
        () => {
          const { wFORMS } = window;
          if (wFORMS && wFORMS.behaviors && wFORMS.behaviors.prefill) {
            wFORMS.behaviors.prefill.skip = true;
          }
          applySavedPrefill(container, embedUrl);
          applyBootstrapClasses(container);
        },
        { once: true },
      );
    },

    hasRendered(container) {
      return container.querySelector('form') !== null;
    },

    decorate: applyBootstrapClasses,
  };
})(Drupal);
