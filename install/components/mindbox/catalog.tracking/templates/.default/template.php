<?php
/**
 * Created by @copyright QSOFT.
 */
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
?>

<script>
    var mindboxViewCategory = function (id) {
        BX.ajax.runComponentAction('mindbox:catalog.tracking', 'viewCategory', {
            mode:'class',
            data: {
                id: id,
            }
        });
    };

    var mindboxViewProduct = function (id) {
        BX.ajax.runComponentAction('mindbox:catalog.tracking', 'viewProduct', {
            mode:'class',
            data: {
                id: id,
            }
        });
    };
</script>
