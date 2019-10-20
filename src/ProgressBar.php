<?php
/**
 * 进度条
 * Created By: PhpStorm
 * User: yaoxiaohang
 * Date: 2019/10/19
 * Time: 21:24
 */

namespace ProgressBar;

class ProgressBar
{
    //显示模式 简洁
    const FORMAT_TYPE_NORMAL = 'normal';

    //总数
    protected $max;
    protected $step = 0;
    protected $barWidth = 28;
    protected $emptyBarChar = '░';
    protected $progressChar = '▓';
    protected $customFormat = null;
    protected $formatType = 'default';
    protected $formatLineCount = 1;
    protected $startTime;
    protected $stepWidth = 4;
    protected $messages = [];
    private $format;

    //格式
    protected $formats = [
        //默认
        'default' => ' %current%/%max% [%bar%] %percent:3s%%  时间:%elapsed%/%estimated% 速度:%speed% 内存:%memory:6s%',
        'default_nomax' => ' %current% [%bar%] 时间:%elapsed:6s% 速度:%speed% 内存:%memory:6s%',

        //简洁
        'normal' => ' %current%/%max% [%bar%] %percent:3s%%',
        'normal_nomax' => ' %current% [%bar%]',
    ];

    /**
     * 创建进度条
     * @param int $count
     * @return ProgressBar
     */
    public static function createProgressBar($count = 0){
        return new self($count);
    }

    /**
     * ProgressBar constructor.
     * @param int $max
     */
    public function __construct($max = 0)
    {
        $this->setMax($max);
        $this->startTime = microtime(true);
        $this->write('loading...');
    }

    /**
     * 进度 + N
     * @param int $step
     */
    public function next($step = 1)
    {
        $step = $this->step + $step;
        if ($this->max && $step > $this->max) {
            $this->max = $step;
        } elseif ($step < 0) {
            $step = 0;
        }
        $this->step = $step;
        $this->display();
    }

    /**
     * 结束
     */
    public function finish()
    {
        if (!$this->max) {
            $this->max = $this->step;
        }
        $this->step = $this->max;
        $this->display();
    }

    /**
     * @param $key
     * @param $message
     */
    public function setMessage($key,$message){
        $this->messages[$key] = $message;
    }

    /**
     * 设置进度条宽度
     * @param $width
     */
    public function setBarWidth($width)
    {
        $this->barWidth = max(1, (int)$width);
    }

    /**
     * 显示
     */
    protected function display()
    {
        if (null === $this->format) {
            $this->setRealFormat();
        }
        $this->clear();
        $this->write($this->buildLine());
    }

    /**
     * 构建行内容
     * @return string
     */
    private function buildLine()
    {
        $regex = "{%([a-z\-_]+)(?:\:([^%]+))?%}i";
        $callback = function ($matches) {
            $text = $matches[0];
            $percent = $this->max ? (float)$this->step / $this->max : 0;
            switch ($matches[1]) {
                //最大
                case 'max':
                    $text = $this->max;
                    break;
                //当前进度
                case 'current':
                    $text = str_pad($this->step, $this->stepWidth, ' ', STR_PAD_LEFT);
                    break;
                //进度条
                case 'bar':
                    $completeBars = floor($this->max > 0 ? $percent * $this->barWidth : $this->step % $this->barWidth);
                    $display = str_repeat($this->progressChar, $completeBars);
                    if ($completeBars < $this->barWidth) {
                        $emptyBars = $this->barWidth - $completeBars;
                        $display .= str_repeat($this->emptyBarChar, $emptyBars);
                    }
                    $text = $display;
                    break;
                //进度百分比
                case 'percent':
                    $text = floor($percent * 100);
                    break;
                //使用内存
                case 'memory':
                    $text = Helper::formatMemory(memory_get_usage(true));
                    break;
                //执行时间
                case 'elapsed':
                    $text = Helper::formatTime(microtime(true) - $this->startTime);
                    break;
                //预计时间
                case 'estimated':
                    if ($this->max) {
                        $text = Helper::formatTime(((microtime(true) - $this->startTime) / $this->step) * $this->max);
                    } else {
                        $text = '';
                    }
                    break;
                //速度
                case 'speed':
                    $time = (microtime(true) - $this->startTime);
                    if($time && $this->step){
                        $text = Helper::formatSpeed($this->step / $time);
                    }else{
                        $text = '';
                    }
                    break;
                default:
                    if(isset($this->messages[$matches[1]])){
                        $text = $this->messages[$matches[1]];
                    }
                    break;
            }

            if (isset($matches[2])) {
                $text = sprintf('%' . $matches[2], $text);
            }

            return $text;
        };
        $line = preg_replace_callback($regex, $callback, $this->format);
        //获取最长的一行 width
        $linesWidth = $this->getMessageMaxWidth($line);
        $terminalWidth = Terminal::getWidth();
        if ($linesWidth <= $terminalWidth) {
            return $line;
        }
        $this->setBarWidth($this->barWidth - $linesWidth + $terminalWidth);
        return preg_replace_callback($regex, $callback, $this->format);
    }

    /**
     * 获取输出文本 width
     * @param $messages
     */
    private function getMessageMaxWidth($messages)
    {
        $linesLength = array_map(function ($subLine) {
            $string = preg_replace("/\033\[[^m]*m/", '', $subLine);
            return Helper::strLen($string);
        }, explode("\n", $messages));
        return max($linesLength);
    }

    /**
     * 清空
     */
    protected function clear()
    {
        //光标移到最左边
        $this->write("\x0D");
        //清除光标所在行的所有字符
        $this->write("\x1B[2K");
        //清除多行
        if ($this->formatLineCount > 0) {
            $this->write(str_repeat("\x1B[1A\x1B[2K", $this->formatLineCount));
        }
    }

    /**
     * 设置格式化
     * @param $format
     */
    private function setRealFormat()
    {
        $format = $this->customFormat ?: $this->formatType;
        if (!$this->max && isset($this->formats[$format . '_nomax'])) {
            $this->format = $this->formats[$format . '_nomax'];
        } else if (isset($this->formats[$format])) {
            $this->format = $this->formats[$format];
        } else {
            $this->format = $format;
        }
        $this->formatLineCount = substr_count($this->format, "\n");
    }

    /**
     * 设置最大值
     * @param int $max
     */
    public function setMax($max = 0)
    {
        $this->max = max(0, $max);
        if ($max) $this->stepWidth = Helper::strLen($max);
    }

    /**
     * 设置模式类型
     * @param $type
     */
    public function setFormatType($type)
    {
        $this->formatType = $type;
    }

    /**
     * 设置自定义 显示格式
     * @param $format
     */
    public function setCustomFormat($format)
    {
        $this->customFormat = $format;
    }

    /**
     * 输出
     * @param $message
     */
    protected function write($message)
    {
        echo $message;
    }
}