<?php

use GuzzleHttp\Client as GuzzleClient;
use Netflie\WhatsAppCloudApi\Http\GuzzleClientHandler;

class WhatsappLibrary
{
    protected $clientHandler;
    protected $client;

    public function __construct()
    {
        $this->clientHandler = new GuzzleClientHandler();
        $this->client = new GuzzleClient();
    }

    public function updateProfile(array $profileData, $phoneNumberId, $accessToken)
    {
        $apiVersion = 'v19.0';
        $url = "https://graph.facebook.com/{$apiVersion}/{$phoneNumberId}/whatsapp_business_profile";

        $data = [
            "messaging_product" => $profileData['messaging_product'],
            "about" => $profileData['about'],
            "address" => $profileData['address'],
            "description" => $profileData['description'],
            "vertical" => $profileData['vertical'],
            "email" => $profileData['email'],
            "websites" => $profileData['websites'],
            "profile_picture_handle" => $profileData['profile_picture_handle'],
        ];

        $timeout = 10; // Set the desired timeout value in seconds

        $response = $this->clientHandler->postJsonData($url, $data, [
            'Authorization' => 'Bearer ' . $accessToken,
            'Content-Type' => 'application/json',
        ], $timeout);

        return $response;
    }

    public function uploadProfilePicture($filePath, $appId, $accessToken)
    {
        $apiVersion = 'v19.0';
        $fileContent = file_get_contents($filePath);

        // Step 1: Upload the image
        $uploadUrl = "https://graph.facebook.com/{$apiVersion}/{$appId}/uploads";
        $response = $this->client->request('POST', $uploadUrl, [
            'query' => [
                'file_length' => filesize($filePath),
                'file_type' => mime_content_type($filePath),
                'access_token' => $accessToken,
            ],
        ]);

        $uploadSessionId = json_decode($response->getBody(), true)['id'];

        // Step 2: Save session ID and signature
        // You can save these values in the database or session as needed.

        // Step 3: Call POST with the session ID and image file
        $fileOffset = 0;
        $uploadSessionUrl = "https://graph.facebook.com/{$apiVersion}/{$uploadSessionId}";
        $response = $this->client->request('POST', $uploadSessionUrl, [
            'headers' => [
                'Authorization' => 'OAuth ' . $accessToken,
                'file_offset' => $fileOffset,
            ],
            'body' => $fileContent,
        ]);

        // Step 4: Save handle result
        $handleResult = json_decode($response->getBody(), true)['h'];
        // Save the handle result in the database or session as needed.

        return $handleResult;
    }

