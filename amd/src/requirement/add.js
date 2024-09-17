import $ from 'jquery';
import ModalForm from 'core_form/modalform';
import {get_string as getString} from 'core/str';
import {addRequirement} from './repository';
import showNotification from 'local_superbadges/notification';

export const init = (trigger, courseid, badgeid) => {
    document.querySelectorAll(trigger).forEach(target =>
        target.addEventListener('click', (event) => {
            event.preventDefault();

            openModal(event.target.dataset.key, courseid, badgeid);
        })
    );
};

const openModal = async (method, courseid, badgeid) => {
    let formClass = `superbadgesrequirement_${method}\\form\\requirement`;

    const modal = new ModalForm({
        formClass,
        args: {formdata: {courseid: courseid, badgeid: badgeid, method: method}},
        saveButtonText: getString('addrequirement', 'local_superbadges'),
        modalConfig: {
            title: getString('addrequirement', 'local_superbadges'),
        }
    });

    modal.addEventListener(modal.events.FORM_SUBMITTED, (event) => {
        submitFormAjax(event.detail);
    });

    modal.show();
};

const submitFormAjax = async(formdata) => {
    try {
        let response = await addRequirement(formdata);

        showNotification(response.message);

        let data = JSON.parse(response.data);

        addRequirementToTable(data);
    } catch (e) {
        showNotification(e.message, 'error');

        return false;
    }
};

const addRequirementToTable = (data) => {
    let target = data.target ??= '';

    let tableLine = $('<tr>' +
        '<th scope="row">'+data.id+'</th>' +
        '<td>'+data.pluginname+'</td>' +
        '<td>'+target +'</td>' +
        '<td>'+data.value+'</td>' +
        '<td style="width: 120px; text-align: center;">' +
        '<a href="#" data-id="'+data.id+'" class="btn btn-danger btn-sm delete-requirement">' +
        '<i class="fa fa-trash-o"></i>' +
        '</a> ' +
        '</td>' +
        '</tr>');

    tableLine
        .appendTo('.table-requirements tbody')
        .hide().fadeIn('normal');
};
