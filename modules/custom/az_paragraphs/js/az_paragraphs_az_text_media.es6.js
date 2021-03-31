(($, Drupal) => {
  Drupal.behaviors.az_youtube_video_bg = {
    attach(context, settings) {
      if (window.screen && window.screen.width > 768) {
        // @see https://developers.google.com/youtube/iframe_api_reference
        // defaults
        const defaults = {
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
        const { bgVideos } = settings.azFieldsMedia;
        // load yt iframe js api
        const tag = document.createElement("script");
        const firstScriptTag = document.getElementsByTagName("script")[0];
        tag.src = "https://www.youtube.com/iframe_api";
        firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);
        // methods
        // set up iframe player, use global scope so YT api can talk
        window.onYouTubeIframeAPIReady = () => {
          $.each(bgVideos, (i, value) => {
            bgVideos[i] = $.extend({}, defaults, value);
            const options = bgVideos[i];
            options.playButtonClass = `.video-play-${i}`;
            options.pauseButtonClass = `.video-pause-${i}`;
            // build container
            const videoContainer = `<div id="${i}-container" style="overflow: hidden; width: 100%; height: 100%" class="az-video-container"><div id="${i}-player" style="position: absolute"></div></div>`;
            const videoContainerOverflow = `<div id="${i}-overflow-container"class="az-video-overflow-container"></div>`;
            $(`#${i}-bg-video-container`)
              .prepend(videoContainer)
              .prepend(videoContainerOverflow)
              .css({ position: "relative" });
            const player = window.YT;
            bgVideos[i].player = new player.Player(`${i}-player`, {
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
                onReady: window.onPlayerReady,
                onStateChange: window.onPlayerStateChange
              }
            });

            $(`#${i}-bg-video-container`)
              .on("click", `${options.playButtonClass}`, e => {
                // play button
                e.preventDefault();
                bgVideos[i].player.playVideo();
                $(`#${i}-bg-video-container`)
                  .removeClass("az-video-paused")
                  .addClass("az-video-playing");
              })
              .on("click", `${options.pauseButtonClass}`, e => {
                // pause button
                e.preventDefault();
                bgVideos[i].player.pauseVideo();
                $(`#${i}-bg-video-container`)
                  .removeClass("az-video-playing")
                  .addClass("az-video-paused");
              })
              .on("click", `${options.muteButtonClass}`, e => {
                // mute button
                e.preventDefault();
                if (bgVideos[i].player.isMuted()) {
                  bgVideos[i].player.unMute();
                } else {
                  bgVideos[i].player.mute();
                }
              })
              .on("click", `${options.volumeDownClass}`, e => {
                // volume down button
                e.preventDefault();
                let currentVolume = bgVideos[i].player.getVolume();
                if (currentVolume < options.increaseVolumeBy) {
                  currentVolume = options.increaseVolumeBy;
                }
                bgVideos[i].player.setVolume(
                  currentVolume - options.increaseVolumeBy
                );
              })
              .on("click", `${options.volumeUpClass}`, e => {
                // volume up button
                e.preventDefault();
                // if mute is on, unmute
                if (settings.azFieldsMedia.bgVideos[i].player.isMuted()) {
                  bgVideos[i].player.unMute();
                }

                let currentVolume = bgVideos[i].player.getVolume();
                if (currentVolume > 100 - options.increaseVolumeBy) {
                  currentVolume = 100 - options.increaseVolumeBy;
                }
                bgVideos[i].player.setVolume(
                  currentVolume + options.increaseVolumeBy
                );
              });
          });
        };

        // resize handler updates width, height and offset of player after resize/init
        const resize = () => {
          $.each(bgVideos, (i, value) => {
            const width = $(`#${i}-bg-video-container`).width();
            let pWidth; // player width, to be defined
            const height = $(`#${i}-bg-video-container`).height();
            let pHeight; // player height, tbd

            // when screen aspect ratio differs from video, video must center and underlay one dimension
            if (width / value.ratio < height) {
              // if new video height < window height (gap underneath)
              pWidth = Math.ceil(height * value.ratio); // get new player width
              $(`#${value.videoId}-player`)
                .width(pWidth)
                .height(height)
                .css({
                  left: (width - pWidth) / 2,
                  top: 0
                }); // player width is greater, offset left; reset top
              $(`#${value.videoId}-overflow-container`)
                .width(pWidth)
                .height(height)
                .css({
                  "border-top-width": (height - pHeight) / 2,
                  left: 0,
                  top: (height - pHeight) / 2
                }); // player width is greater, offset left; reset top
            } else {
              // new video width < window width (gap to right)
              pHeight = Math.ceil(width / value.ratio); // get new player height
              $(`#${value.videoId}-player`)
                .width(width)
                .height(pHeight)
                .css({
                  left: 0,
                  top: (height - pHeight) / 2
                }); // player height is greater, offset top; reset left
              $(`#${value.videoId}-overflow-container`)
                .width(pWidth)
                .height(height)
                .css({
                  "border-top-width": (height - pHeight) / 2,
                  left: 0,
                  top: (height - pHeight) / 2
                }); // player width is greater, offset left; reset top
            }
          });
        };

        window.onPlayerReady = e => {
          const id = e.target.playerInfo.videoData.video_id;
          if (bgVideos[id].mute) {
            e.target.mute();
          }
          e.target.seekTo(bgVideos[id].start);
          e.target.playVideo();
        };

        window.onPlayerStateChange = e => {
          const id = e.target.playerInfo.videoData.video_id;
          if (e.data === 1) {
            resize();
            $(`#${id}-bg-video-container`)
              .addClass("az-video-playing")
              .removeClass("az-video-loading");
          }
          if (e.data === 0 && bgVideos[id].repeat) {
            // video ended and repeat option is set true
            bgVideos[id].player.seekTo(bgVideos[id].start); // restart
          }
        };

        // events
        $(window).on("resize.bgVideo", () => {
          resize();
        });
      }
    }
  };
})(jQuery, Drupal, drupalSettings);
