$(document).ready(function() {
    $('#mindbox-sub-edit-form').on('submit', function (e) {
        e.preventDefault();
        var $targetForLoader = $(this);
        loader.show($targetForLoader);

        var fields = [];
        fields['SUBSCRIPTIONS'] = {'Email': $('#mindbox-sub_email').prop('checked'), 'Sms': $('#mindbox-sub_sms').prop('checked')};

        var request = BX.ajax.runComponentAction('mindbox:sub.edit', 'save', {
            mode:'class',
            data: {
                fields: fields
            }
        });


        request.then(function (response) {
            loader.hide($targetForLoader);
            var data =  response.data;
            if(data.type === 'success') {
                $('#mindbox-message').text(data.message);
                $('#mindbox-message').show();
            } else if(data.type === 'error') {
                $('#mindbox-message').text(data.message);
                $('#mindbox-message').show();
            }
        });
    })
});