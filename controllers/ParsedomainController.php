<?php namespace maxlen\parsedomain\controllers;

use yii\console\Controller;
use yii\db\Exception;
use maxlen\parsedomain\handlers\Parsedomain;
use maxlen\parsedomain\models\ParsedomainDomains;
use maxlen\parsedomain\models\ParsedomainLinks;
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
    public function actionProcess($cronId)
    {
        $procDomain = ParsedomainDomains::getProcesssdDomain();
//        var_dump($procDomain);

        if (empty($procDomain)) {
            echo PHP_EOL . "There are nothing to parse (THROW EXC)";
            return;
        }

        echo PHP_EOL . "PROCESSED DOMAIN: {$procDomain->domain} - {$procDomain->id}";

        $limitter = new CliLimitter();
        $limitter->maxProc = ParsedomainDomains::MAX_PROC;
        $limitter->process = "{$this->action->controller->id}_{$this->action->id}_{$procDomain->id}";
        $limitter->run();

        Proxy::getRandomProxy();

        $link = ParsedomainLinks::find()->where(['domain_id' => $procDomain->id])->limit(1)->one();

        if(is_null($link)) {
            return;
        }

        $processId = $link->process_id;
        Parsedomain::grabLinks($link, $params);

        Parsedomain::parseByLink(
            [
                'domain' => $procDomain->domain,
                'domainId' => $procDomain->id,
                'filetypes' => $procDomain->filetypes
            ]
        );
//        die();
    }




    public function actionGrabLinks($processId)
    {
        $domain = ParsedomainDomains::getProcessedDomain();

        if(empty($domain)) {
            echo PHP_EOL. "THERE IS NO DOMAIN TO PROCESS". PHP_EOL;
            return;
        }

        $limitter = new CliLimitter();
        $limitter->maxProc = ParsedomainLinks::MAX_PROC;
        $limitter->process = "{$this->action->controller->id}_{$this->action->id}_{$domain->id}_{$processId}";
        $limitter->run();

        Proxy::getRandomProxy();

        $link = ParsedomainLinks::find()->where(['domain_id' => $domain->id])->limit(1)->one();

        if(empty($link)) {
            ParsedomainLinks::createRow(
                [
                    'domain_id' => $domain->id,
                    'link' => $domain->domain,
                    'create_date' => date('Y-m-d H:i:s')
                ]
            );
            echo PHP_EOL. "created first link for parse". PHP_EOL;
            return;
        }

        $link = ParsedomainLinks::setAsBeginAndGet($processId, $domain->id);

        if (!empty($link)) {
            Parsedomain::grabLinks($link);
        }

//        if($startNewProcess != 0) {
//            $link = ParsedomainLinks::setAsBeginAndGet($processId, $domain->id);

            if (!is_null($link)) {
//                $cr = new \vova07\console\ConsoleRunner(['file' => '@runnerScript']);
//                $cr->run("parsedomain/parsedomain/grab-links {$params['domainId']}");
            }
//        }

        $someForParse = ParsedomainLinks::find()->where(
            'status != :status AND domain_id = :domain_id',
            [':status' => ParsedomainLinks::TYPE_PARSED, ':domain_id' => $domain->id]
        )->limit(1)->one();

        if(empty($someForParse)) {
            ParsedomainDomains::setAsFinished($domain->id);
        }
    }
}