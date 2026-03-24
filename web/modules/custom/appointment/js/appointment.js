/**
 * @file
 * FullCalendar - generate slots according to adviser working days/hours and dynamic duration
 */
(function (Drupal, drupalSettings, once) {
  'use strict';

  Drupal.behaviors.appointmentCalendar = {
    attach: function (context, settings) {

      once('appointment-calendar', '#appointment-calendar', context).forEach(function (el) {

        const agencyId   = settings.appointment?.agency_id;
        const adviserId  = settings.appointment?.adviser_id;
        const workingDaysRaw  = settings.appointment?.adviser_working_days || '';
        const workingHoursRaw = settings.appointment?.adviser_working_hours || '';
        const slotDurationMinutes = settings.appointment?.default_appointment_duration || 30; // durée dynamique

        if (!agencyId || !adviserId) {
          console.warn('[Appointment] agency_id ou adviser_id manquant dans drupalSettings');
          return;
        }

        // -----------------------------
        // Parse working days
        // -----------------------------
        let workingDays = [];
        const dayNameToNumber = { Sunday:0, Monday:1, Tuesday:2, Wednesday:3, Thursday:4, Friday:5, Saturday:6 };

        if (workingDaysRaw.includes('-')) {
          const [startDay, endDay] = workingDaysRaw.split('-').map(d => d.trim());
          const startNum = dayNameToNumber[startDay];
          const endNum   = dayNameToNumber[endDay];
          if (startNum !== undefined && endNum !== undefined) {
            for (let i = startNum; i <= endNum; i++) workingDays.push(i);
          }
        } else {
          workingDays = workingDaysRaw.split(';').map(d => dayNameToNumber[d.trim()]).filter(d => d !== undefined);
        }

        if (workingDays.length === 0) workingDays = [1,2,3,4,5];

        // -----------------------------
        // Parse working hours
        // -----------------------------
        const hoursRange = workingHoursRaw.split('-');
        const startHour   = parseInt(hoursRange[0].split(':')[0], 10);
        const startMinute = parseInt(hoursRange[0].split(':')[1], 10);
        const endHour     = parseInt(hoursRange[1].split(':')[0], 10);
        const endMinute   = parseInt(hoursRange[1].split(':')[1], 10);

        // -----------------------------
        // Initialize FullCalendar
        // -----------------------------
        const calendar = new FullCalendar.Calendar(el, {
          initialView: 'timeGridWeek',
          locale: 'fr',
          slotDuration: '00:30:00',
          allDaySlot: false,
          nowIndicator: true,
          selectable: false,
          editable: false,
          slotMinTime: '08:00:00',
          slotMaxTime: '19:00:00',
          headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'timeGridWeek,timeGridDay',
          },

          events: function (info, successCallback, failureCallback) {

            fetch('/api/available-slots', {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': drupalSettings.csrfToken?.token || '',
              },
              body: JSON.stringify({
                agency_id:  agencyId,
                adviser_id: adviserId,
              }),
            })
              .then(r => r.json())
              .then(booked => {

                const slots = [];
                const cursor = new Date(info.start);

                while (cursor < info.end) {
                  const day = cursor.getDay();
                  if (workingDays.includes(day)) {

                    let slotStart = new Date(cursor.getFullYear(), cursor.getMonth(), cursor.getDate(), startHour, startMinute, 0);
                    const slotEndLimit = new Date(cursor.getFullYear(), cursor.getMonth(), cursor.getDate(), endHour, endMinute, 0);

                    while (slotStart < slotEndLimit) {
                      const end = new Date(slotStart.getTime() + slotDurationMinutes*60000); // <- durée dynamique
                      const pad = n => String(n).padStart(2,'0');
                      const key = slotStart.getFullYear() + '-' +
                        pad(slotStart.getMonth()+1) + '-' +
                        pad(slotStart.getDate()) + 'T' +
                        pad(slotStart.getHours()) + ':' +
                        pad(slotStart.getMinutes());

                      const available = booked.indexOf(key) === -1;

                      slots.push({
                        title: available ? 'Disponible' : 'Indisponible',
                        start: slotStart,
                        end: end,
                        backgroundColor: available ? '#4CAF50' : '#F44336',
                        borderColor: available ? '#2E7D32' : '#C62828',
                        textColor: '#ffffff',
                        extendedProps: { available: available },
                      });

                      slotStart = end;
                    }

                  }
                  cursor.setDate(cursor.getDate() + 1);
                }

                successCallback(slots);

              })
              .catch(e => {
                console.error('[Appointment] Erreur:', e);
                failureCallback(e);
              });
          },

          eventDidMount: function (info) {
            info.el.style.pointerEvents = 'auto';
            info.el.style.cursor = info.event.extendedProps.available ? 'pointer' : 'not-allowed';
            if (!info.event.extendedProps.available) {
              info.el.style.opacity = '0.7';
            }
          },

          eventClick: function (info) {
            info.jsEvent.preventDefault();
            info.jsEvent.stopPropagation();

            if (!info.event.extendedProps.available) return;

            calendar.getEvents().forEach(ev => {
              if (ev.extendedProps.available) {
                ev.setProp('backgroundColor', '#4CAF50');
                ev.setProp('borderColor', '#2E7D32');
              }
            });

            info.event.setProp('backgroundColor', '#1565c0');
            info.event.setProp('borderColor', '#0d47a1');

            const d = info.event.start;
            const pad = n => String(n).padStart(2,'0');
            const iso = d.getFullYear() + '-' +
              pad(d.getMonth() + 1) + '-' +
              pad(d.getDate()) + 'T' +
              pad(d.getHours()) + ':' +
              pad(d.getMinutes()) + ':' +
              pad(d.getSeconds());

            const field = document.querySelector('[name="appointment_date"]')
              || document.getElementById('appointment-selected-date');
            if (field) {
              field.value = iso;
              field.dispatchEvent(new Event('change'));
            }

            const fmt     = new Intl.DateTimeFormat('fr-FR', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
            const fmtTime = new Intl.DateTimeFormat('fr-FR', { hour: '2-digit', minute: '2-digit' });
            const label   = fmt.format(d) + ' de ' + fmtTime.format(d) + ' à ' + fmtTime.format(info.event.end);

            let infoEl = document.getElementById('appointment-slot-info');
            if (!infoEl) {
              infoEl = document.createElement('div');
              infoEl.id = 'appointment-slot-info';
              infoEl.className = 'appointment-slot-selected';
              infoEl.style.cssText = 'margin-top:10px;padding:10px;background:#e3f2fd;border-left:4px solid #1565c0;font-size:1rem;';
              el.insertAdjacentElement('afterend', infoEl);
            }
            infoEl.innerHTML = '<strong>' + Drupal.t('Créneau sélectionné :') + '</strong> ' + label;
          },
        });

        calendar.render();

      });

    },
  };

}(Drupal, drupalSettings, once));
