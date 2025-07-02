/*!
 * jQuery UI Effects Blind 1.13.2
 * http://jqueryui.com
 *
 * Copyright jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 */
!function(e){"use strict";"function"==typeof define&&define.amd?define(["jquery","../version","../effect"],e):e(jQuery)}((function(e){"use strict";return e.effects.define("blind","hide",(function(t,i){var o={up:["bottom","top"],vertical:["bottom","top"],down:["top","bottom"],left:["right","left"],horizontal:["right","left"],right:["left","right"]},n=e(this),c=t.direction||"up",f=n.cssClip(),r={clip:e.extend({},f)},s=e.effects.createPlaceholder(n);r.clip[o[c][0]]=r.clip[o[c][1]],"show"===t.mode&&(n.cssClip(r.clip),s&&s.css(e.effects.clipToBox(r)),r.clip=f),s&&s.animate(e.effects.clipToBox(r),t.duration,t.easing),n.animate(r,{queue:!1,duration:t.duration,easing:t.easing,complete:i})}))}));
//# sourceMappingURL=effect-blind-min.js.map