<?php

namespace App\Utils;

use \Notification;
use App\Business;
use App\Notifications\CustomerNotification;
use App\Notifications\RecurringInvoiceNotification;

use App\Notifications\SupplierNotification;

use App\NotificationTemplate;
use App\Restaurant\Booking;
use App\System;
use App\Transaction;
use Config;
use GuzzleHttp\Client;

class NotificationUtil extends Util
{

    /**
     * Automatically send notification to customer/supplier if enabled in the template setting
     *
     * @param  int  $business_id
     * @param  string  $notification_type
     * @param  obj  $transaction
     * @param  obj  $contact
     *
     * @return void
     */
    public function autoSendNotification($business_id, $notification_type, $transaction, $contact)
    {
        $notification_template = NotificationTemplate::where('business_id', $business_id)
                ->where('template_for', $notification_type)
                ->first();
        
        $business = Business::findOrFail($business_id);
        $data['email_settings'] = $business->email_settings;
        $data['sms_settings'] = $business->sms_settings;

        if (!empty($notification_template)) {
            
            if (!empty($notification_template->auto_send) || !empty($notification_template->auto_send_sms)) {
                $orig_data = [
                    'email_body' => $notification_template->email_body,
                    'sms_body' => $notification_template->sms_body,
                    'subject' => $notification_template->subject
                ];
                $tag_replaced_data = $this->replaceTags($business_id, $orig_data, $transaction);

                $data['email_body'] = $tag_replaced_data['email_body'];
                $data['sms_body'] = $tag_replaced_data['sms_body'];

                //Auto send email
                if (!empty($notification_template->auto_send)) {
                    $data['subject'] = $tag_replaced_data['subject'];
                    $data['to_email'] = $contact->email;

                    $customer_notifications = NotificationTemplate::customerNotifications();
                    $supplier_notifications = NotificationTemplate::supplierNotifications();
                    if (array_key_exists($notification_type, $customer_notifications)) {
                        Notification::route('mail', $data['to_email'])
                                        ->notify(new CustomerNotification($data));
                    } elseif (array_key_exists($notification_type, $supplier_notifications)) {
                        Notification::route('mail', $data['to_email'])
                                        ->notify(new SupplierNotification($data));
                    }
                }

                //Auto send sms
                if (!empty($notification_template->auto_send_sms)) {
                    $data['mobile_number'] = $contact->mobile;
                    if (!empty($contact->mobile)) {
                        $this->sendSms($data);
                    }
                }
            }
            if($notification_template->template_for == 'departmentwise_pending')
            {
                $orig_data = [
                    'sms_body' => $notification_template->sms_body,
                ];
                $tag_replaced_data = $this->sendMessagePending($business_id, $orig_data, $transaction);

                $data['sms_body'] = $tag_replaced_data['sms_body'];
                if(!empty($tag_replaced_data['sms_body']))
                {
                    $data['mobile_number'] = $contact->mobile;
                    if (!empty($contact->mobile)) {
                        $this->sendSms($data);
                    }
                }
                

            }
        }
    }

    /**
     * Replaces tags from notification body with original value
     *
     * @param  text  $body
     * @param  int  $booking_id
     *
     * @return array
     */
    public function replaceBookingTags($business_id, $data, $booking_id)
    {
        $business = Business::findOrFail($business_id);
        $booking = Booking::where('business_id', $business_id)
                    ->with(['customer', 'table', 'correspondent', 'waiter', 'location', 'business'])
                    ->findOrFail($booking_id);
        foreach ($data as $key => $value) {
            //Replace contact name
            if (strpos($value, '{contact_name}') !== false) {
                $contact_name = $booking->customer->name;

                $data[$key] = str_replace('{contact_name}', $contact_name, $data[$key]);
            }

            //Replace table
            if (strpos($value, '{table}') !== false) {
                $table = !empty($booking->table->name) ?  $booking->table->name : '';

                $data[$key] = str_replace('{table}', $table, $data[$key]);
            }

            //Replace start_time
            if (strpos($value, '{start_time}') !== false) {
                $start_time = $this->format_date($booking->booking_start, true);

                $data[$key] = str_replace('{start_time}', $start_time, $data[$key]);
            }

            //Replace end_time
            if (strpos($value, '{end_time}') !== false) {
                $end_time = $this->format_date($booking->booking_end, true);

                $data[$key] = str_replace('{end_time}', $end_time, $data[$key]);
            }
            //Replace location
            if (strpos($value, '{location}') !== false) {
                $location = $booking->location->name;

                $data[$key] = str_replace('{location}', $location, $data[$key]);
            }

            //Replace service_staff
            if (strpos($value, '{service_staff}') !== false) {
                $service_staff = !empty($booking->waiter) ? $booking->waiter->user_full_name : '';

                $data[$key] = str_replace('{service_staff}', $service_staff, $data[$key]);
            }

            //Replace service_staff
            if (strpos($value, '{correspondent}') !== false) {
                $correspondent = !empty($booking->correspondent) ? $booking->correspondent->user_full_name : '';

                $data[$key] = str_replace('{correspondent}', $correspondent, $data[$key]);
            }

            //Replace business_name
            if (strpos($value, '{business_name}') !== false) {
                $business_name = $business->name;
                $data[$key] = str_replace('{business_name}', $business_name, $data[$key]);
            }

            //Replace business_logo
            if (strpos($value, '{business_logo}') !== false) {
                $logo_name = $business->logo;
                $business_logo = !empty($logo_name) ? '<img src="' . url('storage/business_logos/' . $logo_name) . '" alt="Business Logo" >' : '';

                $data[$key] = str_replace('{business_logo}', $business_logo, $data[$key]);
            }
        }
        return $data;
    }

