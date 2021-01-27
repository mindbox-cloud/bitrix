<?php

namespace Mindbox\Templates;

trait AdminLayouts
{
    /**
     * @return string
     */
    public static function getOrderMatchesTable()
    {
        $styles = self::adminTableStyles();
        $escapeTable = '</td></tr><tr><td colspan="2"><table class="table">';
        $tableHead = '<tr class="tr title"><th class="th">'.getMessage("BITRIX_FIELDS").'</th><th class="th">'.getMessage("MINDBOX_FIELDS").'</th><th class="th-empty"></th></tr>';

        $result = $styles.$escapeTable.$tableHead;

        $bottomPadding = '</table></td></tr><tr><td>&nbsp;</td></tr>';
        $result .= $bottomPadding;
        $result .= self::adminTableScripts();
        return $result;
    }

    /**
     * @return string
     */
    public static function getAddOrderMatchButton()
    {
        $escapeTable = '</td></tr><tr><td>';
        $button = '<a class="module_button module_button_add" href="javascript:void(0)">'.getMessage("BUTTON_ADD").'</a>';

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
    public static function adminTableScripts()
    {
        return <<<HTML
            <script>
                document.addEventListener('DOMContentLoaded', function(){
                    createTable();
                    hideInput('[name="MINDBOX_ORDER_FIELDS_MATCH"]');
                        
                    document.querySelector('.module_button_add').onclick = () => {addButtonHandler()};
                });
                
                function addButtonHandler() {
                    console.log('add');
                    let mindboxKey = document.querySelector('[name="MINDBOX_ORDER_MINDBOX_FIELDS"]').value;
                    let bitrixKey = document.querySelector('[name="MINDBOX_ORDER_BITRIX_FIELDS"]').value;
                
                    if (mindboxKey && bitrixKey) {
                        setProps(bitrixKey, mindboxKey);
                        reInitTable();
                    }
                }
                
                function removeButtonHandler(bitrixId) {
                    console.log(bitrixId);
                    removeProps(bitrixId);
                    reInitTable();
                }
                
                function hideInput(selector) {
                    document.querySelector(selector).style.display = 'none';
                }
                
                function addRow(bitrixKey, mindboxKey) {
                    if (mindboxKey && bitrixKey) {
                        let row = document.querySelector('table.table tbody').insertRow();
                        row.insertCell().appendChild(document.createTextNode(bitrixKey));
                        row.insertCell().appendChild(document.createTextNode(mindboxKey));
                        let link = document.createElement('a');
                        link.classList.add('module_button_delete');
                        link.href = 'javascript:void(0)';
                        link.onclick = () => {removeButtonHandler(bitrixKey)};
                        link.text = 'X';
                        // link.dataset.bitrix = bitrixKey;
                        row.insertCell().appendChild(link);
                    }
                }
                
                function reInitTable() {
                    removeTable();
                    createTable();
                }
                
                function createTable() {
                    let props = getProps();
                
                    Object.keys(props).map((objectKey, index) => {
                        let value = props[objectKey];
                        addRow(objectKey, value);
                    });
                }
                
                function removeProps(key) {
                    let currentProps = getProps();
                    delete currentProps[key];
                    document.querySelector('[name="MINDBOX_ORDER_FIELDS_MATCH"]').value = JSON.stringify(currentProps);
                }
                
                function setProps(key, value) {
                    let currentProps = getProps();
                    currentProps[key] = value;
                    document.querySelector('[name="MINDBOX_ORDER_FIELDS_MATCH"]').value = JSON.stringify(currentProps);
                }
                
                function getProps() {
                    return JSON.parse(document.querySelector('[name="MINDBOX_ORDER_FIELDS_MATCH"]').value);
                }
                
                function removeTable() {
                    document.querySelectorAll('.table tr:not(.title)').forEach((e) => {
                        e.remove()
                    });
                }
            </script>
HTML;
    }

}