import $ from 'jquery';

// Funktion, die bei jedem Seiten- und Turbo-Wechsel frisch aufgesetzt wird
function initCronAutomation() {
    const $cronStatus = $('.js-cron-status');
    const $cronTime   = $('.js-cron-time');
    const $cronLabel  = $('.js-cron-label');
    const $cronSubmit = $('.js-cron-submit');

    // Wenn die Elemente auf der aktuellen Seite nicht existieren -> Abbrechen
    if (!$cronStatus.length) return;

    // 1. Sichere die exakten Werte frisch im Moment des Seiten/Body-Austauschs
    const initialChecked = $cronStatus.is(':checked');
    const initialTime    = $cronTime.val();

    function checkFormChanges() {
        const currentChecked = $cronStatus.is(':checked');
        const currentTime    = $cronTime.val();

        // Zeit-Eingabe sperren oder freigeben
        if (currentChecked) {
            $cronTime.prop('readonly', false).prop('disabled', false);
            $cronLabel.removeClass('text-opacity-50');
        } else {
            $cronTime.prop('readonly', true).prop('disabled', true);
            $cronLabel.addClass('text-opacity-50');
        }

        // Änderungsprüfung im Vergleich zu den eingefrorenen Startwerten
        let hasChanged = false;
        if (currentChecked !== initialChecked) {
            hasChanged = true;
        } else if (currentChecked && currentTime !== initialTime) {
            hasChanged = true;
        }

        // Button steuern
        $cronSubmit.prop('disabled', !hasChanged);
    }

    // 2. Event-Listener (entfernen und neu binden, um Doppel-Bindungen zu vermeiden)
    $cronStatus.off('change').on('change', checkFormChanges);
    $cronTime.off('input change').on('input change', checkFormChanges);
}


$(document).ready(function() {
    initCronAutomation();
});

$(document).on('turbo:load', function() {
    initCronAutomation();
});
