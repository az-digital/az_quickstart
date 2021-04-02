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
        const BgVideoParagraphs = document.getElementsByClassName(
          "az-js-video-background"
        );
        // load yt iframe js api
        const tag = document.createElement("script");
        const firstScriptTag = document.getElementsByTagName("script")[0];
        tag.src = "https://www.youtube.com/iframe_api";
        firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);
        // methods
        // set up iframe player, use global scope so YT api can talk
        window.onYouTubeIframeAPIReady = () => {
          $.each(BgVideoParagraphs, (i, paragraph) => {
            const youtubeId = BgVideoParagraphs[i].dataset.youtubeid;
            bgVideos[youtubeId] = $.extend({}, defaults, paragraph);
            const options = bgVideos[youtubeId];
            videoPlayer = BgVideoParagraphs[i].getElementsByClassName('az-video-player')[0];
            const YouTubePlayer = window.YT;
            BgVideoParagraphs[i].player = new YouTubePlayer.Player(
              videoPlayer,
              {
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
              }
            );
            const PlayButton = BgVideoParagraphs[i].getElementsByClassName(
              "az-video-play"
            );
            PlayButton[0].addEventListener("click", event => {
              event.preventDefault();
              BgVideoParagraphs[i].player.playVideo();
              BgVideoParagraphs[i].classList.remove("az-video-paused");
              BgVideoParagraphs[i].classList.add("az-video-playing");
            });
            const PauseButton = BgVideoParagraphs[i].getElementsByClassName(
              "az-video-pause"
            );
            PauseButton[0].addEventListener("click", event => {
              event.preventDefault();
              BgVideoParagraphs[i].player.playVideo();
              BgVideoParagraphs[i].classList.remove("az-video-playing");
              BgVideoParagraphs[i].classList.add("az-video-paused");
            });
          });
        };

        // Resize handler updates width, height and offset
        // of player after resize/init.
        const resize = () => {
          $.each(BgVideoParagraphs, (i, paragraph) => {
            const youtubeId = BgVideoParagraphs[i].dataset.youtubeid;
            const paragraphId = BgVideoParagraphs[i].dataset.paragraphid;
            const parentParagraph = BgVideoParagraphs[i].parentNode;
            const thisPlayer = BgVideoParagraphs[i].getElementsByClassName(
              "az-video-player"
            )[0];
            const thisContainer = BgVideoParagraphs[i];
console.log(thisContainer);
            const width = BgVideoParagraphs[i].offsetWidth;
            const { ratio } = bgVideos[youtubeId];
            let pWidth; // player width, to be defined
            const height = BgVideoParagraphs[i].offsetHeight;
            let pHeight; // player height, tbd
            let pLeft = BgVideoParagraphs[i].getElementsByClassName('az-video-player')[0].getBoundingClientRect().left;
            let parentHeight = parentParagraph.offsetHeight;
            parentHeight = `${parentHeight.toString()}px`;
            pLeft = `-${pLeft.toString()}px`;
            pWidth = Math.ceil(height * ratio); // get new player width
            let pWidthRatio = (width - pWidth) / 2;
            pWidthRatio = `${pWidthRatio.toString()}px`;
            pWidth = `${pWidth.toString()}px`;
            pHeight = Math.ceil(width / ratio);
            let pHeightRatio = (height - pHeight) / 2;
            pHeightRatio = `${pHeightRatio.toString()}px`;
            pHeight = `${pHeight.toString()}px`;
            // when screen aspect ratio differs from video,
            // video must center and underlay one dimension.
            BgVideoParagraphs[i].style.left = pLeft;
            if (width / ratio < height) {
              // if new video height < window height (gap underneath)
              thisPlayer.style.width = pWidth;
              thisPlayer.style.zIndex = 100 - i;
              thisPlayer.style.height = height;
              thisPlayer.style.left = (width - pWidth) / 2;
              thisPlayer.style.top = 0;
              //thisOverflowContainer.style.width = pWidth;
              //thisOverflowContainer.style.height = height;
              //thisOverflowContainer.style.left = pWidthRatio;
              //thisOverflowContainer.style.top = 0;
              // thisOverflowContainer.style.border-width((width - pWidth), 0, 0, 0);
              // player width is greater, offset left; reset top
            } else {
              // new video width < window width (gap to right)
              // get new player height
              thisContainer.style.height = parentHeight;
							thisPlayer.style.height = pHeight;
							thisPlayer.style.width = width;
							thisPlayer.style.left = 0;
							thisPlayer.style.top = pHeightRatio;
              thisPlayer.style.zIndex = (-100 + i);
              //thisOverflowContainer.style.width = width;
              //thisOverflowContainer.style.height = pHeight;
              //thisOverflowContainer.style.left = 0;
              //thisOverflowContainer.style.top = pHeightRatio;
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
          const stateChangeContainer = document.getElementById(
            `${id}-bg-video-container`
          );
          if (e.data === 0 && bgVideos[id].repeat) {
            // video ended and repeat option is set true
            stateChangeContainer.player.seekTo(bgVideos[id].start); // restart
          }
          if (e.data === 1) {
            resize();
            stateChangeContainer.classList.add("az-video-playing");
            stateChangeContainer.classList.remove("az-video-loading");
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
