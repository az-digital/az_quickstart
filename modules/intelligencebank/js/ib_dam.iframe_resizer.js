((Drupal, once) => {

  Drupal.behaviors.ibDamIframeResizer = {
    attach(context) {
      const embeds = once('ibDamIframeResizer', '.id-dam-embed-wrapper', context) ?? [];

      if (!embeds.length) {
        return;
      }

      const updateEmbed = (embed) => {
        const iframe = embed.querySelector('iframe');

        let ratio = 2;

        if (iframe) {
          const w = parseInt(iframe?.width ?? 0);
          const h = parseInt(iframe?.height ?? 0);
          if (w > 0 && h > 0) {
            ratio = Math.round(w / h);
          }
        }

        let ratioName = 'ib-ratio-16x9';

        switch (ratio) {
          default:
          case 2: ratioName = 'ib-ratio-16x9';
            break;
        }
        embed.classList.remove.apply(
          embed.classList,
          Array.from(embed.classList).filter(v => v.startsWith('ib-ratio'))
        );
        embed.classList.add('ib-ratio', ratioName);
      }

      // Create a ResizeObserver to update the embed "ratio" classes when its
      // parent container resizes.
      const resizeObserver = new ResizeObserver((entries) => {
        for (let entry of entries) {
          embeds.forEach(embed => {
            updateEmbed(embed);
          });
        }
      });

      embeds.forEach((embed) => {
        updateEmbed(embed);
        resizeObserver.observe(embed);
      });
    }
  };
})(Drupal, once);
