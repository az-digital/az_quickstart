(function (Drupal, drupalSettings) {
  Drupal.behaviors.azMediaTrellisSetInputValue = {
    attach: function (context, settings) {
      // Access the variable passed from PHP.
      const tfaVal = drupalSettings.azMediaTrellis.tfa_4;

      // Log the variable to verify.
      console.log('tfaVal Variable:', tfaVal);

      // Use the variable as needed in your JavaScript logic.
      const formContainer = document.querySelector('#fa-form');
      const observer = new MutationObserver((mutationsList, observer) => {
        const inputField = formContainer.querySelector('input[name="tfa_4"]');
        if (inputField) {
          inputField.value = tfaVal; // Use the variable here.
          inputField.dispatchEvent(new Event('input'));
          observer.disconnect();
        }
      });
      observer.observe(formContainer, { childList: true, subtree: true });
    }
  };
})(Drupal, drupalSettings);