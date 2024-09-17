import $ from 'jquery';
import ModalForm from 'core_form/modalform';
import {get_string as getString} from 'core/str';
import showNotification from 'local_superbadges/notification';
import Templates from 'core/templates';

export const init = (selector, courseid) => {
    document.querySelectorAll(selector).forEach(target =>
        target.addEventListener('click', (event) => {
            event.preventDefault();

            openModal(courseid);
        })
    );
};

const openModal = async (courseid) => {
    let formClass = 'local_superbadges\\forms\\badge';

    const modal = new ModalForm({
        formClass,
        args: {formdata: {courseid: courseid}},
        saveButtonText: getString('createbadge', 'local_superbadges'),
        modalConfig: {
            title: getString('createbadge', 'local_superbadges'),
        }
    });

    modal.addEventListener(modal.events.FORM_SUBMITTED, (event) => {
        if (event.detail.message) {
            showNotification(event.detail.message);

            let data = JSON.parse(event.detail.data);

            return addBadgeToTable(data);
        } else {
            const warningMessages = event.detail.warnings.map(warning => warning.message);

            showNotification(warningMessages.join('<br>'), 'error');
        }
    });

    modal.show();
};

const addBadgeToTable = async (data) => {

    let tableLine = await Templates.render('local_superbadges/badge_tableline', data);

    $('.table-badges tbody').append(tableLine).hide().fadeIn();
};
