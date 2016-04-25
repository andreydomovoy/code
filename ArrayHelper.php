<?php

namespace frontend\modules\subtego\helpers;

class ArrayHelper extends \yii\helpers\ArrayHelper
{
    /**
     * Объединяет массивы рекурсивно с вызовом callback функции для каждого совпадающего ключа
     * @param $array1 array Получатель
     * @param $array2 array Источник
     * @param $func callable Пользовательская функция
     */
    public static function mergeCallback(&$array1, $array2, $func) {

        foreach ($array2 as $key => $value) {
            if (is_array($value)) static::mergeCallback($array1[$key], $value, $func);
            else {
                call_user_func_array($func, array($key, &$array1, $array2));
            }
        }
    }

    /**
     * Проверка существования ключей по указанному пути в массиве.
     * Может быть использована как с AND так и с OR-операторами сравнения
     *
     * @param $key string Клюя для проверки
     * @param $array array Массив для проверки
     * @param $path array Массив путей-ключей ['header/icon', 'header/title', ... ]
     * @param $and bool Операция AND или OR для ключей
     * @param bool $caseSensitive
     * @return bool
     */
    public static function multiKeyExists($key, $array, $path, $and = true, $caseSensitive = true)
    {
        $isExist = true;

        foreach($path as $pathKey) {
            $paths = explode('/', $pathKey);
            $paths[] = $key;
            $value = static::walkByKeys($array, $paths);
            $exist = isset($value);
            $isExist = $and ? $isExist && $exist : $isExist || $exist ;
        }

        return $isExist;
    }

    public static function walkByKeys($array, &$keys) {
        $key = array_shift($keys);

        if (!isset($array[$key])) return null;
        if (count($keys))         return static::walkByKeys($array[$key], $keys);

        return $array[$key];
    }
}