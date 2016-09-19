<?php 
namespace maxlen\parsedomain\handlers;

use Yii;
use maxlen\proxy\helpers\Proxy;
use yii\data\ActiveDataProvider;
use common\helpers\ProxyHelpers;
use maxlen\parsedomain\models\ParsedomainDomains;
use maxlen\parsedomain\models\ParsedomainLinks;
use maxlen\parsedomain\models\ParsedomainFiles;
use common\components\CliLimitter;

class Parsedomain
{
    const TYPE_NOT_PARSED = 0;
    const TYPE_PROCESS = 1;
    const TYPE_PARSED = 2;
    const TYPE_DESIRED = 3;
    
    const FLOWS_COUNT = 10;

    /**
     * Grabbing files from domain and save to DB
     * @param string $site - domain (example: pdffiller.com)
     */
    public static function getFromDomain($site, $filetypes)
    {
        $site = self::cleanUrl($site);

//        $domain = self::getDomain($site, true);
        $site = self::cleanDomain($site, true);
        $params['domain'] = $site;
        $site = "http://" . $site;
        
        $myDomain = ParsedomainDomains::find()->where(['domain' => $site])->limit(1)->one();
        if (is_null($myDomain)) {
            $myDomain = ParsedomainDomains::createDomain($site, $params);

            $newLink = new ParsedomainLinks;
            $newLink->link = $site;
            $newLink->domain_id = $myDomain->id;
            $newLink->save();
        } else {
            ParsedomainLinks::cleanNotFinished($myDomain->id);
        }
        

        $params['domainId'] = $myDomain->id;
        
        self::parseByLink($params);
        
        return;
    }

    public static function startParse($params)
    {
        $params = ParsedomainDomains::getParams($params);
        $site = "http://" . $params['domain'];

        $myDomain = ParsedomainDomains::find()->where(['domain' => $site])->limit(1)->one();
        if (is_null($myDomain)) {
            $myDomain = ParsedomainDomains::createDomain($site, $params);

            $newLink = new ParsedomainLinks;
            $newLink->link = $site;
            $newLink->domain_id = $myDomain->id;
            $newLink->save();
        } else {
            ParsedomainLinks::cleanNotFinished($myDomain->id);
        }


        $params['domainId'] = $myDomain->id;

        self::parseByLink($params);

        return;
    }
    
    public static function parseByLink($params)
    {
        $i = $processCount = 1;
        while ($link = ParsedomainLinks::find()->where(['status' => self::TYPE_NOT_PARSED])->limit(1)->one()) {
            $link = ParsedomainLinks::setAsBeginAndGet($i, $params['domainId']);

            $cr = new \vova07\console\ConsoleRunner(['file' => '@runnerScript']);
            $cr->run("parsedomain/parsedomain/grab-links {$params['domainId']}");

//            $command = "php yii parsedomain/parsedomain/process {$params['domainId']} {$link->id} ";
//            if($i > 20) {
//                $command .= "1 > /dev/null &";
                $processCount++;
//            }

//            $i++;

            exec($command);
            
            if($processCount > ParsedomainDomains::MAX_PROC) {
                break;
            }
        }
        
//        ParsedomainDomains::setAsFinished($params['domainId']);
//        ParserLinks::clearTable();
        
        echo PHP_EOL. " ALL DONE ". PHP_EOL;
        
//        mail('maxim.gavrilenko@pdffiller.com', 'site parser is finished', 'Te site parser for ' . $params['domain'] . ' is finished');
        
        return;
    }
    
