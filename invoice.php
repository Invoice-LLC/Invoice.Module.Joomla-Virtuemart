<?php
if (!defined('_VALID_MOS') && !defined('_JEXEC'))
    die('Direct Access to ' . basename(__FILE__) . ' is not allowed.');

if (!class_exists('vmPSPlugin'))
    require(JPATH_VM_PLUGINS . DS . 'vmpsplugin.php');

require_once "sdk/RestClient.php";
require_once "sdk/CREATE_TERMINAL.php";
require_once "sdk/CREATE_PAYMENT.php";
require_once "sdk/common/SETTINGS.php";
require_once "sdk/common/ITEM.php";
require_once "sdk/common/ORDER.php";

class plgVmPaymentInvoice extends vmPSPlugin
{
    /**
     * @var $terminal_id string - Invoice payment terminal ID
     * @var $api_key string - Invoice API key
     * @var $login string - Invoice login
     */
    private $terminal_id;
    private $api_key;
    private $login;

    /**
     * @var $client RestClient
     */
    private $client;

    public function __construct(&$subject, $config)
    {
        parent::__construct($subject, $config);

        $varsToPush        = array(
            'api_key' => array('','string'),
            'login' => array('','string'),
        );

        $this->setConfigParameterable($this->_configTableFieldName, $varsToPush);
    }

    private function initParams($method) {
        $this->login = $method->login;
        $this->api_key = $method->api_key;
        $this->terminal_id = $this->getTerminalId();

        if($this->login == null or $this->api_key == null) {
            throw new Exception("Ошибка при создании платежа");
        }

        $this->client = new RestClient($this->login, $this->api_key);

        if($this->terminal_id == null or $this->terminal_id == "") {
            $terminal = $this->createTerminal();
            if($terminal == null or $terminal->error != null) {
                $terminal = $this->createTerminal();
            }

            if($terminal == null or $terminal->error != null) {
                throw new Exception("Ошибка при создании терминала");
            }
            $this->terminal_id = $terminal->id;

            $this->setTerminalId($this->terminal_id);
        }
    }

    public function getVmPluginCreateTableSQL () {

        return $this->createTableSQL ('Payment Invoice Table');
    }

    function getTableSQLFields () {

        $SQLfields = array(
            'id'                          => 'int(1) UNSIGNED NOT NULL AUTO_INCREMENT',
        );

        return $SQLfields;
    }

    function plgVmDeclarePluginParamsPayment($name, $id, &$data)
    {
        return $this->declarePluginParams('payment', $name, $id, $data);
    }

    function plgVmSetOnTablePluginParamsPayment($name, $id, &$table)
    {
        return $this->setOnTablePluginParams($name, $id, $table);
    }

    function plgVmDeclarePluginParamsPaymentVM3( &$data) {
        return $this->declarePluginParams('payment', $data);
    }

    protected function checkConditions($cart, $method, $cart_prices)
    {
        return true;
    }

    public function plgVmOnSelectCheckPayment(VirtueMartCart $cart)
    {
        return $this->OnSelectCheck($cart);
    }

    public function plgVmDisplayListFEPayment(VirtueMartCart $cart, $selected = 0, &$htmlIn)
    {
        return $this->displayListFE($cart, $selected, $htmlIn);
    }

    public function plgVmonSelectedCalculatePricePayment(VirtueMartCart $cart, array &$cart_prices, &$cart_prices_name)
    {
        return $this->onSelectedCalculatePrice($cart, $cart_prices, $cart_prices_name);
    }

    function plgVmOnCheckAutomaticSelectedPayment(VirtueMartCart $cart, array $cart_prices = array())
    {
        return $this->onCheckAutomaticSelected($cart, $cart_prices);
    }

    function plgVmOnStoreInstallPaymentPluginTable ($jplugin_id) {

        return $this->onStoreInstallPluginTable ($jplugin_id);
    }

