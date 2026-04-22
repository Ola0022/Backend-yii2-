<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%orders}}`.
 */
class m260420_205231_create_orders_table extends Migration
{
   public function safeUp()
    {
        $this->createTable('{{%orders}}', [
            'id'          => $this->primaryKey(),
            'user_id'     => $this->integer()->notNull(),
            'status'      => $this->string(20)->notNull()->defaultValue('pending'),
            'total_price' => $this->decimal(10, 2)->notNull()->defaultValue(0),
            'notes'       => $this->text()->null(),
            'created_at'  => $this->integer()->notNull(),
            'updated_at'  => $this->integer()->notNull(),
        ]);

        $this->addForeignKey(
            'fk_orders_user_id',
            '{{%orders}}',
            'user_id',
            '{{%user}}',
            'id',
            'CASCADE'
        );
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk_orders_user_id', '{{%orders}}');
        $this->dropTable('{{%orders}}');
    }
}