    public function createTemplate($templateRawData, $header, $body, $footer, $buttons, $accountId, $accessToken)
    {
        
        
        $client = new \GuzzleHttp\Client();
        $url = "https://graph.facebook.com/v19.0/{$accountId}/message_templates";
        $templateName = $templateRawData['template_name'];
        if (!strpos($templateName, '_')) {
            $convertedName = str_replace(' ', '_', $templateName);
        } else {
            $convertedName = $templateName;
        }
        $templateData = [
            "name" => $convertedName,
            "category" => $templateRawData['category'],
            "allow_category_change" => true,
            "language" => $templateRawData['language'],
            "components" => [
                $body,
            ],
        ];
        
       if (!empty($header)) {
    $templateData["components"][] = $header;
}

if (!empty($footer)) {
    $templateData["components"][] = $footer;
}

if (!empty($buttons)) {
    $templateData["components"][] = $buttons;
}

        
       //dd($templateData);

        $response = $client->post($url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json',
            ],
            'json' => $templateData,
        ]);

        $responseData = json_decode($response->getBody(), true);
        $status_code = $response->getStatusCode();

        // Handle the API response as needed
        // ...
        
        return $status_code;
    }

    public function getHeaderTextComponent($templateRawData, $media = null)
{
    $headerType = $templateRawData['header_type'];
    $headerExample = [];

    foreach ($templateRawData as $key => $value) {
        if (strpos($key, 'text_parameter_') === 0) {
            $parameterIndex = substr($key, strlen('text_parameter_'));
            $headerExample[] = $value;
        }
    }

    $headerComponent = [
        "type" => "HEADER",
        "format" => $headerType,
    ];

    if ($headerType === "text") {
        $text = $templateRawData['text_header'];
        $headerComponent["text"] = $text;

        if (!empty($headerExample)) {
            $headerComponent["example"] = [
                "header_text" => $headerExample,
            ];
        }
    } elseif ($headerType === "media") {
        $format = $templateRawData['media_type']; // For MEDIA headers only
        $headerComponent["format"] = $format;

        if (!empty($media)) {
            $headerComponent["example"] = [
                "header_handle" => $media,
            ];
        }
    } elseif ($headerType === "none") {
        $headerComponent = [];
    } else {
        $headerComponent = [];
    }

    return $headerComponent;
}


    public function getBodyTextComponent($templateRawData)
    {
        $bodyText = $templateRawData['message'];
        $bodyExample = [];

        foreach ($templateRawData as $key => $value) {
            if (strpos($key, 'message_parameter_') === 0) {
                $parameterIndex = substr($key, strlen('message_parameter_'));
                $bodyExample[$parameterIndex] = $value;
            }
        }

        $bodyComponent = [
            "type" => "BODY",
            "text" => $bodyText,
        ];

        if (!empty($bodyExample)) {
            $bodyComponent["example"] = [
                "body_text" => [
                    array_values($bodyExample)
                ],
            ];
        }

        return $bodyComponent;
    }

    public function getFooterTextComponent($templateRawData)
    {
        $footerText = $templateRawData['footer_text'];

        $footerComponent = [];

        if ($footerText !== null) {
            $footerComponent = [
                "type" => "FOOTER",
                "text" => $footerText,
            ];
        }

        return $footerComponent;
    }

    public function getButtonsComponent($templateRawData)
    {
        $buttons = $templateRawData['buttons'];
        $buttonComponent = [];

        $formattedButtons = [];

        foreach ($buttons as $index => $button) {
            if ($button['type'] === "callButton") {
                $formattedButton = [
                    "type" => "PHONE_NUMBER",
                    "text" => $button['displaytext'],
                    "phone_number" => $button['action'],
                ];
            } elseif ($button['type'] === "urlButton") {
                $formattedButton = [
                    "type" => "URL",
                    "text" => $button['displaytext'],
                    "url" => $button['action'],
                ];

                if (isset($button['example'])) {
                    $formattedButton['example'] = [$button['example']];
                }
            } elseif ($button['type'] === "quickReplyButton") {
                $formattedButton = [
                    "type" => "QUICK_REPLY",
                    "text" => $button['displaytext'],
                ];
            }else{
                
                $formattedButton = [];
            }

            $formattedButtons[] = $formattedButton;
        }

        if (!empty($formattedButtons)) {
            $buttonComponent = [
                "type" => "BUTTONS",
                "buttons" => $formattedButtons,
            ];
        }else{
            $buttonComponent = [];
        }
        

        return $buttonComponent;
    }

    public function getTemplate($businessId, $accessToken)
    {
        $client = new \GuzzleHttp\Client();
        $url = "https://graph.facebook.com/v19.0/{$businessId}/message_templates";

        $response = $client->get($url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json',
            ],
        ]);

        $responseData = json_decode($response->getBody(), true);

        // Extract the desired fields from the response
        $templates = [];
        foreach ($responseData['data'] as $template) {
            $templates[] = [
                'name' => $template['name'],
                'status' => $template['status'],
                'id' => $template['id'],
            ];
        }

        return $templates;
    }

    public function editTemplate($header, $body, $footer, $buttons, $templateId, $accessToken)
    {
        $client = new \GuzzleHttp\Client();
        $url = "https://graph.facebook.com/v19.0/{$templateId}";
        $templateData = [$header, $body, $footer, $buttons];
        $response = $client->post($url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json',
            ],
            'json' => $templateData,
        ]);

        $responseData = json_decode($response->getBody(), true);
        return $responseData;
    }

    public function retrieveUrl($media_id, $accessToken)
{
    // Define the upload folder path
    $uploadFolder = WHATSAPP_MODULE_UPLOAD_FOLDER;

    $client = new \GuzzleHttp\Client();
    $url = "https://graph.facebook.com/v19.0/{$media_id}";
    $response = $client->get($url, [
        'headers' => [
            'Authorization' => 'Bearer ' . $accessToken,
        ],
    ]);
    
    // Check if the request was successful (status code 200)
    if ($response->getStatusCode() === 200) {
        // Parse the JSON response
        $responseData = json_decode($response->getBody(), true);
        
        // Check if the URL key exists in the response data
        if (isset($responseData['url'])) {
            // Return the retrieved URL
            $media = $responseData['url'];
            $mediaData = $client->get($media, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                ],
            ]);
            if ($mediaData->getStatusCode() === 200){
                $imageContent = $mediaData->getBody();
                $contentType = $mediaData->getHeader('Content-Type')[0];

                $extensionMap = [
                        'image/jpeg'=> 'jpg',
                        'image/png'=> 'png',
                        'audio/mpeg'=> 'mp3',
                        'audio/wav'=> 'wav',
                        'video/mp4'=> 'mp4',
                        'audio/aac'=> 'aac',
                        'audio/amr'=> 'amr',
                        'audio/ogg'=> 'ogg',
                        'text/plain'=> 'txt',
                        'application/pdf'=> 'pdf',
                        'application/vnd.ms-powerpoint'=> 'ppt',
                        'application/msword'=> 'doc',
                        'application/vnd.ms-excel'=> 'xls',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document'=> 'docx',
                        'application/vnd.openxmlformats-officedocument.presentationml.presentation'=> 'pptx',
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'=> 'xlsx',
                        'video/3gp'=> '3gp',
                        'image/webp'=> 'webp'
                ];
                $extension = $extensionMap[$contentType] ?? 'unknown';
                $filename = 'media_' . uniqid() . '.' . $extension;
                $storagePath = $uploadFolder . '/' . $filename;

                // Save the file using CodeIgniter's file storage method
                $CI =& get_instance();
                $CI->load->helper('file');
                write_file($storagePath, $imageContent);

                return $filename;
            }
        }
    }
    
    // Return null or handle the error as needed
    return null;
}

   
public function handle_attachment_upload($attachment) {
    // Specify the upload folder
    $uploadFolder = WHATSAPP_MODULE_UPLOAD_FOLDER; // Define this constant in your application

    // Get the file extension based on the MIME type
    $contentType = $attachment['type'];
    $extensionMap = [
        'image/jpeg'=> 'jpg',
        'image/png'=> 'png',
        'audio/mpeg'=> 'mp3',
        'video/mp4'=> 'mp4',
        'audio/aac'=> 'aac',
        'audio/amr'=> 'amr',
        'audio/ogg'=> 'ogg',
        'audio/mp4'=> 'mp4',
        'audio/wav'=> 'wav',
        'text/plain'=> 'txt',
        'application/pdf'=> 'pdf',
        'application/vnd.ms-powerpoint'=> 'ppt',
        'application/msword'=> 'doc',
        'application/vnd.ms-excel'=> 'xls',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document'=> 'docx',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation'=> 'pptx',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'=> 'xlsx',
        'video/3gp'=> '3gp',
        'image/webp'=> 'webp'
    ];
    $extension = $extensionMap[$contentType] ?? 'unknown';

    // Generate a unique filename
    $filename = uniqid('attachment_') . '_' . time() . '_' . mt_rand(1000, 9999) . '.' . $extension; // Adjust filename format as needed

    // Move the uploaded file to the specified directory
    $destination = $uploadFolder . '/' . $filename;
    if (move_uploaded_file($attachment['tmp_name'], $destination)) {
        
        return $filename;
    } else {
        // Failed to upload file
        return false;
    }
}

