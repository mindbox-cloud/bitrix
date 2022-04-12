$(document).ready(function () {
    $('#mindbox-sub-edit-form').on('submit', function (e) {
        e.preventDefault();
        let $targetForLoader = $(this);
        loader.show($targetForLoader);

        let fields = [];
        fields['SUBSCRIPTIONS'] = {
            'Email': $('#mindbox-sub_email').prop('checked'),
            'Sms': $('#mindbox-sub_sms').prop('checked')
        };

        let request = BX.ajax.runComponentAction('mindbox:sub.edit', 'save', {
            mode: 'class',
            data: {
                fields: fields
            }
        });


        request.then(function (response) {
            loader.hide($targetForLoader);

            if (response.data.type === 'success') {
                $('#mindbox-message').text(response.data.message);
                $('#mindbox-message').show();
            } else if (response.data.type === 'error') {
                $('#mindbox-message').text(response.data.message);
                $('#mindbox-message').show();
            }
        });
    })
});