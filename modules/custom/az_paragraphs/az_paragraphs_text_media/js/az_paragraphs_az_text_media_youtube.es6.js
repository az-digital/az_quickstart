((Drupal, once) => {
  Drupal.behaviors.az_youtube_video_bg = {
    attach(context) {
      function initYouTubeBackgrounds() {
        if (window.screen && window.screen.width > 768) {
          // @see https://developers.google.com/youtube/player_parameters
          const defaultSettings = {
            loop: true,
            mute: true,
            playPauseButtonClass: 'az-video-playpause',
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

              // Play/Pause button: delegate state changes to player events.
              const playPauseButton = element.getElementsByClassName(
                defaultSettings.playPauseButtonClass,
              )[0];
              if (playPauseButton) {
                playPauseButton.addEventListener('click', (event) => {
                  event.preventDefault();
                  if (
                    event.currentTarget.getAttribute('aria-pressed') === 'true'
                  ) {
                    element.player.pauseVideo();
                  } else {
                    element.player.playVideo();
                  }
                });
              }
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

          // Sync the play/pause button and paragraph classes with player state.
          const syncPlayPause = (parentContainer, isPlaying) => {
            parentContainer.classList.toggle('az-video-playing', isPlaying);
            parentContainer.classList.toggle('az-video-paused', !isPlaying);
            const playPauseButton = parentContainer.querySelector(
              `.${defaultSettings.playPauseButtonClass}`,
            );
            if (playPauseButton) {
              playPauseButton.textContent = isPlaying
                ? Drupal.t('Pause Video')
                : Drupal.t('Play Video');
              playPauseButton.setAttribute('aria-pressed', String(isPlaying));
            }
          };

          window.onPlayerReady = (event) => {
            // Set the iframe tabindex to -1 to prevent focus from reaching iframe.
            event.target.getIframe().setAttribute('tabindex', '-1');

            const id = event.target.options.videoId;

            // Only reveal the button once the player can respond to it.
            const container = document.getElementById(
              `${id}-bg-video-container`,
            );
            const playPauseButton =
              container &&
              container.getElementsByClassName(
                defaultSettings.playPauseButtonClass,
              )[0];
            if (playPauseButton) {
              playPauseButton.hidden = false;
            }

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
              parentContainer.classList.remove('az-video-loading');
              syncPlayPause(parentContainer, true);
            }
            if (event.data === 2) {
              syncPlayPause(parentContainer, false);
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
