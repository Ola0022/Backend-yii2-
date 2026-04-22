<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\web\IdentityInterface;

/**
 * This is the model class for table "user".
 *
 * @property int $id
 * @property string $username
 * @property string $email
 * @property string $password
 * @property string|null $auth_key
 * @property int $created_at
 * @property int $updated_at
 * @property Order[] $orders
 */
class User extends ActiveRecord implements IdentityInterface
{
    // We use this to receive plain password from request
    // without overwriting the hashed one immediately
    public $plain_password;

    /**
     * Like [Table("user")] in .NET
     */
    public static function tableName()
    {
        return 'user';
    }

    /**
     * TimestampBehavior = automatically sets created_at and updated_at
     */
    public function behaviors()
    {
        return [
            \yii\behaviors\TimestampBehavior::class,
        ];
    }

    /**
     * Validation rules — like Data Annotations in .NET
     * But notice: created_at and updated_at are REMOVED from required
     * because TimestampBehavior handles them automatically
     */
    public function rules()
    {
        return [
            [['username', 'email'], 'required'],
            [['plain_password'], 'required', 'on' => 'create'],
            [['username'], 'string', 'max' => 50],
            [['email'], 'string', 'max' => 100],
            [['email'], 'email'],                    // validates email format
            [['plain_password'], 'string', 'min' => 6],
            [['auth_key'], 'string', 'max' => 32],
            [['username'], 'unique'],
            [['email'], 'unique'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id'             => 'ID',
            'username'       => 'Username',
            'email'          => 'Email',
            'password'       => 'Password',
            'auth_key'       => 'Auth Key',
            'created_at'     => 'Created At',
            'updated_at'     => 'Updated At',
        ];
    }

    /**
     * Runs automatically before every save()
     */
    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            // If creating new user
            if ($insert) {
                // Hash the password before saving to DB
                $this->password = Yii::$app->security->generatePasswordHash($this->plain_password);
                // Generate the Bearer token for API authentication
                $this->auth_key = Yii::$app->security->generateRandomString(32);
            }

            // If updating and password provided
            if (!$insert && !empty($this->plain_password)) {
                $this->password = Yii::$app->security->generatePasswordHash($this->plain_password);
            }
            
            return true;
        }
        return false;
    }

    /**
     * Validates plain password against hashed one
     */
    public function validatePassword($password)
    {
        return Yii::$app->security->validatePassword($password, $this->password);
    }

    /**
     * Find user by username — used during login
     */
    public static function findByUsername($username)
    {
        return static::findOne(['username' => $username]);
    }

    // ---- IdentityInterface methods ----
    // Yii2 requires these 4 methods for authentication to work

    public static function findIdentity($id)
    {
        return static::findOne($id);
    }

    public static function findIdentityByAccessToken($token, $type = null)
    {
        // This is called automatically on every protected request
        // Yii2 reads the Bearer token and calls this to find the user
        return static::findOne(['auth_key' => $token]);
    }

    public function getId()
    {
        return $this->id;
    }

    public function getAuthKey()
    {
        return $this->auth_key;
    }

    public function validateAuthKey($authKey)
    {
        return $this->auth_key === $authKey;
    }

    /**
     * Navigation property — like public ICollection<Order> Orders in .NET
     */
    public function getOrders()
    {
        return $this->hasMany(Order::class, ['user_id' => 'id']);
    }

}
