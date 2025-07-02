/**
 * @file
 * Provides a minimal sanitizer.
 *
 * @internal
 *   This is an internal part of the Blazy system and should only be used by
 *   blazy-related code in Blazy module, or its sub-modules.
 *
 * @todo use native Sanitizer API when ready:
 * @see https://caniuse.com/?search=sanitizer-api
 * @see https://web.dev/sanitizer/
 * @see https://developer.mozilla.org/en-US/docs/Web/API/Sanitizer
 * @see https://developer.mozilla.org/en-US/docs/Web/API/HTML_Sanitizer_API
 * @see https://en.wikipedia.org/wiki/Cross-site_scripting
 * @see https://github.com/cure53/DOMPurify
 */

(function ($, _win, _doc) {

  'use strict';

  /**
   * Sanitize an HTML string.
   *
   * A minimal DOMPurify for semi-trusted Drupal UI/ code outputs. The rest
   * should be taken care of server-side.
   *
   * @private
   *
   * @author 2021 Chris Ferdinandi
   * @link https://vanillajstoolkit.com/helpers/cleanhtml/
   *
   * @param {String} str
   *   The HTML string to sanitize.
   * @param {Object|null} config
   *   The DOMPurify/ Sanitizer API config, if available.
   * @param {Boolean} nodes
   *   If true, returns HTML nodes instead of a string.
   *
   * @return {String|NodeList}
   *   The sanitized string or nodes.
   */
  function sanitize(str, config, nodes) {
    // Save for extra checks.
    if (!str) {
      return '';
    }

    /**
     * Remove potentially dangerous attributes from an element.
     *
     * @param {Node} el
     *   The element.
     */
    function removeAttributes(el) {
      var attrs = $.nodeMapAttr(el.attributes);
      $.each(attrs, function (value, name) {
        if (!isDangerous(name, value)) {
          return false;
        }

        el.removeAttribute(name);
      });
    }

    /**
     * Remove dangerous stuff from the HTML document's nodes.
     *
     * @param {Node} html
     *   The HTML document.
     */
    function clean(html) {
      var children = html.children;

      $.each(children, function (node) {
        removeAttributes(node);
        clean(node);
      });
    }

    // Convert the string to HTML.
    var html;

    // Sanitize it.
    // @todo use native Sanitizer API as the first check when ready.
    if (typeof DOMPurify !== 'undefined') {
      var check = DOMPurify.sanitize(str, config);
      if ($.isObj(config) && config.RETURN_DOM) {
        nodes = true;
        html = check;
      }
      else {
        html = toNode(check);
      }
    }
    else {
      html = toNode(str);
      clean(html);
    }

    // If the user wants HTML nodes back, return them.
    // Otherwise, pass a sanitized string back.
    return nodes ? html.childNodes : html.innerHTML;
  }

  /**
   * Check if the attribute is potentially dangerous.
   *
   * @param {String} name
   *   The attribute name.
   * @param {String} value
   *   The attribute value.
   *
   * @return {Boolean}
   *   If true, the attribute is potentially dangerous.
   */
  function isDangerous(name, value) {
    var key = name.toLowerCase();
    var val = value.replace(/\s+/g, '').toLowerCase();
    if (['src', 'href', 'xlink:href'].includes(key)) {
      // See https://github.com/eslint/eslint/issues/2530
      if (val.includes('script:') || val.includes('data:text/html')) { // eslint-disable-line
        return true;
      }
    }
    return key.startsWith('on');
  }

  /**
   * Convert the string to an HTML document.
   *
   * @param {String} str
   *   The string to convert.
   *
   * @return {Node}
   *   An HTML document.
   */
  function toNode(str) {
    var parser = new DOMParser();
    var doc = parser.parseFromString(str, 'text/html');
    return doc.body || _doc.createElement('body');
  }

  $.create = function (tagName, attrs, html) {
    var el = _doc.createElement(tagName);

    if ($.isStr(attrs) || $.isObj(attrs)) {
      if ($.isStr(attrs)) {
        el.className = attrs;
      }
      else {
        $.attr(el, attrs);
      }
    }

    if (html) {
      html = html.trim();

      // @todo use el.setHTML(html) when Sanitizer API is available.
      // Cannot blindly use .setHTML yet without knowing its behaviors.
      el.innerHTML = sanitize(html);
      if (tagName === 'template') {
        el = el.content.firstChild || el;
      }
    }

    return el;
  };

  $.sanitizer = {
    isDangerous: isDangerous,
    sanitize: sanitize,
    toNode: toNode
  };

  // @todo deprecated and removed for $.sanitizer object.
  $.sanitize = sanitize;

})(dBlazy, this, this.document);
