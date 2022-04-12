$(document).ready(function () {
    $('#mindbox-subscribe-form').on('submit', function (e) {
        e.preventDefault();
        let $targetForLoader = $(this);

        loader.show($targetForLoader);

        let email = $('#mindbox-sub-email').val();

        let request = BX.ajax.runComponentAction('mindbox:subscription', 'subscribe', {
            mode: 'class',
            data: {
                email: email
            }
        });

        request.then(function (response) {
            loader.hide($targetForLoader);

            if (response.data.type === 'success') {
                $('#mindbox-subscribe-form').hide();
                $('#mindbox-message').text(response.data.message).show();
            } else if (response.data.type === 'queue') {
                $('#mindbox-message').text(response.data.message).show();
            }
        });
    })
});