    public function plgVmConfirmedOrder($cart, $order)
    {
        if (!($method = $this->getVmPluginMethod($order['details']['BT']->virtuemart_paymentmethod_id))) {
            return null;
        }

        $this->initParams($method);
        $url = ((!empty($_SERVER['HTTPS'])) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];

        $create_payment = new CREATE_PAYMENT();

        $settings = new SETTINGS();
        $settings->terminal_id = $this->terminal_id;
        $settings->success_url = $url;
        $settings->fail_url = $url;
        $create_payment->settings = $settings;

        $invoice_order = new ORDER();
        $invoice_order->currency = "RUB";
        $invoice_order->amount = $order['details']['BT']->order_total;
        $invoice_order->id = $order['details']['BT']->virtuemart_order_id;
        $create_payment->order = $invoice_order;

        $receipt = array();
        foreach ($cart->products as $product) {
            $item = new ITEM();
            $item->name = $product->product_name;
            $item->quantity = $product->quantity;
            $item->discount = $product->prices["discountAmount"];
            $item->price = $product->prices["salesPrice"];
            $item->resultPrice = $product->prices["salesPriceTt"];
            array_push($receipt, $item);
        }
        $shipment = new ITEM();
        $shipment->name = "Доставка";
        $shipment->quantity = 1;
        $shipment->discount = 0;
        $shipment->price = $order['details']['BT']->order_shipment;
        $shipment->resultPrice = $order['details']['BT']->order_shipment;
        array_push($receipt, $shipment);

        $create_payment->receipt = $receipt;

        $payment = $this->client->CreatePayment($create_payment);

        if($payment == null or $payment->error != null) {
            throw new Exception("Ошибка создания платежа, обратитесь к администратору");
        } else {
            header("Location: ".$payment->payment_url);
        }

        $text = "Оплата заказа через Invoice";
        return $this->processConfirmedOrderPaymentResponse(true, $cart, $order, $text, "invoice", 'P');
    }

    public function plgVmOnPaymentNotification()
    {
        $postData = file_get_contents('php://input');
        $notification = json_decode($postData, true);

        $signature = null;
        if(!isset($notification["signature"])) {
            die("the signature must not be null");
        } else {
            $signature = $notification["signature"];
        }

        $status = null;

        if(!isset($notification["status"])) {
            die("status must not be null");
        } else {
            $status = $notification["status"];
        }

        $order_id = null;

        if(!isset($notification["order"]["id"])) {
            die("Order not found");
        } else {
            $order_id = $notification["order"]["id"];
        }

        $orderModel     = VmModel::getModel('orders');
        $order          = $orderModel->getOrder($order_id);
        $plugin_method  = $this->getVmPluginMethod($order['details']['BT']->virtuemart_paymentmethod_id);
        $key = $plugin_method->api_key;

        if($key == null) {
            die("api key is invalid");
        }

        if($this->getSignature($key, $notification["status"], $notification["id"]) != $signature) {
            die("wrong signature");
        }

        $type = $notification["notification_type"];
        switch ($status) {
            case "successful" :
                if($type == "pay") {
                    $this->pay($notification);
                }
                break;
            case "error":
                $this->error($notification);
                break;
        }
        die("OK");
    }

    private function pay($params) {
        $order_id = $params['order']["id"];
        $orderModel     = VmModel::getModel('orders');

        $orderStatus['order_status']        = 'C';
        $orderStatus['virtuemart_order_id'] = $params['account'];
        $orderStatus['customer_notified']   = 0;
        $orderStatus['comments']            = 'Invoice';
        $orderModel->updateStatusForOneOrder($order_id, $orderStatus, true);
    }

    private function error($params) {
        $order_id = $params['order']["id"];
        $orderModel     = VmModel::getModel('orders');

        $orderStatus['order_status']        = 'X';
        $orderStatus['virtuemart_order_id'] = $params['account'];
        $orderStatus['customer_notified']   = 0;
        $orderStatus['comments']            = 'Invoice';
        $orderModel->updateStatusForOneOrder($order_id, $orderStatus, true);
    }

    private function createTerminal() {
        $create_terminal = new CREATE_TERMINAL();
        $create_terminal->type = "dynamical";
        $create_terminal->name = JFactory::getApplication()->getCfg('sitename');
        $create_terminal->defaultPrice = 0;
        $create_terminal->description = JFactory::getApplication()->getCfg('MetaDesc');

        return $this->client->CreateTerminal($create_terminal);
    }

    private function getSignature($key, $status, $tranId) {
        return md5($tranId.$status.$key);
    }

    private function setTerminalId($id) {
        $fp = fopen('terminal', 'w');
        fwrite($fp, $id);
        fclose($fp);
    }

    private function getTerminalId() {
        $fp = fopen('terminal', 'r');
        $id = fgets($fp, 999);
        fclose($fp);
        return $id;
    }
}