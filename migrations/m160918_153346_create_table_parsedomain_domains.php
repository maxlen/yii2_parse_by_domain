<?php
use yii\db\Schema;
use yii\db\Migration;

class m160918_153346_create_table_parsedomain_domains extends Migration
{
    private $_table = '{{parsedomain_domains}}';

    public function init()
    {
        $this->db = 'dbSpider';
        parent::init();
    }

    public function safeUp()
    {
        $this->createTable($this->_table, [
            'id' => Schema::TYPE_PK,
            'domain' => 'varchar(255) NOT NULL',
            'f_id' => Schema::TYPE_INTEGER,
            'cron_id' => Schema::TYPE_INTEGER . ' DEFAULT NULL',
            'filetypes' => 'varchar(255)',
            'create_date' => 'DATETIME DEFAULT "0000-00-00 00:00:00"',
            'begin_date' => 'DATETIME DEFAULT "0000-00-00 00:00:00"',
            'finish_date' => 'DATETIME DEFAULT "0000-00-00 00:00:00"',
        ], 'CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE=InnoDB');

        $this->createIndex('domain', $this->_table, 'domain');
        $this->createIndex('f_id', $this->_table, 'f_id');
        $this->createIndex('begin_date', $this->_table, 'begin_date');
        $this->createIndex('finish_date', $this->_table, 'finish_date');
    }

    public function safeDown()
    {
        $this->dropTable($this->_table);
    }
}
