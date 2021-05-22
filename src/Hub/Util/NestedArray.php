<?php

namespace Hub\Util;

/**
 * Provides helpers to perform operations on nested arrays and array keys of variable depth.
 *
 * @see https://github.com/drupal/drupal/blob/9.x/core/lib/Drupal/Component/Utility/NestedArray.php
 */
class NestedArray
{
    /**
     * Retrieves a value from a nested array with variable depth.
     *
     * This helper function should be used when the depth of the array element
     * being retrieved may vary (that is, the number of parent keys is variable).
     * It is primarily used for form structures and renderable arrays.
     *
     * Without this helper function the only way to get a nested array value with
     * variable depth in one line would be using eval(), which should be avoided:
     *
     * The return value will be NULL, regardless of whether the actual value is
     * NULL or whether the requested key does not exist. If it is required to know
     * whether the nested array key actually exists, pass a third argument that is
     * altered by reference:
     *
     * @code
     *  $key_exists = NULL;
     *  $value = NestedArray::get($form, $parents, $key_exists);
     *  if ($key_exists) {
     *    // ... do something with $value ...
     *  }
     * @endcode
     *
     * @param array $array      The array from which to get the value
     * @param array $parents    An array of parent keys of the value, starting with the outermost key
     * @param bool  $key_exists (optional) If given, an already defined variable that is altered by reference
     *
     * @return mixed The requested nested value. Possibly NULL if the value is NULL or not all
     *               nested parent keys exist. $key_exists is altered by reference and is a
     *               Boolean that indicates whether all nested parent keys exist (TRUE) or not
     *               (FALSE). This allows to distinguish between the two possibilities when
     *               NULL is returned
     *
     * @see NestedArray::set()
     * @see NestedArray::unset()
     */
    public static function &get(array &$array, array $parents, &$key_exists = null)
    {
        $ref = &$array;
        foreach ($parents as $parent) {
            if (\is_array($ref) && \array_key_exists($parent, $ref)) {
                $ref = &$ref[$parent];
            } else {
                $key_exists = false;
                $null = null;

                return $null;
            }
        }
        $key_exists = true;

        return $ref;
    }

    /**
     * Sets a value in a nested array with variable depth.
     *
     * This helper function should be used when the depth of the array element you
     * are changing may vary (that is, the number of parent keys is variable). It
     * is primarily used for form structures and renderable arrays.
     *
     * To deal with the situation, the code needs to figure out the route to the
     * element, given an array of parents that is either
     *
     * Without this helper function the only way to set the signature element in
     * one line would be using eval(), which should be avoided:
     *
     * @code
     * // Do not do this! Avoid eval().
     * eval('$form[\'' . implode("']['", $parents) . '\'] = $element;');
     * @endcode
     *
     * Instead, use this helper function:
     * @code
     * NestedArray::set($form, $parents, $element);
     * @endcode
     *
     * @param array $array   A reference to the array to modify
     * @param array $parents An array of parent keys, starting with the outermost key
     * @param mixed $value   The value to set
     * @param bool  $force   (optional) If TRUE, the value is forced into the structure even if it
     *                       requires the deletion of an already existing non-array parent value. If
     *                       FALSE, PHP throws an error if trying to add into a value that is not an
     *                       array. Defaults to FALSE
     *
     * @see NestedArray::unset()
     * @see NestedArray::get()
     */
    public static function setValue(array &$array, array $parents, $value, $force = false)
    {
        $ref = &$array;
        foreach ($parents as $parent) {
            // PHP auto-creates container arrays and NULL entries without error if $ref
            // is NULL, but throws an error if $ref is set, but not an array.
            if ($force && isset($ref) && !\is_array($ref)) {
                $ref = [];
            }
            $ref = &$ref[$parent];
        }
        $ref = $value;
    }

