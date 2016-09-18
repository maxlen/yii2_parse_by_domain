<?php namespace maxlen\parsedomain\controllers;

use yii\console\Controller;
use yii\db\Exception;
use maxlen\parsedomain\handlers\Parsedomain;
use maxlen\parsedomain\models\ParserDomains;
use maxlen\parsedomain\models\ParserLinks;
use maxlen\proxy\helpers\Proxy;
use common\components\CliLimitter;

/**
 * Parse domain
 * User: maxim.gavrilenko@pdffiller.com
 * Date: 17.09.16
 * Time: 15:34
 */
class ParsedomainController extends Controller
{
    const MAX_PROC = 10;

    public function actionGrabLinks($domainId, $linkId, $startNewProcess = 0)
    {
        $limitter = new CliLimitter();
        $limitter->maxProc = self::MAX_PROC;
        $limitter->process = "{$this->action->controller->id}_{$this->action->id}_{$domainId}";
        $limitter->run();

        $params = Parser::getParams();

        $domain = ParserDomains::find()->where(['id' => $domainId])->limit(1)->one();

        if(is_null($domain)) {
            echo PHP_EOL. " THERE IS NO DOMAIN id = {$domainId} IN DB". PHP_EOL;
            return;
        }

        $params['domain'] = $domain;

        Proxy::getRandomProxy();

        $link = ParserLinks::find()->where(['id' => $linkId])->limit(1)->one();

        if(is_null($link)) {
            return;
        }

        $processId = $link->process_id;
        Parser::grabLinks($link, $params);

        if($startNewProcess != 0) {
            $link = ParserLinks::setAsBeginAndGet($processId, $domain->id);

            if (!is_null($link)) {
                $command = new \console\modules\bingads\controllers\KeywordsReportController(
                    'KeywordsReport',
                    'bingads'
                );
                $command->actionGrab();

                $command = "php yii parser/parser/grab-links {$domain->id} {$link->id} 1 > /dev/null &";
                exec($command);
            }
        }

        $someForParse = ParserLinks::find()->where(
            'status != :status AND domain_id = :domain_id',
            [':status' => Parser::TYPE_PARSED, ':domain_id' => $domain->id]
        )->limit(1)->one();
        if(is_null($someForParse)) {
            ParserDomains::setAsFinished($domain->id);
        }
    }
}