<?php
/**
 * Created by PhpStorm.
 * User: samtax
 * Date: 14/02/2019
 * Time: 4:05 PM
 */

class exFlutterwaveRavePaymentGateway extends Model1 implements Model1ActionInterface {
    /**
     * PayStack Config
     */
    public static $config = [
        // demo
//        'secret_key'=> 'FLWSECK_TEST-253bc0f6130702b1f511f3f7ddbf945f-X',
//        'public_key'=> 'FLWPUBK_TEST-af1e1aa9ea228f9bd38bebd1d511f9fb-X',

        // live key
      'secret_key'=> 'FLWSECK-fe7a32f0527953904c86a5fb739758d4-X',
      'public_key'=> 'FLWPUBK-2d2ecc3e10bf0b10e601c966d0650374-X',

      'currency'=>'NGN',
      'country'=>'NG',
      'cookie_key'=>'last_payment_'.self::class,
    ];





    /**
     * Model Setup
     */
    public $id = 0;
    public $amount = 0;
    public $user_id = 0;
    public $reference = '';
    public $currency = '';
    public $as_paid = 0;
    public $log = null;




    /**
     * Run method in onPageStart()
     * Note: Cookie Required.
     * this is an action callback for payment made. it pass payment information to onPaymentCompleted(function($button_id, $payment_reference, $payment_info){ ... })
     * @param callable|null $callback($button_id, $payment_reference, $payment_info)
     */
    public static function onPaymentCompleted(callable $callback = null){
        if(isset($_COOKIE[self::$config['cookie_key']])){
            $payment_info = json_decode($_COOKIE[self::$config['cookie_key']], true);
            if($payment_info['status']) {
                if($callback($payment_info['button_id'], $payment_info['reference'], $payment_info)){
                    Cookie1::delete(self::$config['cookie_key']);
                }
            }
        }
    }










    /**
     * Get information of payment
     * @param null $payment_reference
     * @return mixed
     */
    public static function getPaymentInfo($payment_reference = null){
        $payment_reference = String1::isset_or($_POST['reference'], $payment_reference);
        if(empty($payment_reference)) return 'Empty Parameter';
        //open connection
        $query = array(
            "SECKEY" => self::$config['secret_key'],
            "txref" => $payment_reference
        );
        $data_string = json_encode($query);
        $ch = curl_init('https://ravesandboxapi.flutterwave.com/flwv3-pug/getpaidx/api/v2/verify');
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        // exec
        $response = curl_exec($ch);
        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $header = substr($response, 0, $header_size);
        $body = substr($response, $header_size);
        curl_close($ch);
        //declare an array that will contain the result
        return $response? json_decode($response, true): [];
    }





    /**
     * Confirm Payment with payment_reference
     * @param $payment_reference
     * @param bool $silent ( no popup)
     * @param bool $insertPaymentIfTrue ( if $insertPaymentIfTrue then add data to database)
     * @param null $amountExpecting
     * @return bool|integer
     */
    public static function confirmPayment($payment_reference = null, $silent = true, $insertPaymentIfTrue = false, $amountExpecting = null){
        $payment_reference = String1::isset_or($_POST['reference'], $payment_reference);
        $result = static::getPaymentInfo($payment_reference);
        // if sub array data and if data contains an element status and if status equals success.
        $isTrue = (array_key_exists('data', $result) && array_key_exists('status', $result['data']) && ($result['status'] === 'success'));
        $chargeCode = $result['data']['chargecode'];
        $chargeAmount = $result['data']['amount'];
        $chargeCurrency = $result['data']['currency'];
        $isAmountExpectingTrue = $amountExpecting? ($chargeAmount == $amountExpecting): true;
        $isTrue = $isTrue? (($chargeCode == "00" || $chargeCode == "0") && $isAmountExpectingTrue && ($chargeCurrency == self::$config['currency'])): false;

        $id = true;
        if(!$silent) Session1::setStatus($isTrue? 'Payment Confirmed': 'Failed', $isTrue? 'Payment Confirmed Successfully': 'Payment Confirmed Failed', $isTrue? 'success': 'error');
        if($isTrue && $insertPaymentIfTrue) $id = static::insert([
                        'reference'=>$payment_reference,
                        'amount'=>$result['data']['amount'],
                        'currency'=>$chargeCurrency,
                        'user_id'=>Auth1::id(),
                        'as_paid'=>1,
                        'log'=>Array1::toJSON($result)])->id;
        if($isTrue && isset_or($_REQUEST['redirect_to'])) return Url1::redirect(url('form/'.$_REQUEST['redirect_to']).'?token='.token().'&reference='.$payment_reference.'&id='.$id);
        else Session1::setStatusIf(!$silent? ['Failed to confirm payment']: null);
        return $isTrue? $id: false;
    }

















