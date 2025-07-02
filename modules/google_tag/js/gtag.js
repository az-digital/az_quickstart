window.dataLayer = window.dataLayer || [];
function gtag() {
  dataLayer.push(arguments);
}
gtag('js', new Date());
gtag('set', 'developer_id.dMDhkMT', true);

(function (drupalSettings) {
  if (!drupalSettings.gtag) {return;}
  const config = drupalSettings.gtag;

  if (config.consentMode === true) {
    gtag('consent', 'default', {
      ad_storage: 'denied',
      analytics_storage: 'denied',
      ad_user_data: 'denied',
      ad_personalization: 'denied',
      wait_for_update: 500,
    });
  }

  if (config.tagId.length !== 0) {
    const script = document.createElement('script');
    script.async = true;
    script.src = `https://www.googletagmanager.com/gtag/js?id=${config.tagId}`;
    script.type = 'text/javascript';
    document.getElementsByTagName('head')[0].appendChild(script);
  }

  const additionalConfigInfo = config.additionalConfigInfo || [];
  if (additionalConfigInfo.length === 0) {
    gtag('config', config.tagId);
  } else {
    gtag('config', config.tagId, additionalConfigInfo);
  }

  const otherIds = config.otherIds || [];
  otherIds.forEach((id) => gtag('config', id));

  const events = config.events || [];
  events.forEach((event) => gtag('event', event.name, event.data));
})(drupalSettings);
