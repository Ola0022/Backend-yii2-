<?php

namespace app\models;

use Yii;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "order_items".
 *
 * @property int $id
 * @property int $order_id
 * @property string $product_name
 * @property int $quantity
 * @property float $unit_price
 * @property int $created_at
 * @property int $updated_at
 *
 * @property Orders $order
 */
class OrderItem extends \yii\db\ActiveRecord
{

    public function behaviors()
    {
        return [
            TimestampBehavior::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'order_items';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['quantity'], 'default', 'value' => 1],
            [['order_id', 'product_name', 'unit_price'], 'required'],
            [['order_id', 'quantity', 'created_at', 'updated_at'], 'integer'],
            [['unit_price'], 'number'],
            [['product_name'], 'string', 'max' => 100],
            [['order_id'], 'exist', 'skipOnError' => true, 'targetClass' => Order::class, 'targetAttribute' => ['order_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'order_id' => 'Order ID',
            'product_name' => 'Product Name',
            'quantity' => 'Quantity',
            'unit_price' => 'Unit Price',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    /**
     * Gets query for [[Order]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getOrder()
    {
        return $this->hasOne(Order::class, ['id' => 'order_id']);
    }

}
