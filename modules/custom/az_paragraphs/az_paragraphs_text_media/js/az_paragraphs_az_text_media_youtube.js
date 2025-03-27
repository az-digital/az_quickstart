/**
* DO NOT EDIT THIS FILE.
* See the following change record for more information,
* https://www.drupal.org/node/2815083
* @preserve
**/
(function (Drupal, once) {
  Drupal.behaviors.az_youtube_video_bg = {
    attach: function attach(context) {
      function initYouTubeBackgrounds() {
        if (window.screen && window.screen.width > 768) {
          var defaultSettings = {
            loop: true,
            mute: true,
            pauseButtonClass: 'az-video-pause',
            playButtonClass: 'az-video-play',
            ratio: 16 / 9,
            width: document.documentElement.clientWidth
          };
          var bgVideoSettings = {};
          var tag = document.createElement('script');
          var firstScriptTag = document.getElementsByTagName('script')[0];
          tag.src = 'https://www.youtube.com/iframe_api';
          firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);
          var bgVideoParagraphs = document.getElementsByClassName('az-js-video-background');
          window.onYouTubeIframeAPIReady = function () {
            Array.from(bgVideoParagraphs).forEach(function (element) {
              var parentParagraph = document.getElementById(element.dataset.parentid);
              var youtubeId = element.dataset.youtubeid;
              bgVideoSettings[youtubeId] = {
                autoplay: element.dataset.autoplay === 'true',
                start: element.dataset.start
              };
              var videoPlayer = element.getElementsByClassName('az-video-player')[0];
              var youTubePlayer = window.YT;
              element.player = new youTubePlayer.Player(videoPlayer, {
                width: defaultSettings.width,
                height: Math.ceil(defaultSettings.width / defaultSettings.ratio),
                videoId: youtubeId,
                playerVars: {
                  controls: 0,
                  enablejsapi: 1,
                  origin: window.location.origin,
                  rel: 0
                },
                events: {
                  onReady: window.onPlayerReady,
                  onStateChange: window.onPlayerStateChange
                }
              });
              var playButton = element.getElementsByClassName('az-video-play')[0];
              playButton.addEventListener('click', function (event) {
                event.preventDefault();
                element.player.playVideo();
                parentParagraph.classList.remove('az-video-paused');
                parentParagraph.classList.add('az-video-playing');
              });
              var pauseButton = element.getElementsByClassName('az-video-pause')[0];
              pauseButton.addEventListener('click', function (event) {
                event.preventDefault();
                element.player.pauseVideo();
                parentParagraph.classList.remove('az-video-playing');
                parentParagraph.classList.add('az-video-paused');
              });
            });
          };
          var setDimensions = function setDimensions(container) {
            container.style.height = "".concat(container.parentNode.offsetHeight, "px");
            if (container.dataset.style === 'bottom') {
              container.style.top = 0;
            }
            var thisPlayer = container.getElementsByClassName('az-video-player')[0];
            if (thisPlayer === null) {
              return;
            }
            thisPlayer.style.zIndex = -100;
            var width = container.offsetWidth;
            var height = container.offsetHeight;
            var pWidth = Math.ceil(height * defaultSettings.ratio);
            var pHeight = Math.ceil(width / defaultSettings.ratio);
            var widthMinuspWidthDividedByTwo = (width - pWidth) / 2;
            widthMinuspWidthDividedByTwo = "".concat(widthMinuspWidthDividedByTwo.toString(), "px");
            var pHeightRatio = "".concat((height - pHeight) / 2, "px");
            if (width / defaultSettings.ratio < height) {
              thisPlayer.width = pWidth;
              thisPlayer.height = height;
              thisPlayer.style.left = widthMinuspWidthDividedByTwo;
              thisPlayer.style.top = 0;
            } else {
              thisPlayer.height = pHeight;
              thisPlayer.width = width;
              thisPlayer.style.top = pHeightRatio;
              thisPlayer.style.left = 0;
            }
          };
          var resize = function resize() {
            Array.from(bgVideoParagraphs).forEach(function (element) {
              setDimensions(element);
            });
          };
          window.onPlayerReady = function (event) {
            var id = event.target.options.videoId;
            if (!bgVideoSettings[id].autoplay) {
              return;
            }
            if (defaultSettings.mute) {
              event.target.mute();
            }
            event.target.seekTo(bgVideoSettings[id].start);
            event.target.playVideo();
            dispatchEvent(new Event('azVideoPlay'));
          };
          window.onPlayerStateChange = function (event) {
            var id = event.target.options.videoId;
            var stateChangeContainer = document.getElementById("".concat(id, "-bg-video-container"));
            var parentContainer = document.getElementById(stateChangeContainer.dataset.parentid);
            if (event.data === 0 && defaultSettings.loop) {
              stateChangeContainer.player.seekTo(bgVideoSettings[id].start);
            }
            if (event.data === 1) {
              resize();
              parentContainer.classList.add('az-video-playing');
              parentContainer.classList.remove('az-video-loading');
            }
          };
          window.addEventListener('load', function () {
            resize();
          });
          window.addEventListener('resize', function () {
            resize();
          });
        }
      }
      once('youTubeTextOnMedia-init', 'body').forEach(initYouTubeBackgrounds, context);
    }
  };
})(Drupal, once);