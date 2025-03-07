(($, Drupal) => {
  Drupal.behaviors.az_youtube_video_bg = {
    attach(context, settings) {
      if (window.screen && window.screen.width > 768) {
        // @see https://developers.google.com/youtube/iframe_api_reference
        // defaults
        const defaults = {
          ratio: 16 / 9,
          videoId: '',
          mute: true,
          repeat: true,
          width: $(window).width(),
          playButtonClass: 'az-video-play',
          pauseButtonClass: 'az-video-pause',
          muteButtonClass: 'az-video-mute',
          volumeUpClass: 'az-video-volume-up',
          volumeDownClass: 'az-video-volume-down',
          increaseVolumeBy: 10,
          start: 0,
          minimumSupportedWidth: 600,
        };
        const { bgVideos } = settings.azFieldsMedia;
        const bgVideoParagraphs = document.getElementsByClassName(
          'az-js-video-background',
        );
        // load yt iframe js api
        const tag = document.createElement('script');
        const firstScriptTag = document.getElementsByTagName('script')[0];
        tag.src = 'https://www.youtube.com/iframe_api';
        firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);
        // methods
        // set up iframe player, use global scope so YT api can talk
        window.onYouTubeIframeAPIReady = () => {
          $.each(bgVideoParagraphs, (index) => {
            const thisContainer = bgVideoParagraphs[index];
            const parentId = thisContainer.dataset.parentid;
            const parentParagraph = document.getElementById(parentId);
            const youtubeId = thisContainer.dataset.youtubeid;
            bgVideos[youtubeId] = $.extend({}, defaults, thisContainer);
            const options = bgVideos[youtubeId];
            const videoPlayer =
              thisContainer.getElementsByClassName('az-video-player')[0];
            const YouTubePlayer = window.YT;
            thisContainer.player = new YouTubePlayer.Player(videoPlayer, {
              width: options.width,
              height: Math.ceil(options.width / options.ratio),
              videoId: youtubeId,
              playerVars: {
                modestbranding: 1,
                controls: 0,
                showinfo: 0,
                rel: 0,
                wmode: 'transparent',
              },
              events: {
                onReady: window.onPlayerReady,
                onStateChange: window.onPlayerStateChange,
              },
            });
            const playButton =
              bgVideoParagraphs[index].getElementsByClassName(
                'az-video-play',
              )[0];
            playButton.addEventListener('click', (event) => {
              event.preventDefault();
              bgVideoParagraphs[index].player.playVideo();
              parentParagraph.classList.remove('az-video-paused');
              parentParagraph.classList.add('az-video-playing');
            });
            const pauseButton =
              bgVideoParagraphs[index].getElementsByClassName(
                'az-video-pause',
              )[0];
            pauseButton.addEventListener('click', (event) => {
              event.preventDefault();
              bgVideoParagraphs[index].player.pauseVideo();
              parentParagraph.classList.remove('az-video-playing');
              parentParagraph.classList.add('az-video-paused');
            });
          });
        };
        const setDimensions = (container) => {
          const parentParagraph = container.parentNode;
          const youtubeId = container.dataset.youtubeid;
          const thisPlayer =
            container.getElementsByClassName('az-video-player')[0];
          thisPlayer.style.zIndex = -100;
          const { style } = container.dataset;
          const width = container.offsetWidth;
          const height = container.offsetHeight;
          const { ratio } = bgVideos[youtubeId];
          const pWidth = Math.ceil(height * ratio); // get new player width
          const pHeight = Math.ceil(width / ratio); // get new player height
          let parentHeight = parentParagraph.offsetHeight;
          parentHeight = `${parentHeight.toString()}px`;
          container.style.height = parentHeight;
          if (style === 'bottom') {
            container.style.top = 0;
          }
          let widthMinuspWidthdividedbyTwo = (width - pWidth) / 2;
          widthMinuspWidthdividedbyTwo = `${widthMinuspWidthdividedbyTwo.toString()}px`;
          let pHeightRatio = (height - pHeight) / 2;
          pHeightRatio = `${pHeightRatio.toString()}px`;
          // when screen aspect ratio differs from video,
          // video must center and underlay one dimension.
          if (width / ratio < height) {
            // if new video height < window height (gap underneath)
            thisPlayer.width = pWidth;
            thisPlayer.height = height;
            thisPlayer.style.left = widthMinuspWidthdividedbyTwo;
            thisPlayer.style.top = 0;
            // player width is greater, offset left; reset top
          } else {
            // new video width < window width (gap to right)
            // get new player height
            thisPlayer.height = pHeight;
            thisPlayer.width = width;
            thisPlayer.style.top = pHeightRatio;
            thisPlayer.style.left = 0;
          }
        };

        // Resize handler updates width, height and offset
        // of player after resize/init.
        const resize = () => {
          $.each(bgVideoParagraphs, (index) => {
            setDimensions(bgVideoParagraphs[index]);
          });
        };

        window.onPlayerReady = (event) => {
          const id = event.target.getVideoData().video_id;
          if (bgVideos[id].dataset.autoplay === 'false') {
            return;
          }
          if (bgVideos[id].mute) {
            event.target.mute();
          }
          event.target.seekTo(bgVideos[id].start);
          event.target.playVideo();
          // Create and dispatch a new event when video starts playing.
          const azVideoPlayEvent = new Event('azVideoPlay');
          dispatchEvent(azVideoPlayEvent);
        };

        window.onPlayerStateChange = (event) => {
          const id = event.target.getVideoData().video_id;
          const stateChangeContainer = document.getElementById(
            `${id}-bg-video-container`,
          );
          const { parentid } = stateChangeContainer.dataset;
          const parentContainer = document.getElementById(parentid);
          if (event.data === 0 && bgVideos[id].repeat) {
            // video ended and repeat option is set true
            stateChangeContainer.player.seekTo(bgVideos[id].start); // restart
          }
          if (event.data === 1) {
            resize();
            parentContainer.classList.add('az-video-playing');
            parentContainer.classList.remove('az-video-loading');
          }
        };

        // events
        $(window).on('load', () => {
          resize();
        });
        $(window).on('resize.bgVideo', () => {
          resize();
        });
      }
    },
  };
})(jQuery, Drupal, drupalSettings);
