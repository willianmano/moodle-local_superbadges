import $ from 'jquery';
import Swal from 'local_superbadges/sweetalert';
import showNotification from 'local_superbadges/notification';
import {get_strings as getMoodleStrings} from 'core/str';
import {deleteRequirement} from './repository';

let strings = {
    CONFIRM_TITLE: 'Are you sure?',
    CONFIRM_MSG: 'Once deleted, the item cannot be recovered!',
    CONFIRM_YES: 'Yes, delete it!',
    CONFIRM_NO: 'Cancel'
};

const COMPONENT_STRINGS = [
    {
        key: 'deleteitem_confirm_title',
        component: 'local_superbadges'
    },
    {
        key: 'deleteitem_confirm_msg',
        component: 'local_superbadges'
    },
    {
        key: 'deleteitem_confirm_yes',
        component: 'local_superbadges'
    },
    {
        key: 'deleteitem_confirm_no',
        component: 'local_superbadges'
    }
];

const getStrings = async() => {
    const trasnslatedStrings = await getMoodleStrings(COMPONENT_STRINGS);

    strings.CONFIRM_TITLE = trasnslatedStrings[0];
    strings.CONFIRM_MSG = trasnslatedStrings[1];
    strings.CONFIRM_YES = trasnslatedStrings[2];
    strings.CONFIRM_NO = trasnslatedStrings[3];
    strings.SUCCESS = trasnslatedStrings[4];
};

/* eslint-disable */
export const init = (trigger) => {
    getStrings();

    $("body").on("click", trigger, function(event) {
        event.preventDefault();

        let eventTarget = $(event.currentTarget);

        Swal.fire({
            title: strings.CONFIRM_TITLE,
            text: strings.CONFIRM_MSG,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: strings.CONFIRM_YES,
            cancelButtonText: strings.CONFIRM_NO
        }).then(function (result) {
            if (result.value) {
                deleteRequirementEntry(eventTarget);
            }
        });
    });
};

const deleteRequirementEntry = async (target) => {
    let message = await deleteRequirementFromDatabase(target.data('id'));

    if (message !== false) {
        deleteRequirementFromTable(target);

        showNotification(message);
    }
}

const deleteRequirementFromDatabase = async(id) => {
    try {
        let response = await deleteRequirement(id);

        return response.message;
    } catch (e) {
        showNotification(e.message, 'error');

        return false;
    }
}

const deleteRequirementFromTable = (target) => {
    target.closest('tr').fadeOut("normal", function() {
        $(this).remove();
    });
};
