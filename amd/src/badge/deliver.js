import $ from 'jquery';
import Swal from 'local_superbadges/sweetalert';
import showNotification from 'local_superbadges/notification';
import {get_strings as getMoodleStrings} from 'core/str';
import {deliverBadge} from './repository';

let strings = {
    CONFIRM_TITLE: 'Are you sure?',
    CONFIRM_MSG: 'Once delivered, the badge cannot be revoked!',
    CONFIRM_YES: 'Yes, deliver it!',
    CONFIRM_NO: 'Cancel'
};

const COMPONENT_STRINGS = [
    {
        key: 'deliverbadge_confirm_title',
        component: 'local_superbadges'
    },
    {
        key: 'deliverbadge_confirm_msg',
        component: 'local_superbadges'
    },
    {
        key: 'deliverbadge_confirm_yes',
        component: 'local_superbadges'
    },
    {
        key: 'deliverbadge_confirm_no',
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
                deliverTheBadge(eventTarget);
            }
        });
    });
};

const deliverTheBadge = async(eventTarget) => {
    try {
        toggleButton(eventTarget);

        let response = await deliverBadge(eventTarget.data('id'));

        toggleButton(eventTarget);

        showNotification(response.message);

        return true;
    } catch (e) {
        showNotification(e.message, 'error');

        toggleButton(eventTarget);

        return false;
    }
}

const toggleButton = (eventTarget) => {
    let btn = $(eventTarget);
    let icon = btn.find('i');

    // Toggle enable/disable
    btn.prop('disabled', (i, v) => !v);

    let disableButton = btn.prop('disabled');

    if (disableButton) {
        icon.removeClass('fa-paper-plane-o');
        icon.addClass('fa-spinner fa-spin');
    } else {
        icon.removeClass('fa-spinner fa-spin');
        icon.addClass('fa-paper-plane-o');
    }
}
