(function (Drupal, drupalSettings) {
  'use strict';

  Drupal.behaviors.dhcrInstitutionMap = {
    attach: function attach(context) {
      var container = context.querySelector ? context.querySelector('#dhcr-institution-map') : null;
      if (!container || container.dataset.mapInitialized === '1') {
        return;
      }

      var settings = drupalSettings.dhcrInstitution || {};
      var token = settings.mapboxToken || '';
      var initialLon = Number(settings.initialLon || 16.377208);
      var initialLat = Number(settings.initialLat || 48.209131);

      if (!window.mapboxgl || !token) {
        container.innerHTML = '<p>Map preview unavailable. Configure <code>mapbox_access_token</code> in <code>dhcr_backend/config/config.yaml</code>.</p>';
        container.dataset.mapInitialized = '1';
        return;
      }

      var form = container.closest('form') || document;
      var lonInput = form.querySelector('input[name="lon[0][value]"], input[data-drupal-selector="edit-lon-0-value"]');
      var latInput = form.querySelector('input[name="lat[0][value]"], input[data-drupal-selector="edit-lat-0-value"]');

      if (lonInput && lonInput.value !== '') {
        initialLon = Number(lonInput.value);
      }
      if (latInput && latInput.value !== '') {
        initialLat = Number(latInput.value);
      }

      mapboxgl.accessToken = token;
      var map = new mapboxgl.Map({
        container: 'dhcr-institution-map',
        style: 'mapbox://styles/mapbox/streets-v11',
        center: [initialLon, initialLat],
        zoom: 9
      });

      map.addControl(new mapboxgl.NavigationControl());

      var marker = new mapboxgl.Marker({draggable: true})
        .setLngLat([initialLon, initialLat])
        .addTo(map);

      function updateCoordinateInputs(lngLat) {
        if (lonInput) {
          lonInput.value = String(lngLat.lng);
        }
        if (latInput) {
          latInput.value = String(lngLat.lat);
        }
      }

      updateCoordinateInputs(marker.getLngLat());

      marker.on('dragend', function onDragEnd() {
        updateCoordinateInputs(marker.getLngLat());
      });

      map.on('click', function onMapClick(event) {
        marker.setLngLat(event.lngLat);
        updateCoordinateInputs(event.lngLat);
      });

      if (form.addEventListener) {
        form.addEventListener('submit', function onSubmit() {
          updateCoordinateInputs(marker.getLngLat());
        });
      }

      container.dataset.mapInitialized = '1';
    }
  };
})(Drupal, drupalSettings);
