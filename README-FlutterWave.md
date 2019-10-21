#  exFlutterwaveRavePaymentGateway (v1.0)
> This is a payment gateway wrapper for Flutterwave



## Class Wrapper configuration
> Goto [Flutterwave Rave](https://rave.flutterwave.com) website to get there payment api both ```secret_key``` and ```public_key```
```php
    class exFlutterwaveRavePaymentGateway extends Model1 implements Model1ActionInterface {
        /**
         * Flutterwave Config
         */
        public static $config = [
          'secret_key'=> '***',
          'public_key'=> '***',
          'currency'=>'NGN',
          'cookie_key'=>'last_payment_'.self::class,
        ];
```



## exFlutterwaveRavePaymentGateway Database
This consist the list of successful payment.
When payment is made, An Ajax request is sent to 
```confirmPayment($payment_reference = null, $silent = true, $insertPaymentIfTrue = false, $amountExpecting = null)``` 
with parameters ```confirmPayment(null,1,1)``` to save data in the exFlutterwaveRavePaymentGateway database as well by default.


<img src="flutterwave.gif" width="100%">



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
    exFlutterwaveRavePaymentGateway::tableCreate();
    
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
        exFlutterwaveRavePaymentGateway::renderPopup([
            'btn_pay_3k'=>['amount'=>2000, 'param1'=>'...', 'param2'=>'...', ...],
            'btn_pay_5k'=>['amount'=>3000, 'param1'=>'...', 'param2'=>'...', ...],
        ])
    ?>
```
btn_pay_3k is the button id and can accept parameter that can be access later in the cookie in ```onPaymentCompleted callback```.
But amount attribute is important. Else you will get param error.

OR Simply with 
```php
     exFlutterwaveRavePaymentGateway::renderPopup(['btn_pay_3k'=>3000, 'btn_pay_5k'=>5000], null)
```
No parameter, just button id = amount
e.g 
```php
        <button  id="btn_pay_3k"> Pay 3,000 </button>
        <button  id="btn_pay_5k"> Pay 5,000 </button>
        <?= exFlutterwaveRavePaymentGateway::renderPopup(['btn_pay_3k'=>300000, 'btn_pay_5k'=>500000], null);  ?>
```

 Or Directly With Javascript
```php
    <?php exFlutterwaveRavePaymentGateway::renderPopup(null); ?>
    
    <script>
        payWithFlutterwaveRave(amount, 'button_id', JSON.stringify({param1:'value1', param2:'value2', ...}));
    </script>
```
Please, remember to include the ```exFlutterwaveRavePaymentGateway::renderPopup(null);```




### or Flutterwave Quick Button
```php
        <?= exFlutterwaveRavePaymentGateway::renderLazyPopup(30000, 'User::processPaymentRef()');  ?>
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
    exFlutterwaveRavePaymentGateway::renderPopup([
        'btn_pay_3k'=>['amount'=>200000, 'param1'=>'...', 'param2'=>'...', ...],
        'btn_pay_5k'=>['amount'=>300000, 'param1'=>'...', 'param2'=>'...', ...],
    ])
```

OR Simply with 
```php
    exFlutterwaveRavePaymentGateway::renderPopup(['btn_pay_3k'=>300000, 'btn_pay_5k'=>500000], null)
```


and not ```exFlutterwaveRavePaymentGateway::renderLazyPopup()```, Every successful payment info is saved to cookie and can be access in ```exFlutterwaveRavePaymentGateway::onPaymentCompleted()```
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
                                                                                                                               Or Delete Manually with ```Cookie1::delete(exFlutterwaveRavePaymentGateway::$config['cookie_key'])```;






## Param
```php 
    // Parameter
    public static function renderPopup($btnIdEqualsAmount = ['btn_pay_3k'=>300000], $redirectToController = null)
    
    
    // and 
    public static function renderLazyPopup($amount, $redirectToController = null){ 
    
    
    // then in provided  controller ($redirectToController), you can have access to reference and exFlutterwaveRavePaymentGateway data id.
    
```




## Author
:kissing: from the creator of Easytax. Samson Iyanu (@samtax01)