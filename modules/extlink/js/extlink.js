/**
 * @file
 * External links js file.
 */

((Drupal, drupalSettings) => {
  Drupal.extlink = Drupal.extlink || {};

  Drupal.extlink.attach = (context, drupalSettings) => {
    if (typeof drupalSettings.data === 'undefined' || !drupalSettings.data.hasOwnProperty('extlink')) {
      return;
    }

    // Define the method (either 'append' or 'prepend') of placing the
    // icon, defaults to 'append'.
    let extIconPlacement = 'append';
    if (drupalSettings.data.extlink.extIconPlacement && drupalSettings.data.extlink.extIconPlacement !== '0') {
      extIconPlacement = drupalSettings.data.extlink.extIconPlacement;
    }

    // Strip the host name down, removing ports, subdomains, or www.
    const pattern = /^(([^:]+?\.)*)([^.:]+)((\.[a-z0-9]{1,253})*)(:[0-9]{1,5})?$/;
    const host = window.location.host.replace(pattern, '$2$3$6');
    const subdomain = window.location.host.replace(host, '');

    // Determine what subdomains are considered internal.
    let subdomains;
    if (drupalSettings.data.extlink.extSubdomains) {
      subdomains = '([^/]*\\.)?';
    } else if (subdomain === 'www.' || subdomain === '') {
      subdomains = '(www\\.)?';
    } else {
      subdomains = subdomain.replace('.', '\\.');
    }

    // Whitelisted domains.
    let whitelistedDomains = false;
    if (drupalSettings.data.extlink.whitelistedDomains) {
      whitelistedDomains = [];
      for (let i = 0; i < drupalSettings.data.extlink.whitelistedDomains.length; i++) {
        whitelistedDomains.push(new RegExp(`^https?:\\/\\/${drupalSettings.data.extlink.whitelistedDomains[i].replace(/(\r\n|\n|\r)/gm, '')}.*$`, 'i'));
      }
    }

    // Build regular expressions that define an internal link.
    const internalLink = new RegExp(`^https?://([^@]*@)?${subdomains}${host}`, 'i');

    // Extra internal link matching.
    let extInclude = false;
    if (drupalSettings.data.extlink.extInclude) {
      extInclude = new RegExp(drupalSettings.data.extlink.extInclude.replace(/\\/, '\\'), 'i');
    }

    // Extra external link matching.
    let extExclude = false;
    if (drupalSettings.data.extlink.extExclude) {
      extExclude = new RegExp(drupalSettings.data.extlink.extExclude.replace(/\\/, '\\'), 'i');
    }

    // Extra external link matching for excluding noreferrer.
    let extExcludeNoreferrer = false;
    if (drupalSettings.data.extlink.extExcludeNoreferrer) {
      extExcludeNoreferrer = new RegExp(drupalSettings.data.extlink.extExcludeNoreferrer.replace(/\\/, '\\'), 'i');
    }

    // Extra external link CSS selector exclusion.
    let extCssExclude = false;
    if (drupalSettings.data.extlink.extCssExclude) {
      extCssExclude = drupalSettings.data.extlink.extCssExclude;
    }

    // Extra external link CSS selector inclusion.
    let extCssInclude = false;
    if (drupalSettings.data.extlink.extCssInclude) {
      extCssInclude = drupalSettings.data.extlink.extCssInclude;
    }

    // Extra external link CSS selector explicit.
    let extCssExplicit = false;
    if (drupalSettings.data.extlink.extCssExplicit) {
      extCssExplicit = drupalSettings.data.extlink.extCssExplicit;
    }

    // Find all links which are NOT internal and begin with http as opposed
    // to ftp://, javascript:, etc. other kinds of links.
    // When operating on the 'this' variable, the host has been appended to
    // all links by the browser, even local ones.
    const externalLinks = [];
    const mailtoLinks = [];
    const telLinks = [];
    const extlinks = context.querySelectorAll('a:not([data-extlink]), area:not([data-extlink])');
    extlinks.forEach((el) => {
      try {
        let url = '';
        if (typeof el.href === 'string') {
          url = el.href.toLowerCase();
        }
        // Handle SVG links (xlink:href).
        else if (typeof el.href === 'object') {
          url = el.href.baseVal;
        }
        const isExtCssIncluded = extCssInclude && (el.matches(extCssInclude) || el.closest(extCssInclude));
        if (
          url.indexOf('http') === 0 &&
          ((!internalLink.test(url) && !(extExclude && extExclude.test(url))) || (extInclude && extInclude.test(url)) || isExtCssIncluded) &&
          !(extCssExclude && el.matches(extCssExclude)) &&
          !(extCssExclude && el.closest(extCssExclude)) &&
          !(extCssExplicit && !el.closest(extCssExplicit))
        ) {
          let match = false;
          if (!isExtCssIncluded && whitelistedDomains) {
            for (let i = 0; i < whitelistedDomains.length; i++) {
              if (whitelistedDomains[i].test(url)) {
                match = true;
                break;
              }
            }
          }
          if (!match) {
            externalLinks.push(el);
          }
        }
        // Do not include area tags with begin with mailto: (this prohibits
        // icons from being added to image-maps).
        else if (el.tagName !== 'AREA' && !(extCssExclude && el.closest(extCssExclude)) && !(extCssExplicit && !el.closest(extCssExplicit))) {
          if (url.indexOf('mailto:') === 0) {
            mailtoLinks.push(el);
          } else if (url.indexOf('tel:') === 0) {
            telLinks.push(el);
          }
        }
      } catch (error) {
        // IE7 throws errors often when dealing with irregular links, such as:
        // <a href="node/10"></a> Empty tags.
        // <a href="http://user:pass@example.com">example</a> User:pass syntax.
        return false;
      }
    });

    const hasExtIcon = drupalSettings.data.extlink.extClass !== '0' && drupalSettings.data.extlink.extClass !== '';
    const hasAdditionalExtClasses = drupalSettings.data.extlink.extAdditionalLinkClasses !== '';
    Drupal.extlink.applyClassAndSpan(externalLinks, 'ext', hasExtIcon ? extIconPlacement : null);
    if (hasAdditionalExtClasses) {
      Drupal.extlink.applyClassAndSpan(externalLinks, drupalSettings.data.extlink.extAdditionalLinkClasses, null);
    }

    const hasMailtoClass = drupalSettings.data.extlink.mailtoClass !== '0' && drupalSettings.data.extlink.mailtoClass !== '';
    const hasAdditionalMailtoClasses = drupalSettings.data.extlink.extAdditionalMailtoClasses !== '';
    if (hasMailtoClass) {
      Drupal.extlink.applyClassAndSpan(mailtoLinks, drupalSettings.data.extlink.mailtoClass, extIconPlacement);
    }
    if (hasAdditionalMailtoClasses) {
      Drupal.extlink.applyClassAndSpan(mailtoLinks, drupalSettings.data.extlink.extAdditionalMailtoClasses, null);
    }

    const hasTelClass = drupalSettings.data.extlink.telClass !== '0' && drupalSettings.data.extlink.telClass !== '';
    const hasAdditionalTelClasses = drupalSettings.data.extlink.extAdditionalTelClasses !== '0' && drupalSettings.data.extlink.extAdditionalTelClasses !== '';
    if (hasTelClass) {
      Drupal.extlink.applyClassAndSpan(telLinks, drupalSettings.data.extlink.telClass, extIconPlacement);
    }
    if (hasAdditionalTelClasses) {
      Drupal.extlink.applyClassAndSpan(mailtoLinks, drupalSettings.data.extlink.extAdditionalTelClasses, null);
    }

    if (drupalSettings.data.extlink.extTarget) {
      // Add target attr to open link in a new tab if not set.
      externalLinks.forEach((link, i) => {
        if (!(drupalSettings.data.extlink.extTargetNoOverride && link.matches('a[target]'))) {
          externalLinks[i].setAttribute('target', '_blank');
        }
      });

      // Add noopener rel attribute to combat phishing.
      externalLinks.forEach((link, i) => {
        const val = link.getAttribute('rel');
        // If no rel attribute is present, create one with the value noopener.
        if (val === null || typeof val === 'undefined') {
          externalLinks[i].setAttribute('rel', 'noopener');
          return;
        }
        // Check to see if rel contains noopener. Add what doesn't exist.
        if (val.indexOf('noopener') > -1) {
          if (val.indexOf('noopener') === -1) {
            // Add noopener.
            externalLinks[i].setAttribute('rel', `${val} noopener`);
          } else {
            // Noopener exists. Nothing needs to be added.
          }
        }
        // Else, append noopener to val.
        else {
          // Add noopener.
          externalLinks[i].setAttribute('rel', `${val} noopener`);
        }
      });
    }

    if (drupalSettings.data.extlink.extNofollow) {
      externalLinks.forEach((link, i) => {
        const val = link.getAttribute('rel');
        // When the link does not have a rel attribute set it to 'nofollow'.
        if (val === null || typeof val === 'undefined') {
          externalLinks[i].setAttribute('rel', 'nofollow');
          return;
        }
        let target = 'nofollow';
        // Change the target, if not overriding follow.
        if (drupalSettings.data.extlink.extFollowNoOverride) {
          target = 'follow';
        }
        if (val.indexOf(target) === -1) {
          // Add nofollow.
          externalLinks[i].setAttribute('rel', `${val} nofollow`);
        }
      });
    }

    if (drupalSettings.data.extlink.extTitleNoOverride === false) {
      // Set the title attribute of all external links that opens in a new window.
      externalLinks.forEach((link, i) => {
        const oldTitle = link.getAttribute('title');

        // Determine new title based on drupalSettings extTarget configuration.
        let newTitle = drupalSettings.data.extlink.extTarget ? drupalSettings.data.extlink.extTargetAppendNewWindowLabel : '';
        if (oldTitle !== null) {
          if (Drupal.extlink.hasNewWindowText(oldTitle)) {
            return;
          }
          newTitle = Drupal.extlink.combineLabels(oldTitle, newTitle);
        }
        if (newTitle) {
          externalLinks[i].setAttribute('title', newTitle);
        }
      });
    }

    if (drupalSettings.data.extlink.extNoreferrer) {
      externalLinks.forEach((link, i) => {
        // Don't add 'noreferrer' if exclude noreferrer option is set and the
        // link matches the entered pattern to exclude 'noreferrer' tag.
        if (drupalSettings.data.extlink.extExcludeNoreferrer && extExcludeNoreferrer.test(link.getAttribute('href'))) {
          return;
        }

        const val = link.getAttribute('rel');
        // When the link does not have a rel attribute set it to 'noreferrer'.
        if (val === null || typeof val === 'undefined') {
          externalLinks[i].setAttribute('rel', 'noreferrer');
          return;
        }
        // Add noreferrer.
        externalLinks[i].setAttribute('rel', `${val} noreferrer`);
      });
    }

    /* eslint:disable:no-empty */
    Drupal.extlink = Drupal.extlink || {};

    // Set up default click function for the external links popup. This should be
    // overridden by modules wanting to alter the popup.
    Drupal.extlink.popupClickHandler =
      Drupal.extlink.popupClickHandler ||
      (() => {
        if (drupalSettings.data.extlink.extAlert) {
          // eslint-disable-next-line no-restricted-globals
          return confirm(drupalSettings.data.extlink.extAlertText);
        }
      });

    const _that = this;
    Drupal.extlink.handleClick = function (event) {
      const shouldNavigate = Drupal.extlink.popupClickHandler.call(_that, event);
      if (typeof shouldNavigate !== 'undefined' && !shouldNavigate) {
        // Prevent navigation if the user clicks "Cancel".
        event.preventDefault();
      }
    };

    externalLinks.forEach((val, i) => {
      externalLinks[i].removeEventListener('click', Drupal.extlink.handleClick);
      externalLinks[i].addEventListener('click', Drupal.extlink.handleClick);
    });
  };

  /**
   * Check if the label already has 'new window' text.
   *
   *
   * @returns boolean
   * @param label
   */
  Drupal.extlink.hasNewWindowText = function (label) {
    return label.toLowerCase().indexOf(Drupal.t('new window')) !== -1;
  };

  /**
   * Combine two labels.
   *
   * Combine labels in a readable manner, taking into account if the label
   * uses parenthesis or not. For examples,
   *
   *   1. "A" + "B" => "A, B"
   *   2. "A" + "(B)" => "A (B)"
   *   3. "(A)" + "B" => "B (A)"
   *   4. "(A)" + "(B)" => "(A, B)"
   *
   *
   * @returns string
   * @param labelA
   * @param labelB
   */
  Drupal.extlink.combineLabels = function (labelA, labelB) {
    labelA = labelA || '';
    labelB = labelB || '';
    const labelANoParens = labelA.trim().replace('(', '').replace(')', '');
    const labelBNoParens = labelB.trim().replace('(', '').replace(')', '');
    if (labelA === labelANoParens) {
      if (labelB === labelBNoParens) {
        // This is Example 1 above: "A" + "B" => "A, B"
        return `${labelA}, ${labelB}`;
      }

      // This is Example 2 above: "A" + "(B)" => "A (B)"
      return `${labelA} ${labelB}`;
    }

    if (labelB === labelBNoParens) {
      // This is Example 3 above: "(A)" + "B" => "B (A)"
      return `${labelB} ${labelA}`;
    }

    // This is Example 4 above: "(A)" + "(B)" => "(A, B)"
    return `(${labelANoParens}, ${labelBNoParens})`;
  };

  /**
   * Apply a class and a trailing <span> to all links not containing images.
   *
   * @param {object[]} links
   *   An array of DOM elements representing the links.
   * @param {string} className
   *   The class to apply to the links.
   * @param {string} iconPlacement
   *   'append' or 'prepend' the icon to the link.
   */
  Drupal.extlink.applyClassAndSpan = (links, className, iconPlacement) => {
    let linksToProcess;
    if (drupalSettings.data.extlink.extImgClass) {
      linksToProcess = links;
    } else {
      // Only text links.
      linksToProcess = links.filter((link) => {
        return link.querySelector('img, svg') === null;
      });
    }

    for (let i = 0; i < linksToProcess.length; i++) {
      if (className !== '0') {
        linksToProcess[i].classList.add(className);
      }

      // Additional classes:
      if (className === drupalSettings.data.extlink.mailtoClass && drupalSettings.data.extlink.extAdditionalMailtoClasses) {
        // Is mail link:
        linksToProcess[i].classList.add(drupalSettings.data.extlink.extAdditionalMailtoClasses);
      } else if (className === drupalSettings.data.extlink.telClass && drupalSettings.data.extlink.extAdditionalTelClasses) {
        // Is regular external link:
        linksToProcess[i].classList.add(drupalSettings.data.extlink.extAdditionalTelClasses);
      } else if (drupalSettings.data.extlink.extAdditionalLinkClasses) {
        // Is regular external link:
        linksToProcess[i].classList.add(drupalSettings.data.extlink.extAdditionalLinkClasses);
      }

      // Add data-extlink attribute.
      linksToProcess[i].setAttribute('data-extlink', '');

      // Add icon.
      if (iconPlacement) {
        let link = linksToProcess[i];

        // Prevent appended icons from wrapping lines.
        if (drupalSettings.data.extlink.extPreventOrphan && iconPlacement === 'append') {
          // Find the last word in the link.
          let lastTextNode = link.lastChild;
          let trailingWhitespace = null;
          let parentNode = link;
          while (lastTextNode) {
            if (lastTextNode.lastChild) {
              // Last node was not text; step down into child node.
              parentNode = lastTextNode;
              lastTextNode = lastTextNode.lastChild;
            } else if (lastTextNode.nodeName === '#text' && parentNode.lastElementChild && lastTextNode.textContent.trim().length === 0) {
              // Last node was text, but it was whitespace. Step back into previous node.
              trailingWhitespace = lastTextNode;
              parentNode = parentNode.lastElementChild;
              lastTextNode = parentNode.lastChild;
            } else {
              // Last node was null or valid text.
              break;
            }
          }
          if (lastTextNode && lastTextNode.nodeName === '#text' && lastTextNode.textContent.length > 0) {
            const lastText = lastTextNode.textContent;
            const lastWordRegex = new RegExp(/\S+\s*$/, 'g');
            const lastWord = lastText.match(lastWordRegex);
            if (lastWord !== null) {
              // Wrap the last word in a span.
              const breakPreventer = document.createElement('span');
              breakPreventer.classList.add('extlink-nobreak');
              breakPreventer.textContent = lastWord[0];
              if (trailingWhitespace) {
                trailingWhitespace.textContent = '';
                breakPreventer.append(trailingWhitespace.textContent);
              }
              lastTextNode.textContent = lastText.substring(0, lastText.length - lastWord[0].length);
              lastTextNode.parentNode.append(breakPreventer);
              // Insert the icon into the span rather than the link.
              link = breakPreventer;
            }
          }
        }

        // Create an icon element.
        let iconElement;
        if (drupalSettings.data.extlink.extUseFontAwesome) {
          iconElement = document.createElement('span');
          iconElement.setAttribute('class', `fa-${className} extlink`);
          if (className === drupalSettings.data.extlink.mailtoClass) {
            if (drupalSettings.data.extlink.mailtoLabel) {
              link.ariaLabel = drupalSettings.data.extlink.mailtoLabel;
            }
            iconElement.innerHTML = Drupal.theme('extlink_fa_mailto', drupalSettings, iconPlacement);
          } else if (className === drupalSettings.data.extlink.extClass) {
            if (drupalSettings.data.extlink.extLabel) {
              link.ariaLabel = drupalSettings.data.extlink.extLabel;
            }
            iconElement.innerHTML = Drupal.theme('extlink_fa_extlink', drupalSettings, iconPlacement);
          } else if (className === drupalSettings.data.extlink.telClass) {
            if (drupalSettings.data.extlink.telLabel) {
              link.ariaLabel = drupalSettings.data.extlink.telLabel;
            }
            iconElement.innerHTML = Drupal.theme('extlink_fa_tel', drupalSettings, iconPlacement);
          }
        } else {
          iconElement = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
          iconElement.setAttribute('focusable', 'false');
          iconElement.classList.add(className);
          iconElement.setAttribute('data-extlink-placement', iconPlacement);
          if (className === drupalSettings.data.extlink.mailtoClass) {
            iconElement = Drupal.theme('extlink_mailto', iconElement, drupalSettings);
          } else if (className === drupalSettings.data.extlink.extClass) {
            iconElement = Drupal.theme('extlink_extlink', iconElement, drupalSettings);
          } else if (className === drupalSettings.data.extlink.telClass) {
            iconElement = Drupal.theme('extlink_tel', iconElement, drupalSettings);
          }
        }
        iconElement.setAttribute('role', 'img');
        iconElement.setAttribute('aria-hidden', drupalSettings.data.extlink.extHideIcons);
        link[iconPlacement](iconElement);
      }
    }
  };

  /**
   * Theme function for a Font Awesome mailto icon.
   *
   * @param {object} drupalSettings
   *   Settings object used to construct the markup.
   * @param {string} iconPlacement
   *   The class to apply to a link.
   *
   * @return {string}
   *   HTML string of the Font AweSome mailto link icon.
   */
  Drupal.theme.extlink_fa_mailto = function (drupalSettings, iconPlacement) {
    return `<span class="${drupalSettings.data.extlink.extFaMailtoClasses}" data-extlink-placement="${iconPlacement}"></span>`;
  };

  /**
   * Theme function for a Font Awesome external link icon.
   *
   * @param {object} drupalSettings
   *   Settings object used to construct the markup.
   * @param {string} iconPlacement
   *   The class to apply to a link.
   *
   * @return {string}
   *   HTML string of the Font AweSome external link icon.
   */
  Drupal.theme.extlink_fa_extlink = function (drupalSettings, iconPlacement) {
    return `<span class="${drupalSettings.data.extlink.extFaLinkClasses}" data-extlink-placement="${iconPlacement}"></span>`;
  };

  /**
   * Theme function for a Font Awesome telephone icon.
   *
   * @param {object} drupalSettings
   *   Settings object used to construct the markup.
   * @param {string} iconPlacement
   *   The class to apply to a link.
   *
   * @return {string}
   *   HTML string of the Font AweSome telephone icon.
   */
  Drupal.theme.extlink_fa_tel = function (drupalSettings, iconPlacement) {
    return `<span class="${drupalSettings.data.extlink.extFaLinkClasses}" data-extlink-placement="${iconPlacement}"></span>`;
  };

  /**
   * Theme function for a mailto icon.
   *
   * @param {object} iconElement
   *   The current iconElement being altered.
   * @param {object} drupalSettings
   *   Settings object used to construct the markup.
   *
   * @return {object}
   *   The altered iconElement.
   */
  Drupal.theme.extlink_mailto = function (iconElement, drupalSettings) {
    iconElement.setAttribute('aria-label', drupalSettings.data.extlink.mailtoLabel);
    iconElement.setAttribute('viewBox', '0 10 70 20');
    iconElement.innerHTML = `<title>${drupalSettings.data.extlink.mailtoLabel}</title><path d="M56 14H8c-1.1 0-2 0.9-2 2v32c0 1.1 0.9 2 2 2h48c1.1 0 2-0.9 2-2V16C58 14.9 57.1 14 56 14zM50.5 18L32 33.4 13.5 18H50.5zM10 46V20.3l20.7 17.3C31.1 37.8 31.5 38 32 38s0.9-0.2 1.3-0.5L54 20.3V46H10z"/>`;
    return iconElement;
  };

  /**
   * Theme function for an external link icon.
   *
   * @param {object} iconElement
   *   The current iconElement being altered.
   * @param {object} drupalSettings
   *   Settings object used to construct the markup.
   *
   * @return {object}
   *   The altered iconElement.
   */
  Drupal.theme.extlink_extlink = function (iconElement, drupalSettings) {
    iconElement.setAttribute('aria-label', drupalSettings.data.extlink.extLabel);
    iconElement.setAttribute('viewBox', '0 0 80 40');
    iconElement.innerHTML = `<title>${drupalSettings.data.extlink.extLabel}</title><path d="M48 26c-1.1 0-2 0.9-2 2v26H10V18h26c1.1 0 2-0.9 2-2s-0.9-2-2-2H8c-1.1 0-2 0.9-2 2v40c0 1.1 0.9 2 2 2h40c1.1 0 2-0.9 2-2V28C50 26.9 49.1 26 48 26z"/><path d="M56 6H44c-1.1 0-2 0.9-2 2s0.9 2 2 2h7.2L30.6 30.6c-0.8 0.8-0.8 2 0 2.8C31 33.8 31.5 34 32 34s1-0.2 1.4-0.6L54 12.8V20c0 1.1 0.9 2 2 2s2-0.9 2-2V8C58 6.9 57.1 6 56 6z"/>`;
    return iconElement;
  };

  /**
   * Theme function for an external telephone icon.
   *
   * @param {object} iconElement
   *   The current iconElement being altered.
   * @param {object} drupalSettings
   *   Settings object used to construct the markup.
   *
   * @return {object}
   *   The altered iconElement.
   */
  Drupal.theme.extlink_tel = function (iconElement, drupalSettings) {
    iconElement.setAttribute('aria-label', drupalSettings.data.extlink.telLabel);
    iconElement.setAttribute('viewBox', '0 0 181.352 181.352');
    iconElement.innerHTML = `<title>${drupalSettings.data.extlink.telLabel}</title><path xmlns="http://www.w3.org/2000/svg" d="M169.393,167.37l-14.919,9.848c-9.604,6.614-50.531,14.049-106.211-53.404C-5.415,58.873,9.934,22.86,17.134,14.555L29.523,1.678c2.921-2.491,7.328-2.198,9.839,0.811l32.583,38.543l0.02,0.02c2.384,2.824,2.306,7.22-0.83,9.868v0.029l-14.44,10.415c-5.716,5.667-0.733,14.587,5.11,23.204l27.786,32.808c12.926,12.477,20.009,18.241,26.194,14.118l12.008-13.395c2.941-2.472,7.328-2.169,9.839,0.821l32.603,38.543v0.02C172.607,160.316,172.519,164.703,169.393,167.37z"/>`;
    return iconElement;
  };

  Drupal.behaviors.extlink = Drupal.behaviors.extlink || {};
  Drupal.behaviors.extlink.attach = (context, drupalSettings) => {
    // Backwards compatibility, for the benefit of modules overriding extlink
    // functionality by defining an "extlinkAttach" global function.
    if (typeof extlinkAttach === 'function') {
      // eslint-disable-next-line no-undef
      extlinkAttach(context);
    } else {
      Drupal.extlink.attach(context, drupalSettings);
    }
  };
})(Drupal, drupalSettings);
