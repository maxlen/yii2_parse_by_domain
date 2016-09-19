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
    public function actionProcess()
    {
        $procDomain = ParsedomainDomains::getProcesssdDomain();
//        var_dump($procDomain);

        if (empty($procDomain)) {
            echo PHP_EOL . "There are nothing to parse";
            return;
        }

        echo PHP_EOL . "PROCESSED DOMAIN: {$procDomain->domain} - {$procDomain->id}";

        $limitter = new CliLimitter();
        $limitter->maxProc = ParsedomainDomains::MAX_PROC;
        $limitter->process = "{$this->action->controller->id}_{$this->action->id}_{$procDomain->id}";
        $limitter->run();

        Proxy::getRandomProxy();

        $link = ParsedomainLinks::find()->where(['id' => $linkId])->limit(1)->one();

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




    public function actionGrabLinks($domainId, $linkId, $startNewProcess = 0)
    {
        $processedDomain = ParsedomainDomains::getProcesssdDomain();

        var_dump($processedDomain);
        die();

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

        $link = ParsedomainLinks::find()->where(['id' => $linkId])->limit(1)->one();

        if(is_null($link)) {
            return;
        }

        $processId = $link->process_id;
        Parsedomain::grabLinks($link, $params);

        if($startNewProcess != 0) {
            $link = ParsedomainLinks::setAsBeginAndGet($processId, $domain->id);

            if (!is_null($link)) {
                $cr = new \vova07\console\ConsoleRunner(['file' => '@runnerScript']);
                $cr->run("parsedomain/parsedomain/grab-links {$params['domainId']}");
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