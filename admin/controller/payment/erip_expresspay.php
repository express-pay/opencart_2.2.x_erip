<?php
class ControllerPaymentEripExpressPay extends Controller {
 
    private $error = array();

    public function index() {
		define("ERIP_EXPRESSPAY_VERSION", "2.4");
		$this->load->language('payment/erip_expresspay');
		
		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('erip_expresspay', $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('extension/payment', 'token=' . $this->session->data['token'], true));
		}

		$data['heading_title']      = $this->language->get('heading_title');
		$data['text_edit']      = $this->language->get('text_edit');
		$data['button_save']        = $this->language->get('button_save');
		$data['button_cancel']      = $this->language->get('button_cancel');
		$data['error_warning']      = $this->language->get('error_warning');
		$data['token_label']     	  = $this->language->get('token_label');
		$data['token_comment']   	  = $this->language->get('token_comment');
		$data['secret_key_label']       = $this->language->get('secret_key_label');
		$data['secret_key_comment']     = $this->language->get('secret_key_comment');
		$data['sign_invoices_label']     = $this->language->get('sign_invoices_label');
		$data['text_enabled']       = $this->language->get('text_enabled');
		$data['text_disabled']      = $this->language->get('text_disabled');
		$data['status_label']       = $this->language->get('status_label');
		$data['sort_order_label']   = $this->language->get('sort_order_label');
		$data['text_all_zones']     = $this->language->get('text_all_zones');
		$data['pending_status']     = $this->language->get('pending_status');
		$data['cancel_status']	  = $this->language->get('cancel_status');
		$data['processing_status']  = $this->language->get('processing_status');
		$data['handler_url']  	  = str_replace('/admin', '', HTTPS_SERVER . 'index.php?route=payment/erip_expresspay/notify');
		$data['handler_label']  	  = $this->language->get('handler_label');
		$data['test_mode_label']  	  = $this->language->get('test_mode_label');
		$data['name_editable_label']  	  = $this->language->get('name_editable_label');
		$data['address_editable_label']  	  = $this->language->get('address_editable_label');
		$data['amount_editable_label']  	  = $this->language->get('amount_editable_label');
		$data['url_api_label']  	  = $this->language->get('url_api_label');
		$data['url_sandbox_api_label']  	  = $this->language->get('url_sandbox_api_label');
		$data['secret_key_notify_label']  	  = $this->language->get('secret_key_notify_label');
		$data['secret_key_notify_comment']  	  = $this->language->get('secret_key_notify_comment');
		$data['sign_notify_label']  	  = $this->language->get('sign_notify_label');
		$data['text_contacts']  	  = $this->language->get('text_contacts');
		$data['text_phone']  	  = $this->language->get('text_phone');
		$data['settings_module_label']  	  = $this->language->get('settings_module_label');
		$data['name_editable_comment']  	  = $this->language->get('name_editable_comment');
		$data['address_editable_comment']  	  = $this->language->get('address_editable_comment');
		$data['amount_editable_comment']  	  = $this->language->get('amount_editable_comment');
		$data['sign_comment']  	  = $this->language->get('sign_comment');
		$data['text_version']  	  = $this->language->get('text_version');
		$data['text_about']  	  = $this->language->get('text_about');
		$data['message_success_label']  	  = $this->language->get('message_success_label');
		
		$this->load->model('localisation/order_status');
		
		$data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'token=' . $this->session->data['token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_payment'),
			'href' => $this->url->link('extension/payment', 'token=' . $this->session->data['token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('payment/erip_expresspay', 'token=' . $this->session->data['token'], true)
		);

		$data['action'] = $this->url->link('payment/erip_expresspay', 'token=' . $this->session->data['token'], true);

		$data['cancel'] = $this->url->link('extension/payment', 'token=' . $this->session->data['token'], true);

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}
	
		if (isset($this->request->post['erip_expresspay_token'])) {
			$data['erip_expresspay_token'] = $this->request->post['erip_expresspay_token'];
		} else {
			$data['erip_expresspay_token'] = $this->config->get('erip_expresspay_token');
		}

		if (isset($this->request->post['erip_expresspay_secret_key'])) {
			$data['erip_expresspay_secret_key_value'] = $this->request->post['erip_expresspay_secret_key'];
		} else {
			$data['erip_expresspay_secret_key_value'] = $this->config->get('erip_expresspay_secret_key');
		}
		
		if (isset($this->request->post['erip_expresspay_sign_invoices'])) {
			$data['erip_expresspay_sign_invoices_value'] = $this->request->post['erip_expresspay_sign_invoices'];
		} else {
			$data['erip_expresspay_sign_invoices_value'] = $this->config->get('erip_expresspay_sign_invoices');
		}

		if (isset($this->request->post['erip_expresspay_sign_notify'])) {
			$data['erip_expresspay_sign_notify_value'] = $this->request->post['erip_expresspay_sign_notify'];
		} else {
			$data['erip_expresspay_sign_notify_value'] = $this->config->get('erip_expresspay_sign_notify');
		}

