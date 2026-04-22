<?php

namespace app\models;

use Yii;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "orders".
 *
 * @property int $id
 * @property int $user_id
 * @property string $status
 * @property float $total_price
 * @property string|null $notes
 * @property int $created_at
 * @property int $updated_at
 *
 * @property OrderItem[] $orderItems
 * @property User $user
 */
class Order extends \yii\db\ActiveRecord
{

    public function behaviors()
    {
        return [
            TimestampBehavior::class,
        ];
    }

     // The three allowed statuses as constants
    const STATUS_PENDING     = 'pending';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_DELIVERED   = 'delivered';

    // Forward-only flow map
    const STATUS_FLOW = [
        self::STATUS_PENDING     => self::STATUS_IN_PROGRESS,
        self::STATUS_IN_PROGRESS => self::STATUS_DELIVERED,
        self::STATUS_DELIVERED   => null, // final state, no further transitions
    ];

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'orders';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['notes'], 'default', 'value' => null],
            [['status'], 'default', 'value' => self::STATUS_PENDING],
            [['total_price'], 'default', 'value' => 0.00],
            [['user_id'], 'required'],
            [['user_id', 'created_at', 'updated_at'], 'integer'],
            [['total_price'], 'number'],
            [['notes'], 'string'],
            [['status'], 'string', 'max' => 20],
            // Status must be one of the three allowed values
            [['status'], 'in', 'range' => [
                self::STATUS_PENDING,
                self::STATUS_IN_PROGRESS,
                self::STATUS_DELIVERED,
            ]],
            // Validates that user_id actually exists in the user table
            [['user_id'], 'exist',
                'skipOnError'     => true,
                'targetClass'     => User::class,
                'targetAttribute' => ['user_id' => 'id']
            ],        
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'User ID',
            'status' => 'Status',
            'total_price' => 'Total Price',
            'notes' => 'Notes',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

     /**
     * Checks if this order can transition to the given status
     */
    public function canTransitionTo($newStatus)
    {
        if (!isset(self::STATUS_FLOW[$this->status])) {
            return false;
        }
        $next = self::STATUS_FLOW[$this->status];
        return $next !== null && $next === $newStatus;
    }
    
    /**
     * Gets query for [[OrderItems]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getOrderItems()
    {
        return $this->hasMany(OrderItem::class, ['order_id' => 'id']);
    }

    /**
     * Gets query for [[User]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

}
