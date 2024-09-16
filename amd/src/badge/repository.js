import {call as fetchAjax} from 'core/ajax';

export const createBadge = (
    formdata
) => fetchAjax([{
    methodname: 'local_superbadges_createbadge',
    args: {
        jsonformdata: JSON.stringify(formdata)
    },
}])[0];

export const deleteBadge = (
    id
) => fetchAjax([{
    methodname: 'local_superbadges_deletebadge',
    args: {
        id: id
    },
}])[0];
