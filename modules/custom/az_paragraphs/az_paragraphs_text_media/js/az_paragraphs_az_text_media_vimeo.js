/**
* DO NOT EDIT THIS FILE.
* See the following change record for more information,
* https://www.drupal.org/node/2815083
* @preserve
**/
(function ($, Drupal) {
  Drupal.behaviors.az_vimeo_video_bg = {
    attach: function attach(context, settings) {
      if (window.screen && window.screen.width > 768) {
        var defaults = {
          ratio: 16 / 9,
          vimeoId: "",
          mute: true,
          repeat: true,
          width: $(window).width(),
          playButtonClass: "az-video-play",
          pauseButtonClass: "az-video-pause",
          start: 0,
          minimumSupportedWidth: 600
        };
        var bgVideos = settings.azFieldsMedia.bgVideos;
        var bgVideoParagraphs = document.getElementsByClassName("az-js-video-background");
        var tag = document.createElement("script");
        tag.src = "https://player.vimeo.com/api/player.js";
        document.head.appendChild(tag);
        $.each(bgVideoParagraphs, function (index) {
          var thisContainer = bgVideoParagraphs[index];
          var parentParagraph = thisContainer.parentNode;
          var vimeoId = thisContainer.dataset.vimeoId;
          bgVideos[vimeoId] = $.extend({}, defaults, thisContainer);
          var options = bgVideos[vimeoId];
          var videoPlayer = thisContainer.getElementsByClassName("az-video-player")[0];
          var VimeoPlayer = window.Vimeo.Player;
          thisContainer.player = new VimeoPlayer(videoPlayer, {
            id: vimeoId,
            width: options.width,
            height: Math.ceil(options.width / options.ratio),
            autoplay: true,
            muted: options.mute,
            loop: options.repeat
          });
          thisContainer.player.on("play", function () {
            parentParagraph.classList.add("az-video-playing");
            parentParagraph.classList.remove("az-video-paused");
          });
          thisContainer.player.on("pause", function () {
            parentParagraph.classList.add("az-video-paused");
            parentParagraph.classList.remove("az-video-playing");
          });
          thisContainer.player.on("ended", function () {
            if (options.repeat) {
              thisContainer.player.setCurrentTime(0).then(function () {
                thisContainer.player.play();
              });
            }
          });
          var playButton = bgVideoParagraphs[index].getElementsByClassName("az-video-play")[0];
          playButton.addEventListener("click", function (event) {
            event.preventDefault();
            bgVideoParagraphs[index].player.play();
          });
          var pauseButton = bgVideoParagraphs[index].getElementsByClassName("az-video-pause")[0];
          pauseButton.addEventListener("click", function (event) {
            event.preventDefault();
            bgVideoParagraphs[index].player.pause();
          });
        });
        var resize = function resize() {
          $.each(bgVideoParagraphs, function (index) {
            var thisContainer = bgVideoParagraphs[index];
            var videoPlayer = thisContainer.getElementsByClassName("az-video-player")[0];
            var ratio = bgVideos[vimeoId].ratio;
            var width = thisContainer.offsetWidth;
            var height = Math.ceil(width / ratio);
            videoPlayer.style.width = "".concat(width, "px");
            videoPlayer.style.height = "".concat(height, "px");
          });
        };
        $(window).on("load", function () {
          resize();
        });
        $(window).on("resize.bgVideo", function () {
          resize();
        });
      }
    }
  };
})(jQuery, Drupal, drupalSettings);