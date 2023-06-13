import { useEffect, useState } from 'react';
import { hubspotBaseUrl, portalId, locale } from '../constants/leadinConfig';
import { fetchRefreshToken } from '../api/wordpressApiClient';
import Raven from '../lib/Raven';

const IFRAME_DISPLAY_TIMEOUT = 5000;

export default function useAppEmbedder(
  app: string,
  container: HTMLElement | null
) {
  const [iframeNotRendered, setIframeNotRendered] = useState(false);
  useEffect(() => {
    const timer = setTimeout(() => {
      const iframe = document.getElementById(app);
      if (!iframe) {
        Raven.captureException(new Error(`Leadin Iframe blocked`), {
          fingerprint: ['IFRAME_SETUP_ERROR'],
        });
        setIframeNotRendered(true);
      }
    }, IFRAME_DISPLAY_TIMEOUT);

    return () => {
      if (timer) {
        clearTimeout(timer);
      }
    };
  }, []);

  useEffect(() => {
    fetchRefreshToken().then(({ refreshToken }) => {
      const { IntegratedAppEmbedder, FormsAppOptions }: any = window;
      if (IntegratedAppEmbedder) {
        let options = new FormsAppOptions()
          .setRefreshToken(refreshToken)
          .setLocale(locale);

        const queryParams = new URLSearchParams(location.search);
        const route = queryParams.get('leadin_route[0]');
        if (route && route === 'create') {
          options = options.setCreateFormAppInit();
        }
        const embedder = new IntegratedAppEmbedder(
          app,
          portalId,
          hubspotBaseUrl,
          () => {
            const adminMenuWrap = document.getElementById('adminmenuwrap');
            const sideMenuHeight = adminMenuWrap
              ? adminMenuWrap.offsetHeight
              : 0;
            const adminBar = document.getElementById('wpadminbar');
            const adminBarHeight = (adminBar && adminBar.offsetHeight) || 0;
            const offset = 4;
            if (window.innerHeight < sideMenuHeight) {
              return sideMenuHeight - offset;
            } else {
              return window.innerHeight - adminBarHeight - offset;
            }
          }
        ).setOptions(options);
        embedder.attachTo(container);
      }
    });
  }, []);

  return iframeNotRendered;
}
