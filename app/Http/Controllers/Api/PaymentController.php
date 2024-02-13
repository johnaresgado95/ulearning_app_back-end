<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Stripe\Webhook;
use Stripe\Customer;
use Stripe\Price;
use Stripe\Stripe;
use Stripe\Checkout\Session;
use Stripe\Exception\UnexpectedValueException;
use Stripe\Exception\SignatureVerificationException;
use App\Models\Course;
use App\Models\Order;
use Illuminate\Support\Carbon;

class PaymentController extends Controller
{
    //
    public function checkout(Request $request)
    {
        try {

            // This is to get the Course that user buy
            $courseId = $request->id;
            $user = $request->user();
            $token = $user->token;
            // get Stripe key
            Stripe::setApiKey(
                "sk_test_51OZabmJawjoXSEtvFhJ6aKu2AcHNIqTxJQrEAUm1XI4rnW9pAZ7T4ln2cAlxwk61MJ3vAxsEjpCQSUz2OugNldkP00y14s0Ov1"
            );

            $searchCourse = Course::where('id', '=', $courseId)->first();

            if (empty($searchCourse)) {
                return response()->json(
                    [
                        'code' => 204,
                        'msg' => 'No Course Found.',
                        'data' => ''
                    ],
                    200
                );
            }

            $orderMap = [];
            $orderMap['course_id'] = $courseId;
            $orderMap['user_token'] = $token;
            $orderMap['status'] = 1;
            $orderRes = Order::where($orderMap)->first();
            // Status 1 = Successful Order
            // It means we already have an order from the same user within the same course_id
            if (!empty($orderRes)) {
                return response()->json(
                    [
                        'code' => 409,
                        'msg' => 'Order already exist..',
                        'data' => ''
                    ],
                    200
                );
            }

            $my_domain = env('APP_URL');
            $map = [];
            $map['user_token'] = $token;
            $map['course_id'] = $courseId;
            $map['total_amount'] = $searchCourse->price;
            $map['status'] = 0;
            $map['created_at'] = Carbon::now();

            $orderNum = Order::insertGetId($map);

            $checOutSession = Session::create([
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'USD',
                        'product_data' => [
                            'name' => $searchCourse->name,
                            'description' => $searchCourse->description,
                        ],
                        'unit_amount' => intval(($searchCourse->price) * 100)
                    ],
                    'quantity' => 1,
                ]],
                'payment_intent_data' => [
                    'metadata' => ['order_num' => $orderNum, 'user_token' => $token],
                ],
                'metadata' => ['order_num' => $orderNum, 'user_token' => $token],
                'mode' => 'payment',
                'success_url' => $my_domain . 'success',
                'cancel_url' => $my_domain . 'cancel'
            ]);

            return response()->json(
                [
                    'code' => 200,
                    'msg' => 'Order has been placed..',
                    'data' => $checOutSession->url
                ],
                200
            );
        } catch (\Throwable $e) {
            return response()->json(
                [
                    'code' => 500,
                    'msg' => 'Internal Server error',
                    'data' => $e->getMessage()
                ],
                500
            );
        }
    }

    public function webGoHooks()
    {
        Log::info('starts here.....');
        Stripe::setApiKey('sk_test_51OZabmJawjoXSEtvFhJ6aKu2AcHNIqTxJQrEAUm1XI4rnW9pAZ7T4ln2cAlxwk61MJ3vAxsEjpCQSUz2OugNldkP00y14s0Ov1');
        $endPointSecret = 'whsec_iEGDDpFxGkSzGGZoo8ymZPWboTkml6gx';
        $payload = @file_get_contents('php://input');
        $sigHeader = $_SERVER['HTTP_STRIPE_SIGNATURE'];
        $event = null;
        Log::info('setup buffer and handshake done.....');
        try {
            $event = \Stripe\Webhook::constructEvent(
                $payload,
                $sigHeader,
                $endPointSecret
            );
        } catch (\UnexpectedValueException $e) {
            Log::info('UnexpectedValueException ' . $e);
            http_response_code(400);
            exit();
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            Log::info('SignatureVerificationException ' . $e);
            http_response_code(400);
            exit();
        }

        if ($event->type == 'charge.succeeded') {
            $session = $event->data->object;
            $metadata = $session['metadata'];
            $orderNum = $metadata->order_num;
            $userToken = $metadata->user_token;
            Log::info('order id'.$orderNum);
            
            $map =[];
            $map['status'] = 1;
            $map['updated_at'] =Carbon::now();

            $whereMap = [];
            $whereMap['user_token'] = $userToken;
            $whereMap['id'] = $orderNum;
            Order::where($whereMap)->update($map);
        }

        http_response_code(200);
    }
}
