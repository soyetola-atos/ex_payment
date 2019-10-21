#  exPaystackPaymentGateway (v1.0)
> This is a payment gateway wrapper for PayStack


## Class Wrapper configuration
> Goto [Paystack](https://paystack.com/) website to get there payment api both ```secret_key``` and ```public_key```
```php
    class exPaystackPaymentGateway extends Model1 implements Model1ActionInterface {
        /**
         * PayStack Config
         */
        public static $config = [
          'secret_key'=> '***',
          'public_key'=> '***',
          'currency'=>'NGN',
          'cookie_key'=>'last_payment_'.self::class,
        ];
```


## exPaystackPaymentGateway Database
This consist the list of successful payment.
When payment is made, An Ajax request is sent to 
```confirmPayment($payment_reference = null, $silent = true, $insertPaymentIfTrue = false, $amountExpecting = null)``` 
with parameters ```confirmPayment(null,1,1)``` to save data in the exPaystackPaymentGateway database as well by default.




<img src="paystack.gif" width="100%">



## Requirement
> exAppChat : (optional) for mail notification.
> and [SweetAlert](#)  for Notification




## Features
- [x] Payment Popup Dialog
- [x] Payment Lazy Load Form
- [ ] Payment form. 'Will be available in next version'


## Quick Use 
> Clone Plugin Repository and Add it to Plugins Folder of your project or shared plugins folder.
> This will allows you to use the plugin in your  Project.

### create model in config onDebug()
```php
    exPaystackPaymentGateway::tableCreate();
    
    // which will create column for model fields. Use to store tranx records
    public $id = 0;
    public $amount = 0;
    public $user_id = 0;
    public $reference = '';
    public $currency = '';
    public $as_paid = 0;
    public $log = null;
```


### Payment Button
To make payment, Button and id attribute is required
e.g 
```php
    <button  id="btn_pay_3k"> Pay 3,000 </button>
    <button  id="btn_pay_5k"> Pay 5,000 </button>

    <?php 
        exPaystackPaymentGateway::renderPopup([
            'btn_pay_3k'=>['amount'=>200000, 'param1'=>'...', 'param2'=>'...', ...],
            'btn_pay_5k'=>['amount'=>300000, 'param1'=>'...', 'param2'=>'...', ...],
        ])
    ?>
```
btn_pay_3k is the button id and can accept parameter that can be access later in the cookie in ```onPaymentCompleted callback```.
But amount attribute is important. Else you will get param error.

OR Simply with 
```php
 exPaystackPaymentGateway::renderPopup(['btn_pay_3k'=>300000, 'btn_pay_5k'=>500000], null)
```
No parameter, just button id = amount
e.g 
```php
        <button  id="btn_pay_3k"> Pay 3,000 </button>
        <button  id="btn_pay_5k"> Pay 5,000 </button>
        <?= exPaystackPaymentGateway::renderPopup(['btn_pay_3k'=>300000, 'btn_pay_5k'=>500000], null);  ?>
```


 Or Directly With Javascript
```php
    <?php exPaystackPaymentGateway::renderPopup(null); ?>
    
    <script>
        payWithPaystack(amount, 'button_id', JSON.stringify({param1:'value1', param2:'value2', ...}));
    </script>
```
Please, remember to include the ```exPaystackPaymentGateway::renderPopup(null);```




### or Paystack Quick Button
```php
        <?= exPaystackPaymentGateway::renderLazyPopup(30000, 'User::processPaymentRef()');  ?>
```



## Callback
This is a return link that tells you payment is successful, you are meant to still validate your payment 
reference in your callback method for security reason. 

### Declare callback Controller 
Create a callback controller method for return data. e.g ```processPaymentRef()``` under UserPayment class or any where else.
> for example, Return data will consist of
```php
    class User extend Model1{
        ...
        static function processPaymentRef(){
            dd(  $_REQUEST );
        }
```
> Output is
```json
    [
        "token"                      :"...",
        "reference"                  : "T146319269867541",
        "id"                         :"18",
    ]
```



### Or Access Payment information in ```onPaymentCompleted callback```
When payment is made with 
```php
    exPaystackPaymentGateway::renderPopup([
        'btn_pay_3k'=>['amount'=>200000, 'param1'=>'...', 'param2'=>'...', ...],
        'btn_pay_5k'=>['amount'=>300000, 'param1'=>'...', 'param2'=>'...', ...],
    ])
```

OR Simply with 
```php
 exPaystackPaymentGateway::renderPopup(['btn_pay_3k'=>300000, 'btn_pay_5k'=>500000], null)
```
 
and not ```exPaystackPaymentGateway::renderLazyPopup()```, Every successful payment info is saved to cookie and can be access in ```exPaystackPaymentGateway::onPaymentCompleted()```
e.g This information is accessed in config ```onPageStart()```
```php
    // insert in config file
    static function onPageStart() {
    
            // access last payment information
            exFlutterwaveRavePaymentGateway::onPaymentCompleted(function ($button_id, $payment_reference, $payment_info){
                switch ($button_id){
                    case 'btn_pay_3k':
                        // do something with $payment_reference
    
                    case 'btn_pay_5k':
                        // do something with $payment_reference
                }
    
                return true;
            });
            
            ...
```
Note. ensure to return true in your callback function, this will help to delete the last payment cookie from re-appearing again,
Or Delete Manually with ```Cookie1::delete(exPaystackPaymentGateway::$config['cookie_key'])```;






## Param
```php 
    // Parameter
    public static function renderPopup($btnIdEqualsAmount = ['btn_pay_3k'=>300000], $redirectToController = null)
    
    
    // and 
    public static function renderLazyPopup($amount, $redirectToController = null){ 
    
    
    // then in provided  controller ($redirectToController), you can have access to reference and exPaystackPaymentGateway data id.
    
```




## Author
:kissing: from the creator of Easytax. Samson Iyanu (@samtax01)



## Verbose result from ```<?php dd($userInfo->isPremiumUser()) ?>```

```php
 array(3) 
    "status"  => bool(true)
    "message" => string(23) "Verification successful"
    "data"    =>  array(24) 
        [
            "id"               => int(116147674)
            "domain"           => string(4) "test"
            "status"           => string(7) "success"
            "reference"        => string(16) "T10038****88916"
            "amount"           => int(1200000)
            "message"          => NULL
            "gateway_response" => string(10) "Successful"
            "paid_at"          => string(24) "2019-02-19T23:40:49.000Z"
            "created_at"       => string(24) "2019-02-19T23:40:40.000Z"
            "channel"          => string(4) "card"
            "currency"         => string(3) "NGN"
            "ip_address"       => string(12) "41.**.*.***"
            "metadata"         =>  array(2) 
                [
                    "custom_fields" =>  array(3) 
                        [
                            0 =>  array(3) 
                                [
                                    "display_name"  => string(9) "User Name"
                                    "variable_name" => string(9) "user_name"
                                    "value"         => string(3) "sss"
                                ]
                            1 =>  array(3) 
                                [
                                    "display_name"  => string(9) "Full Name"
                                    "variable_name" => string(9) "full_name"
                                    "value"         => string(23) "Oyetola Samson Iyanu"
                                ]
                            2 =>  array(3) 
                                [
                                    "display_name"  => string(7) "User Id"
                                    "variable_name" => string(7) "user_id"
                                    "value"         => string(1) "1"
                                ]
                        ]
                    "referrer"      => string(34) "http://****/profile/upgrade"
                ]
            "log"              =>  array(8) 
                [
                    "start_time" => int(1550619641)
                    "time_spent" => int(9)
                    "attempts"   => int(1)
                    "errors"     => int(0)
                    "success"    => bool(true)
                    "mobile"     => bool(false)
                    "input"      => array(0)
                    "history"    =>  array(2) 
                        [
                            0 =>  array(3) 
                                [
                                    "type"    => string(6) "action"
                                    "message" => string(26) "Attempted to pay with card"
                                    "time"    => int(7)
                                ]
                            1 =>  array(3) 
                                [
                                    "type"    => string(7) "success"
                                    "message" => string(27) "Successfully paid with card"
                                    "time"    => int(9)
                                ]
                        ]
                ]
            "fees"             => int(28000)
            "fees_split"       => NULL
            "authorization"    =>  array(12) 
                [
                    "authorization_code" => string(15) "AUTH_kwzeoz8008"
                    "bin"                => string(6) "408408"
                    "last4"              => string(4) "4081"
                    "exp_month"          => string(2) "12"
                    "exp_year"           => string(4) "2020"
                    "channel"            => string(4) "card"
                    "card_type"          => string(10) "visa DEBIT"
                    "bank"               => string(9) "Test Bank"
                    "country_code"       => string(2) "NG"
                    "brand"              => string(4) "visa"
                    "reusable"           => bool(true)
                    "signature"          => string(24) "SIG_9GQgf4eoeM9oTdReMDuR"
                ]
            "customer"         =>  array(8) 
                [
                    "id"            => int(6780377)
                    "first_name"    => string(0) ""
                    "last_name"     => string(0) ""
                    "email"         => string(15) "sss@hotmail.com"
                    "customer_code" => string(19) "CUS_55inhnhyd2hqni7"
                    "phone"         => string(0) ""
                    "metadata"      => NULL
                    "risk_action"   => string(7) "default"
                ]
            "plan"             => NULL
            "paidAt"           => string(24) "2019-02-19T23:40:49.000Z"
            "createdAt"        => string(24) "2019-02-19T23:40:40.000Z"
            "transaction_date" => string(24) "2019-02-19T23:40:40.000Z"
            "plan_object"      => array(0)
            "subaccount"       => array(0)
        ]
]
```