    /**
     *
     * A quick way to use
     *  <button type="button" id="btn_pay_30k"> Pay 30,000 </button>
     *  <button type="button" id="btn_pay_50k"> Pay 50,000 </button>
     *
     *  <?= exPaystackPaymentGateway::renderPopup(['btn_pay_30k'=>3000000, 'btn_pay_50k'=>5000000]);  ?>
     *
     * @param array $btnIdEqualsAmount
     * @param null $redirectToController
     * @return string
     */
    public static function renderPopup($btnIdEqualsAmount = ['btn_pay_3k'=>300000], $redirectToController = null){?>
        <script src="https://code.jquery.com/jquery-3.3.1.js" integrity="sha256-2Kok7MbOyxpgUVvAk/HJ2jigOSYS2auK4Pfzbm7uH60="crossorigin="anonymous"></script>
        <script src="https://api.ravepay.co/flwv3-pug/getpaidx/api/flwpbf-inline.js"></script>
        <script>
            function payWithFlutterwaveRave(amount = 0, button_id, param) {
                let isPaymentMade = false;
                if(!window.getpaidSetup) return Popup1.alert("Internet error!", "Page not loaded properly");
                getpaidSetup({
                    // confirmed field from flutter
                    customer_email: "<?= Auth1::get('email') ?>",
                    customer_phone: "<?= Auth1::get('phone_number') ?>",
                    customer_firstname: "<?= Auth1::get('full_name') ?>",
                    amount: amount,
                    // payment_method: "both",
                    txref: 'FWEXPG' + Math.random(),
                    currency: "<?= static::$config['currency'] ?>",
                    country: "<?= static::$config['country'] ?>",
                    custom_logo: "<?= asset("favicon_black.png") ?>",
                    custom_title: "<?= Config1::APP_TITLE  ?> Payment",
                    custom_description:"<?= Config1::APP_DESCRIPTION  ?>",
                    PBFPubKey: "<?= static::$config['public_key'] ?>",
                    redirect_url: "<?= $redirectToController ?>",
                    // extra data
                    meta: [
                        {display_name: "User Name", variable_name: "user_name", value: "<?= Auth1::get('user_name') ?>" },
                        {display_name: "Full Name",  variable_name: "full_name",  value: "<?= Auth1::get('full_name') ?>" },
                        {display_name: "User Id",  variable_name: "user_id",  value: "<?= Auth1::get('id') ?>" },
                    ],

                    callback: function(response) {
                        //after the transaction have been completed
                        //make post call  to the server with to verify payment
                        //using transaction reference as post data
                        if(!response || !response.tx || !(response.tx.chargeResponseCode == "00" || response.tx.chargeResponseCode == "0")) return Popup1.alert('Failed', 'Transaction failed: ' + response['data']['message'] + '.<br/><small>' + JSON.stringify(response) + '</small>' , 'error');
                        // set cookie in-case the transaction failed
                        isPaymentMade = true;
                        Cookie1.set("<?= self::$config['cookie_key'] ?>", {
                            status:true,
                            reference:response.tx.txRef,
                            button_id:button_id,
                            param:param? JSON.parse(param): null,
                            redirect_url:`<?= $redirectToController ?>`,
                        });
                        // send confirmation and save to db
                        Popup1.showLoading('Please Wait', 'Confirming Payment...');
                        Ajax1.request("<?= url('/api/').static::class ?>@confirmPayment(null,1,1)?token=<?=token()?>", {reference:response.tx.txRef},
                            function(status) {
                                if(status) {
                                    Popup1.alert('Successful', 'Transaction was successful. Please refresh page now', 'success');
                                    <?php
                                        if($redirectToController){?>  Url1.redirect("<?= url('form/'.$redirectToController).'?token='.token() ?>&reference=" + response.tx.txRef + '&id=' + status); <?php }
                                        else?> Url1.redirect( "<?= Url1::backUrl() ?>" )
                                }
                                else  Popup1.alert('Failed', 'Please refresh page. Transaction failed.<br/><small>' + JSON.stringify(response) + '</small>' , 'error');
                            }
                        );
                    },

                    onclose: function(response) {
                        //when the user close the payment modal
                        if(!isPaymentMade) Popup1.alert('Cancelled', 'Transaction cancelled' , 'error');
                    }
                });
            }
        </script>

    <!-- assign onclick to btn-->
    <?php
        if(!empty($btnIdEqualsAmount)){
            $onClickBuffer = '';
            foreach ($btnIdEqualsAmount as $btn=>$param){
                $isParamArray = is_array($param);
                if($isParamArray && !isset($param['amount'])) die("Payment Button '$btn' value not consist of amount key. e.g ['amount'=>''...]");

                $amount = $isParamArray? $param['amount']: Math1::toNumber($param);
                $param = $isParamArray? json_encode($param): null;
                $onClickBuffer .= <<<HTML
                $(function(){  
                    $(document).on('click', '#$btn', function(event){    
                        payWithFlutterwaveRave($amount, '$btn', '$param');
                    }); 
                });
HTML;
            }
            echo "<script>$onClickBuffer</script>"; return '';
        }

    }













