<?php
/**
 * 获取命令行 宽高
 * Created By: PhpStorm
 * User: yaoxiaohang
 * Date: 2019/10/19
 * Time: 23:25
 */

namespace ProgressBar;

class Terminal
{
    private static $width;
    private static $height;

    /**
     * 获取命令行宽度
     * @return int
     */
    public static function getWidth()
    {
        $width = getenv('COLUMNS');
        if (false !== $width) {
            return (int) trim($width);
        }

        if (null === self::$width) {
            self::initDimensions();
        }

        return self::$width ?: 80;
    }

    /**
     * 获取命令行高度
     * @return int
     */
    public static function getHeight()
    {
        $height = getenv('LINES');
        if (false !== $height) {
            return (int) trim($height);
        }

        if (null === self::$height) {
            self::initDimensions();
        }

        return self::$height ?: 50;
    }

    /**
     *
     */
    private static function initDimensions()
    {
        if ('\\' === DIRECTORY_SEPARATOR) {
            if (preg_match('/^(\d+)x(\d+)(?: \((\d+)x(\d+)\))?$/', trim(getenv('ANSICON')), $matches)) {
                // extract [w, H] from "wxh (WxH)"
                // or [w, h] from "wxh"
                self::$width = (int) $matches[1];
                self::$height = isset($matches[4]) ? (int) $matches[4] : (int) $matches[2];
            } elseif (null !== $dimensions = self::getConsoleMode()) {
                // extract [w, h] from "wxh"
                self::$width = (int) $dimensions[0];
                self::$height = (int) $dimensions[1];
            }
        } elseif ($sttyString = self::getSttyColumns()) {
            if (preg_match('/rows.(\d+);.columns.(\d+);/i', $sttyString, $matches)) {
                // extract [w, h] from "rows h; columns w;"
                self::$width = (int) $matches[2];
                self::$height = (int) $matches[1];
            } elseif (preg_match('/;.(\d+).rows;.(\d+).columns/i', $sttyString, $matches)) {
                // extract [w, h] from "; h rows; w columns"
                self::$width = (int) $matches[2];
                self::$height = (int) $matches[1];
            }
        }
    }

    /**
     * Runs and parses mode CON if it's available, suppressing any error output.
     *
     * @return int[]|null An array composed of the width and the height or null if it could not be parsed
     */
    private static function getConsoleMode()
    {
        if (!function_exists('proc_open')) {
            return;
        }

        $descriptorspec = array(
            1 => array('pipe', 'w'),
            2 => array('pipe', 'w'),
        );
        $process = proc_open('mode CON', $descriptorspec, $pipes, null, null, array('suppress_errors' => true));
        if (is_resource($process)) {
            $info = stream_get_contents($pipes[1]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            proc_close($process);

            if (preg_match('/--------+\r?\n.+?(\d+)\r?\n.+?(\d+)\r?\n/', $info, $matches)) {
                return array((int) $matches[2], (int) $matches[1]);
            }
        }
    }

    /**
     * Runs and parses stty -a if it's available, suppressing any error output.
     *
     * @return string|null
     */
    private static function getSttyColumns()
    {
        if (!function_exists('proc_open')) {
            return;
        }

        $descriptorspec = array(
            1 => array('pipe', 'w'),
            2 => array('pipe', 'w'),
        );

        $process = proc_open('stty -a | grep columns', $descriptorspec, $pipes, null, null, array('suppress_errors' => true));
        if (is_resource($process)) {
            $info = stream_get_contents($pipes[1]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            proc_close($process);

            return $info;
        }
    }
}