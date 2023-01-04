<?php

namespace App\Http\Controllers;

use App\User;
use App\Utils\NotificationUtil;
use App\Utils\Util;
use Illuminate\Http\Request;

class LoginOtpController extends Controller
{
    public function __construct(Util $util,NotificationUtil $notificationUtil)
    {
        $this->util = $util;
        $this->notificationUtil = $notificationUtil;
    }

    public function sendOtp()
    {
        $user = \Auth::user();
        if(!empty($user->contact_number))
        {
            $otp = rand(11111,99999);
            $input['otp']=$otp;
            //$user = User::find($user_id);
            $user->update($input);

            $data['sms_body'] ='*General Store* \r\nOTP is '.$otp;
            $data['mobile_number'] = $user->contact_number;
            $this->notificationUtil->autoSendOtpOnWhatsapp($user->contact_number,$otp);
            $this->notificationUtil->sentOTPViaSMS($user->contact_number,$otp,$user->first_name);
            $output = ['success' => 1,'msg' => __('messages.otp_sent_sucessfully')];
            return redirect('login_otp')->with('status', $output);
        }
        
        $output = ['success' => 0,'msg' => __('messages.otp_not_sent_contcat_store')];
        return redirect('login_otp')->with('status', $output);
    }
     /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function loginOtp()
    {   
        return view('user.login_otp');
    }

    public function loginOtpVerify(Request $request)
    {
        $user = \Auth::user();
       
        if(strcmp($user->otp,$request->otp)==0)
        {
            $user->otp=1;
            $user->save();
            $selected_contacts_array = [];
            if(count($user->contactAccess)) 
            {       
                foreach($user->contactAccess as $contact) 
                {
                    $selected_contacts_array[] = $contact->name; 
                                
                }
                        
            }
            if(!empty($selected_contacts_array))
            {
                return redirect('department_home');
            }
            else
            {
                return redirect('home');
            }
        }
        else
        {
            $output = ['success' => 0,'msg' => __('messages.otp_not_match')];
            return redirect('login_otp')->with('status', $output);
        }
    }
}