<?php

namespace app\controllers;

use Yii;
use yii\rest\Controller;
use yii\web\Response;
use app\models\User;

class AuthController extends Controller
{

    /**
     * POST /auth/login
     * Receives username + password, returns token if valid
     */
    public function actionLogin()
    {
        $request = Yii::$app->request;

        // Get username and password from request body
        $body     = Yii::$app->request->bodyParams;
        $username = $body['username'] ?? null;
        $password = $body['password'] ?? null;

        // Basic input presence check
        if (!$username || !$password) {
            Yii::$app->response->statusCode = 422;
            return [
                'success' => false,
                'message' => 'Username and password are required',
            ];
        }

        // Find user by username
        $user = User::findByUsername($username);

        // Validate user exists and password is correct
        if (!$user || !$user->validatePassword($password)) {
            Yii::$app->response->statusCode = 401;
            return [
                'success' => false,
                'message' => 'Invalid username or password',
            ];
        }

        // Return the token and user info
        return [
            'success' => true,
            'message' => 'Login successful',
            'data' => [
                'token'    => $user->auth_key,
                'id'       => $user->id,
                'username' => $user->username,
                'email'    => $user->email,
            ],
        ];
    }

    /**
     * POST /auth/register
     * Creates a new user account
     */
    public function actionRegister()
    {
        $user = new User();

        // Load data from request body into model
        $body = Yii::$app->request->bodyParams;
        $user->username       = $body['username'] ?? null;
        $user->email          = $body['email'] ?? null;
        $user->plain_password = $body['password'] ?? null;

        // validate() checks all rules() in User model
        if (!$user->validate()) {
            Yii::$app->response->statusCode = 422;
            return [
                'message' => 'Validation failed',
                'errors'  => $user->errors, // returns field-by-field errors
            ];
        }

        // save() triggers beforeSave() which hashes password + generates token
        if ($user->save()) {
            Yii::$app->response->statusCode = 201;
            return [
                'message' => 'Account created successfully',
                'data' => [
                    'token'    => $user->auth_key,
                    'id'       => $user->id,
                    'username' => $user->username,
                    'email'    => $user->email,
                ],
            ];
        }

        // If save() failed for unexpected reason
        Yii::$app->response->statusCode = 500;
        return [
            'message' => 'Could not create account',
            'errors'  => $user->errors,
        ];
    }
}