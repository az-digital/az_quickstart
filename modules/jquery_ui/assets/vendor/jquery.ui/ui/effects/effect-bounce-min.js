/*!
 * jQuery UI Effects Bounce 1.13.2
 * http://jqueryui.com
 *
 * Copyright jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 */
!function(e){"use strict";"function"==typeof define&&define.amd?define(["jquery","../version","../effect"],e):e(jQuery)}((function(e){"use strict";return e.effects.define("bounce",(function(t,i){var n,o,c,f=e(this),u=t.mode,s="hide"===u,a="show"===u,r=t.direction||"up",d=t.distance,p=t.times||5,h=2*p+(a||s?1:0),m=t.duration/h,y=t.easing,l="up"===r||"down"===r?"top":"left",g="up"===r||"left"===r,q=0,w=f.queue().length;for(e.effects.createPlaceholder(f),c=f.css(l),d||(d=f["top"===l?"outerHeight":"outerWidth"]()/3),a&&((o={opacity:1})[l]=c,f.css("opacity",0).css(l,g?2*-d:2*d).animate(o,m,y)),s&&(d/=Math.pow(2,p-1)),(o={})[l]=c;q<p;q++)(n={})[l]=(g?"-=":"+=")+d,f.animate(n,m,y).animate(o,m,y),d=s?2*d:d/2;s&&((n={opacity:0})[l]=(g?"-=":"+=")+d,f.animate(n,m,y)),f.queue(i),e.effects.unshift(f,w,h+1)}))}));
//# sourceMappingURL=effect-bounce-min.js.map