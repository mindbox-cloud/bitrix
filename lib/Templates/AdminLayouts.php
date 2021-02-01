<?php

namespace Mindbox\Templates;

trait AdminLayouts
{
    /**
     * @return string
     */
    public static function getUserMatchesTable()
    {
        $styles = self::adminTableStyles();
        $escapeTable = '</td></tr><tr><td colspan="2"><table class="table user-table">';
        $tableHead = '<tr class="tr title"><th class="th">'.getMessage("BITRIX_FIELDS").'</th><th class="th">'.getMessage("MINDBOX_FIELDS").'</th><th class="th-empty"></th></tr>';

        $result = $styles.$escapeTable.$tableHead;

        $bottomPadding = '</table></td></tr><tr><td>&nbsp;</td></tr>';
        $result .= $bottomPadding;
        $result .= self::adminUserTableScripts();
        return $result;
    }

    /**
     * @return string
     */
    public static function getOrderMatchesTable()
    {
        $styles = self::adminTableStyles();
        $escapeTable = '</td></tr><tr><td colspan="2"><table class="table order-table">';
        $tableHead = '<tr class="tr title"><th class="th">'.getMessage("BITRIX_FIELDS").'</th><th class="th">'.getMessage("MINDBOX_FIELDS").'</th><th class="th-empty"></th></tr>';

        $result = $styles.$escapeTable.$tableHead;

        $bottomPadding = '</table></td></tr><tr><td>&nbsp;</td></tr>';
        $result .= $bottomPadding;
        $result .= self::adminOrderTableScripts();
        return $result;
    }

    /**
     * @return string
     */
    public static function getAddOrderMatchButton($buttonClass)
    {
        $escapeTable = '</td></tr><tr><td>';
        $button = '<a class="module_button module_button_add '.$buttonClass.'" href="javascript:void(0)">'.getMessage("BUTTON_ADD").'</a>';

        return $escapeTable.$button;
    }

    /**
     * @return string
     */
    public static function adminTableStyles()
    {
        return <<<HTML
            <style type="text/css">
                .module_button {
                    border: 1px solid black;
                    border-radius: 5%;
                    padding: 8px 25px;
                    background-color: #e0e8ea;
                    color: black;
                    text-decoration: none;
                    float: right;
                }
                .th {
                    background-color: #e0e8ea;
                    padding: 15px;
                    text-align: center;
                    min-width: 400px;
                }
                .th-empty {
                    background-color: #e0e8ea;
                    padding: 15px;
                    text-align: center;
                }
                .table td {
                    border-top: 1px solid #87919c;
                    padding: 15px;
                    text-align: center;
                }
                .table {
                    margin: 0 auto !important;
                    border-collapse: collapse;
                }
            </style>
HTML;
    }

    /**
     * @return string
     */
    public static function adminUserTableScripts()
    {
        return <<<HTML
            <script>
                document.addEventListener('DOMContentLoaded', function(){
                    createTable('user-table', 'MINDBOX_USER_FIELDS_MATCH');
                    hideInput('[name="MINDBOX_USER_FIELDS_MATCH"]');
                    
                    document.querySelector('.module_button_add.user_module_button_add').onclick = () => {addButtonHandler('MINDBOX_USER_MINDBOX_FIELDS', 'MINDBOX_USER_BITRIX_FIELDS', 'user-table', 'MINDBOX_USER_FIELDS_MATCH')};
                });
            </script>
HTML;
    }

    /**
     * @return string
     */
    public static function adminOrderTableScripts()
    {
        return <<<HTML
            <script>
                document.addEventListener('DOMContentLoaded', function(){
                    createTable('order-table', 'MINDBOX_ORDER_FIELDS_MATCH');
                    hideInput('[name="MINDBOX_ORDER_FIELDS_MATCH"]');
                    
                    document.querySelector('.module_button_add.order_module_button_add').onclick = () => {addButtonHandler('MINDBOX_ORDER_MINDBOX_FIELDS', 'MINDBOX_ORDER_BITRIX_FIELDS', 'order-table', 'MINDBOX_ORDER_FIELDS_MATCH')};
                });
            </script>
HTML;
    }

    /**
     * @return string
     */
    public static function adminTableScripts()
    {
        return <<<HTML
            <script>                
                function addButtonHandler(mindboxName, bitrixName, tableClass, propName) {
                    let mindboxKey = document.querySelector('[name="'+mindboxName+'"]').value;
                    let bitrixKey = document.querySelector('[name="'+bitrixName+'"]').value;
                
                    if (mindboxKey && bitrixKey) {
                        setProps(bitrixKey, mindboxKey, propName);
                        reInitTable(tableClass, propName);
                    }
                }
                
                function removeButtonHandler(bitrixId, tableClass, propName) {
                    removeProps(bitrixId, propName);
                    reInitTable(tableClass, propName);
                }
                
                function hideInput(selector) {
                    document.querySelector(selector).style.display = 'none';
                }
                
                function addRow(bitrixKey, mindboxKey, tableClass, propName) {
                    if (mindboxKey && bitrixKey) {
                        let row = document.querySelector('table.table.'+tableClass+' tbody').insertRow();
                        row.insertCell().appendChild(document.createTextNode(bitrixKey));
                        row.insertCell().appendChild(document.createTextNode(mindboxKey));
                        let link = document.createElement('a');
                        link.classList.add('module_button_delete');
                        link.href = 'javascript:void(0)';
                        link.onclick = () => {removeButtonHandler(bitrixKey, tableClass, propName)};
                        link.text = 'X';
                        row.insertCell().appendChild(link);
                    }
                }
                
                function reInitTable(tableClass, propName) {
                    removeTable(tableClass);
                    createTable(tableClass, propName);
                }
                
                function createTable(tableClass, propName) {
                    let props = getProps(propName);
                
                    Object.keys(props).map((objectKey, index) => {
                        let value = props[objectKey];
                        addRow(objectKey, value, tableClass, propName);
                    });
                }
                
                function removeProps(key, propName) {
                    let currentProps = getProps(propName);
                    delete currentProps[key];
                    document.querySelector('[name="'+propName+'"]').value = JSON.stringify(currentProps);
                }
                
                function setProps(key, value, propName) {
                    let currentProps = getProps(propName);
                    currentProps[key] = value;
                    document.querySelector('[name="'+propName+'"]').value = JSON.stringify(currentProps);
                }
                
                function getProps(propName) {
                    let string = document.querySelector('[name="'+propName+'"]').value;
                    if (string) {
                        return JSON.parse(string);
                    }
                    
                    return JSON.parse('{}');
                }
                
                function removeTable(tableClass) {
                    document.querySelectorAll('.table.'+tableClass+' tr:not(.title)').forEach((e) => {
                        e.remove()
                    });
                }
            </script>
HTML;
    }

}