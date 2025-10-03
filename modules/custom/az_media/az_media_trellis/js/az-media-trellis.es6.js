(function azMediaTrellisIife(Drupal, drupalSettings) {
  // Central debug flag; enable via drupalSettings.azMediaTrellis.debug = true.
  const DEBUG = !!(
    drupalSettings.azMediaTrellis && drupalSettings.azMediaTrellis.debug
  );
  // eslint-disable-next-line no-console
  const log = (level, ...args) => {
    if (DEBUG && typeof console !== 'undefined') {
      // eslint-disable-next-line no-console
      (console[level] || console.log)(...args);
    }
  };
  /**
   * Intercepts attempts by the Trellis (FormAssembly) embed script to inject
   * its default stylesheet (form-assembly.css). This must run BEFORE the
   * remote script is appended to the DOM, so we install it at the top of the
   * behavior execution path and only once.
   */
  function installCssBlockerOnce() {
    if (window.__azTrellisCssBlockerInstalled) return;
    window.__azTrellisCssBlockerInstalled = true;
    // List of stylesheet URL patterns to block (expanded from single file
    // to all Trellis layout/theme CSS we intend to replace with Bootstrap).
    const BLOCK_PATTERNS = [
      /design\.trellis\.arizona\.edu\/css\/form-assembly\.css/i,
      /forms-a\.trellis\.arizona\.edu\/dist\/form-builder\//i,
      /forms-a\.trellis\.arizona\.edu\/uploads\/themes\//i,
      /forms-a\.trellis\.arizona\.edu\/wForms\/3\.11\/css\//i,
      /forms-a\.trellis\.arizona\.edu\/wForms\/3\.11\/js\/css/i, // safety catch
    ];

    function isBlockedStylesheet(node) {
      if (!node || node.tagName !== 'LINK' || node.rel !== 'stylesheet') {
        return false;
      }
      return BLOCK_PATTERNS.some((rx) => rx.test(node.href));
    }

    function interceptorFactory(original) {
      return function trellisCssInterceptor(node) {
        try {
          if (isBlockedStylesheet(node)) {
            return node; // silently drop
          }
        } catch (e) {
          /* ignore */
        }
        return original.call(this, node);
      };
    }

    function interceptorBeforeFactory(original) {
      return function trellisCssInterceptorBefore(newNode, refNode) {
        try {
          if (isBlockedStylesheet(newNode)) {
            return newNode; // drop
          }
        } catch (e) {
          /* ignore */
        }
        return original.call(this, newNode, refNode);
      };
    }

    const protoDoc = Document.prototype;
    const protoHead = HTMLHeadElement.prototype;
    if (!protoDoc.__azPatchedAppendChild) {
      protoDoc.__azPatchedAppendChild = true;
      protoDoc.appendChild = interceptorFactory(protoDoc.appendChild);
    }
    if (!protoHead.__azPatchedAppendChild) {
      protoHead.__azPatchedAppendChild = true;
      protoHead.appendChild = interceptorFactory(protoHead.appendChild);
    }
    if (!protoHead.__azPatchedInsertBefore) {
      protoHead.__azPatchedInsertBefore = true;
      protoHead.insertBefore = interceptorBeforeFactory(protoHead.insertBefore);
    }

    // Mutation observer as a safety net for any <link> nodes inserted through
    // means other than the patched methods.
    const headObserver = new MutationObserver((muts) => {
      muts.forEach((m) => {
        m.addedNodes.forEach((n) => {
          if (isBlockedStylesheet(n) && n.parentNode) {
            n.parentNode.removeChild(n);
            log('log', '[azMediaTrellis] Removed blocked stylesheet', n.href);
          }
        });
      });
    });
    headObserver.observe(document.head, { childList: true });
  }

  /**
   * Dynamically loads the Trellis embed script after blockers are in place.
   * @param {HTMLElement} el Container element with data-trellis-embed-src.
   */
  function loadTrellisScript(el) {
    const url = el.getAttribute('data-trellis-embed-src');
    // If the formatter already injected the script, respect that.
    if (
      !url ||
      el.getAttribute('data-trellis-script-loaded') ||
      el.getAttribute('data-trellis-script-preloaded')
    ) {
      return;
    }
    el.setAttribute('data-trellis-script-loaded', '1');
    const script = document.createElement('script');
    script.src = url;
    script.defer = true;
    script.type = 'text/javascript';
    // Provide the original quick-publish target id for potential script usage.
    if (el.id) {
      script.setAttribute('data-qp-target-id', el.id);
    }
    document.head.appendChild(script);
  }
  function TrellisFormHandler(container, queryParams, editing) {
    this.container = container;
    this.queryParams = queryParams;
    this.editing = editing;
    this.processed = false;
    this.sanitized = false;
    this.spinnerInserted = false;
    this.spinnerRemoved = false;
    this.finalized = false;
    this.spinnerDelayTimer = null;
    this.spinnerStartTs = 0;
    this.prefillObserver = null;
    this.prefillFallbackTimer = null;
  }
  TrellisFormHandler.prototype.init = function init() {
    // Defer spinner slightly so we can skip it entirely if the form renders fast
    // (reduces perceived load delay / flicker). If after the delay we still
    // haven't processed content, we show the spinner.
    const SPINNER_DELAY_MS = 120;
    this.spinnerDelayTimer = setTimeout(() => {
      if (!this.processed && !this.spinnerInserted) {
        this.insertSpinner();
      }
    }, SPINNER_DELAY_MS);

    this.setupContentObserver();
    if (this.container.children.length > 0) {
      this.processForm(); // May finalize before spinner appears.
    }
  };
  TrellisFormHandler.prototype.setupContentObserver =
    function setupContentObserver() {
      if (this.processed) return;
      const observer = new MutationObserver(() => {
        if (!this.processed && this.container.children.length > 0) {
          this.processForm();
          observer.disconnect();
        }
      });
      observer.observe(this.container, {
        childList: true,
        subtree: true,
      });
    };

  TrellisFormHandler.prototype.processForm = function processForm() {
    if (this.processed) return;
    this.processed = true;
    if (this.editing) {
      this.setupEditingMode();
    }
    // Prefill (with retry) then finalize rendering.
    this.prefillFields(() => {
      this.sanitizeAndRetheme();
      this.observeDynamicMutations();
      this.finalized = true;
      // If spinner delay timer still pending & spinner never shown, cancel it.
      if (this.spinnerDelayTimer) {
        clearTimeout(this.spinnerDelayTimer);
        this.spinnerDelayTimer = null;
      }
      this.removeSpinner();
    });
  };
  /**
   * Remove unwanted styling (external or inline) and apply Arizona Bootstrap classes.
   */
  TrellisFormHandler.prototype.sanitizeAndRetheme =
    function sanitizeAndRetheme() {
      if (this.sanitized) return;
      this.sanitized = true;
      const root = this.container;

      // 1. Remove inline style attributes.
      root.querySelectorAll('[style]').forEach((el) => {
        el.removeAttribute('style');
      });

      // 2. Remove <style> tags inside container & any code-section style blocks.
      root.querySelectorAll('style').forEach((s) => s.remove());

      // 2b. Remove any <br> directly between label and input.
      root.querySelectorAll('.oneField br').forEach((br) => {
        const prev = br.previousElementSibling;
        const next = br.nextElementSibling;
        if (
          prev &&
          next &&
          (next.matches('.inputWrapper') ||
            next.querySelector('input,textarea,select'))
        ) {
          br.remove();
        }
      });

      // 3. Basic class remapping for form controls (additive: we do NOT
      // remove original classes to avoid breaking remote script behaviors).
      const addClass = (el, cls) => {
        if (!el.classList.contains(cls)) el.classList.add(...cls.split(/\s+/));
      };

      root.querySelectorAll('input, select, textarea, button').forEach((el) => {
        const tag = el.tagName.toLowerCase();
        const type = (el.getAttribute('type') || '').toLowerCase();
        if (tag === 'input') {
          if (
            [
              'text',
              'email',
              'tel',
              'url',
              'number',
              'search',
              'password',
              'date',
              'datetime-local',
              'time',
            ].includes(type)
          ) {
            addClass(el, 'form-control');
          } else if (['checkbox', 'radio'].includes(type)) {
            addClass(el, 'form-check-input');
            // Try to find its label (sibling or parent) and style appropriately.
            let label = el.closest('label');
            if (!label) {
              // Try next sibling label with for attr.
              const { id } = el;
              if (id) {
                label = root.querySelector(`label[for="${CSS.escape(id)}"]`);
              }
            }
            if (label) addClass(label, 'form-check-label');
            // Ensure wrapper has form-check.
            const wrapper = label ? label.parentElement : el.parentElement;
            if (wrapper && !wrapper.classList.contains('form-check')) {
              wrapper.classList.add('form-check');
            }
          } else if (type === 'submit') {
            addClass(el, 'btn btn-primary');
          }
        } else if (tag === 'select') {
          addClass(el, 'form-select');
        } else if (tag === 'textarea') {
          const name = el.getAttribute('name');
          // tfa_7 => Campaign name
          // tfa_9 => Campaign description
          // These are readonly values that come from Trellis; they should not appear editable
          if (name === 'tfa_7' || name === 'tfa_9') {
            addClass(el, 'form-control-plaintext');
          } else {
            addClass(el, 'form-control');
          }
        } else if (tag === 'button') {
          addClass(el, 'btn btn-primary');
        }
      });

      // 4. Wrap orphaned checkboxes/radios lacking form-check wrapper.
      root.querySelectorAll('input.form-check-input').forEach((input) => {
        if (!input.closest('.form-check')) {
          const wrapper = document.createElement('div');
          wrapper.className = 'form-check';
          input.parentNode.insertBefore(wrapper, input);
          wrapper.appendChild(input);
          // Move label if immediately following.
          const next = input.nextElementSibling;
          if (
            next &&
            next.tagName === 'LABEL' &&
            !next.classList.contains('form-check-label')
          ) {
            next.classList.add('form-check-label');
            wrapper.appendChild(next);
          }
        }
      });

      // 5. Add form-group spacing to direct field wrappers if we can guess them.
      // 5. Add form-group spacing & form-label classes.
      root.querySelectorAll('.oneField').forEach((of) => {
        if (!of.classList.contains('mb-3')) {
          of.classList.add('mb-3');
        }
        const label = of.querySelector('label');
        if (
          label &&
          !label.classList.contains('form-label') &&
          !label.classList.contains('form-check-label')
        ) {
          label.classList.add('form-label');
        }
      });

      // 6. Convert read-only descriptive textareas (campaign info) to plaintext style.
      root.querySelectorAll('textarea[readonly]').forEach((ta) => {
        if (!ta.value || ta.value.length > 160) {
          return; // heuristic skip large bodies
        }
        ta.classList.add('form-control-plaintext');
      });

      // 7. Remove Trellis privacy statement / footer (user requested strip).
      // Target anchor containing 'privacy-statement' and remove closest footer wrappers.
      const privacyLinks = root.querySelectorAll(
        'a[href*="privacy-statement"]',
      );
      privacyLinks.forEach((a) => {
        const footer = a.closest('.wFormFooter');
        if (footer) {
          footer.remove();
        }
        // Also remove any trailing sibling .supportInfo paragraphs with only that link.
        const support = a.closest('p.supportInfo');
        if (support && support.parentNode) {
          // If after removing link paragraph is empty or just <br>, remove it.
          if (
            support.textContent.trim() === '' ||
            support.querySelectorAll('a').length === 0
          ) {
            support.remove();
          }
        }
      });

      // 8. Autosize plaintext (readonly) short textareas so they shrink to content height.
      // We treat the campaign fields (converted to form-control-plaintext) like static text blocks.
      const autosize = (ta) => {
        if (!ta) return;
        // Determine visible line count based on hard line breaks first.
        const rawLines = ta.value.split(/\r?\n/);
        // Ignore trailing empty line produced by newline at end.
        let lineCount =
          rawLines.filter(
            (l, idx, arr) => !(idx === arr.length - 1 && l.trim() === ''),
          ).length || 1;
        // Reasonable upper bound to avoid huge growth for unexpected large text.
        if (lineCount > 6) lineCount = 6;

        // Apply minimal rows then compute scroll height for wrapped long lines.
        ta.setAttribute('rows', lineCount);
        ta.style.resize = 'none';
        ta.style.overflow = 'hidden';

        // Use scrollHeight after temporarily resetting height to auto to shrink.
        const adjustPixelHeight = () => {
          ta.style.height = 'auto';
          // Force a single row baseline then allow expansion; ensures shrink works in Safari.
          ta.rows = lineCount; // keep explicit for consistent baseline
          const scrollH = ta.scrollHeight;
          ta.style.height = `${scrollH}px`;
        };

        adjustPixelHeight();

        if (!ta.hasAttribute('data-az-autosize')) {
          ta.setAttribute('data-az-autosize', 'true');
          ta.addEventListener('input', () => {
            // Recalculate line count if user/script changes value (editing unlikely but safe).
            const newLines =
              ta.value
                .split(/\r?\n/)
                .filter(
                  (l, idx, arr) => !(idx === arr.length - 1 && l.trim() === ''),
                ).length || 1;
            lineCount = Math.min(newLines, 6);
            ta.rows = lineCount;
            adjustPixelHeight();
          });
        } else {
          adjustPixelHeight();
        }
      };
      root.querySelectorAll('textarea.form-control-plaintext').forEach((ta) => {
        // Skip huge bodies (heuristic already above); still ensure sizing if present.
        autosize(ta);
      });
    };

  /**
   * Observe subsequent dynamic DOM mutations (some form scripts progressively enhance fields).
   */
  TrellisFormHandler.prototype.observeDynamicMutations =
    function observeDynamicMutations() {
      const observer = new MutationObserver((mutations) => {
        let needsResanitize = false;
        let hasFormContent = false;
        mutations.forEach((m) => {
          m.addedNodes.forEach((node) => {
            if (node.nodeType === 1) {
              if (node.hasAttribute && node.hasAttribute('style')) {
                needsResanitize = true;
              }
              if (node.querySelector && node.querySelector('[style]')) {
                needsResanitize = true;
              }
              if (
                !hasFormContent &&
                (node.matches('form') ||
                  (node.querySelector && node.querySelector('form')))
              ) {
                hasFormContent = true;
              }
            }
          });
        });
        if (needsResanitize) {
          // Allow incremental sanitize without undoing previously added classes.
          this.sanitized = false; // Force re-run of sanitizeAndRetheme enhancements.
          this.sanitizeAndRetheme();
        }
        // Only allow spinner removal here if finalization already occurred.
        if (hasFormContent && this.finalized) {
          this.removeSpinner();
        }
      });
      observer.observe(this.container, { childList: true, subtree: true });
    };

  TrellisFormHandler.prototype.insertSpinner = function insertSpinner() {
    if (this.spinnerInserted) return;
    this.spinnerInserted = true;
    this.spinnerStartTs =
      window.performance && performance.now ? performance.now() : Date.now();
    this.container.setAttribute('data-loading', 'true');
    const overlay = document.createElement('div');
    overlay.className = 'az-media-trellis__spinner-overlay';
    // Bootstrap 5 spinner (relies on theme including Bootstrap assets).
    overlay.innerHTML =
      '<div class="az-media-trellis__spinner-wrapper d-flex flex-column align-items-center justify-content-center py-4">' +
      '<div class="spinner-border text-primary" role="status" aria-live="polite" aria-label="Loading"></div>' +
      '<div class="visually-hidden">Loading form…</div>' +
      '</div>';
    this.container.appendChild(overlay);
    // Safety timeout: remove spinner after 15s even if form failed.
    this.spinnerTimeout = setTimeout(() => {
      this.removeSpinner(true);
    }, 15000);
  };

  TrellisFormHandler.prototype.removeSpinner = function removeSpinner(
    fallback,
  ) {
    if (this.spinnerRemoved) return;
    const overlay = this.container.querySelector(
      '.az-media-trellis__spinner-overlay',
    );
    if (!overlay) return; // Spinner never displayed.
    if (!fallback) {
      // If we have actual form content keep removing; if not and fallback triggered, leave a subtle message.
      const hasForm = this.container.querySelector('form');
      if (!hasForm && !fallback) return; // wait until form appears
    }
    // Optional: ensure spinner does not flash too briefly (< 100ms). If it
    // was visible for a very short time, we could delay removal a touch. For
    // now we prioritize immediate display of the ready form (no added delay).
    overlay.remove();
    this.spinnerRemoved = true;
    this.container.removeAttribute('data-loading');
    if (this.spinnerTimeout) clearTimeout(this.spinnerTimeout);
  };
  TrellisFormHandler.prototype.setupEditingMode = function setupEditingMode() {
    const requiredFields = this.container.querySelectorAll(
      'input[aria-required], input.required',
    );
    requiredFields.forEach((field) => {
      field.removeAttribute('aria-required');
      field.classList.remove('required');
    });
  };
  TrellisFormHandler.prototype.prefillFields = function prefillFields(done) {
    // Assumption: All possible prefillable field names that actually exist in the
    // embedded form are represented as keys in this.queryParams (even if empty
    // string). This lets us treat the presence of those input elements as the
    // readiness signal and avoid polling loops.
    const callback = typeof done === 'function' ? done : function noop() {};
    const qp = this.queryParams || {};
    const targetNames = Object.keys(qp);

    const finalizePrefill = () => {
      // Hide optional prefill-only fields after any attempted prefills.
      this.hideEmptyOptionalPrefillFields();
      if (this.prefillObserver) {
        this.prefillObserver.disconnect();
        this.prefillObserver = null;
      }
      if (this.prefillFallbackTimer) {
        clearTimeout(this.prefillFallbackTimer);
        this.prefillFallbackTimer = null;
      }
      callback();
    };

    if (targetNames.length === 0) {
      // Nothing to prefill, still perform optional hide logic.
      finalizePrefill();
      return;
    }

    const tryApply = () => {
      // Check that every target name has a corresponding field OR at least one field exists (remote may omit some legitimately).
      const allPresent = targetNames.every(
        (name) => !!this.container.querySelector(`[name="${name}"]`),
      );
      if (!allPresent) return false;
      targetNames.forEach((name) => {
        const value = qp[name];
        const field = this.container.querySelector(`[name="${name}"]`);
        if (field && value != null && field.value !== String(value)) {
          field.value = value;
          field.dispatchEvent(new Event('input', { bubbles: true }));
          field.dispatchEvent(new Event('change', { bubbles: true }));
        }
      });
      return true;
    };

    // Attempt immediately in case fields already present.
    if (tryApply()) {
      finalizePrefill();
      return;
    }

    // Observe for fields to arrive; each mutation re-attempts.
    this.prefillObserver = new MutationObserver(() => {
      if (tryApply()) {
        finalizePrefill();
      }
    });
    this.prefillObserver.observe(this.container, {
      childList: true,
      subtree: true,
    });

    // Fallback: after 2000ms, proceed even if not all fields appeared.
    this.prefillFallbackTimer = setTimeout(() => {
      // eslint-disable-next-line no-console
      console.warn('Prefill fallback: proceeding before all fields detected');
      // Best-effort partial prefill for whatever exists now.
      targetNames.forEach((name) => {
        const field = this.container.querySelector(`[name="${name}"]`);
        if (field && qp[name] != null && field.value !== String(qp[name])) {
          field.value = qp[name];
          field.dispatchEvent(new Event('input', { bubbles: true }));
          field.dispatchEvent(new Event('change', { bubbles: true }));
        }
      });
      finalizePrefill();
    }, 2000);
  };
  /**
   * Hide optional campaign fields (tfa_7, tfa_9) if they were not prefilled.
   * Criteria:
   *  - Field exists in the rendered Trellis form.
   *  - Query params did not supply a non-empty value for that field.
   *  - Current field value is empty/whitespace.
   * Adds a wrapper class 'az-trellis-hidden-prefill'. If the user types
   * into the field (e.g., future requirement change), the wrapper is shown again.
   */
  TrellisFormHandler.prototype.hideEmptyOptionalPrefillFields =
    function hideEmptyOptionalPrefillFields() {
      const optionalIds = ['tfa_7', 'tfa_9'];
      optionalIds.forEach((id) => {
        const field = this.container.querySelector(`[name="${id}"]`);
        if (!field) return;
        const supplied = !!(
          this.queryParams &&
          Object.prototype.hasOwnProperty.call(this.queryParams, id) &&
          this.queryParams[id] &&
          String(this.queryParams[id]).trim() !== ''
        );
        const emptyCurrent = !field.value || field.value.trim() === '';
        if (!supplied && emptyCurrent) {
          const wrapper =
            field.closest('.oneField') ||
            this.container.querySelector(`#${id}-D`);
          if (
            wrapper &&
            !wrapper.classList.contains('az-trellis-hidden-prefill')
          ) {
            wrapper.classList.add('az-trellis-hidden-prefill');
            // If user starts typing later (should remain hidden use-case, but be resilient).
            field.addEventListener(
              'input',
              () => {
                if (field.value && field.value.trim() !== '') {
                  wrapper.classList.remove('az-trellis-hidden-prefill');
                }
              },
              { once: true },
            );
          }
        }
      });
    };
  Drupal.behaviors.azMediaTrellis = {
    attach(context) {
      const config = drupalSettings.azMediaTrellis || {};
      const blockRemoteCss = !!config.blockRemoteCss;
      if (blockRemoteCss) {
        installCssBlockerOnce();
      }
      const formContainers = context.querySelectorAll(
        '.az-media-trellis:not([data-az-processed])',
      );
      formContainers.forEach((container) => {
        container.setAttribute('data-az-processed', 'true');

        // Get query parameters and editing context specific to this container.
        const queryParamsJson =
          container.getAttribute('data-query-params') || '{}';
        log(
          'log',
          '[azMediaTrellis] raw query params attribute:',
          queryParamsJson,
        );

        let queryParams = {};
        try {
          queryParams = JSON.parse(queryParamsJson);
        } catch (e) {
          log(
            'error',
            '[azMediaTrellis] Failed to parse query params JSON:',
            e,
          );
        }

        const editing = container.getAttribute('data-editing') === 'true';

        log('log', '[azMediaTrellis] container:', container);
        log('log', '[azMediaTrellis] queryParams:', queryParams);
        log('log', '[azMediaTrellis] editing mode:', editing);

        const handler = new TrellisFormHandler(container, queryParams, editing);
        handler.init();
        if (blockRemoteCss) {
          loadTrellisScript(container);
        }
      });
    },
  };
})(Drupal, drupalSettings);
