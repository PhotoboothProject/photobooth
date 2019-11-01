<?php

/**
  * array_deep_merge
  *
  * array array_deep_merge ( array array1 [, array array2 [, array ...]] )
  *
  * Like array_merge
  *
  *   array_deep_merge() merges the elements of one or more arrays together so
  * that the values of one are appended to the end of the previous one. It
  * returns the resulting array.
  *   If the input arrays have the same string keys, then the later value for
  * that key will overwrite the previous one. If, however, the arrays contain
  * numeric keys, the later value will not overwrite the original value, but
  * will be appended.
  *   If only one array is given and the array is numerically indexed, the keys
  * get reindexed in a continuous way.
  *
  * Different from array_merge
  *   If string keys have arrays for values, these arrays will merge recursively.
  *
  * @source https://www.php.net/manual/en/function.array-merge.php#54946
  */
function array_deep_merge()
{
    switch (func_num_args()) {
      case 0: return false; break;
      case 1: return func_get_arg(0); break;
      case 2:
        $args = func_get_args();
        $args[2] = array();
        if (is_array($args[0]) and is_array($args[1])) {
            foreach (array_unique(array_merge(array_keys($args[0]), array_keys($args[1]))) as $key) {
                if (is_string($key) and array_key_exists($key, $args[0]) and is_array($args[0][$key]) and array_key_exists($key, $args[1]) and is_array($args[1][$key])) {
                    $args[2][$key] = array_deep_merge($args[0][$key], $args[1][$key]);
                } elseif (is_string($key) and isset($args[0][$key]) and isset($args[1][$key])) {
                    $args[2][$key] = $args[1][$key];
                } elseif (is_integer($key) and isset($args[0][$key]) and isset($args[1][$key])) {
                    $args[2][] = $args[0][$key];
                    $args[2][] = $args[1][$key];
                } elseif (is_integer($key) and isset($args[0][$key])) {
                    $args[2][] = $args[0][$key];
                } elseif (is_integer($key) and isset($args[1][$key])) {
                    $args[2][] = $args[1][$key];
                } elseif (! isset($args[1][$key])) {
                    $args[2][$key] = $args[0][$key];
                } elseif (! isset($args[0][$key])) {
                    $args[2][$key] = $args[1][$key];
                }
            }
            return $args[2];
        } else {
            return $args[1];
        } break;
      default:
        $args = func_get_args();
        $args[1] = array_deep_merge($args[0], $args[1]);
        array_shift($args);
        return call_user_func_array('array_deep_merge', $args);
        break;
    }
}
