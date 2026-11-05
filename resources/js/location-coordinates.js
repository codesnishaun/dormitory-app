export function parseCoordinates(latitude, longitude) {
    if (latitude === '' || longitude === '' || latitude === null || longitude === null) {
        return null;
    }

    const parsedLatitude = Number(latitude);
    const parsedLongitude = Number(longitude);

    if (
        !Number.isFinite(parsedLatitude)
        || !Number.isFinite(parsedLongitude)
        || parsedLatitude < -90
        || parsedLatitude > 90
        || parsedLongitude < -180
        || parsedLongitude > 180
    ) {
        return null;
    }

    return [parsedLatitude, parsedLongitude];
}

export function readConfiguredCoordinates(locationElement) {
    return parseCoordinates(
        locationElement.dataset.latitude,
        locationElement.dataset.longitude,
    );
}
