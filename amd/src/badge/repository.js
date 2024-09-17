import {call as fetchAjax} from 'core/ajax';

export const deleteBadge = (
    id
) => fetchAjax([{
    methodname: 'local_superbadges_deletebadge',
    args: {
        id: id
    },
}])[0];

export const deliverBadge = (
    id
) => fetchAjax([{
    methodname: 'local_superbadges_deliverbadge',
    args: {
        id: id
    },
}])[0];
