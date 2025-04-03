(function (Drupal) {
  Drupal.behaviors.azMediaTrellisSetInputValue = {
    attach: function (context, settings) {
      // Select the container where the external form will be loaded.
      const formContainer = document.querySelector('#fa-form');

      // Use MutationObserver to detect when the form is dynamically added.
      const observer = new MutationObserver((mutationsList, observer) => {
        // Check if the input field exists in the dynamically loaded form.
        const inputField = formContainer.querySelector('input[name="tfa_4"]'); // Replace with the actual input name or selector.
        if (inputField) {
          // Set the value of the input field.
          inputField.value = '7018N00000071eDQAQ'; // Replace with the value you want to set.

          // Optionally trigger an input event if needed.
          inputField.dispatchEvent(new Event('input'));

          // Stop observing once the input is found and updated.
          observer.disconnect();
        }
      });

      // Start observing the container for changes in its child elements.
      observer.observe(formContainer, { childList: true, subtree: true });
    }
  };
})(Drupal);