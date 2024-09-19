import $ from 'jquery';
import ModalForm from 'core_form/modalform';
import {get_string as getString} from 'core/str';
import showNotification from 'local_superbadges/notification';

/* eslint-disable */
let eventTarget;

export const init = (selector, courseid) => {
    $("body").on("click", selector, function(event) {
        event.preventDefault();

        eventTarget = $(event.currentTarget);

        openModal();
    });
};

const openModal = () => {
    let formClass = 'local_superbadges\\forms\\badge';

    const modal = new ModalForm({
        formClass,
        args: {formdata: {
            id: eventTarget.data('id'),
            courseid: eventTarget.data('courseid'),
            badgeid: eventTarget.data('badgeid'),
            name: eventTarget.data('name'),
            description: eventTarget.data('description'),
        }},
        saveButtonText: getString('editbadge', 'local_superbadges'),
        modalConfig: {
            title: getString('editbadge', 'local_superbadges'),
        }
    });

    modal.addEventListener(modal.events.FORM_SUBMITTED, (event) => {
        if (event.detail.message) {
            showNotification(event.detail.message);

            let data = JSON.parse(event.detail.data);

            return changeTableRowData(data);
        } else {
            const warningMessages = event.detail.warnings.map(warning => warning.message);

            showNotification(warningMessages.join('<br>'), 'error');
        }
    });

    modal.show();
};

const changeTableRowData = (data) => {
    const tablenamecolumn = eventTarget.closest('tr').find('td:first');

    tablenamecolumn.html(data.name);

    eventTarget.data('name', data.name);
    eventTarget.data('description', data.description);

    eventTarget.closest('tr').hide('normal').show('normal');
};
