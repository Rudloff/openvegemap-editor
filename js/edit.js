/*jslint browser: true*/
/*global window*/
var editForm = (function () {
    'use strict';

    function onChange(e) {
        var vegetarianInput = document.getElementById('diet:vegetarian');
        var triggerValues = ['yes', 'only'];
        if (triggerValues.includes(e.target.value)) {
            vegetarianInput.disabled = true;
            vegetarianInput.value = 'yes';
        } else {
            vegetarianInput.disabled = false;
        }
    }

    function init() {
        var veganInput = document.getElementById('diet:vegan');
        veganInput.addEventListener('change', onChange, false);
        onChange({target: veganInput});
    }

    return {
        init: init
    };
}());


window.addEventListener('load', editForm.init, false);
