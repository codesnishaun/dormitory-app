import L from 'leaflet';
import { readConfiguredCoordinates } from './location-coordinates.js';

export function initializeDormitoryMap(root = document) {
    const locationElement = root.querySelector('[data-dormitory-location]');
    const mapElement = root.querySelector('#dormitory-map');
    const fallbackElement = root.querySelector('#map-fallback');
    const mapStatus = root.querySelector('#map-status');

    if (!locationElement || !mapElement || !fallbackElement) {
        return;
    }

    const coordinates = readConfiguredCoordinates(locationElement);

    if (!coordinates) {
        return;
    }

    try {
        const map = L.map(mapElement, {
            keyboard: true,
            scrollWheelZoom: true,
        }).setView(coordinates, 16);

        const tileLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap contributors</a>',
        }).addTo(map);

        tileLayer.on('tileerror', () => {
            if (mapStatus) {
                mapStatus.textContent = 'Map tiles could not be loaded. Check your connection or use manual directions in Google Maps.';
            }
        });

        const markerIcon = L.divIcon({
            className: 'dormitory-marker',
            html: '<span class="dormitory-marker__pin"><span aria-hidden="true">⌂</span></span>',
            iconAnchor: [18, 35],
            iconSize: [36, 36],
            popupAnchor: [0, -32],
        });
        const marker = L.marker(coordinates, {
            alt: locationElement.dataset.name,
            icon: markerIcon,
            title: locationElement.dataset.name,
        }).addTo(map);
        const popup = document.createElement('div');
        const name = document.createElement('strong');
        const address = document.createElement('div');

        name.textContent = locationElement.dataset.name;
        address.textContent = locationElement.dataset.address;
        popup.append(name, address);
        marker.bindPopup(popup);

        fallbackElement.classList.add('hidden');
        requestAnimationFrame(() => map.invalidateSize({ pan: false }));

        if ('ResizeObserver' in window) {
            const resizeObserver = new ResizeObserver(() => map.invalidateSize({ pan: false }));
            resizeObserver.observe(mapElement);
        }
    } catch (error) {
        console.error('The dormitory map could not be initialized.', error);
        fallbackElement.textContent = 'The interactive map could not be loaded. Check your connection or use manual directions in Google Maps.';
    }
}
