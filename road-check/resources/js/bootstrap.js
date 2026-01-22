import axios from 'axios';
window.axios = axios;

const ROLE_STORAGE_KEY = 'rc-current-role';

const resolveStoredRole = () => {
	if (typeof window === 'undefined') {
		return 'visitor';
	}

	if (typeof window.location !== 'undefined' && window.location.pathname === '/users') {
		if (typeof window.localStorage !== 'undefined') {
			window.localStorage.setItem(ROLE_STORAGE_KEY, 'manager');
		}
		return 'manager';
	}

	if (typeof window.localStorage !== 'undefined') {
		const stored = window.localStorage.getItem(ROLE_STORAGE_KEY);
		if (stored) {
			return stored;
		}
	}

	return 'visitor';
};

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

window.axios.interceptors.request.use((config) => {
	const role = resolveStoredRole();
	config.headers = config.headers || {};
	config.headers['X-User-Role'] = role;
	return config;
});
