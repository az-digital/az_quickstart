(($, Drupal) => {
    Drupal.behaviors.az_vimeo_video_bg = {
      attach(context, settings) {
        if (window.screen && window.screen.width > 768) {
          // @see https://developer.vimeo.com/player/sdk/basics
          // Defaults
          const defaults = {
            ratio: 16 / 9,
            vimeoId: "",
            mute: true,
            repeat: true,
            width: $(window).width(),
            playButtonClass: "az-video-play",
            pauseButtonClass: "az-video-pause",
            start: 0,
            minimumSupportedWidth: 600
          };
          const { bgVideos } = settings.azFieldsMedia;
          const bgVideoParagraphs = document.getElementsByClassName(
            "az-js-vimeo-video-background"
          );
          // Load Vimeo API
          const tag = document.createElement("script");
          tag.src = "https://player.vimeo.com/api/player.js";
          document.head.appendChild(tag);
  
          // Methods
          // Ensure Vimeo API is loaded before proceeding
          tag.onload = () => {
            $.each(bgVideoParagraphs, index => {
                const thisContainer = bgVideoParagraphs[index];
                const parentParagraph = thisContainer.parentNode;
                const vimeoId = thisContainer.dataset.vimeoId;
                bgVideos[vimeoId] = $.extend({}, defaults, thisContainer);
                const options = bgVideos[vimeoId];
                const videoPlayer = thisContainer.getElementsByClassName(
                  "az-video-player"
                )[0];
                const VimeoPlayer = window.Vimeo;
      
                // Initialize Vimeo Player
                thisContainer.player = new VimeoPlayer.Player(videoPlayer, {
                  id: vimeoId,
                  width: options.width,
                  height: Math.ceil(options.width / options.ratio),
                  autoplay: true,
                  muted: options.mute,
                  loop: options.repeat,
                });
      
                // Event Listeners
                thisContainer.player.on("play", () => {
                  parentParagraph.classList.add("az-video-playing");
                  parentParagraph.classList.remove("az-video-paused");
                });
      
                thisContainer.player.on("pause", () => {
                  parentParagraph.classList.add("az-video-paused");
                  parentParagraph.classList.remove("az-video-playing");
                });
      
                thisContainer.player.on("ended", () => {
                  if (options.repeat) {
                    thisContainer.player.setCurrentTime(0).then(() => {
                      thisContainer.player.play();
                    });
                  }
                });
      
                // Play Button
                const playButton = bgVideoParagraphs[index].getElementsByClassName(
                  "az-video-play"
                )[0];
                playButton.addEventListener("click", async (event) => {
                  event.preventDefault();
                  await bgVideoParagraphs[index].player.play();
                });
      
                // Pause Button
                const pauseButton = bgVideoParagraphs[index].getElementsByClassName(
                  "az-video-pause"
                )[0];
                pauseButton.addEventListener("click", async (event) => {
                  event.preventDefault();
                  await bgVideoParagraphs[index].player.pause();
                });
              });
          }
  
          // Resize Logic (optional, similar to YouTube)
          const setDimensions = (container) => {
            const parentParagraph = container.parentNode;
            const vimeoId = container.dataset.vimeoId;
            const thisPlayer =
              container.getElementsByClassName('az-video-player')[0];
            thisPlayer.style.zIndex = -100;
            const { style } = container.dataset;
            const width = container.offsetWidth;
            const height = container.offsetHeight;
            const { ratio } = bgVideos[vimeoId];
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
  
          // Event Handlers
          $(window).on("load", () => {
            resize();
          });
          $(window).on("resize.bgVideo", () => {
            resize();
          });
        }
      }
    };
  })(jQuery, Drupal, drupalSettings);
  