    public static function grabLinks($site, $params)
    { 
        $domain = $params['domain'];
        
        $result = ProxyHelpers::getHtmlByUrl($site->link, ['getInfo' => true, 'content_type' => ['html']]);
            
        if ($result !== FALSE && in_array($result['info']['http_code'], [404])) {
            $site->delete();
            return;
        }

        if (!$result || in_array($result['info']['http_code'], [301, 302])) {
            $site->link = $result['info']['redirect_url'];
            
            if(!$site->save()) {
                $site->delete();
                return;
            }

            $result = ProxyHelpers::getHtmlByUrl($site->link, ['getInfo' => true, 'content_type' => ['html']]);
        }
        
        if ($result) {
            
            $parseDom = parse_url($site->link);

            if ($parseDom['host'] != '') {
                $siteDomain = $parseDom['host'];
            }

            \phpQuery::newDocument($result['page']);
            $links = pq('a');

            foreach ($links as $link) {

                $href = pq($link)->attr('href');

                if (in_array($href, $params['exceptions'])) {
                    continue;
                }

                $hrefDomain = self::getDomain($href);

                if (!is_null($hrefDomain)) {
                    $isSource = strpos(self::cleanDomain($hrefDomain), self::cleanDomain($domain->domain));
                    if($isSource !== false) {
                        if(!$params['parseSubdomains'] && $isSource != 0) {
                            continue;
                        }
                    }
                    else {
                        continue;
                    }
                }

                if (is_null($hrefDomain) && isset($siteDomain)) {
                    if (is_null($hrefDomain)) {
                        $href = (strpos($href, '/') !== FALSE && strpos($href, '/') == 0) ? "{$siteDomain}{$href}" : "{$siteDomain}/{$href}";
                    } else {
                        $href = self::cleanDomain($href);
                    }

                    $href = "http://" . $href;
                }

                $href = self::cleanUrl($href);
                
                $approved = true;
                foreach ($params['exceptions'] as $exception) {
                    if (stripos($href, $exception)) {
                        $approved = false;
                        break;
                    }
                }

                if ($approved) {
                    if (isset($params['exts']) && self::isHtml($href, $params['exts'], true)) {
                        // save to spider_forms
                        ParsedomainFiles::createForm($domain->id, $href);
                    } elseif (self::isHtml($href)) {
                        $newLink = new ParserLinks;
                        $newLink->domain_id = $domain->id;
                        $newLink->link = $href;
                        $newLink->save();
                    }
                }
            }
            
            $site->status = self::TYPE_PARSED;
            $site->save();
        }
        else {
            $site->delete();
        }
    }
    
    /**
     * Grabbing array of files by site (without DB)
     * 
     * @param string $site - domain (example: pdffiller.com)
     * @param array $params - addition parameters
     *                  'exts' - (array) file extensions wich must be grabbed (example: ['pdf', 'doc'])
     *                  'parseSubdomains' - (bool) is parse links from subdomains (default = true)
     * @return array - links
     */
    public static function getFromSite($site, $params = [])
    {   
        $return = [];
        $exceptions = ['mailto:', '#'];
        $params['exceptions'] = $exceptions;

        $parseSubdomains = true;
        if(isset($params['parseSubdomains'])) {
            $parseSubdomains = $params['parseSubdomains'];
        }
        
        $resLinks = [];

        $site = self::cleanUrl($site);

        $domain = self::getDomain($site, true);
        $site = self::cleanDomain($site, true);
        $site = "http://" . $site;

        $resLinks[$site] = self::TYPE_NOT_PARSED;

        do {
            if ($resLinks[$site] == self::TYPE_NOT_PARSED) {
                print_r($resLinks);
                var_dump(count($resLinks));

                $cleanSite = $site;

                $site = self::cleanUrl($site);

                $domain = self::getDomain($site, true);
                $site = self::cleanDomain($site, true);
                $site = "http://" . $site;

                if (isset($resLinks[$site])) {
                    $isParse = false;
                    if (isset($params['exts']) && self::isHtml($site, $params['exts'], true)) {
                        $resLinks[$site] = self::TYPE_DESIRED;
                        if(!in_array($site, $return)) {
                            $return[] = $site;
                        }
                    } elseif (self::isHtml($site)) {
                        $resLinks[$site] = self::TYPE_NOT_PARSED;
                        $isParse = true;
                    }
                } else {
                    $isParse = true;
                }

                if (isset($params['exts']) && self::isHtml($site, $params['exts'], true)) {
                    $resLinks[$site] = self::TYPE_DESIRED;
                    if(!in_array($site, $return)) {
                        $return[] = $site;
                    }
                } else {
                    if (self::isHtml($site) && $isParse) {
                        $result = ProxyHelpers::getHtmlByUrl($site, ['getInfo' => true, 'content_type' => ['html']]);

                        $addDomain = true;

                        if ($result !== FALSE && in_array($result['info']['http_code'], [404])) {
                            unset($resLinks[$site]);
                            unset($resLinks[$cleanSite]);
                            continue;
                        }

                        if (!$result || in_array($result['info']['http_code'], [301])) {
                            unset($resLinks[$site]);
                            unset($resLinks[$cleanSite]);
                            $site = $result['info']['redirect_url'];
                            $resLinks[$site] = self::TYPE_NOT_PARSED;

                            $result = ProxyHelpers::getHtmlByUrl($site, ['getInfo' => true, 'content_type' => ['html']]);

                            if ($result) {
                                $domain = self::getDomain($site, false, true);
                            }

                            $addDomain = false;
                        }

                        if ($result) {
                            $parseDom = parse_url($site);

                            if ($parseDom['host'] != '') {
                                $siteDomain = $parseDom['host'];
                            }

                            \phpQuery::newDocument($result['page']);
                            $links = pq('a');

                            foreach ($links as $link) {

                                $href = pq($link)->attr('href');

                                if (in_array($href, $exceptions)) {
                                    continue;
                                }

                                $hrefDomain = self::getDomain($href);

                                if (!is_null($hrefDomain)) {
                                    $isSource = strpos(self::cleanDomain($hrefDomain), self::cleanDomain($domain));
                                    if($isSource !== false) {
                                        if(!$parseSubdomains && $isSource != 0) {
                                            continue;
                                        }
                                    }
                                    else {
                                        continue;
                                    }
                                }

                                if (is_null($hrefDomain) && isset($siteDomain)) {
                                    if (is_null($hrefDomain)/* && strpos($href, $domain) */) {
                                        $href = (strpos($href, '/') !== FALSE && strpos($href, '/') == 0) ? "{$siteDomain}{$href}" : "{$siteDomain}/{$href}";
                                    } else {
                                        $href = self::cleanDomain($href);
                                    }

                                    $href = "http://" . $href;
                                }

                                $href = self::cleanUrl($href);

                                if (!isset($resLinks[$href]) && !isset($resLinks[rtrim($href, '/')])) {
                                    $approved = true;
                                    foreach ($exceptions as $exception) {
                                        if (stripos($href, $exception)) {
                                            $approved = false;
                                            break;
                                        }
                                    }

                                    if ($approved) {
                                        $resLinks[$href] = self::TYPE_NOT_PARSED;
                                    }
                                }
                            }

                            $resLinks[$site] = self::TYPE_PARSED;
                        } else {
                            unset($resLinks[$site]);
                            unset($resLinks[$cleanSite]);
                        }
                    } else {
                        unset($resLinks[$site]);
                        unset($resLinks[$cleanSite]);
                    }
                }
            }

        } while ($site = array_search(self::TYPE_NOT_PARSED, $resLinks));
        
        return $return;
    }

