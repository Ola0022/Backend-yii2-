<?php

namespace app\controllers;

use Yii;
use yii\rest\Controller;
use yii\web\Response;
use yii\filters\auth\HttpBearerAuth;
use yii\rest\ActiveController;
use app\models\User;

class UserController extends Controller
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();

        // This protects ALL actions in this controller
        // Every request must include: Authorization: Bearer <token>
        // Yii2 calls findIdentityByAccessToken() automatically
        $behaviors['authenticator'] = [
            'class' => HttpBearerAuth::class,
        ];

        return $behaviors;
    }

    /**
     * GET /user/profile
     * Returns the authenticated user's profile
     */
    public function actionProfile()
    {
        // Yii::$app->user->identity gives us the currently logged in user
        $user = Yii::$app->user->identity;

        return [
            'data' => [
                'id'         => $user->id,
                'username'   => $user->username,
                'email'      => $user->email,
                'created_at' => date('Y-m-d H:i:s', $user->created_at),
            ],
        ];
    }

    /**
     * PUT /user/update-profile
     * Updates the authenticated user's profile
    */
    public function actionUpdateProfile()
    {
        /** @var \app\models\User $user */
        $user = Yii::$app->user->identity;
        $body = Yii::$app->request->bodyParams;

        // Only update fields that were actually sent in the request
        if (isset($body['username'])) {
            $user->username = $body['username'];
        }
        if (isset($body['email'])) {
            $user->email = $body['email'];
        }
        if (isset($body['password'])) {
            $user->plain_password = $body['password'];
        }

        // validate() will check uniqueness of username/email
        // and minimum length of password if provided
        if (!$user->validate()) {
            Yii::$app->response->statusCode = 422;
            return [
                'message' => 'Validation failed',
                'errors'  => $user->errors,
            ];
        }

        if ($user->save(false)) {
            return [
                'message' => 'Profile updated successfully',
                'data' => [
                    'id'       => $user->id,
                    'username' => $user->username,
                    'email'    => $user->email,
                ],
            ];
        }

        Yii::$app->response->statusCode = 500;
        return [
            'message' => 'Could not update profile',
            'errors'  => $user->errors,
        ];
    }
    
}