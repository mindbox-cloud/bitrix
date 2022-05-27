$(document).ready(function () {
    $('#mindbox-order-history--load-more').on('click', function () {
        let $moreButton = $('#mindbox-order-more');
        let $targetForLoader = $moreButton;

        loader.show($targetForLoader);

        let page = parseInt($moreButton.data('page')) + 1;

        let request = BX.ajax.runComponentAction('mindbox:order.history', 'page', {
            mode: 'class',
            data: {
                page: page,
            }
        });

        request.then(function (response) {
            loader.hide($targetForLoader);

            if (response.data.type === 'error') {
                $moreButton.hide();
            }

            if (response.data.type === 'success') {
                $moreButton.data('page', response.data.page);

                if (response.data.html === '') {
                    $moreButton.hide();
                }

                if (response.data.more === false) {
                    $moreButton.hide();
                }

                $('#mindbox-orders-history').append(response.data.html);
            }
        });
    });
});
