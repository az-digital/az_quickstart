/*!
 * jQuery UI Effects Drop 1.13.2
 * http://jqueryui.com
 *
 * Copyright jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 */
!function(e){"use strict";"function"==typeof define&&define.amd?define(["jquery","../version","../effect"],e):e(jQuery)}((function(e){"use strict";return e.effects.define("drop","hide",(function(t,i){var n,o=e(this),f="show"===t.mode,c=t.direction||"left",u="up"===c||"down"===c?"top":"left",r="up"===c||"left"===c?"-=":"+=",d="+="===r?"-=":"+=",s={opacity:0};e.effects.createPlaceholder(o),n=t.distance||o["top"===u?"outerHeight":"outerWidth"](!0)/2,s[u]=r+n,f&&(o.css(s),s[u]=d+n,s.opacity=1),o.animate(s,{queue:!1,duration:t.duration,easing:t.easing,complete:i})}))}));
//# sourceMappingURL=effect-drop-min.js.map