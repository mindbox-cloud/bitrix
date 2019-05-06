$(document).ready(function() {
    var $moreButton = $('#mindbox-order-more');
    $('#mindbox-order-history--load-more').on('click', function () {
        var $targetForLoader = $moreButton;
        loader.show($targetForLoader);

        var page = parseInt($moreButton.data('page'))  + 1;

        var request = BX.ajax.runComponentAction('mindbox:order.history', 'page', {
            mode:'class',
            data: {
                page: page,
            }
        });

        request.then(function(response) {
            loader.hide($targetForLoader);
            var data = response.data;
            if(data.type === 'error') {
                $moreButton.hide();
            }

            if(data.type === 'success') {
                $moreButton.data('page', data.page);
                var $html = data.html;
                if($html === '') {
                    $moreButton.hide();
                }

                if(data.more === false) {
                    $moreButton.hide();
                }

                $('#mindbox-orders-history').append($html);
            }
        });
    });

});