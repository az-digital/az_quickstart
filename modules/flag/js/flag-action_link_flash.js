function _toConsumableArray(arr) {
  if (Array.isArray(arr)) {
    const arr2 = Array(arr.length);
    for (let i = 0; i < arr.length; i++) {
      arr2[i] = arr[i];
    }
    return arr2;
  }
  return Array.from(arr);
}

(function (Drupal) {
  Drupal.behaviors.flagAttach = {
    attach: function attach(context) {
      const links = [].concat(
        _toConsumableArray(context.querySelectorAll('.flag a')),
      );
      links.forEach(function (link) {
        return link.addEventListener('click', function (event) {
          return event.target.parentNode.classList.add('flag-waiting');
        });
      });
    },
  };

  Drupal.AjaxCommands.prototype.actionLinkFlash = function (
    ajax,
    response,
    status,
  ) {
    if (status === 'success') {
      if (response.message.length) {
        const para = document.createElement('P');
        para.innerText = response.message;

        para.setAttribute('class', 'js-flag-message');

        para.addEventListener(
          'animationend',
          function (event) {
            return event.target.remove();
          },
          false,
        );

        document.querySelector(response.selector).appendChild(para);
      }
    } else {
      const links = [].concat(
        _toConsumableArray(document.querySelectAll('.flag-waiting')),
      );
      links.forEach(function (link) {
        return link.classList.remove('flag-waiting');
      });
    }
  };
})(Drupal);