public static function detectFileType($url) {
    // Parse the URL to extract the path
    $parsedUrl = parse_url($url);
    
    // Check if the URL contains a path component
    if (isset($parsedUrl['path'])) {
        // Get the file path
        $filePath = $parsedUrl['path'];
        
        // Extract the file extension
        $fileExtension = pathinfo($filePath, PATHINFO_EXTENSION);

        // Map file extensions to file types
        $fileTypes = [
            'audio' => ['mp3', 'mp4', 'ogg', 'wav', 'flac'],
            'document' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt'],
            'image' => ['jpg', 'jpeg', 'png', 'gif', 'svg', 'tiff', 'bmp', 'webp', 'ico'],
            'video' => ['mp4', 'webm', '3gp', 'mpeg', 'mov', 'avi', 'mkv'],
        ];

        // Iterate over file types and check if the file extension matches
        foreach ($fileTypes as $type => $extensions) {
            if (in_array($fileExtension, $extensions)) {
                return $type;
            }
        }
    }

    // If no match is found or the URL doesn't contain a path, return 'unknown'
    return 'unknown';
}


    // December 2023
    
    public function loadTemplatesFromWhatsApp($businessId, $accessToken, $after=""){
        $client = new \GuzzleHttp\Client();
        $url="https://graph.facebook.com/v19.0/{$businessId}/message_templates";
        $queryParams = [
            'fields' => 'name,category,language,quality_score,components,status',
            'limit' => 100
        ];
        if($after!=""){
            $queryParams['after']=$after;
        }

         $response = $client->get($url, [
    'headers' => [
        'Authorization' => 'Bearer ' . $accessToken,
    ],
    'query' => $queryParams,
]);

    

        // Handle the response here
        if ($response->getStatusCode() === 200) {
            $responseData = json_decode($response->getBody(), true);
            return $responseData;
        }else {
            // Handle error response
           return false;
        }
    }


    public function metaTemplatemessage($accountId, $accessToken, $message)
    {
        //dd($message);
        $client = new \GuzzleHttp\Client();
        $url = "https://graph.facebook.com/v19.0/{$accountId}/messages";
        
        $components = json_decode($message->updated_parameters);
        //dd($components);
if (!empty($components) && count($components) > 0){
foreach ($components as $component) {
    // Check if the component type is 'HEADER' and has parameters
    if ($component->type === 'HEADER' && isset($component->parameters)) {
        // Iterate through parameters
        foreach ($component->parameters as $parameter) {
            // Check if the parameter type is 'image' and has a 'link' property
            if ($parameter->type === 'image' && isset($parameter->image->link)) {
                // Use the saveFile function to get the image link
                $imageLink = $this->saveFile($message, 'header_image');
                $parameter->image->link = $imageLink;
            }
            
            if ($parameter->type === 'document' && isset($parameter->document->link)){
                 $docLink = $this->saveFile($message, 'header_document');
                 $parameter->document->link = $docLink;
            }
        }
    }
}
}

            $requestData = [
        'messaging_product' => 'whatsapp',
        'to' => $message->phone,
        'type' => 'template',
        'template' => [
            'name' => $message->template_text,
            'language' => [
                'code' => $message->language,
            ],
        ],
    ];
    
    if (!empty($components) && count($components) > 0) {
        $requestData['template']['components'] = $components;
    }
    
    $response = $client->post($url, [
        'headers' => [
            'Authorization' => 'Bearer ' . $accessToken,
            'Content-Type' => 'application/json',
        ],
        'json' => $requestData,
    ]);
    
    $statusCode = $response->getStatusCode();
        $content = json_decode($response->getBody(), true);
        
        return $statusCode;

        // Handle the response as needed based on $statusCode and $content
    
}

