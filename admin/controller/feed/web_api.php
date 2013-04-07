<?php

class ControllerFeedWebApi extends Controller {

	public function index() {
		$this->load->language('feed/web_api');
		$this->load->model('setting/setting');

		$this->document->setTitle($this->language->get('heading_title'));


		$this->data = array(
			'version'             => '1.0',
			'heading_title'       => $this->language->get('heading_title'),
			
			'text_enabled'        => $this->language->get('text_enabled'),
			'text_disabled'       => $this->language->get('text_disabled'),
			'text_homepage'       => $this->language->get('text_homepage'),
			'tab_general'         => $this->language->get('tab_general'),

			'entry_status'        => $this->language->get('entry_status'),
			'entry_key'           => $this->language->get('entry_key'),

			'button_save'         => $this->language->get('button_save'),
			'button_cancel'       => $this->language->get('button_cancel'),

			'action'              => $this->url->link('feed/web_api', 'token=' . $this->session->data['token'], 'SSL'),
			'cancel'              => $this->url->link('extension/feed', 'token=' . $this->session->data['token'], 'SSL')
		);

		if (($this->request->server['REQUEST_METHOD'] == 'POST')) {
			$this->model_setting_setting->editSetting('web_api', $this->request->post);				
			$this->session->data['success'] = $this->language->get('text_success');
			$this->redirect($this->url->link('extension/feed', 'token=' . $this->session->data['token'], 'SSL'));
		}

  		$this->data['breadcrumbs'] = array();

		$this->data['breadcrumbs'][] = array(
			'text'      => $this->language->get('text_home'),
			'href'      => $this->url->link('common/home', 'token=' . $this->session->data['token'], 'SSL'),
			'separator' => false
   		);

   		$this->data['breadcrumbs'][] = array(
			'text'      => $this->language->get('text_feed'),
			'href'      => $this->url->link('extension/feed', 'token=' . $this->session->data['token'], 'SSL'),       		
			'separator' => ' :: '
   		);

   		$this->data['breadcrumbs'][] = array(
			'text'      => $this->language->get('heading_title'),
			'href'      => $this->url->link('feed/web_api', 'token=' . $this->session->data['token'], 'SSL'),
			'separator' => ' :: '
   		);

   		if (isset($this->request->post['web_api_status'])) {
			$this->data['web_api_status'] = $this->request->post['web_api_status'];
		} else {
			$this->data['web_api_status'] = $this->config->get('web_api_status');
		}

		if (isset($this->request->post['web_api_key'])) {
			$this->data['web_api_key'] = $this->request->post['web_api_key'];
		} else {
			$this->data['web_api_key'] = $this->config->get('web_api_key');
		}


   		$this->template = 'feed/web_api.tpl';
		
		$this->children = array(
			'common/header',
			'common/footer'
		);
				
		$this->response->setOutput($this->render());
	}

}