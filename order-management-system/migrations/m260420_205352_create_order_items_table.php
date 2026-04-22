<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%order_items}}`.
 */
class m260420_205352_create_order_items_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%order_items}}', [
            'id'           => $this->primaryKey(),
            'order_id'     => $this->integer()->notNull(),
            'product_name' => $this->string(100)->notNull(),
            'quantity'     => $this->integer()->notNull()->defaultValue(1),
            'unit_price'   => $this->decimal(10, 2)->notNull(),
            'created_at'   => $this->integer()->notNull(),
            'updated_at'   => $this->integer()->notNull(),
        ]);

        $this->addForeignKey(
            'fk_order_items_order_id',
            '{{%order_items}}',
            'order_id',
            '{{%orders}}',
            'id',
            'CASCADE'
        );
        }

    public function safeDown()
    {
        $this->dropForeignKey('fk_order_items_order_id', '{{%order_items}}');
        $this->dropTable('{{%order_items}}');
    }
}
