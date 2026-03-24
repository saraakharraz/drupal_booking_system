/**
 * Booking Calendar - FullCalendar Integration
 */
(function (Drupal, drupalSettings, once) {
  'use strict';

  Drupal.behaviors.appointmentBookingCalendar = {
    attach: function (context, settings) {
      const calendarContainer = once('booking-calendar', '#appointment-calendar', context);

      if (!calendarContainer.length) {
        return;
      }

      let selectedEvent = null;

      const calendar = new FullCalendar.Calendar(calendarContainer[0], {
        initialView: 'timeGridWeek',
        headerToolbar: {
          left: 'prev,next today',
          center: 'title',
          right: 'timeGridWeek,timeGridDay',
        },
        height: 'auto',
        slotDuration: '00:30:00',
        slotLabelInterval: '00:30',
        slotLabelFormat: {
          meridiem: 'short',
          hour: 'numeric',
          minute: '2-digit',
        },
        // FILTRER SEULEMENT LUNDI À SAMEDI (1-6)
        // Masquer dimanche (0) et dimanche (7)
        dayCellClassNames: function(arg) {
          // arg.date = Date objet du jour
          const dayOfWeek = arg.date.getDay(); // 0=Dimanche, 6=Samedi
          // Afficher seulement lundi (1) à samedi (6)
          if (dayOfWeek === 0) {
            return ['fc-disabled-day'];
          }
          return [];
        },
        slotMinTime: '07:00:00',
        slotMaxTime: '22:00:00',
        selectable: true,
        eventInteractive: true,
        events: function (info, successCallback, failureCallback) {
          const agencyId = document.querySelector('[name="agency"]')?.value;
          const adviserId = document.querySelector('[name="adviser"]')?.value;

          if (!agencyId || !adviserId) {
            successCallback([]);
            return;
          }

          fetch('/api/available-slots', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-Token': drupalSettings.csrfToken?.token || '',
            },
            body: JSON.stringify({
              agency_id: agencyId,
              adviser_id: adviserId,
              start: info.startStr,
              end: info.endStr,
            }),
          })
            .then(response => response.json())
            .then(data => {
              console.log('✓ Slots loaded:', data.length + ' slots');
              successCallback(data);
            })
            .catch(error => {
              console.error('✗ Error loading slots:', error);
              failureCallback(error);
            });
        },
        select: function (info) {
          // Ne pas sélectionner dimanche
          const dayOfWeek = info.start.getDay();
          if (dayOfWeek === 0) {
            alert('Dimanche n\'est pas disponible');
            return;
          }

          selectedEvent = info.start;
          saveDatetime(info.start);
          updateCalendarColors(calendar, selectedEvent);
          calendar.unselect();
        },
        eventClick: function (info) {
          if (info.event.extendedProps.available) {
            selectedEvent = info.event.start;
            saveDatetime(info.event.start);
            updateCalendarColors(calendar, selectedEvent);
          }
        },
      });

      calendar.render();

      // Reload events when agency or adviser changes
      const agencySelect = document.querySelector('[name="agency"]');
      const adviserSelect = document.querySelector('[name="adviser"]');

      if (agencySelect) {
        agencySelect.addEventListener('change', function () {
          selectedEvent = null;
          clearDatetime();
          calendar.refetchEvents();
        });
      }

      if (adviserSelect) {
        adviserSelect.addEventListener('change', function () {
          selectedEvent = null;
          clearDatetime();
          calendar.refetchEvents();
        });
      }

      /**
       * Save datetime in Drupal format: YYYY-MM-DD HH:mm:ss
       */
      function saveDatetime(dateObj) {
        const dateTimeField = document.querySelector('[name="appointment_date"]');
        if (dateTimeField) {
          // Format: YYYY-MM-DD HH:mm:ss (Drupal datetime format)
          const year = dateObj.getFullYear();
          const month = String(dateObj.getMonth() + 1).padStart(2, '0');
          const day = String(dateObj.getDate()).padStart(2, '0');
          const hours = String(dateObj.getHours()).padStart(2, '0');
          const minutes = String(dateObj.getMinutes()).padStart(2, '0');

          const drupalFormat = `${year}-${month}-${day} ${hours}:${minutes}:00`;
          dateTimeField.value = drupalFormat;

          console.log('✓ Datetime saved:', drupalFormat);
          console.log('✓ Field value:', dateTimeField.value);
          console.log('✓ Field exists:', !!dateTimeField);
        }

        // Display selected datetime
        const displayField = document.querySelector('#selected-datetime');
        if (displayField) {
          displayField.innerHTML = '<p style="color: #4CAF50; font-weight: bold;"><strong>✓ Selected:</strong> ' +
            dateObj.toLocaleString() + '</p>';
        }
      }

      /**
       * Clear datetime field
       */
      function clearDatetime() {
        const dateTimeField = document.querySelector('[name="appointment_date"]');
        if (dateTimeField) {
          dateTimeField.value = '';
        }

        const displayField = document.querySelector('#selected-datetime');
        if (displayField) {
          displayField.innerHTML = '';
        }
      }

      /**
       * Update calendar colors based on selection
       */
      function updateCalendarColors(cal, selected) {
        cal.getEvents().forEach(event => {
          if (event.start && selected && event.start.getTime() === selected.getTime() && event.extendedProps.available) {
            event.setProp('backgroundColor', '#2196F3');  // Bleu
            event.setProp('borderColor', '#1976D2');
            event.setProp('textColor', '#fff');
            event.setProp('title', '✓ Selected');
          } else if (event.extendedProps.available) {
            event.setProp('backgroundColor', '#4CAF50');  // Vert
            event.setProp('borderColor', '#2E7D32');
            event.setProp('textColor', '#fff');
            event.setProp('title', 'Available');
          }
        });
      }
    },
  };
})(Drupal, drupalSettings, once);
