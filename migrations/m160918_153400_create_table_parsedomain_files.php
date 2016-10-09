<?php
use yii\db\Schema;
use yii\db\Migration;

class m160918_153400_create_table_parsedomain_files extends Migration
{
    private $_table = '{{parsedomain_files}}';

    public function init()
    {
        $this->db = 'dbSpider';
        parent::init();
    }

    public function safeUp()
    {
        $this->createTable($this->_table, [
            'id' => Schema::TYPE_PK,
            'domain_id' => Schema::TYPE_INTEGER . ' NOT NULL',
            'link' => 'varchar(255) NOT NULL',
            'form_id' => Schema::TYPE_INTEGER . ' DEFAULT NULL',
            'processed' => 'tinyint(1) NOT NULL DEFAULT 0',
            'is_new' => 'tinyint(1) NOT NULL DEFAULT 0',
            'create_date' => 'DATETIME DEFAULT "0000-00-00 00:00:00"',
            'proc_date' => 'DATETIME DEFAULT "0000-00-00 00:00:00"',
        ], 'CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE=InnoDB');

        $this->createIndex('domain_id', $this->_table, 'domain_id');
        $this->createIndex('link', $this->_table, 'link(50)');
        $this->createIndex('processed', $this->_table, 'processed');
        $this->createIndex('is_new', $this->_table, 'is_new');
        $this->createIndex('proc_date', $this->_table, 'proc_date');
    }

    public function safeDown()
    {
        $this->dropTable($this->_table);
    }
}
