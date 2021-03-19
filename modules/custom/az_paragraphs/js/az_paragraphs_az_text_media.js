(function ($, Drupal, drupalSettings) {

  Drupal.behaviors.az_youtube_video_bg = {
    attach: function (context, settings) {
      if (screen && screen.width > 768) {
        // @see https://developers.google.com/youtube/iframe_api_reference

        // defaults
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

        // load yt iframe js api
        var tag = document.createElement("script");
        tag.src = "https://www.youtube.com/iframe_api";
        var firstScriptTag = document.getElementsByTagName("script")[0];
        firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);

        // methods
        // set up iframe player, use global scope so YT api can talk
        window.onYouTubeIframeAPIReady = function () {
          $.each(settings.azFieldsMedia.bgVideos, function (i, value) {
            var options = settings.azFieldsMedia.bgVideos[i] = $.extend({}, defaults, value);

            // build container
            var videoContainer = "<div id=\"" + i + "-container\" style=\"overflow: hidden; width: 100%; height: 100%\" class=\"az-video-container\"><div id=\"" + i + "-player\" style=\"position: absolute\"></div></div>";

            $("#" + i + "-bg-video-container").prepend(videoContainer).css({position: "relative"});

            settings.azFieldsMedia.bgVideos[i].player = new YT.Player(i + "-player", {
              width: options.width,
              height: Math.ceil(options.width / options.ratio),
              videoId: i,
              playerVars: {
                modestbranding: 1,
                controls: 0,
                showinfo: 0,
                rel: 0,
                wmode: "transparent"
              },
              events: {
                "onReady": window.onPlayerReady,
                "onStateChange": window.onPlayerStateChange
              }
            });

            $("#" + i + "-bg-video-container").on("click", "#video-play-" + i, function (e) { // play button
              e.preventDefault();
              settings.azFieldsMedia.bgVideos[i].player.playVideo();
              $("#" + i + "-bg-video-container").removeClass("az-video-paused").addClass("az-video-playing");
            }).on("click", "#video-pause-" + i, function (e) { // pause button
              e.preventDefault();
              settings.azFieldsMedia.bgVideos[i].player.pauseVideo();
              $("#" + i + "-bg-video-container").removeClass("az-video-playing").addClass("az-video-paused");
            }).on("click", "." + options.muteButtonClass, function (e) { // mute button
              e.preventDefault();
              (settings.azFieldsMedia.bgVideos[i].player.isMuted()) ? settings.azFieldsMedia.bgVideos[i].player.unMute() : settings.azFieldsMedia.bgVideos[i].player.mute();
            }).on("click", "." + options.volumeDownClass, function (e) { // volume down button
              e.preventDefault();
              var currentVolume = settings.azFieldsMedia.bgVideos[i].player.getVolume();
              if (currentVolume < options.increaseVolumeBy) {
                currentVolume = options.increaseVolumeBy;
              }
              settings.azFieldsMedia.bgVideos[i].player.setVolume(currentVolume - options.increaseVolumeBy);
            }).on("click", "." + options.volumeUpClass, function (e) { // volume up button
              e.preventDefault();
              // if mute is on, unmute
              if (settings.azFieldsMedia.bgVideos[i].player.isMuted()) {
                settings.azFieldsMedia.bgVideos[i].player.unMute();
              }

              var currentVolume = settings.azFieldsMedia.bgVideos[i].player.getVolume();
              if (currentVolume > 100 - options.increaseVolumeBy) {
                currentVolume = 100 - options.increaseVolumeBy;
              }
              settings.azFieldsMedia.bgVideos[i].player.setVolume(currentVolume + options.increaseVolumeBy);
            });
          });
        };

        window.onPlayerReady = function (e) {
          var id = e.target.playerInfo.videoData.video_id;
          if (settings.azFieldsMedia.bgVideos[id].mute) {
            e.target.mute();
          }
          e.target.seekTo(settings.azFieldsMedia.bgVideos[id].start);
          e.target.playVideo();
        };

        window.onPlayerStateChange = function (e) {
          var id = e.target.playerInfo.videoData.video_id;
          if (e.data === 1) {
            resize();
            $("#" + id + "-bg-video-container").addClass("az-video-playing").removeClass("az-video-loading");
          }
          if (e.data === 0 && settings.azFieldsMedia.bgVideos[id].repeat) { // video ended and repeat option is set true
            settings.azFieldsMedia.bgVideos[id].player.seekTo(settings.azFieldsMedia.bgVideos[id].start); // restart
          }
        };

        // resize handler updates width, height and offset of player after resize/init
        var resize = function () {
          $.each(settings.azFieldsMedia.bgVideos, function (i, value) {
            var width = $("#" + i + "-bg-video-container").width(),
              pWidth, // player width, to be defined
              height = $("#" + i + "-bg-video-container").height(),
              pHeight; // player height, tbd

            // when screen aspect ratio differs from video, video must center and underlay one dimension
            if (width / value.ratio < height) { // if new video height < window height (gap underneath)
              pWidth = Math.ceil(height * value.ratio); // get new player width
              $("#" + value.videoId + "-player").width(pWidth).height(height).css({left: (width - pWidth) / 2, top: 0}); // player width is greater, offset left; reset top
            } else { // new video width < window width (gap to right)
              pHeight = Math.ceil(width / value.ratio); // get new player height
              $("#" + value.videoId + "-player").width(width).height(pHeight).css({
                                                                                  left: 0,
                                                                                  top: (height - pHeight) / 2
              }); // player height is greater, offset top; reset left
            }
          });
        };

        // events
        $(window).on("resize.bgVideo", function () {
          resize();
        });
      }
    }
  };
}(jQuery, Drupal, drupalSettings));