<?php

declare(strict_types=1);

// Сообщения правил валидации. Английские берутся из Laravel; правило без строки здесь
// покажет английский текст, поэтому новое правило — сразу новая строка.
// :attribute — название поля из lang/ru/fields.php (появится с первой формой)
return [
    'required'  => 'Заполни поле «:attribute».',
    'string'    => 'В поле «:attribute» должен быть текст.',
    'integer'   => 'В поле «:attribute» должно быть целое число.',
    'boolean'   => 'В поле «:attribute» должно быть «да» или «нет».',
    'email'     => 'В поле «:attribute» нужен адрес почты, например anya@example.com.',
    'confirmed' => 'Поле «:attribute» не совпадает с подтверждением.',
    'unique'    => 'Такое значение поля «:attribute» уже занято.',
    'exists'    => 'В поле «:attribute» выбрано то, чего нет.',
    'in'        => 'В поле «:attribute» выбрано недопустимое значение.',
    'max'       => [
        'numeric' => 'В поле «:attribute» число не больше :max.',
        'string'  => 'В поле «:attribute» не больше :max символов.',
        'array'   => 'В поле «:attribute» не больше :max элементов.',
    ],
    'min' => [
        'numeric' => 'В поле «:attribute» число не меньше :min.',
        'string'  => 'В поле «:attribute» не меньше :min символов.',
        'array'   => 'В поле «:attribute» не меньше :min элементов.',
    ],
];
