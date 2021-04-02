/**
* DO NOT EDIT THIS FILE.
* See the following change record for more information,
* https://www.drupal.org/node/2815083
* @preserve
**/

(function ($, Drupal) {
  Drupal.behaviors.az_youtube_video_bg = {
    attach: function attach(context, settings) {
      if (window.screen && window.screen.width > 768) {
        var defaults = {
          ratio: 16 / 9,
          videoId: "",
          mute: true,
          repeat: true,
          width: $(window).width(),
          playButtonClass: "az-video-play",
          pauseButtonClass: "az-video-pause",
          muteButtonClass: "az-video-mute",
          volumeUpClass: "az-video-volume-up",
          volumeDownClass: "az-video-volume-down",
          increaseVolumeBy: 10,
          start: 0,
          minimumSupportedWidth: 600
        };
        var bgVideos = settings.azFieldsMedia.bgVideos;
        var BgVideoParagraphs = document.getElementsByClassName("az-js-video-background");
        var tag = document.createElement("script");
        var firstScriptTag = document.getElementsByTagName("script")[0];
        tag.src = "https://www.youtube.com/iframe_api";
        firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);

        window.onYouTubeIframeAPIReady = function () {
          $.each(BgVideoParagraphs, function (i, paragraph) {
            var youtubeId = BgVideoParagraphs[i].dataset.youtubeid;
            bgVideos[youtubeId] = $.extend({}, defaults, paragraph);
            var options = bgVideos[youtubeId];
            videoPlayer = BgVideoParagraphs[i].getElementsByClassName('az-video-player')[0];
            var YouTubePlayer = window.YT;
            BgVideoParagraphs[i].player = new YouTubePlayer.Player(videoPlayer, {
              width: options.width,
              height: Math.ceil(options.width / options.ratio),
              videoId: youtubeId,
              playerVars: {
                modestbranding: 1,
                controls: 0,
                showinfo: 0,
                rel: 0,
                wmode: "transparent"
              },
              events: {
                onReady: window.onPlayerReady,
                onStateChange: window.onPlayerStateChange
              }
            });
            var PlayButton = BgVideoParagraphs[i].getElementsByClassName("az-video-play");
            PlayButton[0].addEventListener("click", function (event) {
              event.preventDefault();
              BgVideoParagraphs[i].player.playVideo();
              BgVideoParagraphs[i].classList.remove("az-video-paused");
              BgVideoParagraphs[i].classList.add("az-video-playing");
            });
            var PauseButton = BgVideoParagraphs[i].getElementsByClassName("az-video-pause");
            PauseButton[0].addEventListener("click", function (event) {
              event.preventDefault();
              BgVideoParagraphs[i].player.playVideo();
              BgVideoParagraphs[i].classList.remove("az-video-playing");
              BgVideoParagraphs[i].classList.add("az-video-paused");
            });
          });
        };

        var resize = function resize() {
          $.each(BgVideoParagraphs, function (i, paragraph) {
            var youtubeId = BgVideoParagraphs[i].dataset.youtubeid;
            var paragraphId = BgVideoParagraphs[i].dataset.paragraphid;
            var parentParagraph = BgVideoParagraphs[i].parentNode;
            var thisPlayer = BgVideoParagraphs[i].getElementsByClassName("az-video-player")[0];
            var thisContainer = BgVideoParagraphs[i];
            console.log(thisContainer);
            var width = BgVideoParagraphs[i].offsetWidth;
            var ratio = bgVideos[youtubeId].ratio;
            var pWidth;
            var height = BgVideoParagraphs[i].offsetHeight;
            var pHeight;
            var pLeft = BgVideoParagraphs[i].getElementsByClassName('az-video-player')[0].getBoundingClientRect().left;
            var parentHeight = parentParagraph.offsetHeight;
            parentHeight = "".concat(parentHeight.toString(), "px");
            pLeft = "-".concat(pLeft.toString(), "px");
            pWidth = Math.ceil(height * ratio);
            var pWidthRatio = (width - pWidth) / 2;
            pWidthRatio = "".concat(pWidthRatio.toString(), "px");
            pWidth = "".concat(pWidth.toString(), "px");
            pHeight = Math.ceil(width / ratio);
            var pHeightRatio = (height - pHeight) / 2;
            pHeightRatio = "".concat(pHeightRatio.toString(), "px");
            pHeight = "".concat(pHeight.toString(), "px");
            BgVideoParagraphs[i].style.left = pLeft;

            if (width / ratio < height) {
              thisPlayer.style.width = pWidth;
              thisPlayer.style.zIndex = 100 - i;
              thisPlayer.style.height = height;
              thisPlayer.style.left = (width - pWidth) / 2;
              thisPlayer.style.top = 0;
            } else {
              thisContainer.style.height = parentHeight;
              thisPlayer.style.height = pHeight;
              thisPlayer.style.width = width;
              thisPlayer.style.left = 0;
              thisPlayer.style.top = pHeightRatio;
              thisPlayer.style.zIndex = -100 + i;
            }
          });
        };

        window.onPlayerReady = function (e) {
          var id = e.target.playerInfo.videoData.video_id;

          if (bgVideos[id].mute) {
            e.target.mute();
          }

          e.target.seekTo(bgVideos[id].start);
          e.target.playVideo();
        };

        window.onPlayerStateChange = function (e) {
          var id = e.target.playerInfo.videoData.video_id;
          var stateChangeContainer = document.getElementById("".concat(id, "-bg-video-container"));

          if (e.data === 0 && bgVideos[id].repeat) {
            stateChangeContainer.player.seekTo(bgVideos[id].start);
          }

          if (e.data === 1) {
            resize();
            stateChangeContainer.classList.add("az-video-playing");
            stateChangeContainer.classList.remove("az-video-loading");
          }
        };

        $(window).on("resize.bgVideo", function () {
          resize();
        });
      }
    }
  };
})(jQuery, Drupal, drupalSettings);