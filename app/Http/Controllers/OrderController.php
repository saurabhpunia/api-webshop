<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderProduct;
use Illuminate\Support\Facades\Log;
use App\Traits\PaymentProvider;

class OrderController extends Controller
{
    // use payment provider in class
    use PaymentProvider;

    // use to select default column in whole controller
    protected $arrFields = ['customer', 'payed'];

    // use to get the order data
    public function index(Request $objRequest){

        // doing pagination calculation
        $intPage    =   number_format($objRequest->page_no??1);
        $intLimit   =   number_format($objRequest->rows_per_page??10);
        $intOffset  =   ($intPage-1) * $intLimit;

        $objOrder   =   Order::select($this->arrFields)->with('customerDetail',function($query){
            return $query->select('id','job_title','email','first_name','last_name');
        });;

        $arrResult['total_records']     =   $objOrder->count();
        $arrResult['data']              =   $objOrder->take($intLimit)->skip($intOffset)->get();

        if(count($arrResult['data']) == 0){
            return response()->json(['message'=> 'No data found.'], 204);
        }

        return response()->json($arrResult);
    }

    // validating the request form
    public static function indexValidator(Request $objRequest){
        return [
            'rows_per_page' => ['numeric', 'max:99', 'min:1'],
            'page_no' => ['numeric', 'min:1'],
        ];
    }

    // use to create the order
    public function create(Request $objRequest){

        try{
            $intOrderId = Order::insertGetId([
                'customer' => $objRequest->customer,
            ]);
            $arrResult = [
                'message' => 'Order created successfully.',
            ];
            return response()->json($arrResult);
        }
        catch(\Exception $e){
            $this->logErrors('Error while creating order.',$e->getMessage());

            if(config('app.debug') !== false)
                return response()->json(['error' => 'Error while creating order.', 'description'=>$e->getMessage()],500);

            return response()->json(['error' => 'Server Error.'],500);
        }

    }
        
    // validating creating request
    public static function createValidator(Request $objRequest){
        return [
            'customer' => ['required', 'integer'],
        ];
    }

    // use to update the exist order if found
    public function update(Request $objRequest,$id){

        try{
            $objOrder = Order::find($id);

            if($objOrder){
                $objOrder->customer = $objRequest->customer??$objOrder->customer;
                $objOrder->save();
                $arrResponse = [
                    'message' => 'Order updated successfully.'
                ];
                return response()->json($arrResponse);
            }

            return response()->json(['error' => 'Order Id not found.'],422);
        }
        catch(\Exception $e){
            $this->logErrors('Error while updating order.',$e->getMessage());

            if(config('app.debug') !== false)
                return response()->json(['error' => 'Error while updating order.', 'description'=>$e->getMessage()],500);

            return response()->json(['error' => 'Server Error.'],500);
        }

    }
        
    // validate the update request form
    public static function updateValidator(Request $objRequest){
        return [
            'customer' => ['required', 'integer'],
        ];
    }

    // use to delete the order if found
    public function delete($id){

        try{

            $objOrder = Order::find($id);

            if($objOrder){
                $objOrder->delete();
                $arrResponse = [
                    'message' => 'Order deleted successfully.'
                ];
                return response()->json($arrResponse);
            }

            return response()->json(['error' => 'Order Id not found.'],422);
        }
        catch(\Exception $e){
            $this->logErrors('Error while deleting order.',$e->getMessage());

            if(config('app.debug') !== false)
                return response()->json(['error' => 'Error while deleting order.', 'description'=>$e->getMessage()],500);

            return response()->json(['error' => 'Server Error.'],500);
        }
    }

    // add product to order
    public function addProduct(Request $objRequest, $id){

        try{
            $objOrder = Order::whereId($id)->whereNull('payed')->first();

            if($objOrder){
                $objProduct = OrderProduct::where(['order_id' => $objOrder->id, 'product_id' => $objRequest->product_id])->first();

                // check if product already added with the given order id
                if($objProduct){
                    return response()->json(['error' => 'Product already added with this order.'], 422);
                }

                OrderProduct::insert([
                    'order_id' => $objOrder->id,
                    'product_id' => $objRequest->product_id,
                ]);

                $arrResponse = [
                    'message' => 'Product added to order id: '.$objOrder->id,
                ];
                return response()->json($arrResponse);
            }

            // return if order id is invalid
            return response()->json(['error' => 'Invalid Order Id.'], 422);
        }
        catch(\Exception $e){
            $this->logErrors('Error while adding product to order.',$e->getMessage());

            if(config('app.debug') !== false)
                return response()->json(['error' => 'Error while adding product to order.', 'description'=>$e->getMessage()],500);

            return response()->json(['error' => 'Server Error.'],500);
        }
    }

    // validate the add product request form
    public static function addProductValidator(Request $objRequest){
        return [
            'product_id' => ['required', 'integer', 'min:1'],
        ];
    }

    // use to make payment of order if product is added with given order id
    public function payOrder(Request $objRequest, $id){

        try{
            $arrOrderDetails    =  Order::whereId($id)
                                    ->with('orderProductMapping',function($query){
                                        return $query->select('order_id','product_id')->with('productDetail',function($query){
                                            return $query->select('id','price');
                                        });
                                    })
                                    ->with('customerDetail',function($query){
                                        return $query->select('id','email');
                                    })->get()->toArray();

            // check if order id is invalid
            if(count($arrOrderDetails) == 0){
                return response()->json(['error' => 'Invalid Order Id.'], 422);
            }

            $arrOrderDetails = $arrOrderDetails[0];
            // check if order is already payed
            if($arrOrderDetails['payed'] !== null){
                return response()->json(['error' => 'Order already payed.'], 422);
            }

            // calculate the sum of all the product price
            $intTotalPrice = round(array_sum(array_column(array_column($arrOrderDetails['order_product_mapping'], 'product_detail'), 'price')), 2);
            
            // make a request body for payment service provider
            $arrRequestData     =   [
                'order_id'      =>  $arrOrderDetails['id'],
                'customer_email' => $arrOrderDetails['customer_detail']['email'],
                'value'         =>  $intTotalPrice,
            ];

            // make a post request to payment service provider
            $blnRequestDone     =   $this->paymentRequest($arrRequestData);

            // check if error while making request to payment provider and logging
            if($blnRequestDone !== true){
                $this->logErrors('Error in payment request.', $blnRequestDone, 'payment_log');
                return response()->json(['error' => 'Error in making payment.'], 500);
            }

            // getting response from payment service provider
            $arrResponse    =   json_decode($this->objPaymentResponse->getBody(), true);
            $strResponseMsg =   $arrResponse['message'];
            
            // update the order if payment is successful
            if($strResponseMsg === 'Payment Successful'){
                Order::whereId($id)->update([
                    'payed' => $intTotalPrice
                ]);
            }
        }
        catch(\Exception $e){
            // logging error
            $this->logErrors('Error while requesting payment.',$e->getMessage());

            if(config('app.debug') !== false)
                return response()->json(['error' => 'Error while requesting payment.', 'description'=>$e->getMessage()], 500);
            
            return response()->json(['error' => 'Server Error.'], 500);
        }

        return response()->json(['message' => $strResponseMsg]);
    }

    /**
     * use to store the error logs
     *
     * @var string strMessage - message data
     * @var string strDescription - description of log
     * @var string strChannel - log channel
     * return null
     */
    protected function logErrors($strMessage, $strDescription, $strChannel = 'order_log'){
        Log::channel($strChannel)->error(['message' => $strMessage, 'description' => $strDescription]);
    }

}
