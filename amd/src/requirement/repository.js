import {call as fetchAjax} from 'core/ajax';

export const deleteRequirement = (
    id
) => fetchAjax([{
    methodname: 'local_superbadges_deleterequirement',
    args: {
        id: id
    },
}])[0];

export const addRequirement = (
    formdata
) => fetchAjax([{
    methodname: 'local_superbadges_addrequirement',
    args: {
        jsonformdata: JSON.stringify(formdata)
    },
}])[0];