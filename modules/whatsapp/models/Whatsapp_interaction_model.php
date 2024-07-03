<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Whatsapp_interaction_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Get all interaction messages from the database
     *
     * @return array Array of interaction messages
     */
public function get_interactions()
{
            $this->db->query("SET character_set_connection=utf8mb4");
        $this->db->query("SET character_set_results=utf8mb4");
    // Fetch interactions ordered by time_sent in descending order
    $interactions = $this->db->order_by('time_sent', 'DESC')
                      ->get(db_prefix() . 'whatsapp_interactions')
                      ->result_array();

    // Fetch messages for each interaction
    foreach ($interactions as &$interaction) {
        $interaction_id = $interaction['id'];
        $messages = $this->get_interaction_messages($interaction_id);
        $this->map_interaction($interaction);
        $interaction['messages'] = $messages;

        // Fetch staff name for each message in the interaction
        foreach ($interaction['messages'] as &$message) {
            if (!empty($message['staff_id'])) {
                $message['staff_name'] = get_staff_full_name($message['staff_id']);
            } else {
                $message['staff_name'] = null;
            }

          // Check if URL is already a base name
            if ($message['url'] && strpos($message['url'], '/') === false) {
                // If URL doesn't contain "/", consider it as a file name
                // Assuming base URL is available
                $message['asset_url'] = WHATSAPP_MODULE_UPLOAD_URL . $message['url'];
            } else {
                // Otherwise, use the URL directly
                $message['asset_url'] = $message['url'] ?? null;
            }

        }
    }

    return $interactions;
}




    /**
     * Insert a new interaction message into the database
     *
     * @param array $data Data to be inserted
     * @return int Insert ID
     */
public function insert_interaction($data)
{
    
        $this->db->query("SET NAMES utf8mb4");
        $this->db->query("SET character_set_connection=utf8mb4");
        $this->db->query("SET character_set_results=utf8mb4");
        $this->db->query("SET character_set_client=utf8mb4");
        
   $existing_interaction = $this->db->where('receiver_id', $data['receiver_id'])
    ->where('wa_no', $data['wa_no']) ->where('wa_no_id', $data['wa_no_id']) // Check if 'wa_no' matches
    ->get(db_prefix() .'whatsapp_interactions')
    ->row();

if ($existing_interaction) {
    // Existing interaction found with matching 'receiver_id' and 'wa_no'
    $this->db->where('id', $existing_interaction->id)
        ->update(db_prefix() .'whatsapp_interactions', $data);
    return $existing_interaction->id;
} else {
    // No existing interaction found with matching 'receiver_id' and 'wa_no'
    $this->db->insert(db_prefix() .'whatsapp_interactions', $data);
    return $this->db->insert_id();
}

}


    /**
     * Get all interaction messages for a specific interaction ID
     *
     * @param int $interaction_id ID of the interaction
     * @return array Array of interaction messages
     */
    public function get_interaction_messages($interaction_id)
    {
        $this->db->where('interaction_id', $interaction_id)
            ->order_by('time_sent', 'asc');
        return $this->db->get(db_prefix() . 'whatsapp_interaction_messages')->result_array();
    }

    /**
     * Insert a new interaction message into the database
     *
     * @param array $data Data to be inserted
     * @return int Insert ID
     */
    public function insert_interaction_message($data)
    {
            // Set character set for the connection and results to utf8mb4
        // Set character set for the connection and results to utf8mb4
        $this->db->query("SET NAMES utf8mb4");
        $this->db->query("SET character_set_connection=utf8mb4");
        $this->db->query("SET character_set_results=utf8mb4");
        $this->db->query("SET character_set_client=utf8mb4");
        
        $this->db->insert(db_prefix() .'whatsapp_interaction_messages', $data);
    
        // Check if the insert was successful
        if ($this->db->affected_rows() > 0) {
            // Return the ID of the inserted message
            return $this->db->insert_id();
        } else {
            // Return false if the insert failed
            return false;
        }
    }

    /**
     * Get the ID of the last message for a given interaction
     *
     * @param int $interaction_id ID of the interaction
     * @return int ID of the last message
     */
    public function get_last_message_id($interaction_id)
    {
        $this->db->select_max('id')
            ->where('interaction_id', $interaction_id);
        $query = $this->db->get(db_prefix() .'whatsapp_interaction_messages');
        $result = $query->row_array();
        return $result['id'];
    }

    /**
     * Update the status of a message in the database
     *
     * @param int $interaction_id ID of the interaction
     * @param string $status Status to be updated
     * @return void
     */
    public function update_message_status($interaction_id, $status)
    {
        $this->db->where('message_id', $interaction_id)
            ->update(db_prefix() . 'whatsapp_interaction_messages', ['status' => $status]);
    }

    /**
     * Map interaction data to entities based on receiver ID
     *
     * @param array $interaction interaction data
     * @return void
     */
