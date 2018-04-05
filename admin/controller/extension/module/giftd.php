<?php
class ControllerExtensionModuleGiftd extends Controller{
    private $error = array();
    
    public function index(){   
        $this->load->language('extension/module/giftd');

        $this->document->setTitle($this->language->get('heading_title'));
       
        $this->load->model('setting/setting');
                
        if(($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()){
            if((!$this->request->post['giftd_api_key'] && $this->config->get('giftd_api_key')) || (!$this->request->post['giftd_user_id'] && $this->config->get('giftd_user_id'))){
                $this->uninstall();
            }
            $this->model_setting_setting->editSetting('giftd', $this->request->post);        
            
            $this->cache->delete('product');
            
            $this->session->data['success'] = $this->language->get('text_success');
                        
            $this->response->redirect($this->url->link('extension/module/giftd', 'token=' . $this->session->data['token'], 'SSL'));
        }
                
        $data['heading_title'] = $this->language->get('heading_title');

        $data['text_api_key']      = $this->language->get('text_api_key');
        $data['text_user_id']      = $this->language->get('text_user_id');
        $data['text_partner_code'] = $this->language->get('text_partner_code');
        $data['text_prefix']       = $this->language->get('text_prefix');        
        
        $data['button_save']   = $this->language->get('button_save');
        $data['button_cancel'] = $this->language->get('button_cancel');
        $data['button_remove'] = $this->language->get('button_remove');
        
         if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }
        
        if (isset($this->error['image'])) {
            $data['error_image'] = $this->error['image'];
        } else {
            $data['error_image'] = array();
        }
        
        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text'      => $this->language->get('text_home'),
            'href'      => $this->url->link('common/home', 'token=' . $this->session->data['token'], 'SSL'),
            'separator' => false
        );

        $data['breadcrumbs'][] = array(
            'text'      => $this->language->get('text_module'),
            'href'      => $this->url->link('extension/module', 'token=' . $this->session->data['token'], 'SSL'),
            'separator' => ' :: '
        );
        
        $data['breadcrumbs'][] = array(
            'text'      => $this->language->get('heading_title'),
            'href'      => $this->url->link('extension/module/giftd', 'token=' . $this->session->data['token'], 'SSL'),
            'separator' => ' :: '
        );
        
        $data['action'] = $this->url->link('extension/module/giftd', 'token=' . $this->session->data['token'], 'SSL');
        
        $data['cancel'] = $this->url->link('extension/module', 'token=' . $this->session->data['token'], 'SSL');
        
        if(isset($this->request->post['giftd_api_key'])){
            $data['giftd_api_key'] = $this->request->post['giftd_api_key'];
        }elseif($this->config->get('giftd_api_key')){ 
            $data['giftd_api_key'] = $this->config->get('giftd_api_key');
        }else{
            $data['giftd_api_key'] = '';
        }
        
        if(isset($this->request->post['giftd_user_id'])){
            $data['giftd_user_id'] = $this->request->post['giftd_user_id'];
        }elseif($this->config->get('giftd_user_id')){ 
            $data['giftd_user_id'] = $this->config->get('giftd_user_id');
        }else{
            $data['giftd_user_id'] = '';
        }
        
        if($data['giftd_user_id'] && $data['giftd_api_key']){
            $partner_data = $this->get_data($data['giftd_user_id'], $data['giftd_api_key']);
        }
        
        if(isset($this->request->post['giftd_partner_code'])){
            $data['giftd_partner_code'] = $this->request->post['giftd_partner_code'];
        }elseif($this->config->get('giftd_partner_code')){ 
            $data['giftd_partner_code'] = $this->config->get('giftd_partner_code');
        }elseif(isset($partner_data['data']['code']) && ($partner_data['data']['code'])){ 
            $data['giftd_partner_code'] = $partner_data['data']['code'];
        }else{
            $data['giftd_partner_code'] = '';
        }                
        
        if(isset($this->request->post['giftd_prefix'])){
            $data['giftd_prefix'] = $this->request->post['giftd_prefix'];
        }elseif($this->config->get('giftd_prefix')){ 
            $data['giftd_prefix'] = $this->config->get('giftd_prefix');
        }elseif(isset($partner_data['data']['token_prefix']) && ($partner_data['data']['token_prefix'])){ 
            $data['giftd_prefix'] = $partner_data['data']['token_prefix'];
        }else{
            $data['giftd_prefix'] = '';
        }
        
        $js_code = '';

        if((!$this->config->get('giftd_code_updated')) || ($this->config->get('giftd_code_updated') && ((time() - $this->config->get('giftd_code_updated')) > 24 * 3600))){
            if($data['giftd_user_id'] && $data['giftd_api_key']){
                $js_code = $this->get_js($data['giftd_user_id'], $data['giftd_api_key']);
            }
            $data['giftd_code_updated'] = time();
        }else{
            $data['giftd_code_updated'] = $this->config->get('giftd_code_updated');
        } 
        if($js_code){
            $data['giftd_js_code'] = $js_code;
        }elseif($this->config->get('giftd_js_code')){
            $data['giftd_js_code'] = $this->config->get('giftd_js_code');
        }else{
            $data['giftd_js_code'] = '';
        }               

        
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/module/giftd.tpl', $data));
    }
    
    protected function validate() {
        if (!$this->user->hasPermission('modify', 'extension/module/giftd')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }
                
         return !$this->error; 
    }
    
    protected function get_data($user_id, $api_key){
        
        $data = array(
            'email' => $this->config->get('config_email'),
            'phone' => $this->config->get('config_telephone'),
            'name' => $this->config->get('config_owner'),
            'url' => HTTP_SERVER,
            'title' => $this->config->get('config_name'), 
            'opencart_version' => VERSION 
        );
        
        require_once(DIR_SYSTEM . 'GiftdApiClient.php');
        
        $client = new Giftd_Client($user_id, $api_key);
        
        $result = $client->query("openCart/install", $data);
       
        return $result;
    }
    
    protected function get_js($user_id, $api_key){
        require_once(DIR_SYSTEM . 'GiftdApiClient.php');
        
        $client = new Giftd_Client($user_id, $api_key);
        $result = $client->query('partner/getJs');

        $code = isset($result['data']['js']) ? $result['data']['js'] : false;
        
        return $code;
    }
    
    public function uninstall(){
        require_once(DIR_SYSTEM . 'GiftdApiClient.php');
        
        if($this->config->get('giftd_user_id') && $this->config->get('giftd_api_key')){
            $client = new Giftd_Client($this->config->get('giftd_user_id'), $this->config->get('giftd_api_key'));
            $result = $client->query("openCart/uninstall");
        }
        
        $this->load->model('extension/module/giftd');
        
        $this->model_extension_module_giftd->uninstall();

    }
    
    public function install(){
        $this->load->model('extension/module/giftd');
        $this->model_extension_module_giftd->install();
    }
} 
?>