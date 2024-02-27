define([
    'jquery',
    'core/modal_factory',
    'core/modal_events',
    'core/templates',
    'core/ajax',
], function(
    $,
    ModalFactory,
    ModalEvents,
    templates,
    ajax,
) {
    return {
        init: function() {
            $(".ibob-badge").on("click", function() {
                let elem = $(this);
                let promises =
                    ajax.call([{
                        methodname: 'local_ibob_detail_badge_function',
                        args: {badgeid: elem.data("id")},}
                    ]);
                promises[0].done(function(response) {
                    returnfunc(response);
                }).fail(function(ex) {
                    alert('error : ' + ex);
                });
                function returnfunc(returnjson){
                    let modalTitle = 'Détail du badge';
                    let trigger = $('#badge_'+returnjson.id);
                    ModalFactory.create({
                        title: modalTitle,
                        body: templates.render('local_ibob/userbadgedisplayer', returnjson),
                    }, trigger)
                         .done(function(modal) {
                             if (modal.countOtherVisibleModals() == 0) {
                                 modal.getRoot().on(ModalEvents.hidden, function() {
                                        modal.destroy();
                                     }
                                 );
                             }
                             modal.show();
                             modal.getRoot().removeClass('hide').addClass('show');
                        });
                }
            });
        }
    };
});
