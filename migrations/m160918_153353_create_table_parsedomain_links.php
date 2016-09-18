<?php
use yii\db\Schema;
use yii\db\Migration;

class m160918_153353_create_table_parsedomain_links extends Migration
{
    private $_table = '{{parsedomain_links}}';

    public function init()
    {
        $this->db = 'dbSpider';
        parent::init();
    }

    public function safeUp()
    {
        $this->createTable($this->_table, [
            'id' => Schema::TYPE_PK,
            'domain_id' => Schema::TYPE_INTEGER,
            'link' => 'varchar(255) NOT NULL',
            'process_id' => 'tinyint(2) UNSIGNED DEFAULT NULL',
            'status' => 'ENUM("none", "process", "parsed") DEFAULT "none"',
            'create_date' => 'DATETIME DEFAULT "0000-00-00 00:00:00"',
            'begin_date' => 'DATETIME DEFAULT "0000-00-00 00:00:00"',
            'finish_date' => 'DATETIME DEFAULT "0000-00-00 00:00:00"',
        ], 'CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE=InnoDB');

        $this->createIndex('domain_id', $this->_table, 'domain_id');
        $this->createIndex('link', $this->_table, 'link');
        $this->createIndex('process_id', $this->_table, 'process_id');
        $this->createIndex('status', $this->_table, 'status');
        $this->createIndex('begin_date', $this->_table, 'begin_date');
        $this->createIndex('finish_date', $this->_table, 'finish_date');
    }

    public function safeDown()
    {
        $this->dropTable($this->_table);
    }
}