    public static function getDomain($url, $isDom = false, $saveWww = false)
    {
        if ($isDom) {
            $parse = parse_url($url);
            $domain = (isset($parse['host']) && !is_null($parse['host'])) ? $parse['host'] : rtrim($url, '/');
        } else {
            $parse = parse_url($url);
            $domain = (isset($parse['host']) && !is_null($parse['host'])) ? $parse['host'] : null;
        }

        if (!is_null($domain)) {
            $domain = self::cleanDomain($domain, $saveWww);
        }

        return $domain;
    }

    public static function cleanDomain($url, $saveWww = false)
    {
        if (strpos($url, 'http://') == 0) {
            $url = str_replace('http://', '', $url);
        }

        if (strpos($url, 'https://') == 0) {
            $url = str_replace('https://', '', $url);
        }

        if (!$saveWww && strpos($url, 'www.') == 0) {
            $url = str_replace('www.', '', $url);
        }

        return $url;
    }

    public static function isHtml($url, $extensions = [], $yes = false)
    {
        $ext = pathinfo($url, PATHINFO_EXTENSION);

        if (empty($extensions)) {
            return in_array(
                $ext,
                ['jpg', 'jpeg', 'bmp', 'png', 'gif', 'iso', 'avi', 'mov', 'mp3',
                'doc', 'docx', 'pdf', 'txt', 'rtf', 'zip', 'xls', 'xml']
                ) ? false : true;
        } else {
            if ($yes) {
                return in_array($ext, $extensions) ? true : false;
            }
            
            return !in_array($ext, $extensions) ? true : false;
        }
    }

    public static function addWww($url)
    {
        if (!strpos($url, 'www.')) {
            $url = 'http://www.' . str_replace('http://', '', $url);
        }

        return $url;
    }

    public static function cleanUrl($url)
    {
        if (strpos($url, '../')) {
            $url = str_replace('../', '', $url);
        }

        return $url;
    }
}
