(function (Drupal, drupalSettings) {
  Drupal.behaviors.azMediaTrellis = {
    attach(context, drupalSettings) {
      const config = drupalSettings.azMediaTrellis || {};
      const queryParams = config.queryParams || {};
      const editing = config.editing || false;

      // Find form containers that haven't been processed yet
      const formContainers = context.querySelectorAll(
        '.az-media-trellis:not([data-az-processed])',
      );

      formContainers.forEach((container) => {
        // Mark as processed to avoid duplicate initialization
        container.setAttribute('data-az-processed', 'true');

        new TrellisFormHandler(container, queryParams, editing);
      });
    },
  };

  /**
   * Handles a single Trellis form instance
   */
  function TrellisFormHandler(container, queryParams, editing) {
    this.container = container;
    this.queryParams = queryParams;
    this.editing = editing;
    this.processed = false;

    this.init();
  }

  TrellisFormHandler.prototype = {
    init() {
      // Set up mutation observer to wait for FormAssembly content
      this.setupContentObserver();

      // If there's already content, process it immediately
      if (this.container.children.length > 0) {
        this.processForm();
      }
    },

    setupContentObserver() {
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
    },

    processForm() {
      if (this.processed) return;

      this.processed = true;

      // Handle editing mode adjustments
      if (this.editing) {
        this.setupEditingMode();
      }

      // Prefill form fields from query parameters
      this.prefillFields();
    },

    setupEditingMode() {
      // Remove validation requirements in editing mode
      const requiredFields = this.container.querySelectorAll(
        'input[aria-required], input.required',
      );

      requiredFields.forEach((field) => {
        field.removeAttribute('aria-required');
        field.classList.remove('required');
      });
    },

    prefillFields() {
      Object.entries(this.queryParams).forEach(([name, value]) => {
        const field = this.container.querySelector(`[name="${name}"]`);
        if (field && field.value !== value) {
          field.value = value;
          field.dispatchEvent(new Event('input', { bubbles: true }));
        }
      });
    },
  };
})(Drupal, drupalSettings);
