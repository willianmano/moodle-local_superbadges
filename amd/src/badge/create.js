import $ from 'jquery';
import ModalForm from 'core_form/modalform';
import {get_string as getString} from 'core/str';
import showNotification from 'local_superbadges/notification';
import Config from 'core/config';

/* eslint-disable */
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
            Notification.addNotification({
                type: 'error',
                message: warningMessages.join('<br>')
            });
        }
    });

    modal.show();
};

const addBadgeToTable = (data) => {
    let tableLine = $('<tr>' +
        '<th scope="row">' + data.id + '</th>' +
        '<td>' + data.name + '</td>' +
        '<td style="width: 200px; text-align: center;">' +
        '<a href="#" data-id="' + data.id + '" class="btn btn-info btn-sm deliver-badge">' +
        '<i class="fa fa-paper-plane-o"></i>' +
        '</a> ' +
        '<a href="' + Config.wwwroot + '/local/superbadges/requirements.php?id=' +
        data.id + '" data-id="' + data.id + '" ' +
        'class="btn btn-primary btn-sm"><i class="fa fa-list"></i></a>' +
        '</a> ' +
        '<a href="#" data-id="' + data.id + '" data-name="' + data.name + '"' +
        ' data-description="' + data.description + '"' +
        ' data-courseid="' + data.courseid + '" data-badgeid="' + data.badgeid + '"' +
        ' class="btn btn-warning btn-sm edit-badge">' +
        '<i class="fa fa-pencil-square-o text-white"></i>' +
        '</a> ' +
        '<a href="#" data-id="' + data.id + '" class="btn btn-danger btn-sm delete-badge">' +
        '<i class="fa fa-trash-o"></i>' +
        '</a> ' +
        '</td>' +
        '</tr>');

    tableLine
        .appendTo('.table-badges tbody')
        .hide().fadeIn('normal');
};
