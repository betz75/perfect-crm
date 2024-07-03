<?php
defined('BASEPATH') or exit('No direct script access allowed');
// Require the Composer autoloader.

use Netflie\WhatsAppCloudApi\WhatsAppCloudApi;

class Whatsapp extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('whatsapp_interaction_model');
            $this->load->library('WhatsappLibrary'); // Load and instantiate WhatsappLibrary
}

public function interaction()
{
   
 
    $this->load->view('admin/interaction');
}


public function interactions()
{
    // Load interactions from the model
    $data['interactions'] = $this->whatsapp_interaction_model->get_interactions();
    // Send the data as JSON response
    header('Content-Type: application/json');
    echo json_encode($data);
    exit; // Terminate the script after sending the JSON response
}



}
