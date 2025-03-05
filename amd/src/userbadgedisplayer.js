
import $ from 'jquery';
import Modal from 'core/modal';
import ModalEvents from 'core/modal_events';
import templates from 'core/templates';
import {call as fetchMany} from 'core/ajax';
import {get_string as getString} from 'core/str';

export const init = () => {
    getDetailBadge();
};
export const getDetailBadge = () => {
    $(".ibob-badge").on("click", async function() {
        let elem = $(this);
        let badgeid = elem.data("id");

            const response = await detailBadgeModal(badgeid);
            const data = JSON.parse(response);

            displayBadgeDetails(data);
    });
};

const detailBadgeModal = (badgeid) => fetchMany([{
    methodname: 'local_ibob_detail_badge_function',
    args: {
        badgeid,
    },
}])[0];

const displayBadgeDetails = (data) => {
    let modalTitle = getString('modalBadgeDetail', 'local_ibob');
    let trigger = $('#badge_' + data.id);

    return Modal.create({
        title: modalTitle,
        body: templates.render('local_ibob/userbadgedisplayer', data),
    }, trigger).then(function(modal) {
        if (modal.countOtherVisibleModals() == 0) {
            modal.getRoot().on(ModalEvents.hidden, function() {
                modal.destroy();
            });
        }
        modal.show();
        modal.getRoot().removeClass('hide').addClass('show');
        return true;
    });
};
