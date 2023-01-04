<?php

namespace App\Http\Controllers;

use App\Business;
use App\BusinessLocation;
use App\Contact;
use App\CustomRequirements;
use App\Utils\NotificationUtil;
use App\Utils\Util;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class CustomRequirementsController extends Controller
{
    public function __construct(Util $util,NotificationUtil $notificationUtil)
    {
        $this->util = $util;
        $this->notificationUtil = $notificationUtil;
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (!auth()->user()->can('custom_req.view')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');

            $customRequirements = CustomRequirements::where('business_id', $business_id)
                                ->select(['business_id', 'contact_id','id','requirements']);
            

            return Datatables::of($customRequirements)
                    ->addColumn(
                        'action',
                        '
                            <a href="{{action(\'CustomRequirementsController@show\', [$id])}}" class="btn btn-xs btn-info"><i class="fa fa-eye"></i> @lang("messages.view")</button>
                       '
                    )
                    ->editColumn('contact_id', function ($row) {
                            $customer_name = Contact::where('id',$row->contact_id)->pluck('name')->first();
                            return $customer_name;
                        }
                    )
                    // ->removeColumn('id')
                    // ->rawColumns([2])
                    ->rawColumns(['business_id', 'contact_id', 'id','action','requirements'])
                    ->make(true);
        }
        return view('custom_requirements.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        if (!auth()->user()->can('custom_req.create')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $business_locations = BusinessLocation::forDropdown($business_id);
        $customers = Contact::customersDropdown($business_id);

        $business_locations = BusinessLocation::forDropdown($business_id, false, true);
        $bl_attributes = $business_locations['attributes'];
        $business_locations = $business_locations['locations'];

        $default_location = null;
        if (count($business_locations) == 1) {
            foreach ($business_locations as $id => $name) {
                $default_location = $id;
            }
        }
        $defaultCustomerId='';
        if(count(auth()->user()->contactAccess)) 
        {       
            foreach(auth()->user()->contactAccess as $contact) 
            {
                $selected_contacts_array[] = $contact->id;
                $selected_contacts_array_name[]=$contact->name.' '.'('.$contact->mobile.')';
            }
            if(count($selected_contacts_array)==1)
            {
                $defaultCustomerId = $selected_contacts_array[0];
                $defaultCustomerName= $selected_contacts_array_name[0];
            }
            
            $departmentUser = 1;
                       
        }
        
        return view('custom_requirements.create')->with(compact('business_locations','customers','business_id','default_location','defaultCustomerId'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        
        if (!auth()->user()->can('custom_req.create')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $input = $request->only(['contact_id', 'requirements']);
            $input['business_id'] = $request->session()->get('user.business_id');
            $business_id = $request->session()->get('user.business_id');

            $business = Business::findOrFail($business_id);

            $custom_req = json_decode($business->custom_req,true);
            
            $customer_group = CustomRequirements::create($input);
            
            if(!empty($custom_req['notify_mob_no']))
            {
                $mobiles = explode(',',$custom_req['notify_mob_no']);
                $customer_name = Contact::where('id',$input['contact_id'])->select(['name','contact_id'])->first()->toArray();
                
                $requirements  = $input['requirements'];
                foreach($mobiles as $mobile) {    
                    $this->notificationUtil->autoSendCustomRequirementsOnWhatsapp($mobile,$requirements,$customer_name['name'],$customer_name['contact_id']);
                    //autoSendCustomRequirementsOnWhatsapp($mobileNo,$requirements,$departmentContact,$departmentName)
                }
                
            }

            
            $output = ['success' => true,
                            'data' => $customer_group,
                            'msg' => __("lang_v1.success")
                        ];
        } catch (\Exception $e) {
            
            $output = ['success' => false,
                            'msg' => __("messages.something_went_wrong")
                        ];
        }

        return redirect('department_home');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $business_id = request()->session()->get('user.business_id');
        $business_locations = BusinessLocation::forDropdown($business_id);
        $customers = Contact::customersDropdown($business_id);

        $requirementData = CustomRequirements::where('business_id', $business_id)
                                ->where('id', $id)
                                ->first();
        
        return view('custom_requirements.view')->with(compact('business_locations','customers','requirementData'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
