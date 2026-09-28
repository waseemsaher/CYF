try {
	const theme = localStorage.getItem('codeera_theme') || localStorage.getItem('coderaa_theme') || localStorage.getItem('fcai_theme') || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
	document.documentElement.setAttribute('data-theme', theme);
	if (theme === 'dark') {
		document.documentElement.classList.add('dark');
	} else {
		document.documentElement.classList.remove('dark');
	}
	const locale = localStorage.getItem('codeera_locale');
	if (locale === 'en' || locale === 'ar') {
		document.documentElement.setAttribute('lang', locale);
		document.documentElement.setAttribute('dir', locale === 'ar' ? 'rtl' : 'ltr');
		document.documentElement.classList.toggle('locale-ar', locale === 'ar');
		document.documentElement.classList.toggle('locale-en', locale === 'en');
	}
} catch (_) {}
