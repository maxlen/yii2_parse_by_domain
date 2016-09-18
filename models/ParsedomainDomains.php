<?php

namespace maxlen\parsedomain\models;

use Yii;

/**
 * This is the model class for table "parser_domains".
 *
 * @property integer $id
 * @property string $domain
 * @property string $begin_date
 * @property string $finish_date
 */
class ParsedomainDomains extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'parsedomain_domains';
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
            [['domain'], 'required'],
            [['domain'], 'unique'],
            [['f_id', 'cron_id', 'create_date', 'begin_date', 'finish_date'], 'safe'],
            [['domain', 'filetypes'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'domain' => 'Domain',
            'create_date' => 'Create Date',
            'begin_date' => 'Begin Date',
            'finish_date' => 'Finish Date',
        ];
    }
    
    public static function createDomain($domainName)
    {
        $domain = self::find()->where('domain = :domain', [':domain' => $domainName])->limit(1)->one();
        
        if(is_null($domain)) {
            $domain = new self;
            $domain->domain = $domainName;
            $domain->create_date = date('Y-m-d H:i:s');
            $domain->begin_date = date('Y-m-d H:i:s');
            $domain->save();
        }
        
        return $domain;
    }
    
    public static function setAsFinished($id)
    {
        self::updateAll(
            ['finish_date' => date('Y-m-d H:i:s')],
            'id = :id', [':id' => $id]
        );
    }
}
