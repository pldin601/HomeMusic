<?php

namespace app\lang\option;


class Filter {

    /**
     * @param \Closure ...$filters
     * @return \Closure
     */
    public static function matchAll(\Closure ...$filters) {
        return function ($obj) use (&$filters) {
            foreach ($filters as &$filter) {
                if (! $filter($obj)) {
                    return false;
                }
            }
            return true;
        };
    }

    /**
     * @param \Closure ...$filters
     * @return \Closure
     */
    public static function matchAny(\Closure ...$filters) {
        return function ($obj) use (&$filters) {
            foreach ($filters as &$filter) {
                if ($filter($obj)) {
                    return true;
                }
            }
            return false;
        };
    }

    /**
     * @return \Closure
     */
    public static function isNumber() {
        return function ($value) { return is_numeric($value); };
    }

    /**
     * @return \Closure
     */
    public static function isString() {
        return function ($value) { return is_string($value); };
    }

    /**
     * @return callable
     */
    public static function isPositiveNumber() {
        return function ($value) { return is_numeric($value) && $value >= 0; };
    }

    /**
     * @return \Closure
     */
    public static function isNull() {
        return function ($value) { return is_null($value); };
    }

    /**
     * @return \Closure
     */
    public static function isNullOrNumber() {
        return function ($value) { return is_null($value) || is_numeric($value); };
    }

    /**
     * @return \Closure
     */
    public static function isValidId() {
        return function ($value) { return is_numeric($value) && $value > 0; };
    }

    /**
     * @return \Closure
     */
    public static function isArray() {
        return function ($value) { return is_array($value); };
    }

    /**
     * @return \Closure
     */
    public static function notEmpty() {
        return function ($value) {
            if (is_array($value) && count($value) === 0) {
                return false;
            } else if (is_string($value) && strlen($value) === 0) {
                return false;
            } else if (is_null($value)) {
                return false;
            }
            return true;
        };
    }

    /**
     * @param $that
     * @return \Closure
     */
    public static function value($that) {
        return function ($value) use (&$that) {
            return $value === $that;
        };
    }

    /**
     * @param $that
     * @return callable
     */
    public static function isLessThan($that) {
        return function ($value) use ($that) {
            return $value < $that;
        };
    }

    /**
     * @param $that
     * @return callable
     */
    public static function isMoreThan($that) {
        return function ($value) use ($that) {
            return $value > $that;
        };
    }

    /**
     * @param $regexp
     * @return callable
     */
    public static function matchRegExp($regexp) {
        return function ($value) use ($regexp) {
            return preg_match($regexp, $value);
        };
    }

    /**
     * @param $min
     * @param $max
     * @return callable
     */
    public static function lengthInRange($min, $max) {
        return function ($value) use ($min, $max) {
            $len = strlen($value);
            return $len >= $min && $len <= $max;
        };
    }

}