    public function recurringInvoiceNotification($user, $invoice)
    {
        $user->notify(new RecurringInvoiceNotification($invoice));
    }

    public function configureEmail($notificationInfo)
    {
        $email_settings = $notificationInfo['email_settings'];

        $is_superadmin_settings_allowed = System::getProperty('allow_email_settings_to_businesses');

        //Check if prefered email setting is superadmin email settings
        if (!empty($is_superadmin_settings_allowed) && !empty($email_settings['use_superadmin_settings'])) {
            $email_settings['mail_driver'] = env('MAIL_DRIVER');
            $email_settings['mail_host'] = env('MAIL_HOST');
            $email_settings['mail_port'] = env('MAIL_PORT');
            $email_settings['mail_username'] = env('MAIL_USERNAME');
            $email_settings['mail_password'] = env('MAIL_PASSWORD');
            $email_settings['mail_encryption'] = env('MAIL_ENCRYPTION');
            $email_settings['mail_from_address'] = env('MAIL_FROM_ADDRESS');
        }

        $mail_driver = !empty($email_settings['mail_driver']) ? $email_settings['mail_driver'] : 'smtp';
        Config::set('mail.driver', $mail_driver);
        Config::set('mail.host', $email_settings['mail_host']);
        Config::set('mail.port', $email_settings['mail_port']);
        Config::set('mail.username', $email_settings['mail_username']);
        Config::set('mail.password', $email_settings['mail_password']);
        Config::set('mail.encryption', $email_settings['mail_encryption']);

        Config::set('mail.from.address', $email_settings['mail_from_address']);
        Config::set('mail.from.name', $email_settings['mail_from_name']);
    }

    /**
     * Automatically send notification to customer/supplier if enabled in the template setting
     *
     * @param  int  $business_id
     * @param  string  $notification_type
     * @param  obj  $transaction
     * @param  obj  $contact
     *
     * @return void
     */
    public function autoSendWhatsappNotification($business_id,$notification_type,$transaction,$contact,$templateName,$language='en_US')
    {
        $notification_template = NotificationTemplate::where('business_id', $business_id)
                ->where('template_for', $notification_type)
                ->first();
        
        $business = Business::findOrFail($business_id);
        
        $data['whatsapp_settings'] = $business->whatsapp_settings;

        if (!empty($notification_template)) {
            
            if (!empty($notification_template->auto_send_whatsapp_message)) {

               if($notification_type=='new_sale')
               {
                    $componentData = $this->whatsappMesaage($business_id, $transaction);
               }

               if($notification_type=='sell_return_genralstore')
               {
                    $componentData = $this->whatsappMesaageSellReturn($business_id, $transaction);
               }

               
                if(strlen($componentData[1]['parameters'][0]['text'])>900)
                {
                    $chunks = explode("||||",wordwrap($componentData[1]['parameters'][0]['text'],900,"||||",false));
                    $total = count($chunks);
                    if($total>1)
                    {
                        foreach($chunks as $page => $chunk)
                        {
                            $componentData[1]['parameters'][0]['text']=$chunk;
                            $contact = strlen($contact) <=10 ? '91'.$contact : $contact;
                            //Auto send sms
                            if (!empty($notification_template->auto_send_whatsapp_message)) {
                                $data['whatsapp_data']['messaging_product'] = "whatsapp";
                                $data['whatsapp_data']['to'] = $contact;
                                $data['whatsapp_data']['type'] = "template";
                                $data['whatsapp_data']['template']['name']=$templateName;
                                $data['whatsapp_data']['template']['language']['code']=$language;
                                $data['whatsapp_data']['template']['components']=$componentData;
                                
                            
                                if (!empty($contact)) {
                                    $this->sendMessageWhatsapp($data);
                                }
                            }
                        }
                    }

                }
                else
                {
                    $contact = strlen($contact) <=10 ? '91'.$contact : $contact;
                    //Auto send sms
                    if (!empty($notification_template->auto_send_whatsapp_message)) {
                        $data['whatsapp_data']['messaging_product'] = "whatsapp";
                        $data['whatsapp_data']['to'] = $contact;
                        $data['whatsapp_data']['type'] = "template";
                        $data['whatsapp_data']['template']['name']=$templateName;
                        $data['whatsapp_data']['template']['language']['code']=$language;
                        $data['whatsapp_data']['template']['components']=$componentData;
                        
                    
                        if (!empty($contact)) {
                            $this->sendMessageWhatsapp($data);
                        }
                    }
                }
            }
            
        }
    }

