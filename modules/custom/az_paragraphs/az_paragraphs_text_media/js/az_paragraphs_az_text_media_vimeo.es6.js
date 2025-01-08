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
            "az-js-video-background"
          );
          // Load Vimeo API
          const tag = document.createElement("script");
          tag.src = "https://player.vimeo.com/api/player.js";
          document.head.appendChild(tag);
  
          // Methods
          $.each(bgVideoParagraphs, index => {
            const thisContainer = bgVideoParagraphs[index];
            const parentParagraph = thisContainer.parentNode;
            const vimeoId = thisContainer.dataset.vimeo-id;
            bgVideos[vimeoId] = $.extend({}, defaults, thisContainer);
            const options = bgVideos[vimeoId];
            const videoPlayer = thisContainer.getElementsByClassName(
              "az-video-player"
            )[0];
            const VimeoPlayer = window.Vimeo.Player;
  
            // Initialize Vimeo Player
            thisContainer.player = new VimeoPlayer(videoPlayer, {
              id: vimeoId,
              width: options.width,
              autoplay: true,
              muted: options.mute,
              loop: options.repeat
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
            playButton.addEventListener("click", event => {
              event.preventDefault();
              bgVideoParagraphs[index].player.play();
            });
  
            // Pause Button
            const pauseButton = bgVideoParagraphs[index].getElementsByClassName(
              "az-video-pause"
            )[0];
            pauseButton.addEventListener("click", event => {
              event.preventDefault();
              bgVideoParagraphs[index].player.pause();
            });
          });
  
          // Resize Logic (optional, similar to YouTube)
          const resize = () => {
            $.each(bgVideoParagraphs, index => {
              const thisContainer = bgVideoParagraphs[index];
              const videoPlayer = thisContainer.getElementsByClassName(
                "az-video-player"
              )[0];
              const width = thisContainer.offsetWidth;
              const height = Math.ceil(width / defaults.ratio);
              videoPlayer.style.width = `${width}px`;
              videoPlayer.style.height = `${height}px`;
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
  