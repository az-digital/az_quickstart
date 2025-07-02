((Drupal, once) => {

  const replacements = {
    direct: ['/streaming/', '/mp4/'],
    streaming: ['/mp4/', '/streaming/'],
  };

  /**
   * Find and replace link type with correct value in asset url.
   *
   * @param assetUrl string
   * @param linkType 'direct' | 'streaming'
   */
  const correctAssetUrl = (assetUrl, linkType) => {
    if (!assetUrl) {
      return;
    }

    let url;

    try {
      url = new URL(assetUrl);
    } catch (e) {
      return;
    }

    if (url instanceof URL) {
      url.pathname = url.pathname.replaceAll(
        replacements[linkType][0],
        replacements[linkType][1]
      )

      return url.toString();
    }

    return assetUrl;
  }

  Drupal.behaviors.ibDamLinkTypeCheker = {
    attach(context) {
      let linkType;

      let assetUrl = once('ibDamLinkTypeChecker', 'input[name="settings[remote_url]"]', context)[0] ?? false;
      let linkTypeOptions = once('ibDamLinkTypeChecker', 'input[name="settings[link_type]"]', context) ?? [];

      if (!linkTypeOptions.length || !assetUrl) {
        return;
      }

      assetUrl.addEventListener('blur', e => {
        const adjusted = correctAssetUrl(e.target.value, linkType);
        if (adjusted) {
          e.target.value = adjusted;
        }
      });

      linkTypeOptions.forEach(type => {
        type.addEventListener('click', e => {
          linkType = e.target.value;
          const adjusted = correctAssetUrl(assetUrl?.value, linkType);
          if (adjusted) {
            assetUrl.value = adjusted;
          }
        });
      })
    }
  };

})(Drupal, once);
