const dl = drupalSettings.gtm ? drupalSettings.gtm.settings.data_layer : 'dataLayer';
window[dl] = window[dl] || [];

(function (drupalSettings) {
  if (!drupalSettings.gtm) {return;}
  const config = drupalSettings.gtm;

  window[dl].push({ 'gtm.start': new Date().getTime(), event: 'gtm.js' });
  const gtmSettings = config.settings;
  if (gtmSettings.include_classes === true) {
    window[dl].push({
      'gtm.allowlist': gtmSettings.allowlist_classes ?? [],
      'gtm.blocklist': gtmSettings.blocklist_classes ?? [],
    });
  }

  let gtm_environment = '';
  if (gtmSettings.include_environment === true) {
    const gtm_auth = gtmSettings.environment_token ?? '';
    const gtm_preview = gtmSettings.environment_id ?? '';
    gtm_environment = `&gtm_auth=${gtm_auth}&gtm_preview=${gtm_preview}&gtm_cookies_win=x`;
  }
  config.tagIds.forEach(function (tagId) {
    const script = document.createElement('script');
    script.async = true;
    const dLink = dl != 'dataLayer' ? `&l=${dl}` : '';
    script.src = `https://www.googletagmanager.com/gtm.js?id=${tagId}${gtm_environment}${dLink}`;
    script.type = 'text/javascript';
    document.getElementsByTagName('head')[0].appendChild(script);
  });
})(drupalSettings);
