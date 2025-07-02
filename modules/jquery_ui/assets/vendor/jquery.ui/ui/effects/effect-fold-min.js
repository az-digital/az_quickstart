/*!
 * jQuery UI Effects Fold 1.13.2
 * http://jqueryui.com
 *
 * Copyright jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 */
!function(e){"use strict";"function"==typeof define&&define.amd?define(["jquery","../version","../effect"],e):e(jQuery)}((function(e){"use strict";return e.effects.define("fold","hide",(function(i,t){var c=e(this),n=i.mode,s="show"===n,f="hide"===n,o=i.size||15,a=/([0-9]+)%/.exec(o),u=!!i.horizFirst?["right","bottom"]:["bottom","right"],l=i.duration/2,r=e.effects.createPlaceholder(c),p=c.cssClip(),d={clip:e.extend({},p)},h={clip:e.extend({},p)},m=[p[u[0]],p[u[1]]],g=c.queue().length;a&&(o=parseInt(a[1],10)/100*m[f?0:1]),d.clip[u[0]]=o,h.clip[u[0]]=o,h.clip[u[1]]=0,s&&(c.cssClip(h.clip),r&&r.css(e.effects.clipToBox(h)),h.clip=p),c.queue((function(t){r&&r.animate(e.effects.clipToBox(d),l,i.easing).animate(e.effects.clipToBox(h),l,i.easing),t()})).animate(d,l,i.easing).animate(h,l,i.easing).queue(t),e.effects.unshift(c,g,4)}))}));
//# sourceMappingURL=effect-fold-min.js.map