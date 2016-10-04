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
    const TYPE_NOT_PARSED = 0;
    const TYPE_PROCESS = 1;
    const TYPE_PARSED = 2;
    const TYPE_DESIRED = 3;

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
            [['status', 'domain_id', 'process_id'], 'integer'],
            [['create_date', 'begin_date', 'finish_date'], 'safe'],
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
        return $this->hasOne(ParserDomains::className(), ['id' => 'domain_id']);
    }
    
    public static function clearTable() {
        self::getDb()->createCommand()->truncateTable(self::tableName(true))->execute();
    }

    public static function createRow($data)
    {
        if (empty($data)) {
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
            'UPDATE ' . self::tableName() . ' SET status = ' . self::TYPE_PROCESS
            . ', process_id = ' . $processId . ', begin_date = "' . self:: ZERO_DATE . '"'
            . ' WHERE status = ' . self::TYPE_NOT_PARSED . ' AND domain_id = ' . $domainId . ' LIMIT 1'
        )->execute();
        
        return self::find()->where(['status' => self::TYPE_PROCESS, 'process_id' => $processId])->limit(1)->one();
    }

    public function saveOrDelete($data = [])
    {
        if (!empty($data)) {
            foreach ($data as $k => $v) {
                $this->$k = $v;
            }
        }
//        $this->status = ParsedomainLinks::TYPE_NOT_PARSED;
//        $this->begin_date = self:: ZERO_DATE;
//        $this->process_id = 0;

        if(!$this->save()) {
            $this->delete();
            return false;
        }

        return $this;
    }
    
    public static function cleanNotFinished($domainId)
    {
        self::getDb()->createCommand(
            'UPDATE ' . self::tableName() . ' SET status = ' . self::TYPE_NOT_PARSED
            . ', process_id = ' . self::TYPE_NOT_PARSED . ', begin_date = "' . self:: ZERO_DATE . '"'
            . ' WHERE status = ' . self::TYPE_PROCESS . ' AND domain_id = ' . $domainId
        )->execute();
    }
}