    /**
     * Unsets a value in a nested array with variable depth.
     *
     * This helper function should be used when the depth of the array element you
     * are changing may vary (that is, the number of parent keys is variable). It
     * is primarily used for form structures and renderable arrays.
     *
     * Without this helper function the only way to unset the signature element in
     * one line would be using eval(), which should be avoided:
     *
     * @code
     * // Do not do this! Avoid eval().
     * eval('unset($form[\'' . implode("']['", $parents) . '\']);');
     * @endcode
     *
     * Instead, use this helper function:
     * @code
     * NestedArray::unset($form, $parents, $element);
     * @endcode
     *
     * @param array $array       A reference to the array to modify
     * @param array $parents     An array of parent keys, starting with the outermost key and including
     *                           the key to be unset
     * @param bool  $key_existed (optional) If given, an already defined variable that is altered by
     *                           reference
     *
     * @see NestedArray::set()
     * @see NestedArray::get()
     */
    public static function unset(array &$array, array $parents, &$key_existed = null)
    {
        $unset_key = array_pop($parents);
        $ref = &self::get($array, $parents, $key_existed);
        if ($key_existed && \is_array($ref) && \array_key_exists($unset_key, $ref)) {
            $key_existed = true;
            unset($ref[$unset_key]);
        } else {
            $key_existed = false;
        }
    }

    /**
     * Determines whether a nested array contains the requested keys.
     *
     * This helper function should be used when the depth of the array element to
     * be checked may vary (that is, the number of parent keys is variable). See
     * NestedArray::setValue() for details. It is primarily used for form
     * structures and renderable arrays.
     *
     * If it is required to also get the value of the checked nested key, use
     * NestedArray::get() instead.
     *
     * @param array $array   The array with the value to check for
     * @param array $parents An array of parent keys of the value, starting with the outermost key
     *
     * @return bool TRUE if all the parent keys exist, FALSE otherwise
     *
     * @see NestedArray::get()
     */
    public static function exists(array $array, array $parents)
    {
        // Although this function is similar to PHP's array_key_exists(), its
        // arguments should be consistent with getValue().
        $key_exists = null;
        self::get($array, $parents, $key_exists);

        return $key_exists;
    }

    /**
     * Merges multiple arrays, recursively, and returns the merged array.
     *
     * This function is similar to PHP's array_merge_recursive() function, but it
     * handles non-array values differently. When merging values that are not both
     * arrays, the latter value replaces the former rather than merging with it.
     *
     * @param array ...$args Arrays to merge
     *
     * @return array The merged array
     *
     * @see NestedArray::mergeArray()
     */
    public static function merge(...$args)
    {
        return self::mergeArray($args);
    }

    /**
     * Merges multiple arrays, recursively, and returns the merged array.
     *
     * This function is equivalent to NestedArray::merge(), except the
     * input arrays are passed as a single array parameter rather than a variable
     * parameter list.
     *
     * @param array[] Arrays to merge
     * @param bool $preserveIntegerKeys
     *    (optional) If given, integer keys will be preserved and merged instead of appended
     *
     * @return array The merged array
     *
     * @see NestedArray::merge()
     */
    public static function mergeArray(array $arrays, $preserveIntegerKeys = false)
    {
        $result = [];
        foreach ($arrays as $array) {
            foreach ($array as $key => $value) {
                // Renumber integer keys as array_merge_recursive() does unless
                // $preserve_integer_keys is set to TRUE. Note that PHP automatically
                // converts array keys that are integer strings (e.g., '1') to integers.
                if (\is_int($key) && !$preserveIntegerKeys) {
                    $result[] = $value;
                }
                // Recurse when both values are arrays.
                elseif (isset($result[$key]) && \is_array($result[$key]) && \is_array($value)) {
                    $result[$key] = self::mergeArray([$result[$key], $value], $preserveIntegerKeys);
                }
                // Otherwise, use the latter value, overriding any previous value.
                else {
                    $result[$key] = $value;
                }
            }
        }

        return $result;
    }
}
