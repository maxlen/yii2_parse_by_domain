<?php

namespace maxlen\parsedomain\models;

use maxlen\parsedomain\handlers\Parsedomain;
use Yii;
use maxlen\parser\helpers\Parser;

/**
 * This is the model class for table "parser_links".
 *
 * @property integer $id
 * @property string $link
 * @property integer $status
 */
class ParsedomainLinks extends \yii\db\ActiveRecord
{
    const TYPE_NOT_PARSED = 'none';
    const TYPE_PROCESS = 'process';
    const TYPE_PARSED = 'parsed';

    const MAX_PROC = 1;

    const ZERO_DATE = '0000-00-00 00:00:00';

    static $exceptions = ['mailto:', '#'];
    static $parseSubdomains = true;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'parsedomain_links';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('dbSpider');
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['link'], 'required'],
            ['link', 'unique'],
            [['domain_id', 'process_id'], 'integer'],
            [['create_date', 'begin_date', 'finish_date', 'status'], 'safe'],
            [['link'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'domain_id' => 'Domain',
            'link' => 'Link',
            'status' => 'Status',
        ];
    }
    
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getDomain()
    {
        return $this->hasOne(ParsedomainDomains::className(), ['id' => 'domain_id']);
    }

    public function setAsFinished()
    {
        $this->finish_date = date('Y-m-d H:i:s');
        $this->status = self::TYPE_PARSED;
        return $this->save();
    }
    
    public static function clearTable() {
        self::getDb()->createCommand()->truncateTable(self::tableName(true))->execute();
    }

    public static function createRow($data)
    {
        if (empty($data)) {
            return false;
        }

        if (!empty(self::isAlreadyLink($data['domain_id'], $data['link']))) {
            return false;
        }

        $row = new self;
        foreach ($data as $k => $v) {
            $row->$k = $v;
        }
        return $row->save();
    }
    
    public static function setAsBeginAndGet($processId, $domainId)
    {
        self::getDb()->createCommand(
            "UPDATE " . self::tableName() . " SET status = '" . self::TYPE_PROCESS . "'"
            . ", process_id = " . $processId . ", begin_date = '" . date('Y-m-d H:i:s') . "'"
            . " WHERE status = '" . self::TYPE_NOT_PARSED . "' AND domain_id = " . $domainId . " LIMIT 1"
        )->execute();
        
        return self::find()->where(['status' => self::TYPE_PROCESS, 'process_id' => $processId])->limit(1)->one();
    }

    public function saveOrDelete($data = [])
    {
        if (!empty(self::isAlreadyLink($this->domain->id, $data['link']))) {
            return false;
        }

        if (!empty($data)) {
            foreach ($data as $k => $v) {
                $this->$k = $v;
            }
        }

//        $this->status = ParsedomainLinks::TYPE_NOT_PARSED;
//        $this->begin_date = self:: ZERO_DATE;
//        $this->process_id = 0;

        if(!$this->save()) {
            var_dump($this->errors);
            $this->delete();
            return false;
        }

        return $this;
    }
    
    public static function cleanNotFinished($domainId)
    {
        self::getDb()->createCommand(
            "UPDATE " . self::tableName() . " SET status = " . self::TYPE_NOT_PARSED
            . ", process_id = " . self::TYPE_NOT_PARSED . ", begin_date = '" . self:: ZERO_DATE . "'"
            . " WHERE status = '" . self::TYPE_PROCESS . "' AND domain_id = " . $domainId
        )->execute();
    }

    public static function isAlreadyLink($domainId, $link)
    {
        return self::find()->where(['domain_id' => $domainId, 'link' => $link])->limit(1)->one();
    }

    public static function isLinkInExcept($href)
    {
        foreach (self::$exceptions as $exc) {
            if (strpos($href, $exc) === 0) {
                return true;
            }
        }

        return false;
    }
}
