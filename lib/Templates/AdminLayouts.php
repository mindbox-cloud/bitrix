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
        return '<a class="module_button module_button_add '.$buttonClass.'" href="javascript:void(0)">'.getMessage("BUTTON_ADD").'</a>';
    }

    /**
     * @return string
     */
    public static function adminTableStyles()
    {
        return <<<HTML
            <style type="text/css">
                .module_button {
                    padding: 6px 13px 6px;
                    margin: 2px;
                    border-radius: 4px;
                    border: none;
                    border-top: 1px solid #fff;
                    -webkit-box-shadow: 0 0 1px rgba(0,0,0,.11), 0 1px 1px rgba(0,0,0,.3), inset 0 1px #fff, inset 0 0 1px rgba(255,255,255,.5);
                    box-shadow: 0 0 1px rgba(0,0,0,.3), 0 1px 1px rgba(0,0,0,.3), inset 0 1px 0 #fff, inset 0 0 1px rgba(255,255,255,.5);
                    background-color: #e0e9ec;
                    background-image: -webkit-linear-gradient(bottom, #d7e3e7, #fff) !important;
                    background-image: -moz-linear-gradient(bottom, #d7e3e7, #fff) !important;
                    background-image: -ms-linear-gradient(bottom, #d7e3e7, #fff) !important;
                    background-image: -o-linear-gradient(bottom, #d7e3e7, #fff) !important;
                    background-image: linear-gradient(bottom, #d7e3e7, #fff) !important;
                    color: #3f4b54;
                    cursor: pointer;
                    font-family: "Helvetica Neue",Helvetica,Arial,sans-serif;
                    font-weight: bold;
                    font-size: 13px;
                    line-height: 18px;
                    text-shadow: 0 1px rgba(255,255,255,0.7);
                    text-decoration: none;
                    position: relative;
                    vertical-align: middle;
                    -webkit-font-smoothing: antialiased;
                    margin-right: 10px;
                    outline: none;
                    border-spacing: 0;
                    float: left;
                }
                .module_button_delete {
                    height: 10px;
                    display: inline-block;
                    width: 10px;
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
                        link.innerHTML = '<svg version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 96 96" enable-background="new 0 0 96 96" xml:space="preserve"><polygon fill="#AAAAAB" points="96,14 82,0 48,34 14,0 0,14 34,48 0,82 14,96 48,62 82,96 96,82 62,48 "></polygon></svg>';
                        link.href = 'javascript:void(0)';
                        link.onclick = () => {removeButtonHandler(bitrixKey, tableClass, propName)};
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
                    if (Object.values(currentProps).indexOf(value) === -1) {
                        currentProps[key] = value;
                    }
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