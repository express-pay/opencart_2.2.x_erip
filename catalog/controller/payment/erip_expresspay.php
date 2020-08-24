<?php
class ControllerPaymentEripExpressPay extends Controller {
	public function index() {
		$data['button_confirm'] = $this->language->get('button_confirm');
		$data['redirect'] = $this->url->link('payment/erip_expresspay/send');
		$data['text_loading'] = $this->language->get('text_loading');
		
		return $this->load->view('payment/erip_expresspay.tpl', $data);
	}
	
	public function send() {
		$this->log_info('send', 'Initialization request for add invoice');
		$this->load->model('checkout/order');
		$order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
		$secret_word = $this->config->get('erip_expresspay_secret_key');
		$is_use_signature = ( $this->config->get('erip_expresspay_sign_invoices') == 'on' ) ? true : false;

		$url = ( $this->config->get('erip_expresspay_test_mode') != 'on' ) ? $this->config->get('erip_expresspay_url_api') : $this->config->get('erip_expresspay_url_sandbox_api');
		$url .= "/v1/invoices?token=" . $this->config->get('erip_expresspay_token');

	    $currency = (date('y') > 16 || (date('y') >= 16 && date('n') >= 7)) ? '933' : '974';
  
        $request_params = array(
            "AccountNo" => $this->session->data['order_id'],
            "Amount" => $this->currency->format($order_info['total'], $this->session->data['currency'], '', false),
            "Currency" => $currency,
            "Surname" => $order_info['payment_lastname'],
            "FirstName" => $order_info['payment_firstname'],
            "City" => $order_info['payment_city'],
            "IsNameEditable" => ( ( $this->config->get('erip_expresspay_name_editable') == 'on' ) ? 1 : 0 ),
            "IsAddressEditable" => ( ( $this->config->get('erip_expresspay_address_editable') == 'on' ) ? 1 : 0 ),
            "IsAmountEditable" => ( ( $this->config->get('erip_expresspay_amount_editable') == 'on' ) ? 1 : 0 )
        );

        if($is_use_signature)
        	$url .= "&signature=" . $this->compute_signature_add_invoice($request_params, $secret_word);

        $request_params = http_build_query($request_params);

        $this->log_info('send', 'Send POST request; ORDER ID - ' . $this->session->data['order_id'] . '; URL - ' . $url . '; REQUEST - ' . $request_params);

        $response = "";

        try {
	        $ch = curl_init();
	        curl_setopt($ch, CURLOPT_URL, $url);
	        curl_setopt($ch, CURLOPT_POST, 1);
	        curl_setopt($ch, CURLOPT_POSTFIELDS, $request_params);
	        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	        $response = curl_exec($ch);
	        curl_close($ch);
    	} catch (Exception $e) {
    		$this->log_error_exception('send', 'Get response; ORDER ID - ' . $this->session->data['order_id'] . '; RESPONSE - ' . $response, $e);

    		$this->redirect($this->url->link('payment/erip_expresspay/fail'));
    	}

    	$this->log_info('send', 'Get response; ORDER ID - ' . $this->session->data['order_id'] . '; RESPONSE - ' . $response);

		try {
        	$response = json_decode($response);
    	} catch (Exception $e) {
    		$this->log_error_exception('send', 'Get response; ORDER ID - ' . $this->session->data['order_id'] . '; RESPONSE - ' . $response, $e);

    		$this->redirect($this->url->link('payment/erip_expresspay/fail'));
    	}

        if(isset($response->InvoiceNo))
        	$this->response->redirect($this->url->link('payment/erip_expresspay/success'));
        else
        	$this->response->redirect($this->url->link('payment/erip_expresspay/fail'));
	}

