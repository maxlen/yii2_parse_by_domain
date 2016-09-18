<?php

namespace maxlen\parsedomain\models;

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
    
    public static function setAsBeginAndGet($processId, $domainId)
    {
        self::getDb()->createCommand(
            'UPDATE ' . self::tableName() . ' SET status = ' . Parser::TYPE_PROCESS
            . ', process_id = ' . $processId
            . ' WHERE status = ' . Parser::TYPE_NOT_PARSED . ' AND domain_id = ' . $domainId . ' LIMIT 1'
        )->execute();
        
        return self::find()->where(['status' => Parser::TYPE_PROCESS, 'process_id' => $processId])->limit(1)->one();
    }
    
    public static function cleanNotFinished($domainId)
    {
        self::getDb()->createCommand(
            'UPDATE ' . self::tableName() . ' SET status = ' . Parser::TYPE_NOT_PARSED
            . ', process_id = NULL '
            . ' WHERE status = ' . Parser::TYPE_PROCESS . ' AND domain_id = ' . $domainId
        )->execute();
    }
}
