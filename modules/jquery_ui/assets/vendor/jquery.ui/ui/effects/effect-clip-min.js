/*!
 * jQuery UI Effects Clip 1.13.2
 * http://jqueryui.com
 *
 * Copyright jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 */
!function(t){"use strict";"function"==typeof define&&define.amd?define(["jquery","../version","../effect"],t):t(jQuery)}((function(t){"use strict";return t.effects.define("clip","hide",(function(e,i){var o,c={},n=t(this),r=e.direction||"vertical",f="both"===r,l=f||"horizontal"===r,s=f||"vertical"===r;o=n.cssClip(),c.clip={top:s?(o.bottom-o.top)/2:o.top,right:l?(o.right-o.left)/2:o.right,bottom:s?(o.bottom-o.top)/2:o.bottom,left:l?(o.right-o.left)/2:o.left},t.effects.createPlaceholder(n),"show"===e.mode&&(n.cssClip(c.clip),c.clip=o),n.animate(c,{queue:!1,duration:e.duration,easing:e.easing,complete:i})}))}));
//# sourceMappingURL=effect-clip-min.js.map