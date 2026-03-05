import './bootstrap.js';
/*
 * Welcome to your app's main JavaScript file!
 *
 * This file will be included onto the page via the importmap() Twig function,
 * which should already be in your base.html.twig.
 */
import './styles/app.css';
import 'flatpickr/dist/flatpickr.min.css';

console.log('This log comes from assets/app.js - welcome to AssetMapper! 🎉');

function ensureSymfonyToolbarVisible() {
	const toolbar = document.querySelector('.sf-toolbar.sf-display-none');
	if (toolbar) {
		toolbar.classList.remove('sf-display-none');
	}
}

document.addEventListener('DOMContentLoaded', ensureSymfonyToolbarVisible);
document.addEventListener('turbo:load', ensureSymfonyToolbarVisible);
