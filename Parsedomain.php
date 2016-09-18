<?php

namespace maxlen\parsedomain;

use yii\console\Exception;

class Parsedomain extends \yii\base\Module
{
    public $controllerNamespace = 'maxlen\parsedomain\controllers';

    public $reportSettings = [];

    public function init()
    {
        parent::init();
        if(empty($this->reportSettings)) {
            throw new Exception('Empty reportSettings');
        }
    }
}
