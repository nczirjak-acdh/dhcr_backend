(function (Drupal, drupalSettings, once) {
  'use strict';

  Drupal.behaviors.dhcrUserEditCountry = {
    attach: function attach(context) {
      once('dhcr-user-edit-country', '.dhcr-user-edit-form', context).forEach(function (form) {
        var roleSelect = form.querySelector('[name="user_role_id"]');
        var institutionSelect = form.querySelector('[name="institution_id"]');
        var countryRow = form.querySelector('[data-dhcr-moderated-country]');
        var countryValue = form.querySelector('[data-dhcr-moderated-country-value]');
        var countries = (drupalSettings.dhcrUserEdit || {}).institutionCountries || {};

        if (!roleSelect || !institutionSelect || !countryRow || !countryValue) {
          return;
        }

        function updateModeratedCountry() {
          countryRow.hidden = roleSelect.value !== '2';
          countryValue.textContent = countries[institutionSelect.value] || '-';
        }

        roleSelect.addEventListener('change', updateModeratedCountry);
        institutionSelect.addEventListener('change', updateModeratedCountry);
        updateModeratedCountry();
      });
    }
  };
})(Drupal, drupalSettings, once);
