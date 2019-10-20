
# PHP 命令行进度条

PHP ProgressBar

## 使用 Example

```php
$count = 100;
//创建进度条
$progress = ProgressBar::createProgressBar($count);
//设置进度条宽度 单位 一格
//$progress->setBarWidth(50);

//简洁模式
//$progress->setFormatType(ProgressBar::FORMAT_TYPE_NORMAL);

//自定义内容
//$progress->setCustomFormat('%title% %current%/%max% [%bar%] %percent:3s%%  时间:%elapsed%/%estimated% 速度:%speed% 内存:%memory:6s%');
//$progress->setMessage('title','heihei');

for ($i = 0; $i < $count; $i ++){
    sleep(1);
    //下一步
//    $progress->next(2);
    $progress->next();
}
//结束
$progress->finish();
```

## Requirements

- PHP version 5.4.0 or higher

## Installation

### Using Composer

Get the package:
```
$ composer require yxh/php-progressbar
```

### Manual Installation

Clone git repository:
```
$ git clone https://github.com/yaoxiaohang/php-progressbar.git
```
or download the package at https://github.com/yaoxiaohang/php-progressbar/archive/master.zip