public function map_interaction($interaction)
{
    if ($interaction['type'] === null || $interaction['type_id'] === null) {
            $interaction_id = $interaction['id'];
            $receiver_id = $interaction['receiver_id'];
            $customer = $this->db->where('phonenumber', $receiver_id)->get(db_prefix() .'clients')->row();
            $contact = $this->db->where('phonenumber', $receiver_id)->get(db_prefix() .'contacts')->row();
            $lead = $this->db->where('phonenumber', $receiver_id)->get(db_prefix() .'leads')->row();
            $staff = $this->db->where('phonenumber', $receiver_id)->get(db_prefix() .'staff')->row();

            $entity = null;
            $type = null;

            if ($customer) {
                $entity = $customer->userid;
                $type = 'customer';
            } elseif ($contact) {
                $entity = $contact->id;
                $type = 'contact';
            } elseif ($staff) {
                $entity = $staff->staffid;
                $type = 'staff';
            } else {
                if ($lead !== null) {
                    $entity = $lead->id;
                }

                $type = 'lead';

                $lead_data = array(
                    'phonenumber' => $receiver_id,
                    'name' => $interaction['name'],
                    'status' => get_option('whatsapp_lead_status'),
                    'assigned' => get_option('whatsapp_lead_assigned'),
                    'source' => get_option('whatsapp_lead_source'),
                    'dateadded'=>date("Y-m-d H:i:s"),
                );

                $existing_lead = $this->db->where('phonenumber', $receiver_id)->get(db_prefix() .'leads')->row();
                if ($existing_lead) {
                    $this->db->where('phonenumber', $receiver_id)->update(db_prefix() .'leads', $lead_data);
                } else {
                    $this->db->insert(db_prefix() .'leads', $lead_data);
                }
            }

            $data = array(
                'type' => $type,
                'type_id' => $entity,
                'wa_no' => $interaction['wa_no']??get_option('phone_number'),
                'receiver_id' => $receiver_id
            );

            $existing_interaction = $this->db->where('id', $interaction_id)->get(db_prefix() .'whatsapp_interactions')->row();

            if ($existing_interaction) {
                $this->db->where('id', $interaction_id)->update(db_prefix() .'whatsapp_interactions', $data);
            } else {
                $data['id'] = $interaction_id;
                $this->db->insert(db_prefix() .'whatsapp_interactions', $data);
            }
        }
            

if ($interaction['wa_no'] === null || $interaction['wa_no_id'] === null) {
    $interaction_id = $interaction['id'];

    // Use null coalescing operator to provide default values if 'wa_no' or 'wa_no_id' is null
    $wa_no = $interaction['wa_no'] ?? get_option('phone_number');
    $wa_no_id = $interaction['wa_no_id'] ?? get_option('phone_number_id');

    // Prepare data for update
    $data = array(
        'wa_no' => $wa_no,
        'wa_no_id' => $wa_no_id
    );

    // Check if the interaction exists
    $existing_interaction = $this->db->where('id', $interaction_id)->get(db_prefix() . 'whatsapp_interactions')->row();

    if ($existing_interaction) {
        // Update the existing interaction
        $this->db->where('id', $interaction_id)->update(db_prefix() . 'whatsapp_interactions', $data);
    }
}

    
}
public function get_interaction($interaction_id)
{
    
    return $this->db->where('id', $interaction_id)
                    ->get(db_prefix() . 'whatsapp_interactions')
                    ->row_array(); // Adjusted to return an array instead of object for consistency
}
public function get_interaction_history($interaction_id)
{
    // Fetch the interaction details
    $interaction = $this->get_interaction($interaction_id);

    if ($interaction) {
        // Fetch the messages associated with this interaction
        $messages = $this->get_interaction_messages($interaction_id);

        // Filter only text messages
        $textMessages = array_filter($messages, function ($message) {
            return $message['type'] === 'text';
        });

        // Assign filtered text messages back to interaction
        $interaction['messages'] = $textMessages;

        // Fetch staff name for each message if available
        foreach ($interaction['messages'] as &$message) {
            if (!empty($message['staff_id'])) {
                $message['staff_name'] = get_staff_full_name($message['staff_id']);
            } else {
                $message['staff_name'] = null;
            }
        }
    }

    // Format messages for OpenAI API
    $formattedMessages = [];
    foreach ($interaction['messages'] as $chatMessage) {
        $formattedMessages[] = [
            'role' => $interaction['wa_no'] === $chatMessage['sender_id'] ? 'assistant' : 'user', // Assuming 'wa_no' is the user identifier
            'content' => $chatMessage['message'] // Assuming 'message_content' contains the message text
        ];
    }

    return $formattedMessages;
}



    
}
