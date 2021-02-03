<?php
/**
 * Created by @copyright INTENSA.
 */
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
?>

<script>
    var mindboxViewCategory = function (id) {
        mindbox("async", {
            operation: "<?=Mindbox\Options::getModuleOption('WEBSITE_PREFIX')?>.ViewCategory",
            data: {
                viewProductCategory: {
                    productCategory: {
                        ids: {
                            <?=Mindbox\Options::getModuleOption('EXTERNAL_SYSTEM')?>: id
                        }
                    }
                }
            }
        });
    };

    var mindboxViewProduct = function (id) {
        mindbox("async", {
            operation: "<?=Mindbox\Options::getModuleOption('WEBSITE_PREFIX')?>.ViewProduct",
            data: {
                viewProduct: {
                    product: {
                        ids: {
                            <?=Mindbox\Options::getModuleOption('EXTERNAL_SYSTEM')?>: id
                        }
                    }
                }
            }
        });
    };
</script>
