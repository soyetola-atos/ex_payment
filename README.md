#  exPayment Wrapper Guild
> This is a guild for common payment.

## Click to read now
- [x] [Read Paystack](README-PayStack.md)
- [x] [Flutterwave Rave](README-FlutterWave.md)

<br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/>
# SAMPLE USAGE
## Quick Payment Button
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



### Access Payment information in ```config onPaymentCompleted callback```
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
            })
            
            ...
```
Note. ensure to return true in your callback function, this will help to delete the last payment cookie from re-appearing again,
Or Delete Manually with ```Cookie1::delete(exFlutterwaveRavePaymentGateway::$config['cookie_key'])```;





## Sample payment confirm in config
```php
    ...
     static function onPageStart() {
            // Flutterwave Rave PaymentGateway onPaymentCompleted
            exFlutterwaveRavePaymentGateway::onPaymentCompleted(function ($button_id, $payment_reference, $payment_info){
                $myPaymentInfo = exFlutterwaveRavePaymentGateway::getPaymentInfo($payment_reference);
                $clearLastPayment = false;
                if(isset($myPaymentInfo['status']) && $myPaymentInfo['status'] == 'error'){
                    $clearLastPayment = true;
                    Session1::setStatus("Payment Error", 'Payment verification failed, please contact admin if this is an error', 'error');
                }else{
                    if(isset($myPaymentInfo['data']) && isset($myPaymentInfo['data']['chargecode'])){
                        if( ($myPaymentInfo['data']['chargecode'] == '0')  || ($myPaymentInfo['data']['chargecode'] == '00') ){
                            if($myPaymentInfo['data']['amount'] == $payment_info['param']['amount']){
    
    
    
                                //dd("Payment Confirmed", $payment_info);
                                User::getLogin()->update(['payment' => 'paid']) 
                                $clearLastPayment = true;
                                Cookie1::delete(exFlutterwaveRavePaymentGateway::$config['cookie_key']);
                                
    
    
    
                            }else{
                                Session1::setStatus("Payment Error", 'Payment amount not tally', 'error');
                                $clearLastPayment = true;
                            }
                        }else{
                            Session1::setStatus("Payment Error", 'Payment verification failed, please contact admin if this is an error', 'error');
                            $clearLastPayment = true;
                        }
                    }
                }
    
    
                // clear last payment is payment error
                if($clearLastPayment){
                    Cookie1::delete(exFlutterwaveRavePaymentGateway::$config['cookie_key']);
                    return true;
                }
            });
            ....
```


## Author
:kissing: from the creator of Easytax. Samson Iyanu (@samtax01)