	public function success() {
		$this->log_info('send', 'End request for add invoice');
		$this->log_info('success', 'Initialization render success page; ORDER ID - ' . $this->session->data['order_id']);

		$this->cart->clear();

		$this->load->language('payment/erip_expresspay');
		$data['heading_title'] = $this->language->get('heading_title');
		$data['text_message'] = $this->language->get('text_message');
		$this->document->setTitle($this->data['heading_title']);

		$data['breadcrumbs'] = array(); 

		$data['breadcrumbs'][] = array(
			'href'      => $this->url->link('common/home'),
			'text'      => $this->language->get('text_home'),
			'separator' => false
		);

		$data['breadcrumbs'][] = array(
			'href'      => $this->url->link('checkout/cart'),
			'text'      => $this->language->get('text_basket'),
			'separator' => $this->language->get('text_separator')
		);

		$data['breadcrumbs'][] = array(
			'href'      => $this->url->link('checkout/checkout', '', 'SSL'),
			'text'      => $this->language->get('text_checkout'),
			'separator' => $this->language->get('text_separator')
		);	

		$data['button_continue'] = $this->language->get('button_continue');
		$data['text_loading'] = $this->language->get('text_loading');
		$data['continue'] = $this->url->link('common/home');
		$data['test_mode_label'] = $this->language->get('test_mode_label');
		$data['text_send_notify_success'] = $this->language->get('text_send_notify_success');
		$data['text_send_notify_cancel'] = $this->language->get('text_send_notify_cancel');
		$data['test_mode'] = ( $this->config->get('erip_expresspay_test_mode') == 'on' ) ? true : false;
		$data['message_success'] = nl2br($this->config->get('erip_expresspay_message_success'), true);
		$data['order_id'] = $this->session->data['order_id'];
		$data['message_success'] = str_replace("##order_id##", $data['order_id'], $data['message_success']);
		$data['is_use_signature'] = ( $this->config->get('erip_expresspay_sign_invoices') == 'on' ) ? true : false;
		$data['signature_success'] = $data['signature_cancel'] = "";

		if($data['is_use_signature']) {
			$secret_word = $this->config->get('erip_expresspay_secret_key_notify');
			$data['signature_success'] = $this->compute_signature('{"CmdType": 1, "AccountNo": ' . $data["order_id"] . '}', $secret_word);
			$data['signature_cancel'] = $this->compute_signature('{"CmdType": 2, "AccountNo": ' . $data["order_id"] . '}', $secret_word);
		}

		$this->load->model('checkout/order');
		$this->model_checkout_order->addOrderHistory($this->session->data['order_id'], $this->config->get('erip_expresspay_pending_status_id'));

		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

		$this->log_info('success', 'End render success page; ORDER ID - ' . $this->session->data['order_id']);

		$this->response->setOutput($this->load->view('payment/erip_expresspay_success.tpl', $data));
	}

	public function fail() {
		$this->log_info('send', 'End request for add invoice');
		$this->log_info('fail', 'Initialization render fail page; ORDER ID - ' . $this->session->data['order_id']);

		$this->load->language('payment/erip_expresspay');
		$data['heading_title'] = $this->language->get('heading_title_error');
		$data['text_message'] = $this->language->get('text_message_error');
		$this->document->setTitle($this->data['heading_title']);

		$data['breadcrumbs'] = array(); 

		$data['breadcrumbs'][] = array(
			'href'      => $this->url->link('common/home'),
			'text'      => $this->language->get('text_home'),
			'separator' => false
		);

		$data['breadcrumbs'][] = array(
			'href'      => $this->url->link('checkout/cart'),
			'text'      => $this->language->get('text_basket'),
			'separator' => $this->language->get('text_separator')
		);

		$data['breadcrumbs'][] = array(
			'href'      => $this->url->link('checkout/checkout', '', 'SSL'),
			'text'      => $this->language->get('text_checkout'),
			'separator' => $this->language->get('text_separator')
		);	

		$data['button_continue'] = $this->language->get('button_continue');
		$data['text_loading'] = $this->language->get('text_loading');
		$data['continue'] = $this->url->link('checkout/checkout');

		$this->load->model('checkout/order');
		$this->model_checkout_order->addOrderHistory($this->session->data['order_id'], $this->config->get('erip_expresspay_cancel_status_id'));

		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

		$this->log_info('fail', 'End render fail page; ORDER ID - ' . $this->session->data['order_id']);

		$this->response->setOutput($this->load->view('payment/erip_expresspay_failure.tpl', $data));
	}

