$(function () {
    $('#mindbox-bonus-history--load-more').on('click', function () {
        let $bonusMore = $('#mindbox-bonus-more');
        loader.show($bonusMore);

        let page = parseInt($bonusMore.data('page')) + 1;

        let request = BX.ajax.runComponentAction('mindbox:bonus.history', 'page', {
            mode: 'class',
            data: {
                page: page
            }
        });

        request.then(function (response) {
            loader.hide($bonusMore);

            if (response.data.type === 'error') {
                $bonusMore.hide();
            } else if (response.data.type === 'success') {
                $bonusMore.data('page', response.data.page);

                $('#mindbox-bonus-history').append(response.data.html);

                if (response.data.more === false) {
                    $bonusMore.hide();
                }
            }
        });
    });
});