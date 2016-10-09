<?php
namespace Hub\EntryList;

/**
 * Used to handle lists in json format.
 *
 * @package AwesomeHub
 */
class EntryListJson extends EntryList
{
    /**
     * JSON error types and messages.
     *
     * @var array
     */
    static private $errors = [
        JSON_ERROR_DEPTH            => ['DEPTH', 'Maximum stack depth exceeded'],
        JSON_ERROR_STATE_MISMATCH   => ['STATE_MISMATCH', 'Underflow or the modes mismatch'],
        JSON_ERROR_CTRL_CHAR        => ['CTRL_CHAR', 'Unexpected control character found'],
        JSON_ERROR_SYNTAX           => ['SYNTAX', 'Syntax error, malformed JSON'],
        JSON_ERROR_UTF8             => ['UTF8', 'Malformed UTF-8 characters, possibly incorrectly encoded'],
    ];

    /**
     * @inheritdoc
     */
    protected function parse($data)
    {
        $decoded = json_decode($data, true);
        $errcode = json_last_error();
        if($errcode !== JSON_ERROR_NONE){
            $error = self::$errors[$errcode] ?? ['UNKNOWN', 'Unknown error'];
            throw new \RuntimeException("[JSON_ERROR_{$error[0]}] {$error[1]}.");
        }
        return $decoded;
    }
}