	public function notify() {
		$this->log_info('notify', 'Get notify from server; REQUEST METHOD - ' . $_SERVER['REQUEST_METHOD']);

		if ($_SERVER['REQUEST_METHOD'] === 'POST') {
			$secret_word = $this->config->get('erip_expresspay_secret_key_notify');
			$is_use_signature = ( $this->config->get('erip_expresspay_sign_notify') == 'on' ) ? true : false;
			$data = ( isset($this->request->post['Data']) ) ? htmlspecialchars_decode($this->request->post['Data']) : '';
			$signature = ( isset($this->request->post['Signature']) ) ? $this->request->post['Signature'] : '';
		    
		    if($is_use_signature) {
		    	if($signature == $this->compute_signature($data, $secret_word))
			        $this->notify_success($data);
			    else  
			    	$this->notify_fail($data);
		    } else 
		    	$this->notify_success($data);
		}

		$this->log_info('notify', 'End (Get notify from server); REQUEST METHOD - ' . $_SERVER['REQUEST_METHOD']);
	}

	private function notify_success($dataJSON) {
		try {
        	$data = json_decode($dataJSON);
    	} catch(Exception $e) {
    		$this->log_error('notify_fail', "Fail to parse the server response; RESPONSE - " . $dataJSON);
    		$this->notify_fail($dataJSON);
    	}

		$this->load->model('checkout/order');

        if(isset($data->CmdType)) {
        	switch ($data->CmdType) {
        		case '1':
        			$this->model_checkout_order->addOrderHistory($data->AccountNo, $this->config->get('erip_expresspay_processing_status_id'));
        			$this->log_info('notify_success', 'Initialization to update status. STATUS ID - ' . $this->config->get('erip_processing_status_id') . "; RESPONSE - " . $dataJSON);

        			break;
        		case '2':
        			$this->model_checkout_order->addOrderHistory($data->AccountNo, $this->config->get('erip_expresspay_cancel_status_id'));
					$this->log_info('notify_success', 'Initialization to update status. STATUS ID - ' . $this->config->get('erip_cancel_status_id') . "; RESPONSE - " . $dataJSON);

        			break;
        		default:
					$this->notify_fail($dataJSON);
					die();
        	}

	    	header("HTTP/1.0 200 OK");
	    	echo 'SUCCESS';
        } else
			$this->notify_fail($dataJSON);	
	}

	private function notify_fail($dataJSON) {
		$this->log_error('notify_fail', "Fail to update status; RESPONSE - " . $dataJSON);

		header("HTTP/1.0 400 Bad Request");
		echo 'FAILED | Incorrect digital signature';
	}

	private function compute_signature($json, $secret_word) {
	    $hash = NULL;
	    $secret_word = trim($secret_word);
	    
	    if(empty($secret_word))
			$hash = strtoupper(hash_hmac('sha1', $json, ""));
	    else
	        $hash = strtoupper(hash_hmac('sha1', $json, $secret_word));

	    return $hash;
	}	

    private function compute_signature_add_invoice($request_params, $secret_word) {
    	$secret_word = trim($secret_word);
        $normalized_params = array_change_key_case($request_params, CASE_LOWER);
        $api_method = array(
                "accountno",
                "amount",
                "currency",
                // "expiration",
                // "info",
                "surname",
                "firstname",
                // "patronymic",
                "city",
                // "street",
                // "house",
                // "building",
                // "apartment",
                "isnameeditable",
                "isaddresseditable",
                "isamounteditable"
        );

        $result = $this->config->get('erip_expresspay_token');

        foreach ($api_method as $item)
            $result .= ( isset($normalized_params[$item]) ) ? $normalized_params[$item] : '';

        $hash = strtoupper(hash_hmac('sha1', $result, $secret_word));

        return $hash;
    }

    private function log_error_exception($name, $message, $e) {
    	$this->log($name, "ERROR" , $message . '; EXCEPTION MESSAGE - ' . $e->getMessage() . '; EXCEPTION TRACE - ' . $e->getTraceAsString());
    }

    private function log_error($name, $message) {
    	$this->log($name, "ERROR" , $message);
    }

    private function log_info($name, $message) {
    	$this->log($name, "INFO" , $message);
    }

    private function log($name, $type, $message) {
    	$log = new Log('erip_expresspay/express-pay-' . date('Y.m.d') . '.log');
    	$log->write($type . " - IP - " . $_SERVER['REMOTE_ADDR'] . "; USER AGENT - " . $_SERVER['HTTP_USER_AGENT'] . "; FUNCTION - " . $name . "; MESSAGE - " . $message . ';');
    }
}

?>