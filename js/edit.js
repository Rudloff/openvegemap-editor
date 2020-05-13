/*jslint browser, es6*/
/*global window*/
const editForm = (function () {
    'use strict';

    function onChange(e) {
        const vegetarianInput = document.getElementById('diet:vegetarian');
        const vegetarianNoOption = document.getElementById('diet:vegetarian-no');
        const triggerValues = ['yes', 'only'];

        if (triggerValues.includes(e.target.value)) {
            vegetarianNoOption.disabled = true;

            if (vegetarianInput.value === 'no') {
                vegetarianInput.value = 'yes';
            }
        } else {
            vegetarianNoOption.disabled = false;
        }
    }

    function init() {
        const veganInput = document.getElementById('diet:vegan');
        veganInput.addEventListener('change', onChange, false);
        onChange({target: veganInput});
    }

    return {
        init: init
    };
}());


window.addEventListener('load', editForm.init, false);
