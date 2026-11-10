/** @type {import('tailwindcss').Config} */
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import markerIcon from 'leaflet/dist/images/marker-icon.png';
import markerShadow from 'leaflet/dist/images/marker-shadow.png';
import { initializeDirections } from './directions.js';
import { initializeDormitoryMap } from './dormitory-map.js';
import './chat-assistant.js';

delete L.Icon.Default.prototype._getIconUrl;

L.Icon.Default.mergeOptions({
    iconUrl: markerIcon,
    shadowUrl: markerShadow,
});

initializeDormitoryMap();
initializeDirections();

document.querySelectorAll('[data-mobile-navigation] a').forEach((link) => {
    link.addEventListener('click', () => {
        const mobileNavigation = link.closest('[data-mobile-navigation]');

        if (mobileNavigation) {
            mobileNavigation.open = false;
        }
    });
});
