$(document).ready(function () {
    var func = {
        jsMask: function () {
            $('.js-mask').each(function () {
                var $input = $(this),
                    inputType = $input.data('type'),
                    maskOptions = {};

                switch (inputType) {
                    case 'phone': {
                        maskOptions.mask = '+7 (999) 999-99-99';

                        break;
                    }
                    case 'email': {
                        maskOptions.alias = 'email';
                        maskOptions.showMaskOnHover = false;
                        maskOptions.placeholder = '';
                        maskOptions.jitMasking = true;

                        break;
                    }
                    case 'decimal': {
                        maskOptions.alias = 'decimal';
                        maskOptions.groupSeparator = '';
                        maskOptions.autoGroup = true;
                        maskOptions.rightAlign = false;

                        break;
                    }
                    case 'date': {
                        maskOptions.alias = 'datetime';
                        maskOptions.inputFormat = 'dd.mm.yyyy';
                    }
                }

                $input.inputmask(maskOptions);
            });
        },

        jsTimer: function () {
            var _this = this;

            $('.js-auth-code').each(function () {
                var authButton = {};

                authButton.$elem = $(this),
                    authButton.$elemText = authButton.$elem.find('.js-auth-code-text'),
                    authButton.$elemTimer = authButton.$elem.find('.js-auth-code-timer');

                var lastTime = new Date().getTime();

                authButton.$elem.on('click', function () {
                    var lastTime = new Date().getTime();

                    _this._timer(lastTime, authButton);
                });
            });
        },

        _timer: function (lastTime, authButton) {
            if (!lastTime) return;

            var intervalID;

            startTimer(lastTime);

            function startTimer(lastTime) {
                authButton.$elem.attr('disabled', true);
                authButton.$elemTimer.html('(30)');

                intervalID = setInterval(function () {
                    timer(lastTime);
                }, 1000);
            }

            function timer(endTime) {
                var nowTime = new Date().getTime(),
                    secondsLeft = Math.floor((nowTime - lastTime) / 1000),
                    secondsRemaining = 30 - secondsLeft;

                if (secondsRemaining) {
                    authButton.$elemTimer.html('(' + secondsRemaining + ')');
                } else {
                    clearInterval(intervalID);
                    authButton.$elemTimer.html('');
                    authButton.$elem.attr('disabled', false);
                }
            }
        },

        jsClearField: function () {
            $('.js-clear-field').each(function () {
                var $clearField = $(this),
                    $clearInput = $clearField.find('.js-clear-field-input'),
                    $clearBtn = $clearField.find('.js-clear-field-btn');

                toggleValue($clearInput, $clearBtn);

                $clearInput.on('input', function () {
                    toggleValue($(this), $clearBtn);
                });

                $clearBtn.on('click', function () {
                    $clearInput.val('');
                    $(this).hide();
                });
            });
        }
    };

    function toggleValue($clearInput, $clearBtn) {
        var value = $clearInput.val();

        if (value) {
            $clearBtn.show();
        } else {
            $clearBtn.hide();
        }
    }

    window.loader = {
        show: function ($target) {
            $('<div>', {class: 'loader'}).appendTo($target);
        },

        hide: function ($target) {
            $target.find('.loader').remove();
        }
    };

    func.jsMask();
    func.jsTimer();
    func.jsClearField();
});

BX.ready(function(){
    let productPriceItems = document.querySelectorAll('.mindbox-product-price');

    if (productPriceItems.length > 0) {
        productPriceItems.forEach(function (item) {
            item.addEventListener("changeProductPrice", function(event) { // (1)
                console.log(event.target)
                console.log('1111111')
                console.log(event.detail)
            });
        });
    }

});