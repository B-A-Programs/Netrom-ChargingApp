function GenerateMap(longitude, latitude, zoom)
{
    let map = L.map('map').setView({lon: longitude, lat: latitude}, zoom);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; <a href="https://openstreetmap.org/copyright">OpenStreetMap contributors</a>'
    }).addTo(map);

    L.control.scale({imperial: true, metric: true}).addTo(map);
    return map;
}

function PlaceMarkersMap(map, longitude, latitude, name)
{
    let layer = L.marker({
        lon: longitude,
        lat: latitude
    }).addTo(map);
    layer.on('click', function () {
        window.location.href = `/location/${name}`;
    });
}