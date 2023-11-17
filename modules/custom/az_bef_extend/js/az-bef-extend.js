(function (Drupal) {
  Drupal.behaviors.myBehavior = {
    attach: function (context, settings) {
      // Find all 'li' elements within the context.
      let listItems = context.querySelectorAll('li');
      // Loop through each 'li' element.
      listItems.forEach(function (li) {
        // Check if 'li' has a 'ul' as a child.
        if (li.querySelector('ul')) {
          // Add a class to the 'li'.
          li.classList.add('has-nested-ul');
        }
      });
    }
  };
})(Drupal);
