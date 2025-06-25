(function (Drupal, drupalSettings) {
  Drupal.behaviors.azMediaTrellisSetInputValue = {
    attach: function (context, settings) {
      // Access the variables passed from PHP.
      const queryParams = drupalSettings.azMediaTrellis.queryParams;
      const editing = drupalSettings.azMediaTrellis.editing;

      // Log the queryParams to verify.
      console.log('Query Parameters:', queryParams);

      const formContainer = document.querySelector('#fa-form');
      const observer = new MutationObserver((mutationsList, observer) => {

        console.log('editing = ' + editing);
        if (editing) {
          // Find all input fields inside #fa-form.
          const inputFields = formContainer.querySelectorAll('input');
          console.log(inputFields);
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
          }
        }

        observer.disconnect();
      });

      observer.observe(formContainer, { childList: true, attributes: true, subtree: true });
    }
  };
})(Drupal, drupalSettings);