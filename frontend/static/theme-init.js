try {
	const savedTheme = localStorage.getItem('codeera_theme') || localStorage.getItem('coderaa_theme') || localStorage.getItem('fcai_theme');
	const theme = savedTheme === 'light' ? 'light' : 'dark';
	document.documentElement.setAttribute('data-theme', theme);
	if (theme === 'dark') {
		document.documentElement.classList.add('dark');
	} else {
		document.documentElement.classList.remove('dark');
	}
	const savedLocale = localStorage.getItem('codeera_locale');
	const locale = savedLocale === 'en' ? 'en' : 'ar';
	document.documentElement.setAttribute('lang', locale);
	document.documentElement.setAttribute('dir', locale === 'ar' ? 'rtl' : 'ltr');
	document.documentElement.classList.toggle('locale-ar', locale === 'ar');
	document.documentElement.classList.toggle('locale-en', locale === 'en');
} catch (_) {}
