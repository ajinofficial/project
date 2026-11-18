(function () {
    'use strict';
    window.t = function (key, replacements) {
        var messages = window.AppTranslations || {};
        var value = Object.prototype.hasOwnProperty.call(messages, key) ? messages[key] : key;
        return value.replace(/:([a-z_][a-z0-9_]*)/gi, function (placeholder, name) {
            return Object.prototype.hasOwnProperty.call(replacements || {}, name)
                ? String(replacements[name]) : placeholder;
        });
    };
    window.localizedValidationMessage = function (field) {
        var label = field.labels && field.labels[0];
        var span = label && label.querySelector(':scope > span');
        var labelText = span && span.textContent.trim();
        if (!labelText && label) {
            labelText = Array.from(label.childNodes).filter(function (node) { return node.nodeType === 3; })
                .map(function (node) { return node.textContent; }).join('').trim();
        }
        if (!labelText) {
            var key = 'validation.attributes.' + field.name;
            labelText = window.t(key);
            if (labelText === key) {
                var name = String(field.name || '').replace(/_/g, ' ');
                labelText = window.t(name.charAt(0).toUpperCase() + name.slice(1));
            }
        }
        var replacements = {attribute: labelText};
        if (field.validity.customError) return field.validationMessage;
        if (field.validity.valueMissing) return window.t('validation.required', replacements);
        if (field.validity.typeMismatch && field.type === 'email') return window.t('validation.email', replacements);
        if (field.validity.rangeUnderflow) return window.t('validation.min.numeric', {attribute: labelText, min: field.min});
        if (field.validity.rangeOverflow) return window.t('validation.max.numeric', {attribute: labelText, max: field.max});
        if (field.validity.tooLong) return window.t('validation.max.string', {attribute: labelText, max: field.maxLength});
        if (field.validity.tooShort) return window.t('validation.min.string', {attribute: labelText, min: field.minLength});
        return window.t('common.invalid_field', replacements);
    };
    document.addEventListener('DOMContentLoaded', function () {
        var menus = document.querySelectorAll('.language-menu');
        document.addEventListener('click', function (event) {
            menus.forEach(function (menu) {
                if (!menu.contains(event.target)) menu.open = false;
            });
        });
        document.addEventListener('keydown', function (event) {
            if (event.key !== 'Escape') return;
            menus.forEach(function (menu) {
                if (!menu.open) return;
                menu.open = false;
                menu.querySelector('summary').focus();
            });
        });
    });
}());