function send_whatsapp_message($to, $message_data)
{
    // Retrieve necessary options
    $from_phone_number_id = get_option('phone_number_id');
    $access_token = get_option('whatsapp_access_token');

    // Validate recipient and message data
    if (empty($to) || empty($message_data) || empty($from_phone_number_id) || empty($access_token)) {
        error_log('Invalid recipient, message data, or access token');
        return ['error' => 'Invalid recipient, message data, or access token'];
    }

    // Define API endpoint
    $api_url = 'https://graph.facebook.com/v19.0/' . $from_phone_number_id . '/messages';

    // Prepare request data
    $data = [
        'messaging_product' => 'whatsapp',
        'recipient_type' => 'individual',
        'to' => $to,
        'type' => $message_data['type']
    ];

    // Add specific message data based on message type
    switch ($message_data['type']) {
        case 'text':
            $data['text'] = $message_data['text'];
            break;
        case 'reaction':
            $data['reaction'] = $message_data['reaction'];
            break;
        case 'image':
            $data['image'] = $message_data['image'];
            break;
        case 'location':
            $data['location'] = $message_data['location'];
            break;
        case 'contacts':
            $data['contacts'] = $message_data['contacts'];
            break;
        case 'interactive':
            $data['interactive'] = $message_data['interactive'];
            break;
        default:
            error_log('Invalid message type');
            return ['error' => 'Invalid message type'];
    }

    // Initialize cURL session
    $ch = curl_init();

    // Set cURL options
    curl_setopt($ch, CURLOPT_URL, $api_url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $access_token,
        'Content-Type: application/json',
    ]);

    // Execute cURL request
    $response = curl_exec($ch);

    // Check for cURL errors
    if (curl_errno($ch)) {
        $error_message = curl_error($ch);
        error_log('Failed to send message: ' . $error_message);
        return ['error' => 'Failed to send message: ' . $error_message];
    }

    // Close cURL session
    curl_close($ch);

    // Process the response
    $response_data = json_decode($response, true);

    // Check if the response data contains the message ID
    if (isset($response_data['messages'][0]['id'])) {
        // Message sent successfully
        $messageId = $response_data['messages'][0]['id'];
        return ['success' => true, 'id' => $messageId];
    } else {
        // Failed to send message
        error_log('Failed to send message. Response: ' . $response_data);
        return ['error' => 'Failed to send message'];
    }
}

/**
 * Handle audio file upload.
 * Move the uploaded file to the specified directory and generate a unique filename.
 * Return the path to the uploaded file.
 * 
 * @param array $file The uploaded file information ($_FILES['audio']).
 * @return string|bool The path to the uploaded file, or false if failed to upload.
 */


}