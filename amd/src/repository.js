import {call as fetchMany} from 'core/ajax';

export const detailBadgeModal = (
    badgeid,
) => fetchMany([{
    methodname: 'local_ibob_detail_badge_function',
    args: {
        badgeid,
    },
}])[0];
export const init = () => fetchMany([{
    methodname: 'local_ibob_detail_badge_function',
}])[0];
