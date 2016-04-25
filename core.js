"use strict";

var _sg = {};

(function($) {

    // Добавление/удаление БЭМ модификаторов к блоку/элементу
    // Например: _bem(".b-lightbox").addMods("hello, benny"); (=>) b-lightbox_hello b-lightbox_benny
    var _sgBem = function (target) {

        return {
            // селектор блока
            el: target,

            // добавить один или более модификаторов к блоку или элементу блока
            // модификаторы должны быть разделены запятой
            // addMods("type, hello")
            addMods: function (mods) {

                var modArray = mods.replace(/\s+/g, '').split(','), classes = '', target = this.el.replace('.', '');
                modArray.forEach(function (modificator) {
                    classes += ( target + '_' + modificator + " " );
                });

                $(this.el).addClass(classes);

                return this;
            },
            // удалить один, несколько или все модификаторы с блока/элемента
            delMods: function (mods) {

                mods = mods ? mods.replace(/\s+/g, '').split(',') : [];

                var classList = $(this.el).attr('class').split(/\s+/),
                    target = this.el.replace('.', ''),
                    re = new RegExp(target + '_', 'i'),
                    toRemove = '';

                $.each(classList, function (index, item) {

                    if (mods.length) {

                        $.each(mods, function (i, mod) {
                            if (item === target + '_' + mod) toRemove += ( item + ' ' );
                        });

                    } else if (item.match(re)) toRemove += ( item + ' ' );
                });

                $(this.el).removeClass(toRemove);

                return this;
            },
            // проверяет установлен ли модификатор у блока/элемента
            hasMod: function (mod) {
                var classList = $(this.el).attr('class'),
                    target = this.el.replace('.', ''),
                    re = new RegExp(target + '_' + mod, 'g');
                return classList.match(re) != null;
            }
        };

    };

    // перевод templates на актуальный язык
    // язык выбирается на основе <html lang="?">
    var _sgTrans = function (text) {

        if ($('html').attr('lang') != 'ru') {
            // словарь перевода
            var dict = window.TRANS;
            // находим все русские слова в тексте
            var russian = text.match(/([а-яёА-ЯЁ]+\s?)+/g);

            // переводим эти слова из словаря
            russian.forEach(function (item) {
                if (dict[item.trim()] != undefined) text = text.replace(item, dict[item.trim()]);
            });
        }

        // заменяем "bad" символы если есть, обычно mustache парсер ломает русскую букву "Р"
        return text.replace("\uFFFD ", 'Р');
    };

    // рендеринг mustache шаблона
    var _sgRender = function (templateName, model, translate) {

        translate = translate || true;

        var template = MST[templateName];
        Mustache.parse(template);
        var result = Mustache.render(template, model);

        return translate ? _sgTrans(result) : result;
    };

    // поправить высплывающие подсказки для chosenSelect компонента
    var _fixSelectTooltip = function() {
        $("select.b-controls__select").each(function() {
            var chosen = $(this).next();
            chosen.attr("title", $(this).attr('title'));
            chosen.attr("data-uk-tooltip", $(this).attr('data-uk-tooltip'));
        });
    };

    // установка зависимости двух селектов
    var _chooseTwice = function(first, second, config) {

        var params      = {},
            keyPrefix   = '#!#';

        // проходим по всем опциям селекта
        $(second + ' option').each(function() {
            // используем "!" как префикс ключа, чтобы ключи в объекте потом не сортировались автоматически
            params['#!#' + $(this).val()] = $(this).html();
        });

        // сохраняем параметры селекта в глобальной переменной для дальнейшего backup
        _setTempVar(second, params);

        // вызываем изменения слектов по умолчанию
        updateSecondChoose(config[1]);

        // обработчик изменения первого селекта
        $(first).chosen().change(function() {
            updateSecondChoose(config[$(this).prop('selectedIndex') + 1]);
        });

        function updateSecondChoose(param2change) {

            var options = '', index = 0;

            // обнуляем селект
            $(second).html('');

            // формируем новый набор опций селекта
            _.each(_getTempVar(second), function(value, key) {
                if (!_.contains(param2change, index + 1)) options += '<option value="' + key.replace(keyPrefix, '') + '">' + value + '</option>\n';
                index++;
            });

            // обновляем селект новым набором опций
            $(second).html(options);
            // вызываем триггер на изменение chosen
            $(second).trigger('chosen:updated');

        }
    };

    // Глобальная переменная для сохранения временных данных
    var gParam = 'SG_STATES';

    // сохраняет параметры в глобальной переменной
    var _setTempVar = function (name, value) {
        if (_.isUndefined(window[gParam])) window[gParam] = {};
        window[gParam][name] = value;
    };

    // возвращает параметры из глобальной переменной
    var _getTempVar = function (name) {
        return window[gParam][name];
    };

    // exports
    _sg.bem         = _sgBem;
    _sg.trans       = _sgTrans;
    _sg.mRender     = _sgRender;
    _sg.fixSelect   = _fixSelectTooltip;
    _sg.chooseTwice = _chooseTwice;
    _sg.setVar      = _setTempVar;
    _sg.getVar      = _getTempVar;

})(window.jQuery);

//---------------------------------------------------------------------------------------------------------------

$(document).ready(function () {
    _sg.fixSelect();

    $(document).on('pjax:success', _sg.fixSelect);
});

