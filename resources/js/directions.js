import { parseCoordinates, readConfiguredCoordinates } from './location-coordinates.js';

export function buildGoogleMapsDirectionsUrl(origin, destination) {
    const googleMapsUrl = new URL('https://www.google.com/maps/dir/');
    const query = new URLSearchParams({
        api: '1',
        destination: `${destination[0]},${destination[1]}`,
        origin: `${origin[0]},${origin[1]}`,
    });

    googleMapsUrl.search = query.toString();

    return googleMapsUrl.toString();
}

function showLocationError(status, errorCode) {
    const messages = {
        1: 'Location access was denied. Allow location access in your browser settings or use the manual directions link.',
        2: 'Your location is unavailable right now. Retry or use the manual directions link.',
        3: 'Finding your location took too long. Retry or use the manual directions link.',
    };

    status.textContent = messages[errorCode] ?? 'Your location could not be retrieved. Retry or use the manual directions link.';
}

export function initializeDirections(root = document) {
    const locationElement = root.querySelector('[data-dormitory-location]');
    const directionsButton = root.querySelector('#get-directions');
    const directionsStatus = root.querySelector('#directions-status');
    const buttonLabel = root.querySelector('[data-directions-label]');
    const loadingIndicator = root.querySelector('[data-loading-indicator]');

    if (!locationElement || !directionsButton || !directionsStatus || !buttonLabel || !loadingIndicator) {
        return;
    }

    let locationRequestPending = false;

    directionsButton.addEventListener('click', () => {
        if (locationRequestPending) {
            return;
        }

        const destination = readConfiguredCoordinates(locationElement);

        if (!destination) {
            directionsStatus.textContent = 'Automatic directions are unavailable until verified dormitory coordinates are configured. Use the manual Google Maps link and confirm the destination.';
            return;
        }

        if (!navigator.geolocation) {
            directionsStatus.textContent = 'Automatic location detection is not supported by this browser. Use the manual directions link instead.';
            return;
        }

        locationRequestPending = true;
        directionsButton.disabled = true;
        buttonLabel.textContent = 'Finding your location…';
        loadingIndicator.classList.remove('hidden');
        directionsStatus.textContent = 'Requesting your location to open a Google Maps route…';

        navigator.geolocation.getCurrentPosition(
            ({ coords }) => {
                locationRequestPending = false;
                const origin = parseCoordinates(coords.latitude, coords.longitude);

                if (!origin) {
                    directionsButton.disabled = false;
                    buttonLabel.textContent = 'Get Directions';
                    loadingIndicator.classList.add('hidden');
                    directionsStatus.textContent = 'Your location could not be retrieved. Retry or use the manual directions link.';
                    return;
                }

                directionsStatus.textContent = 'Location found. Opening Google Maps; you can choose your travel mode and start navigation there.';
                window.location.assign(buildGoogleMapsDirectionsUrl(origin, destination));
            },
            ({ code }) => {
                locationRequestPending = false;
                directionsButton.disabled = false;
                buttonLabel.textContent = 'Get Directions';
                loadingIndicator.classList.add('hidden');
                showLocationError(directionsStatus, code);
            },
            {
                enableHighAccuracy: false,
                maximumAge: 0,
                timeout: 10000,
            },
        );
    });
}
