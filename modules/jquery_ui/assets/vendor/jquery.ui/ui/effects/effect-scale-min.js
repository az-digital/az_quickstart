/*!
 * jQuery UI Effects Scale 1.13.2
 * http://jqueryui.com
 *
 * Copyright jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 */
!function(e){"use strict";"function"==typeof define&&define.amd?define(["jquery","../version","../effect","./effect-size"],e):e(jQuery)}((function(e){"use strict";return e.effects.define("scale",(function(t,f){var i=e(this),n=t.mode,c=parseInt(t.percent,10)||(0===parseInt(t.percent,10)||"effect"!==n?0:100),s=e.extend(!0,{from:e.effects.scaledDimensions(i),to:e.effects.scaledDimensions(i,c,t.direction||"both"),origin:t.origin||["middle","center"]},t);t.fade&&(s.from.opacity=1,s.to.opacity=0),e.effects.effect.size.call(this,s,f)}))}));
//# sourceMappingURL=effect-scale-min.js.map