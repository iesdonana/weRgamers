<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace app\assets;

use yii\web\AssetBundle;

/**
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class JsAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $css = [
    ];
    public $js = [
        'js/jquery.countdown.js',
        'js/moment.js',
        'js/moment-timezone-with-data.js',
        'js/jquery.knob.js',
        'js/typeahead.jquery.js',
        'js/bloodhound.js',
        'js/search.js',
        'js/socket.io.js',
    ];
    public $depends = [
        '\yii\web\JqueryAsset',
    ];
}