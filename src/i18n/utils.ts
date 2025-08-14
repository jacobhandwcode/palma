import { ui, defaultLang, type UiType } from './ui';

export function getLangFromUrl(url: URL) {
  const [, lang] = url.pathname.split('/');
  if (lang in ui) return lang as keyof UiType;
  return defaultLang;
}

export function useTranslations(lang: keyof UiType) {
  return function t(key: keyof UiType[typeof defaultLang]) {
    return ui[lang][key] || ui[defaultLang][key];
  };
}

export function getLocalizedUrl(lang: string, path: string = '') {
  const cleanPath = path.replace(/^\//, '');

  if (lang === defaultLang) {
    return cleanPath ? `/${cleanPath}` : '/';
  }

  return cleanPath ? `/${lang}/${cleanPath}` : `/${lang}/`;
}

import { defineConfig } from 'astro/config';

export default defineConfig({
  i18n: {
    defaultLocale: 'en',
    locales: ['en', 'es', 'pt'],
    routing: {
      prefixDefaultLocale: false,
    },
  },
});
