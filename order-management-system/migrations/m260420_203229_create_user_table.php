<?php

use yii\db\Migration;

class m260420_203229_create_user_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%user}}', [
            'id'         => $this->primaryKey(),
            'username'   => $this->string(50)->notNull()->unique(),
            'email'      => $this->string(100)->notNull()->unique(),
            'password'   => $this->string(255)->notNull(),
            'auth_key'   => $this->string(32)->null(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);
    }

    public function safeDown()
    {
        $this->dropTable('{{%user}}');
    }
}