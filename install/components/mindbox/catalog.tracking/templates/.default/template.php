<?php
/**
 * Created by @copyright QSOFT.
 */
?>

<script>
    var mindboxViewCategory = function (id) {
        mindbox("async", {
            operation: "<?=Mindbox\Options::getPrefix()?>.ViewCategory",
            data: {
                viewProductCategory: {
                    productCategory: {
                        ids: {
                            <?=Mindbox\Options::getExternalSystem()?>: id
                        }
                    }
                }
            }
        });
    };

    var mindboxViewProduct = function (id) {
        mindbox("async", {
            operation: "<?=Mindbox\Options::getPrefix()?>.ViewProduct",
            data: {
                viewProduct: {
                    product: {
                        ids: {
                            <?=Mindbox\Options::getExternalSystem()?>: id
                        }
                    }
                }
            }
        });
    };
</script>
