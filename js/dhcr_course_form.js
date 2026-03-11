(function (Drupal, drupalSettings) {
  'use strict';

  Drupal.behaviors.dhcrCourseMap = {
    attach: function attach(context) {
      var container = context.querySelector ? context.querySelector('#dhcr-course-map') : null;
      if (!container || container.dataset.mapInitialized === '1') {
        return;
      }

      var settings = drupalSettings.dhcrBackend || {};
      var token = settings.mapboxToken || '';
      var initialLon = Number(settings.initialLon || 16.377208);
      var initialLat = Number(settings.initialLat || 48.209131);

      if (!window.mapboxgl || !token) {
        container.innerHTML = '<p>Map preview unavailable. Configure <code>mapbox_access_token</code> in <code>dhcr_backend/config/config.yaml</code>.</p>';
        container.dataset.mapInitialized = '1';
        return;
      }

      var lonInput = document.querySelector('input[name="lon[0][value]"]');
      var latInput = document.querySelector('input[name="lat[0][value]"]');

      if (lonInput && lonInput.value !== '') {
        initialLon = Number(lonInput.value);
      }
      if (latInput && latInput.value !== '') {
        initialLat = Number(latInput.value);
      }

      mapboxgl.accessToken = token;
      var map = new mapboxgl.Map({
        container: 'dhcr-course-map',
        style: 'mapbox://styles/mapbox/streets-v11',
        center: [initialLon, initialLat],
        zoom: 9
      });

      map.addControl(new mapboxgl.NavigationControl());

      var marker = new mapboxgl.Marker({draggable: true})
        .setLngLat([initialLon, initialLat])
        .addTo(map);

      marker.on('dragend', function onDragEnd() {
        var lngLat = marker.getLngLat();
        if (lonInput) {
          lonInput.value = String(lngLat.lng);
        }
        if (latInput) {
          latInput.value = String(lngLat.lat);
        }
      });

      container.dataset.mapInitialized = '1';
    }
  };
})(Drupal, drupalSettings);