    /**
     * Simple Popup with Lazy Load
     * @param $amount
     * @param null $redirectToController
     * @param bool $addScript
     * @return string
     */
    public static function renderLazyPopup($amount, $redirectToController = null, $addScript = true){?>
        <form>
            <a class="flwpug_getpaid"
               data-PBFPubKey="<?= static::$config['public_key'] ?>"
               data-amount="<?= $amount ?>"
               data-customer_email="<?= Auth1::get('email') ?>"
               data-customer_firstname="<?= Auth1::get('full_name') ?>"
               data-currency="NGN"
               data-pay_button_text="Pay Now"
               data-country="NG"
               data-redirect_url="<?= url('form/').static::class.'@confirmPayment(null, 0, 1)?token='.token() ?>&redirect_to=<?= urlencode($redirectToController) ?>"></a>
        </form>
   <?php echo $addScript? '<script type="text/javascript" src="https://api.ravepay.co/flwv3-pug/getpaidx/api/flwpbf-inline.js"></script>': ''; return ''; }


















    /************************************
     *
     *  Dashboard  Menu
     *
     ************************************/


    /**
     * @return mixed|array
     */
    static function getMenuList() {
        return [
            'Payment Gateway'=>[
                    Dashboard::getManageUrl(exFlutterwaveRavePaymentGateway::class) =>'<i class="fa fa-gear"></i><span> FlutterWave Rave </span>',
                    Dashboard::getManageUrl(exPaystackPaymentGateway::class) =>'<i class="fa fa-gear"></i><span> Paystack </span>',
            ],
        ];
    }

    /**
     * @param exRoute1 $route
     */
    static function onRoute($route){}






