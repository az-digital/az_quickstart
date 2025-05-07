(function (Drupal, drupalSettings) {
  Drupal.behaviors.azMediaTrellisSetInputValue = {
    attach: function (context, settings) {
      // Access the variable passed from PHP.
      const tfaVal = drupalSettings.azMediaTrellis.tfa_4;
      const editing = drupalSettings.azMediaTrellis.editing;

      // Log the variable to verify.
      console.log('tfaVal Variable:', tfaVal);

      // Use the variable as needed in your JavaScript logic.
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

        const inputField = formContainer.querySelector('input[name="tfa_4"]');
        if (inputField) {
          inputField.value = tfaVal; // Use the variable here.
          inputField.dispatchEvent(new Event('input'));
          observer.disconnect();
        }
      });



      observer.observe(formContainer, { childList: true, attributes: true, subtree: true });
    }
  };
})(Drupal, drupalSettings);