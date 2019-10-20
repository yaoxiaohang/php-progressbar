<?php
namespace ProgressBar;
/**
 * Created By: PhpStorm
 * User: yaoxiaohang
 * Date: 2019/10/20
 * Time: 21:15
 */
class Helper
{
    /**
     * 格式化内存
     * @param $memory
     * @return string
     */
    public static function formatMemory($memory)
    {
        if ($memory >= 1024 * 1024 * 1024) {
            return sprintf('%.1f GB', $memory / 1024 / 1024 / 1024);
        }

        if ($memory >= 1024 * 1024) {
            return sprintf('%.1f MB', $memory / 1024 / 1024);
        }

        if ($memory >= 1024) {
            return sprintf('%d KB', $memory / 1024);
        }

        return sprintf('%d B', $memory);
    }

    /**
     * 获取字符长度
     * @param $string
     * @return int
     */
    public static function strLen($string)
    {
        if (false === $encoding = mb_detect_encoding($string, null, true)) {
            return strlen($string);
        }
        return mb_strwidth($string, $encoding);
    }

    /**
     * 格式化时间
     * @param $secs
     * @return mixed|string
     */
    public static function formatTime($time)
    {
        $time = ceil($time);
        if($time > 3600){
            $hours = floor($time/3600);
            return $hours.":".gmstrftime('%M:%S', $time);
        }else{
            return gmstrftime('%M:%S', $time);
        }
    }

    /**
     * 格式化速度
     * @param $speed
     * @return mixed
     */
    public static function formatSpeed($speed){
        if($speed > 0.9){
            return round($speed) . '/s';
        }else if($speed * 60 > 1){
            return round($speed * 60) . '/m';
        }else{
            return round($speed * 3600) . '/h';
        }
    }
}