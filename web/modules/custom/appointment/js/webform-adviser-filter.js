(function (Drupal, drupalSettings, once) {
  'use strict';

  Drupal.behaviors.appointmentAdviserFilter = {
    attach: function (context, settings) {
      // Get the radio buttons from webform avec les BONNES CLÉS
      const agencyRadios = document.querySelectorAll('[name="webform_submission[agency_select]"]');
      const typeRadios = document.querySelectorAll('[name="webform_submission[appointment_type]"]');
      const adviserRadios = document.querySelectorAll('[name="webform_submission[adviser]"]');

      if (!agencyRadios.length || !typeRadios.length || !adviserRadios.length) {
        console.warn('Adviser filter: Missing form elements');
        console.log('Agency radios found:', agencyRadios.length);
        console.log('Type radios found:', typeRadios.length);
        console.log('Adviser radios found:', adviserRadios.length);
        return;
      }

      // Get advisers data from drupalSettings
      const advisersData = drupalSettings.appointmentBooking?.advisers || {};

      console.log('=== ADVISER FILTER INITIALIZED ===');
      console.log('Advisers Data:', advisersData);

      /**
       * Filter and show/hide adviser options based on selected agency and type.
       */
      function updateAdviserOptions() {
        // Get selected agency
        let selectedAgency = null;
        agencyRadios.forEach(radio => {
          if (radio.checked) {
            selectedAgency = radio.value;
          }
        });

        // Get selected type
        let selectedType = null;
        typeRadios.forEach(radio => {
          if (radio.checked) {
            selectedType = radio.value;
          }
        });

        console.log('--- UPDATE ADVISER OPTIONS ---');
        console.log('Selected Agency:', selectedAgency);
        console.log('Selected Type:', selectedType);

        // Get matching advisers
        const key = selectedAgency + '_' + selectedType;
        const matchingAdvisers = advisersData[key] || {};

        console.log('Key:', key);
        console.log('Matching Advisers:', matchingAdvisers);

        // Show/hide adviser options
        let adviserFound = false;
        adviserRadios.forEach(radio => {
          const adviserId = radio.value;
          const adviserLabel = radio.parentElement;

          if (matchingAdvisers[adviserId]) {
            // Show this adviser
            adviserLabel.style.display = 'block';
            adviserFound = true;
            radio.disabled = false;
            console.log('✓ Showing adviser:', adviserId);
          } else {
            // Hide this adviser
            adviserLabel.style.display = 'none';
            radio.checked = false; // Uncheck if hidden
            radio.disabled = true;
            console.log('✗ Hiding adviser:', adviserId);
          }
        });

        // Log result
        if (!adviserFound) {
          console.warn('⚠ No matching advisers found for agency:', selectedAgency, 'and type:', selectedType);
        } else {
          console.log('✓ Advisers updated successfully');
        }
      }

      // Listen for changes on agency radios
      agencyRadios.forEach(radio => {
        radio.addEventListener('change', updateAdviserOptions);
      });

      // Listen for changes on type radios
      typeRadios.forEach(radio => {
        radio.addEventListener('change', updateAdviserOptions);
      });

      // Initial update
      console.log('Running initial adviser filter...');
      updateAdviserOptions();
    }
  };
})(Drupal, drupalSettings, once);
