/**
* DO NOT EDIT THIS FILE.
* See the following change record for more information,
* https://www.drupal.org/node/2815083
* @preserve
**/
function _slicedToArray(r, e) { return _arrayWithHoles(r) || _iterableToArrayLimit(r, e) || _unsupportedIterableToArray(r, e) || _nonIterableRest(); }
function _nonIterableRest() { throw new TypeError("Invalid attempt to destructure non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); }
function _unsupportedIterableToArray(r, a) { if (r) { if ("string" == typeof r) return _arrayLikeToArray(r, a); var t = {}.toString.call(r).slice(8, -1); return "Object" === t && r.constructor && (t = r.constructor.name), "Map" === t || "Set" === t ? Array.from(r) : "Arguments" === t || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(t) ? _arrayLikeToArray(r, a) : void 0; } }
function _arrayLikeToArray(r, a) { (null == a || a > r.length) && (a = r.length); for (var e = 0, n = Array(a); e < a; e++) n[e] = r[e]; return n; }
function _iterableToArrayLimit(r, l) { var t = null == r ? null : "undefined" != typeof Symbol && r[Symbol.iterator] || r["@@iterator"]; if (null != t) { var e, n, i, u, a = [], f = !0, o = !1; try { if (i = (t = t.call(r)).next, 0 === l) { if (Object(t) !== t) return; f = !1; } else for (; !(f = (e = i.call(t)).done) && (a.push(e.value), a.length !== l); f = !0); } catch (r) { o = !0, n = r; } finally { try { if (!f && null != t.return && (u = t.return(), Object(u) !== u)) return; } finally { if (o) throw n; } } return a; } }
function _arrayWithHoles(r) { if (Array.isArray(r)) return r; }
(function ($, Drupal, window, document, once) {
  Drupal.azSelectMenu = Drupal.azSelectMenu || {};
  Drupal.behaviors.azSelectMenu = {
    attach: function attach(context, settings) {
      var azSelectMenuArr = Object.values(settings.azSelectMenu);

      for (var i = 0; i < azSelectMenuArr.length; i++) {
        var selectFormId = azSelectMenuArr[i];
        var selectForm = document.querySelector("#".concat(selectFormId));
        once('azSelectMenu', selectForm, context).forEach(function (element) {
          $(element).popover();
          element.addEventListener('focus', function (event) {
            Drupal.azSelectMenu.handleEvents(event);
          });
          element.addEventListener('change', function (event) {
            Drupal.azSelectMenu.handleEvents(event);
          });
          element.addEventListener('mouseenter', function (event) {
            Drupal.azSelectMenu.handleEvents(event);
          });
          var button = element.querySelector('button');
          button.addEventListener('click', function (event) {
            Drupal.azSelectMenu.handleEvents(event);
          });
          button.addEventListener('touchstart', function (event) {
            Drupal.azSelectMenu.handleEvents(event);
          });
          button.addEventListener('mouseenter', function (event) {
            Drupal.azSelectMenu.handleEvents(event);
          });
          button.addEventListener('mouseleave', function (event) {
            Drupal.azSelectMenu.handleEvents(event);
          });
          button.addEventListener('focus', function (event) {
            Drupal.azSelectMenu.handleEvents(event);
          });
          button.addEventListener('blur', function (event) {
            Drupal.azSelectMenu.handleEvents(event);
          });
          document.addEventListener('touchstart', function (event) {
            Drupal.azSelectMenu.handleEvents(event);
          });
          element.classList.add('processed');
        });
      }
    }
  };
  Drupal.azSelectMenu.handleEvents = function (event) {
    if (event.type === 'touchstart') {
      if (event.target.classList.contains('js_select_menu_button')) {
        event.stopPropagation();
      } else {
        $('.az-select-menu').popover('hide');
        return;
      }
    }
    var selectForm = event.target.closest('form');
    var $selectForm = $(selectForm);
    var selectElement = selectForm.querySelector('select');
    var _selectElement$select = _slicedToArray(selectElement.selectedOptions, 1),
      optionsSelected = _selectElement$select[0];
    var selectElementHref = optionsSelected.dataset.href;
    var button = selectForm.querySelector('button');
    if (selectElementHref !== '') {
      $selectForm.popover('hide');
      button.classList.remove('disabled');
      button.setAttribute('aria-disabled', 'false');
      switch (event.type) {
        case 'click':
          event.stopImmediatePropagation();
          window.location = selectElementHref;
          break;
        default:
          break;
      }
    } else {
      button.classList.add('disabled');
      button.setAttribute('aria-disabled', 'true');
      selectElement.setAttribute('aria-disabled', 'true');
      switch (event.type) {
        case 'click':
          if (event.target.classList.contains('js_select_menu_button')) {
            $selectForm.popover('show');
            selectElement.focus();
          }
          break;
        case 'focus':
        case 'mouseenter':
          if (event.target.classList.contains('js_select_menu_button')) {
            $selectForm.popover('show');
          } else {
            $selectForm.popover('hide');
          }
          break;
        case 'mouseleave':
          $selectForm.popover('hide');
          break;
        default:
          break;
      }
    }
  };
})(jQuery, Drupal, this, this.document, once);