<?php

namespace app\controllers;

use Yii;
use yii\rest\Controller;
use yii\web\Response;
use yii\filters\auth\HttpBearerAuth;
use app\models\Order;
use app\models\OrderItem;
use yii\web\UnprocessableEntityHttpException;
use yii\web\NotFoundHttpException;


class OrderController extends Controller
{

    public function behaviors()
    {
        $behaviors = parent::behaviors();

        $behaviors['authenticator'] = [
            'class' => HttpBearerAuth::class,
        ];

        return $behaviors;
    }

    /**
     * POST /order/create
     * Creates a new order with its items
     */
    public function actionCreate()
    {
        $body = Yii::$app->request->bodyParams;

        // Get the authenticated user's ID from the token
        $userId = Yii::$app->user->id;

        // Validate that items array exists and is not empty
        if (empty($body['items']) || !is_array($body['items'])) {
            Yii::$app->response->statusCode = 422;
            return [
                'message' => 'Order must contain at least one item',
            ];
        }

        // Start a DB transaction, If anything fails, everything rolls back
        $transaction = Yii::$app->db->beginTransaction();

        try {
            // Create the order
            $order          = new Order();
            $order->user_id = $userId;
            $order->notes   = $body['notes'] ?? null;

            if (!$order->save()) {
                $transaction->rollBack();
                Yii::$app->response->statusCode = 422;
                return [
                    'message' => 'Could not create order',
                    'errors'  => $order->errors,
                ];
            }

            // Create each order item and calculate total
            $totalPrice = 0;
            $savedItems = [];

            foreach ($body['items'] as $itemData) {
                $item               = new OrderItem();
                $item->order_id     = $order->id;
                $item->product_name = $itemData['product_name'] ?? null;
                $item->quantity     = $itemData['quantity'] ?? 1;
                $item->unit_price   = $itemData['unit_price'] ?? null;

                if (!$item->save()) {
                    $transaction->rollBack();
                    Yii::$app->response->statusCode = 422;
                    return [
                        'message' => 'Could not save order item',
                        'errors'  => $item->errors,
                    ];
                }

                // Accumulate total price
                $totalPrice += $item->unit_price * $item->quantity;
                $savedItems[] = [
                    'id'           => $item->id,
                    'product_name' => $item->product_name,
                    'quantity'     => $item->quantity,
                    'unit_price'   => $item->unit_price
                ];
            }

            // Update total price on the order
            // save(false) = skip validation, just run the UPDATE
            $order->total_price = $totalPrice;
            $order->save(false);

            // All good — commit the transaction
            $transaction->commit();

            Yii::$app->response->statusCode = 201;
            return [
                'message' => 'Order created successfully',
                'data'    => [
                    'id'          => $order->id,
                    'status'      => $order->status,
                    'total_price' => $order->total_price,
                    'notes'       => $order->notes,
                    'items'       => $savedItems,
                    'created_at'  => date('Y-m-d H:i:s', $order->created_at),
                ],
            ];

        } catch (\Exception $e) {
            $transaction->rollBack();
            Yii::$app->response->statusCode = 500;
            return [
                'message' => 'Unexpected error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * GET /order/index
     * Returns paginated list of orders for the authenticated user
     */
    public function actionIndex()
    {
        $userId   = Yii::$app->user->id;
        $page     = (int) Yii::$app->request->get('page', 1);
        $pageSize = (int) Yii::$app->request->get('per_page', 10);

        // ActiveDataProvider = Yii2's built-in paginator
        $provider = new \yii\data\ActiveDataProvider([
            'query' => Order::find()
                ->where(['user_id' => $userId])
                ->orderBy(['created_at' => SORT_DESC])
                ->with('orderItems'), 
            'pagination' => [
                'pageSize' => $pageSize,
                'page'     => $page - 1, // Yii2 pagination is 0-indexed
            ],
        ]);

        $orders = [];
        foreach ($provider->getModels() as $order) {
            $orders[] = $this->formatOrder($order);
        }

        return [
            'data'    => $orders,
            'meta'    => [
                'total_count'       => $provider->getTotalCount(),
                'current_page'        => $page,
                'per_page'    => $pageSize,
                'total_pages' => ceil($provider->getTotalCount() / $pageSize),
            ],
        ];
    }

    /**
     * GET /order/view?id=1
     */
    public function actionView($id)
    {
        $userId = Yii::$app->user->id;

        // Find order AND make sure it belongs to this user
        $order = Order::find()
            ->where(['id' => $id, 'user_id' => $userId])
            ->one();

        if (!$order) {
            throw new \yii\web\NotFoundHttpException('Order not found');
        }
        return [
            'data' => $this->formatOrder($order)            
        ];
    }

    /**
     * GET /order/details?id=1
     */
    public function actionDetails($id)
    {
        $userId = Yii::$app->user->id;

        $order = Order::find()
            ->where(['id' => $id, 'user_id' => $userId])
            ->with('orderItems') 
            ->one();

        if (!$order) {
            throw new \yii\web\NotFoundHttpException('Order not found');
        }

        return [
            'data' => array_merge(
                $this->formatOrder($order),
                ['items' => $this->formatItems($order)]
            )
        ];
    }

    /**
     * PATCH /order/update-status?id=1
     * Updates order status following forward-only flow
     */
    public function actionUpdateStatus($id)
    {
        $body   = Yii::$app->request->bodyParams;
        $userId = Yii::$app->user->id;

        $order = Order::find()
            ->where(['id' => $id, 'user_id' => $userId])
            ->one();

        if (!$order) {
            throw new NotFoundHttpException('Not found');
        }

        $newStatus = $body['status'] ?? null;

        if (!$newStatus) {
            Yii::$app->response->statusCode = 422;
            return [
                'message' => 'Status is required',
            ];
        }

        // canTransitionTo() checks the STATUS_FLOW map
        if (!$order->canTransitionTo($newStatus)) {
            throw new \yii\web\UnprocessableEntityHttpException('Invalid status transition');
        }

        $order->status = $newStatus;
        $order->save(false);

        return [
            'message' => 'Order status updated successfully'            
        ];
    }

    /**
     * DELETE /order/delete?id=1
     * Deletes an order — only allowed if status is pending
     */
    public function actionDelete($id)
    {
        $userId = Yii::$app->user->id;

        // Find the order and make sure it belongs to this user
        $order = Order::find()
            ->where(['id' => $id, 'user_id' => $userId])
            ->one();

        if (!$order) {
            Yii::$app->response->statusCode = 404;
            return [
                'message' => 'Order not found',
            ];
        }

        // Only pending orders can be deleted
        if ($order->status !== Order::STATUS_PENDING) {
            Yii::$app->response->statusCode = 422;
            return [
                'message' => 'Only pending orders can be deleted. This order is "' . $order->status . '"',
            ];
        }

        // delete() removes the order and cascades to order_items automatically
        $order->delete();

        return [
            'message' => 'Order deleted successfully',
        ];
    }

    /**
     * Helper method to format an order for API response
     */
    private function formatOrder($order)
    {

        return [
            'id'          => $order->id,
            'status'      => $order->status,
            'total_price' => (float)$order->total_price,
            'notes'       => $order->notes,
            'created_at'  => date('Y-m-d H:i:s', $order->created_at),
            'updated_at'  => date('Y-m-d H:i:s', $order->updated_at),
        ];
    }

    private function formatItems($order)
    {
        $items = [];
        foreach ($order->orderItems as $item) {
            $items[] = [
                'id' => $item->id,
                'product_name' => $item->product_name,
                'quantity' => $item->quantity,
                'unit_price' => (float)$item->unit_price,
            ];
        }
        return $items;
    }
}
