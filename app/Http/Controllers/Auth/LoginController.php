<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;

use App\Utils\BusinessUtil;
use Validator;
class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * All Utils instance.
     *
     */
    protected $businessUtil;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    // protected $redirectTo = '/home';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(BusinessUtil $businessUtil)
    {
        $this->middleware('guest')->except('logout');
        $this->businessUtil = $businessUtil;
    }

    /**
     * Change authentication from email to username
     *
     * @return void
     */
    public function username()
    {
        return 'username';
    }

    public function logout()
    {
        request()->session()->flush();
        \Auth::logout();
        return redirect('/login');
    }

    /**
     * The user has been authenticated.
     * Check if the business is active or not.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  mixed  $user
     * @return mixed
     */
    protected function authenticated(Request $request, $user)
    {
        if (!$user->business->is_active) {
            \Auth::logout();
            return redirect('/login')
              ->with(
                  'status',
                  ['success' => 0, 'msg' => __('lang_v1.business_inactive')]
              );
        } elseif ($user->status != 'active') {
            \Auth::logout();
            return redirect('/login')
              ->with(
                  'status',
                  ['success' => 0, 'msg' => __('lang_v1.user_inactive')]
              );
        }
    }

    protected function redirectTo()
    {
        $user = \Auth::user();
        $selected_contacts_array = [];
        if(count($user->contactAccess)) 
        {       
            foreach($user->contactAccess as $contact) 
            {
                $selected_contacts_array[] = $contact->name; 
                            
            }
                       
        }
        
        if (!$user->can('dashboard.data') && $user->can('sell.create')) {
            if($user->login_with_otp)
            {
                return '/sendotp';
            }
            return '/pos/create';
        }
        if(!empty($selected_contacts_array))
        {
            if($user->login_with_otp)
            {
                return '/sendotp';
            }
            return '/department_home';
        }
        else
        {
            if($user->login_with_otp)
            {
                return '/sendotp';
            }
            return '/home';
        }
        
    }

    protected function validator(array $data)
    {
        return Validator::make($data, [
            'g-recaptcha-response' => 'recaptcha',
        ]);
    }

    protected function validateLogin(Request $request)
    {
        $this->validate($request, [
            // 'g-recaptcha-response' => 'recaptcha',
            'captcha' => 'required|captcha',
        ]);
        // adjust as needed
    }
}