		if (isset($this->request->post['erip_expresspay_message_success'])) {
			$data['erip_expresspay_message_success_value'] = $this->request->post['erip_expresspay_message_success'];
		} else {
			$data['erip_expresspay_message_success_value'] = $this->config->get('erip_expresspay_message_success');
			$data['erip_expresspay_message_success_value'] = ( !empty($data['erip_expresspay_message_success_value']) ) ? $this->config->get('erip_expresspay_message_success') : $this->language->get('message_success');
		}

		if (isset($this->request->post['erip_expresspay_secret_key_notify'])) {
			$data['erip_expresspay_secret_key_notify_value'] = $this->request->post['erip_expresspay_secret_key_notify'];
		} else {
			$data['erip_expresspay_secret_key_notify_value'] = $this->config->get('erip_expresspay_secret_key_notify');
		}
		
		if (isset($this->request->post['erip_expresspay_test_mode'])) {
			$data['erip_expresspay_test_mode_value'] = $this->request->post['erip_expresspay_test_mode'];
		} else {
			$data['erip_expresspay_test_mode_value'] = $this->config->get('erip_expresspay_test_mode');
		}

		if (isset($this->request->post['erip_expresspay_name_editable'])) {
			$data['erip_expresspay_name_editable_value'] = $this->request->post['erip_expresspay_name_editable'];
		} else {
			$data['erip_expresspay_name_editable_value'] = $this->config->get('erip_expresspay_name_editable');
		}

		if (isset($this->request->post['erip_expresspay_address_editable'])) {
			$data['erip_expresspay_address_editable_value'] = $this->request->post['erip_expresspay_address_editable'];
		} else {
			$data['erip_expresspay_address_editable_value'] = $this->config->get('erip_expresspay_address_editable');
		}

		if (isset($this->request->post['erip_expresspay_amount_editable'])) {
			$data['erip_expresspay_amount_editable_value'] = $this->request->post['erip_expresspay_amount_editable'];
		} else {
			$data['erip_expresspay_amount_editable_value'] = $this->config->get('erip_expresspay_amount_editable');
		}
		
		if (isset($this->request->post['erip_expresspay_url_api'])) {
			$data['erip_expresspay_url_api_value'] = $this->request->post['erip_expresspay_url_api'];
		} else {
			$data['erip_expresspay_url_api_value'] = $this->config->get('erip_expresspay_url_api');
		}

		if (isset($this->request->post['erip_expresspay_url_sandbox_api'])) {
			$data['erip_expresspay_url_sandbox_api_value'] = $this->request->post['erip_expresspay_url_sandbox_api'];
		} else {
			$data['erip_expresspay_url_sandbox_api_value'] = $this->config->get('erip_expresspay_url_sandbox_api');
		}

		if (isset($this->request->post['erip_expresspay_sort_order'])) {
			$data['erip_expresspay_sort_order'] = $this->request->post['erip_expresspay_sort_order'];
		} else {
			$data['erip_expresspay_sort_order'] = $this->config->get('erip_expresspay_sort_order');
		}
		if (isset($this->request->post['erip_expresspay_status'])) {
			$data['erip_expresspay_status'] = $this->request->post['erip_expresspay_status'];
		} else {
			$data['erip_expresspay_status'] = $this->config->get('erip_expresspay_status');
		}
	
		if (isset($this->request->post['erip_expresspay_pending_status_id'])) {
			$data['erip_expresspay_pending_status_id'] = $this->request->post['erip_expresspay_pending_status_id'];
		} elseif ($this->config->has('erip_expresspay_pending_status_id')) {
			$data['erip_expresspay_pending_status_id'] = $this->config->get('erip_expresspay_pending_status_id');
		} else {
			$data['erip_expresspay_pending_status_id'] = '1';
		}

		if (isset($this->request->post['erip_expresspay_cancel_status_id'])) {
			$data['erip_expresspay_cancel_status_id'] = $this->request->post['erip_expresspay_cancel_status_id'];
		} elseif ($this->config->has('erip_expresspay_cancel_status_id')) {
			$data['erip_expresspay_cancel_status_id'] = $this->config->get('erip_expresspay_cancel_status_id');
		} else {
			$data['erip_expresspay_cancel_status_id'] = '10';
		}

		if (isset($this->request->post['erip_expresspay_processing_status_id'])) {
			$data['erip_expresspay_processing_status_id'] = $this->request->post['erip_expresspay_processing_status_id'];
		} elseif ($this->config->has('erip_expresspay_processing_status_id')) {
			$data['erip_expresspay_processing_status_id'] = $this->config->get('erip_expresspay_processing_status_id');
		} else {
			$data['erip_expresspay_processing_status_id'] = '2';
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('payment/erip_expresspay.tpl', $data));
    }

	private function validate() {
		$this->error = false;

		if (!$this->error)
			return true;
		else
			return false;
	}
}
?>