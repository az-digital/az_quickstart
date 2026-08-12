((Drupal, once) => {
  Drupal.behaviors.az_vimeo_video_bg = {
    attach() {
      function initVimeoBackgrounds() {
        // Set default aspect ratio for Vimeo videos.
        const defaultAspectRatio = 16 / 9;

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

        // Resize Logic
        function setDimensions(container) {
          const parentParagraph = container.parentNode;
          let parentHeight = parentParagraph.offsetHeight;
          parentHeight = `${parentHeight.toString()}px`;
          container.style.height = parentHeight;
          if (container.dataset.style === 'bottom') {
            container.style.top = 0;
          }
          const thisPlayer =
            container.getElementsByClassName('az-video-player')[0].firstChild;
          if (thisPlayer === null) {
            return;
          }
          thisPlayer.style.zIndex = -100;
          const width = container.offsetWidth;
          const height = container.offsetHeight;
          const pWidth = Math.ceil(height * defaultAspectRatio); // get new player width
          const pHeight = Math.ceil(width / defaultAspectRatio); // get new player height
          let widthMinuspWidthDividedByTwo = (width - pWidth) / 2;
          widthMinuspWidthDividedByTwo = `${widthMinuspWidthDividedByTwo.toString()}px`;
          let pHeightRatio = (height - pHeight) / 2;
          pHeightRatio = `${pHeightRatio.toString()}px`;
          // when screen aspect ratio differs from video,
          // video must center and underlay one dimension.
          if (width / defaultAspectRatio < height) {
            // if new video height < window height (gap underneath)
            thisPlayer.width = pWidth;
            thisPlayer.height = height;
            thisPlayer.style.left = widthMinuspWidthDividedByTwo;
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
        }

        if (window.screen && window.screen.width > 768) {
          // @see https://developer.vimeo.com/player/sdk/basics
          const defaultOptions = {
            vimeoId: '',
            autopause: false,
            autoplay: true,
            controls: 0,
            loop: true,
            muted: true,
            playButtonClass: 'az-video-play',
            pauseButtonClass: 'az-video-pause',
          };
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
            Array.from(bgVideoParagraphs).forEach((element) => {
              const parentParagraph = element.parentNode;
              const vimeoId = element.dataset.vimeoVideoId;
              const videoPlayer =
                element.getElementsByClassName('az-video-player')[0];
              const VimeoPlayer = window.Vimeo;

              // Initialize Vimeo Player
              element.player = new VimeoPlayer.Player(videoPlayer, {
                id: vimeoId,
                autopause: defaultOptions.autopause,
                autoplay: element.dataset.autoplay === 'true',
                controls: 0,
                loop: defaultOptions.loop,
                muted: defaultOptions.muted,
              });

              // Event listener for starting play.
              element.player.on('bufferend', () => {
                setDimensions(element);
                parentParagraph.classList.add('az-video-playing');
              });

              // Play Button
              const playButtons =
                element.getElementsByClassName('az-video-play');
              if (playButtons[0]) {
                playButtons[0].addEventListener('click', (event) => {
                  event.preventDefault();
                  element.player.play().catch((error) => vimeoError(error));
                  parentParagraph.classList.add('az-video-playing');
                  parentParagraph.classList.remove('az-video-paused');
                });
              }

              // Pause Button
              const pauseButtons =
                element.getElementsByClassName('az-video-pause');
              if (pauseButtons[0]) {
                pauseButtons[0].addEventListener('click', (event) => {
                  event.preventDefault();
                  element.player.pause().catch((error) => vimeoError(error));
                  parentParagraph.classList.add('az-video-paused');
                  parentParagraph.classList.remove('az-video-playing');
                });
              }
            });
          };

          // Resize handler updates width, height and offset
          // of player after resize/init.
          const resize = () => {
            Array.from(bgVideoParagraphs).forEach((element) => {
              setDimensions(element);
            });
          };
          window.addEventListener('resize', () => {
            resize();
          });
        }
      }

      once('vimeoTextOnMedia-init', 'body').forEach(initVimeoBackgrounds);
    },
  };
})(Drupal, once);
