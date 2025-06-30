(function (Drupal, drupalSettings) {
  Drupal.behaviors.azMediaTrellisSetInputValue = {
    attach: function (context, settings) {
      // Access the variables passed from PHP.
      const queryParams = drupalSettings.azMediaTrellis?.queryParams || {};
      const editing = drupalSettings.azMediaTrellis?.editing || false;

      // Log the queryParams to verify.
      console.log('Query Parameters:', queryParams);

      const formContainer = document.querySelector('#fa-form');
      if (!formContainer) {
        console.warn('Trellis form container #fa-form not found');
        return;
      }

      let formProcessed = false;
      let lastUpdateTime = 0;
      let updateTimeout = null;

      // Debounced function to prevent infinite loops
      function debouncedUpdate(fn, delay = 250) {
        const now = Date.now();
        if (now - lastUpdateTime < delay) {
          if (updateTimeout) clearTimeout(updateTimeout);
          updateTimeout = setTimeout(() => {
            lastUpdateTime = Date.now();
            fn();
          }, delay);
        } else {
          lastUpdateTime = now;
          fn();
        }
      }

      // Function to update form sizing based on view mode class
      function updateFormSizing() {
        const classes = formContainer.className;
        console.log('Current form container classes:', classes);
        
        // Only force re-layout in editing mode if really needed
        if (editing && !formContainer.hasAttribute('data-sizing-applied')) {
          formContainer.setAttribute('data-sizing-applied', 'true');
        }
      }

      // Function to handle form field prefilling and editing mode adjustments
      function processForm() {
        if (formProcessed) return;

        console.log('Processing Trellis form, editing =', editing);

        if (editing) {
          // Find all input fields inside #fa-form.
          const inputFields = formContainer.querySelectorAll('input');
          console.log('Found input fields:', inputFields.length);
          inputFields.forEach((input) => {
            // Remove aria-required attribute if it exists.
            if (input.hasAttribute('aria-required')) {
              input.removeAttribute('aria-required');
            }
  
            // Remove the "Required" class if it exists.
            if (input.classList.contains('required')) {
              input.classList.remove('required');
            }
          });
        }

        // Prefill form fields based on query parameters.
        for (const [key, value] of Object.entries(queryParams)) {
          const inputField = formContainer.querySelector(`[name="${key}"]`);
          if (inputField) {
            inputField.value = value;
            inputField.dispatchEvent(new Event('input'));
            console.log(`Prefilled field ${key} with value:`, value);
          }
        }

        formProcessed = true;
        updateFormSizing();
      }

      // Function to trigger form resize/refresh (now debounced)
      function refreshForm() {
        debouncedUpdate(() => {
          const injectedContent = formContainer.firstElementChild;
          if (injectedContent && !injectedContent.hasAttribute('data-refreshed')) {
            injectedContent.setAttribute('data-refreshed', 'true');
            console.log('Refreshing form layout');
          }
        });
      }

      // Mutation observer to detect when form is loaded (one-time only)
      const observer = new MutationObserver((mutationsList, observer) => {
        if (!formProcessed) {
          processForm();
          observer.disconnect(); // Stop observing after first processing
        }
      });

      observer.observe(formContainer, { childList: true, subtree: true });

      // Only add intersection observer if not in editing mode
      if ('IntersectionObserver' in window && !editing) {
        const intersectionObserver = new IntersectionObserver((entries) => {
          entries.forEach((entry) => {
            if (entry.isIntersecting && entry.intersectionRatio > 0) {
              console.log('Trellis form became visible');
              debouncedUpdate(updateFormSizing);
            }
          });
        }, { threshold: 0.1 });

        intersectionObserver.observe(formContainer);
      }

      // Simplified resize observer with better debouncing
      if ('ResizeObserver' in window && !editing) {
        let resizeCount = 0;
        const maxResizes = 5; // Limit to prevent infinite loops
        
        const resizeObserver = new ResizeObserver((entries) => {
          resizeCount++;
          if (resizeCount > maxResizes) {
            console.warn('Too many resize events, disconnecting observer');
            resizeObserver.disconnect();
            return;
          }
          
          entries.forEach((entry) => {
            if (entry.contentRect.width > 0 && entry.contentRect.height > 0) {
              debouncedUpdate(() => {
                console.log('Form container resized');
              }, 500);
            }
          });
        });

        resizeObserver.observe(formContainer);
        
        // Clean up after 10 seconds to prevent long-term issues
        setTimeout(() => {
          resizeObserver.disconnect();
        }, 10000);
      }

      // Watch for class changes only (simplified)
      if ('MutationObserver' in window) {
        const classObserver = new MutationObserver((mutations) => {
          mutations.forEach((mutation) => {
            if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
              console.log('Form container class changed');
              debouncedUpdate(updateFormSizing, 100);
            }
          });
        });

        classObserver.observe(formContainer, { 
          attributes: true, 
          attributeFilter: ['class'] 
        });
      }
    }
  };
})(Drupal, drupalSettings);