    /**
     * Manage Blog with HtmlForm1 or xcrud
     * @return mixed|Xcrud|HtmlForm1
     */
    static function manage() {
        // add pretty print?>
        <script>var prettyPrint=(function(){var b={el:function(f,d){var e=document.createElement(f),c;d=b.merge({},d);if(d&&d.style){var g=d.style;b.applyCSS(e,d.style);delete d.style}for(c in d){if(d.hasOwnProperty(c)){e[c]=d[c]}}return e},applyCSS:function(c,d){for(var g in d){if(d.hasOwnProperty(g)){try{c.style[g]=d[g]}catch(f){}}}},txt:function(c){return document.createTextNode(c)},row:function(e,f,c){c=c||"td";var h=b.count(e,null)+1,g=b.el("tr"),i,d={style:b.getStyles(c,f),colSpan:h,onmouseover:function(){var j=this.parentNode.childNodes;b.forEach(j,function(k){if(k.nodeName.toLowerCase()!=="td"){return}b.applyCSS(k,b.getStyles("td_hover",f))})},onmouseout:function(){var j=this.parentNode.childNodes;b.forEach(j,function(k){if(k.nodeName.toLowerCase()!=="td"){return}b.applyCSS(k,b.getStyles("td",f))})}};b.forEach(e,function(j){if(j===null){return}i=b.el(c,d);if(j.nodeType){i.appendChild(j)}else{i.innerHTML=b.shorten(j.toString())}g.appendChild(i)});return g},hRow:function(c,d){return b.row(c,d,"th")},table:function(h,e){h=h||[];var d={thead:{style:b.getStyles("thead",e)},tbody:{style:b.getStyles("tbody",e)},table:{style:b.getStyles("table",e)}},g=b.el("table",d.table),f=b.el("thead",d.thead),c=b.el("tbody",d.tbody);if(h.length){g.appendChild(f);f.appendChild(b.hRow(h,e))}g.appendChild(c);return{node:g,tbody:c,thead:f,appendChild:function(i){this.tbody.appendChild(i)},addRow:function(k,j,i){this.appendChild(b.row.call(b,k,(j||e),i));return this}}},shorten:function(d){var c=40;d=d.replace(/^\s\s*|\s\s*$|\n/g,"");return d.length>c?(d.substring(0,c-1)+"..."):d},htmlentities:function(c){return c.replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;")},merge:function(h,g){if(typeof h!=="object"){h={}}for(var f in g){if(g.hasOwnProperty(f)){var c=g[f];if(typeof c==="object"){h[f]=b.merge(h[f],c);continue}h[f]=c}}for(var e=2,d=arguments.length;e<d;e++){b.merge(h,arguments[e])}return h},count:function(c,g){var f=0;for(var e=0,d=c.length;e<d;e++){if(c[e]===g){f++}}return f},thead:function(c){return c.getElementsByTagName("thead")[0]},forEach:function(e,d,g){if(!g){g=d}var c=e.length,f=-1;while(++f<c){if(g(e[f],f,e)===false){break}}return true},type:function(c){try{if(c===null){return"null"}if(c===undefined){return"undefined"}var f=Object.prototype.toString.call(c).match(/\s(.+?)\]/)[1].toLowerCase();if(c.nodeType){if(c.nodeType===1){return"domelement"}return"domnode"}if(/^(string|number|array|regexp|function|date|boolean)$/.test(f)){return f}if(typeof c==="object"){return c.jquery&&typeof c.jquery==="string"?"jquery":"object"}if(c===window||c===document){return"object"}return"default"}catch(d){return"default"}},within:function(c){return{is:function(e){for(var d in c){if(c[d]===e){return d}}return""}}},common:{circRef:function(e,c,d){return b.expander("[POINTS BACK TO <strong>"+(c)+"</strong>]","Click to show this item anyway",function(){this.parentNode.appendChild(a(e,{maxDepth:1}))})},depthReached:function(d,c){return b.expander("[DEPTH REACHED]","Click to show this item anyway",function(){try{this.parentNode.appendChild(a(d,{maxDepth:1}))}catch(f){this.parentNode.appendChild(b.table(["ERROR OCCURED DURING OBJECT RETRIEVAL"],"error").addRow([f.message]).node)}})}},getStyles:function(d,c){c=a.settings.styles[c]||{};return b.merge({},a.settings.styles["default"][d],c[d])},expander:function(e,d,c){return b.el("a",{innerHTML:b.shorten(e)+' <b style="visibility:hidden;">[+]</b>',title:d,onmouseover:function(){this.getElementsByTagName("b")[0].style.visibility="visible"},onmouseout:function(){this.getElementsByTagName("b")[0].style.visibility="hidden"},onclick:function(){this.style.display="none";c.call(this);return false},style:{cursor:"pointer"}})},stringify:function(e){var d=b.type(e),g,f=true;if(d==="array"){g="[";b.forEach(e,function(j,h){g+=(h===0?"":", ")+b.stringify(j)});return g+"]"}if(typeof e==="object"){g="{";for(var c in e){if(e.hasOwnProperty(c)){g+=(f?"":", ")+c+":"+b.stringify(e[c]);f=false}}return g+"}"}if(d==="regexp"){return"/"+e.source+"/"}if(d==="string"){return'"'+e.replace(/"/g,'\\"')+'"'}return e.toString()},headerGradient:(function(){var d=document.createElement("canvas");if(!d.getContext){return""}var c=d.getContext("2d");d.height=30;d.width=1;var f=c.createLinearGradient(0,0,0,30);f.addColorStop(0,"rgba(0,0,0,0)");f.addColorStop(1,"rgba(0,0,0,0.25)");c.fillStyle=f;c.fillRect(0,0,1,30);var e=d.toDataURL&&d.toDataURL();return"url("+(e||"")+")"})()};var a=function(g,k){k=k||{};var f=b.merge({},a.config,k),c=b.el("div"),d=a.config,h=0,i={},e=false;a.settings=f;var j={string:function(l){return b.txt('"'+b.shorten(l.replace(/"/g,'\\"'))+'"')},number:function(l){return b.txt(l)},regexp:function(n){var o=b.table(["RegExp",null],"regexp");var l=b.table();var m=b.expander("/"+n.source+"/","Click to show more",function(){this.parentNode.appendChild(o.node)});l.addRow(["g",n.global]).addRow(["i",n.ignoreCase]).addRow(["m",n.multiline]);o.addRow(["source","/"+n.source+"/"]).addRow(["flags",l.node]).addRow(["lastIndex",n.lastIndex]);return f.expanded?o.node:m},domelement:function(m,p){var o=b.table(["DOMElement",null],"domelement"),n=["id","className","innerHTML","src","href"],l=m.nodeName||"";o.addRow(["tag","&lt;"+l.toLowerCase()+"&gt;"]);b.forEach(n,function(q){if(m[q]){o.addRow([q,b.htmlentities(m[q])])}});return f.expanded?o.node:b.expander("DOMElement ("+l.toLowerCase()+")","Click to show more",function(){this.parentNode.appendChild(o.node)})},domnode:function(l){var n=b.table(["DOMNode",null],"domelement"),m=b.htmlentities((l.data||"UNDEFINED").replace(/\n/g,"\\n"));n.addRow(["nodeType",l.nodeType+" ("+l.nodeName+")"]).addRow(["data",m]);return f.expanded?n.node:b.expander("DOMNode","Click to show more",function(){this.parentNode.appendChild(n.node)})},jquery:function(m,n,l){return j.array(m,n,l,true)},object:function(o,m,t){var l=b.within(i).is(o);if(l){return b.common.circRef(o,l,f)}i[t||"TOP"]=o;if(m===f.maxDepth){return b.common.depthReached(o,f)}var u=b.table(["Object",null],"object"),n=true;for(var p in o){if(!o.hasOwnProperty||o.hasOwnProperty(p)){var v=o[p],s=b.type(v);n=false;try{u.addRow([p,j[s](v,m+1,p)],s)}catch(r){if(window.console&&window.console.log){console.log(r.message)}}}}if(n){u.addRow(["<small>[empty]</small>"])}else{u.thead.appendChild(b.hRow(["key","value"],"colHeader"))}var q=(f.expanded||e)?u.node:b.expander(b.stringify(o),"Click to show more",function(){this.parentNode.appendChild(u.node)});e=true;return q},array:function(p,n,s,l){var m=b.within(i).is(p);if(m){return b.common.circRef(p,m)}i[s||"TOP"]=p;if(n===f.maxDepth){return b.common.depthReached(p)}var r=l?"jQuery":"Array",t=b.table([r+"("+p.length+")",null],l?"jquery":r.toLowerCase()),o=true,q=0;if(l){t.addRow(["selector",p.selector])}b.forEach(p,function(v,u){if(f.maxArray>=0&&++q>f.maxArray){t.addRow([u+".."+(p.length-1),j[b.type(v)]("...",n+1,u)]);return false}o=false;t.addRow([u,j[b.type(v)](v,n+1,u)])});if(!l){if(o){t.addRow(["<small>[empty]</small>"])}else{t.thead.appendChild(b.hRow(["index","value"],"colHeader"))}}return f.expanded?t.node:b.expander(b.stringify(p),"Click to show more",function(){this.parentNode.appendChild(t.node)})},"function":function(q,s,o){var m=b.within(i).is(q);if(m){return b.common.circRef(q,m)}i[o||"TOP"]=q;var r=b.table(["Function",null],"function"),p=b.table(["Arguments"]),n=q.toString().match(/\((.+?)\)/),l=q.toString().match(/\(.*?\)\s+?\{?([\S\s]+)/)[1].replace(/\}?$/,"");r.addRow(["arguments",n?n[1].replace(/[^\w_,\s]/g,""):"<small>[none/native]</small>"]).addRow(["body",l]);return f.expanded?r.node:b.expander("function(){...}","Click to see more about this function.",function(){this.parentNode.appendChild(r.node)})},date:function(l){var m=b.table(["Date",null],"date"),n=l.toString().split(/\s/);m.addRow(["Time",n[4]]).addRow(["Date",n.slice(0,4).join("-")]);return f.expanded?m.node:b.expander("Date (timestamp): "+(+l),"Click to see a little more info about this date",function(){this.parentNode.appendChild(m.node)})},"boolean":function(l){return b.txt(l.toString().toUpperCase())},"undefined":function(){return b.txt("UNDEFINED")},"null":function(){return b.txt("NULL")},"default":function(){return b.txt("prettyPrint: TypeNotFound Error")}};c.appendChild(j[(f.forceObject)?"object":b.type(g)](g,h));return c};a.config={expanded:true,forceObject:false,maxDepth:3,maxArray:-1,styles:{array:{th:{backgroundColor:"#6DBD2A",color:"white"}},"function":{th:{backgroundColor:"#D82525"}},regexp:{th:{backgroundColor:"#E2F3FB",color:"#000"}},object:{th:{backgroundColor:"#1F96CF"}},jquery:{th:{backgroundColor:"#FBF315"}},error:{th:{backgroundColor:"red",color:"yellow"}},domelement:{th:{backgroundColor:"#F3801E"}},date:{th:{backgroundColor:"#A725D8"}},colHeader:{th:{backgroundColor:"#EEE",color:"#000",textTransform:"uppercase"}},"default":{table:{borderCollapse:"collapse",width:"100%"},td:{padding:"5px",fontSize:"12px",backgroundColor:"#FFF",color:"#222",border:"1px solid #000",verticalAlign:"top",fontFamily:'"Consolas","Lucida Console",Courier,mono',whiteSpace:"nowrap"},td_hover:{},th:{padding:"5px",fontSize:"12px",backgroundColor:"#222",color:"#EEE",textAlign:"left",border:"1px solid #000",verticalAlign:"top",fontFamily:'"Consolas","Lucida Console",Courier,mono',backgroundImage:b.headerGradient,backgroundRepeat:"repeat-x"}}}};return a})();</script>
        <?php
            // search box
            $searchUrl  = url('/api/').self::class."@getPaymentInfo()?token=".token();
            $loading = HtmlWidget1::loader().' <h5 align="left">  Loading...  </h5>';
            echo "<script>
                function searchReference(){
                    let result_box = $('#search_payment_reference_result');
                    result_box.html('$loading');
                    Ajax1.request('$searchUrl', { reference: $('#query_payment_reference').val() },  function(status) {
                        result_box.html(status['data']? prettyPrint(status['data']): '<br/><br/><h3> Reference NOT  FOUND</h3><br/><br/>');
                        result_box.append(`<br/><button class='btn btn-danger btn-lg' onclick=$(this).parent().html('')><i class='fa fa-close'></i> Clear Result</button>`);
                    })
                }
                </script>".
            '<form>'.HtmlForm1::addInput('<h6>Search Payment Reference</h6>', ['id'=>'query_payment_reference'])."<button type='button' onclick='searchReference()' class='btn btn-primary btn-lg'> <i class='fa fa-search'></i> Search Reference</button></form><div id='search_payment_reference_result' style='padding:10px;'></div> <div class='clearfix' style='margin-top:20px;'></div><h6 class=\"m-0\">Payment Table</h6><hr/>";
        // manage table
        return self::xcrud()->columns('updated_at', true)->unset_title()->unset_add();
    }

    /**
     * Dashboard Menu
     * @return array
     */
    static function getDashboard() { return [];  }

    /**
     * Save  Model Information
     * @param $id
     */
    static function processSave($id = null){  }
}