    public function autoSendOtpOnWhatsapp($mobileNo,$otp)
    {
        
        $business_id = session()->get('user.business_id');
        $business = Business::findOrFail($business_id);
        
        $data['whatsapp_settings'] = $business->whatsapp_settings;


        $data['whatsapp_data']['messaging_product'] = "whatsapp";
        $data['whatsapp_data']['to'] = $mobileNo;
        $data['whatsapp_data']['type'] = "template";
        $data['whatsapp_data']['template']['name']='otp';
        $data['whatsapp_data']['template']['language']['code']='en_US';
        $data['whatsapp_data']['template']['components'][0]['type']='body';
        $data['whatsapp_data']['template']['components'][0]['parameters'][0]['type']='text';
        $data['whatsapp_data']['template']['components'][0]['parameters'][0]['text']='*'.$otp.'*';

       
        if (!empty($mobileNo)) {
            $this->sendMessageWhatsapp($data);
        }
    }

    public function autoSendCustomRequirementsOnWhatsapp($mobileNo,$requirements,$departmentName,$departmentContact)
    {
        
        $business_id = session()->get('user.business_id');
        $business = Business::findOrFail($business_id);
        
        $data['whatsapp_settings'] = $business->whatsapp_settings;


        $data['whatsapp_data']['messaging_product'] = "whatsapp";
        $data['whatsapp_data']['to'] = $mobileNo;
        $data['whatsapp_data']['type'] = "template";
        $data['whatsapp_data']['template']['name']='custom_requirements';
        $data['whatsapp_data']['template']['language']['code']='gu';
        $data['whatsapp_data']['template']['components'][0]['type']='header';
        $data['whatsapp_data']['template']['components'][0]['parameters'][0]['type']='text';
        $data['whatsapp_data']['template']['components'][0]['parameters'][0]['text']=$departmentName;
        $data['whatsapp_data']['template']['components'][1]['type']='body';
        $data['whatsapp_data']['template']['components'][1]['parameters'][0]['type']='text';
        $data['whatsapp_data']['template']['components'][1]['parameters'][0]['text']=$requirements;
        $data['whatsapp_data']['template']['components'][1]['parameters'][1]['type']='text';
        $data['whatsapp_data']['template']['components'][1]['parameters'][1]['text']=$departmentContact;
        

        
        if (!empty($mobileNo)) {
        
            $this->sendMessageWhatsapp($data);
        }
    }

    public function sentOTPViaSMS($mobileNo,$otp,$customerName)
    {
        $client = new Client();
        $connected = @fsockopen("google.com", 80); 
        
        $request_data['username'] ='gs.mahotsav';
        $request_data['sendername'] ='PSMIOO';
        $request_data['smstype'] ='TRANS';
        $request_data['numbers'] =$mobileNo;
        $request_data['apikey'] ='de3c9592-3cf4-454b-8355-ee714fd96356';
        $request_data['message'] ='Jay Swaminarayan '.$customerName.'. Your OTP is '.$otp.'. Please do not share with anyone. - General Store';
        

        if ($connected){
            if(strlen($mobileNo)==12)
            {
                $response = $client->post('http://sms.cubetechsolutions.in/sendSMS', [
                    'form_params' => $request_data
                ]);
            }
            fclose($connected);
        }
    }
}
