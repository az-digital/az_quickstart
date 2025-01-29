(($, Drupal, once) => {
  Drupal.behaviors.az_vimeo_video_bg = {
    attach(context, settings) {
      // Error messaging function
      function vimeoError(error) {
        switch (error.name) {
          case 'PasswordError':
            console.log('The Vimeo video is password-protected.');
            break;
          case 'PrivacyError':
            console.log('The Vimeo video is private.');
            break;
          default:
            console.log(
              `Some errors occurred with the Vimeo video: ${error.name}`,
            );
            break;
        }
      }

      if (window.screen && window.screen.width > 768) {
        // @see https://developer.vimeo.com/player/sdk/basics
        // Defaults
        const defaults = {
          vimeoId: '',
          width: $(window).width(),
          ratio: 16 / 9,
          autoplay: true,
          controls: 0,
          loop: true,
          muted: true,
          playButtonClass: 'az-video-play',
          pauseButtonClass: 'az-video-pause',
          minimumSupportedWidth: 600,
        };
        const { bgVideos } = settings.azFieldsMedia;
        const bgVideoParagraphs = document.getElementsByClassName(
          'az-js-vimeo-video-background',
        );

        // Load Vimeo API
        const tag = document.createElement('script');
        tag.src = 'https://player.vimeo.com/api/player.js';
        document.head.appendChild(tag);

        // Methods
        // Ensure Vimeo API is loaded before proceeding
        tag.onload = () => {
          $.each(bgVideoParagraphs, (index) => {
            const thisContainer = bgVideoParagraphs[index];
            const parentParagraph = thisContainer.parentNode;
            const vimeoId = thisContainer.dataset.vimeoVideoId;
            bgVideos[vimeoId] = $.extend({}, defaults, thisContainer);
            const options = bgVideos[vimeoId];
            const videoPlayer =
              thisContainer.getElementsByClassName('az-video-player')[0];
            const VimeoPlayer = window.Vimeo;

            // Initialize Vimeo Player
            thisContainer.player = new VimeoPlayer.Player(videoPlayer, {
              id: vimeoId,
              width: options.width,
              height: Math.ceil(options.width / options.ratio),
              autoplay: thisContainer.dataset.autoplay === 'true',
              controls: 0,
              loop: options.loop,
              muted: options.muted,
            });

            // Event listener for starting play.
            thisContainer.player.on('play', () => {
              parentParagraph.classList.add('az-video-playing');
            });

            // Play Button
            const playButtons = once(
              'play-once',
              bgVideoParagraphs[index].getElementsByClassName('az-video-play'),
            );
            if (playButtons[0]) {
              playButtons[0].addEventListener('click', (event) => {
                event.preventDefault();
                bgVideoParagraphs[index].player
                  .play()
                  .catch((error) => vimeoError(error));
                parentParagraph.classList.add('az-video-playing');
                parentParagraph.classList.remove('az-video-paused');
              });
            }

            // Pause Button
            const pauseButtons = once(
              'pause-once',
              bgVideoParagraphs[index].getElementsByClassName('az-video-pause'),
            );
            if (pauseButtons[0]) {
              pauseButtons[0].addEventListener('click', (event) => {
                event.preventDefault();
                bgVideoParagraphs[index].player
                  .pause()
                  .catch((error) => vimeoError(error));
                parentParagraph.classList.add('az-video-paused');
                parentParagraph.classList.remove('az-video-playing');
              });
            }
          });
        };

        // Resize Logic
        const setDimensions = (container) => {
          const parentParagraph = container.parentNode;
          let parentHeight = parentParagraph.offsetHeight;
          parentHeight = `${parentHeight.toString()}px`;
          container.style.height = parentHeight;
          const { style } = container.dataset;
          if (style === 'bottom') {
            container.style.top = 0;
          }
          const thisPlayer =
            container.getElementsByClassName('az-video-player')[0].firstChild;
          if (thisPlayer === null) {
            return;
          }
          thisPlayer.style.zIndex = -100;
          const vimeoId = container.dataset.vimeoVideoId;
          const width = container.offsetWidth;
          const height = container.offsetHeight;
          const { ratio } = bgVideos[vimeoId];
          const pWidth = Math.ceil(height * ratio); // get new player width
          const pHeight = Math.ceil(width / ratio); // get new player height
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

        // Event Handlers
        $(window).on('load', () => {
          resize();
        });
        $(window).on('resize.bgVideo', () => {
          resize();
        });
      }
    },
  };
})(jQuery, Drupal, once);
