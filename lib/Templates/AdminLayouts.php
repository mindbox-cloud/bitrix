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
        return '<a class="module_button module_button_add" href="javascript:void(0)">'.getMessage("BUTTON_ADD").'</a>';
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
                        link.innerHTML = '<svg version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 96 96" enable-background="new 0 0 96 96" xml:space="preserve"><polygon fill="#AAAAAB" points="96,14 82,0 48,34 14,0 0,14 34,48 0,82 14,96 48,62 82,96 96,82 62,48 "></polygon></svg>';
                        link.href = 'javascript:void(0)';
                        link.onclick = () => {removeButtonHandler(bitrixKey)};
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