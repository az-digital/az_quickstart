((Drupal, once) => {
  Drupal.behaviors.az_youtube_video_bg = {
    attach(context) {
      function initYouTubeBackgrounds() {
        if (window.screen && window.screen.width > 768) {
          // @see https://developers.google.com/youtube/player_parameters
          const defaultSettings = {
            loop: true,
            mute: true,
            pauseButtonClass: 'az-video-pause',
            playButtonClass: 'az-video-play',
            ratio: 16 / 9,
            width: document.documentElement.clientWidth,
          };
          const bgVideoSettings = {};

          // Load YouTube IFrame player API
          const tag = document.createElement('script');
          const firstScriptTag = document.getElementsByTagName('script')[0];
          tag.src = 'https://www.youtube.com/iframe_api';
          firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);

          // Set up IFrame player
          const bgVideoParagraphs = document.getElementsByClassName(
            'az-js-video-background',
          );
          window.onYouTubeIframeAPIReady = () => {
            Array.from(bgVideoParagraphs).forEach((element) => {
              const parentParagraph = document.getElementById(
                element.dataset.parentid,
              );
              const youtubeId = element.dataset.youtubeid;
              bgVideoSettings[youtubeId] = {
                autoplay: element.dataset.autoplay === 'true',
                start: element.dataset.start,
              };
              const videoPlayer =
                element.getElementsByClassName('az-video-player')[0];
              const youTubePlayer = window.YT;
              element.player = new youTubePlayer.Player(videoPlayer, {
                width: defaultSettings.width,
                height: Math.ceil(
                  defaultSettings.width / defaultSettings.ratio,
                ),
                videoId: youtubeId,
                playerVars: {
                  controls: 0,
                  enablejsapi: 1,
                  origin: window.location.origin,
                  rel: 0,
                },
                events: {
                  onReady: window.onPlayerReady,
                  onStateChange: window.onPlayerStateChange,
                },
              });
              const playButton =
                element.getElementsByClassName('az-video-play')[0];
              playButton.addEventListener('click', (event) => {
                event.preventDefault();
                element.player.playVideo();
                parentParagraph.classList.remove('az-video-paused');
                parentParagraph.classList.add('az-video-playing');
              });
              const pauseButton =
                element.getElementsByClassName('az-video-pause')[0];
              pauseButton.addEventListener('click', (event) => {
                event.preventDefault();
                element.player.pauseVideo();
                parentParagraph.classList.remove('az-video-playing');
                parentParagraph.classList.add('az-video-paused');
              });
            });
          };

          // Updates width, height, and offset of player after resize/init.
          const setDimensions = (container) => {
            container.style.height = `${container.parentNode.offsetHeight}px`;
            if (container.dataset.style === 'bottom') {
              container.style.top = 0;
            }
            const thisPlayer =
              container.getElementsByClassName('az-video-player')[0];
            if (thisPlayer === null) {
              return;
            }
            thisPlayer.style.zIndex = -100;
            const width = container.offsetWidth;
            const height = container.offsetHeight;
            const pWidth = Math.ceil(height * defaultSettings.ratio);
            const pHeight = Math.ceil(width / defaultSettings.ratio);
            let widthMinuspWidthDividedByTwo = (width - pWidth) / 2;
            widthMinuspWidthDividedByTwo = `${widthMinuspWidthDividedByTwo.toString()}px`;
            const pHeightRatio = `${(height - pHeight) / 2}px`;
            // When screen aspect ratio differs from video,
            // video must center and underlay one dimension.
            if (width / defaultSettings.ratio < height) {
              // If new video height < window height (gap underneath)
              thisPlayer.width = pWidth;
              thisPlayer.height = height;
              thisPlayer.style.left = widthMinuspWidthDividedByTwo;
              thisPlayer.style.top = 0;
              // Player width is greater, offset left; reset top
            } else {
              // New video width < window width (gap to right)
              // Get new player height
              thisPlayer.height = pHeight;
              thisPlayer.width = width;
              thisPlayer.style.top = pHeightRatio;
              thisPlayer.style.left = 0;
            }
          };

          // Resize handler
          const resize = () => {
            Array.from(bgVideoParagraphs).forEach((element) => {
              setDimensions(element);
            });
          };

          window.onPlayerReady = (event) => {
            const id = event.target.options.videoId;
            if (!bgVideoSettings[id].autoplay) {
              return;
            }
            if (defaultSettings.mute) {
              event.target.mute();
            }
            event.target.seekTo(bgVideoSettings[id].start);
            event.target.playVideo();
            // Create and dispatch a new event when video starts playing.
            dispatchEvent(new Event('azVideoPlay'));
          };

          window.onPlayerStateChange = (event) => {
            const id = event.target.options.videoId;
            const stateChangeContainer = document.getElementById(
              `${id}-bg-video-container`,
            );
            const parentContainer = document.getElementById(
              stateChangeContainer.dataset.parentid,
            );
            if (event.data === 0 && defaultSettings.loop) {
              // Video ended and loop option is set true.
              stateChangeContainer.player.seekTo(bgVideoSettings[id].start);
            }
            if (event.data === 1) {
              resize();
              parentContainer.classList.add('az-video-playing');
              parentContainer.classList.remove('az-video-loading');
            }
          };

          // Events
          window.addEventListener('load', () => {
            resize();
          });
          window.addEventListener('resize', () => {
            resize();
          });
        }
      }

      once('youTubeTextOnMedia-init', 'body').forEach(
        initYouTubeBackgrounds,
        context,
      );
    },
  };
})(Drupal, once);
