<?php
/* LICENSE: This source file belongs to The Web Fosters. The customer
 * is provided a licence to use it.
 * Permission is hereby granted, to any person obtaining the licence of this
 * software and associated documentation files (the "Software"), to use the
 * Software for personal or business purpose ONLY. The Software cannot be
 * copied, published, distribute, sublicense, and/or sell copies of the
 * Software, and to permit persons to whom the Software is furnished to do so,
 * subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. THE AUTHOR CAN FIX
 * ISSUES ON INTIMATION. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS
 * BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
 * OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH
 * THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 *
 * @author     The Web Fosters <thewebfosters@gmail.com>
 * @owner      The Web Fosters <thewebfosters@gmail.com>
 * @copyright  2018 The Web Fosters
 * @license    As attached in zip file.
 */

namespace App\Http\Controllers;

use App\Account;
use App\AccountTransaction;
use App\Brands;
use App\Business;
use App\BusinessLocation;
use App\Category;
use App\Contact;
use App\CustomerGroup;
use App\Events\DepartmentRequirement;
use App\Events\SendMessage;
use App\Events\SendMessageCategory;
use App\Media;
use App\Product;
use App\SellingPriceGroup;
use App\TaxRate;
use App\Transaction;
use App\TransactionSellLine;
use App\User;

use App\Utils\BusinessUtil;
use App\Utils\CashRegisterUtil;
use App\Utils\ContactUtil;

use App\Utils\ModuleUtil;
use App\Utils\NotificationUtil;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

use Yajra\DataTables\Facades\DataTables;

class SellPosController extends Controller
{
    /**
     * All Utils instance.
     *
     */
    protected $contactUtil;
    protected $productUtil;
    protected $businessUtil;
    protected $transactionUtil;
    protected $cashRegisterUtil;
    protected $moduleUtil;
    protected $notificationUtil;

    /**
     * Constructor
     *
     * @param ProductUtils $product
     * @return void
     */
    public function __construct(
        ContactUtil $contactUtil,
        ProductUtil $productUtil,
        BusinessUtil $businessUtil,
        TransactionUtil $transactionUtil,
        CashRegisterUtil $cashRegisterUtil,
        ModuleUtil $moduleUtil,
        NotificationUtil $notificationUtil
    ) {
        $this->contactUtil = $contactUtil;
        $this->productUtil = $productUtil;
        $this->businessUtil = $businessUtil;
        $this->transactionUtil = $transactionUtil;
        $this->cashRegisterUtil = $cashRegisterUtil;
        $this->moduleUtil = $moduleUtil;
        $this->notificationUtil = $notificationUtil;

        $this->dummyPaymentLine = ['method' => 'cash', 'amount' => 0, 'note' => '', 'card_transaction_number' => '', 'card_number' => '', 'card_type' => '', 'card_holder_name' => '', 'card_month' => '', 'card_year' => '', 'card_security' => '', 'cheque_number' => '', 'bank_account_number' => '',
        'is_return' => 0, 'transaction_no' => ''];
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        
        if (!auth()->user()->can('sell.view') && !auth()->user()->can('sell.create')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        $business_locations = BusinessLocation::forDropdown($business_id, false);
        $customers = Contact::customersDropdown($business_id, false,false);

        $userId = '';
        $departmentUser=0;
        
        if(count(auth()->user()->contactAccess) >0) 
        {
            $departmentUser=1;
        }
        if (auth()->user()->can('sell.view_own_sell') && auth()->user()->roles->first()->name != 'Admin#1')
        {
            $users = User::allUsersDropdown($business_id, false,session()->get('user.id'));
            $userId = session()->get('user.id');
        }
        else
        {
            $users = User::allUsersDropdown($business_id, false);
        }
        

        return view('sale_pos.index')->with(compact('business_locations', 'customers','users','userId','departmentUser'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        if (!auth()->user()->can('sell.create')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        //Check if subscribed or not, then check for users quota
        if (!$this->moduleUtil->isSubscribed($business_id)) {
            return $this->moduleUtil->expiredResponse(action('HomeController@index'));
        } elseif (!$this->moduleUtil->isQuotaAvailable('invoices', $business_id)) {
            return $this->moduleUtil->quotaExpiredResponse('invoices', $business_id, action('SellPosController@index'));
        }
        
        //Check if there is a open register, if no then redirect to Create Register screen.
        if ($this->cashRegisterUtil->countOpenedRegister() == 0 ) {
            return redirect()->action('CashRegisterController@create');
        }

        $walk_in_customer = $this->contactUtil->getWalkInCustomer($business_id);
        
        $business_details = $this->businessUtil->getDetails($business_id);
        
        $taxes = TaxRate::forBusinessDropdown($business_id, true, true);
        $payment_types = $this->productUtil->payment_types();

        $payment_lines[] = $this->dummyPaymentLine;

        $business_locations = BusinessLocation::forDropdown($business_id, false, true);
        $bl_attributes = $business_locations['attributes'];
        $business_locations = $business_locations['locations'];

        $default_location = null;
        if (count($business_locations) == 1) {
            foreach ($business_locations as $id => $name) {
                $default_location = $id;
            }
        }

        //Shortcuts
        $shortcuts = json_decode($business_details->keyboard_shortcuts, true);
        $pos_settings = empty($business_details->pos_settings) ? $this->businessUtil->defaultPosSettings() : json_decode($business_details->pos_settings, true);
        $genral_store_settings = empty($business_details->genral_store_settings) ? $this->businessUtil->defaultGenralStoreSettings() : json_decode($business_details->genral_store_settings, true);
        
        $commsn_agnt_setting = $business_details->sales_cmsn_agnt;
        $commission_agent = [];
        if ($commsn_agnt_setting == 'user') {
            $commission_agent = User::forDropdown($business_id, false);
        } elseif ($commsn_agnt_setting == 'cmsn_agnt') {
            $commission_agent = User::saleCommissionAgentsDropdown($business_id, false);
        }
        
        //If brands, category are enabled then send else false.
        $categories = (request()->session()->get('business.enable_category') == 1) ? Category::catAndSubCategories($business_id) : false;
        $brands = (request()->session()->get('business.enable_brand') == 1) ? Brands::where('business_id', $business_id)
                    ->pluck('name', 'id')
                    ->prepend(__('lang_v1.all_brands'), 'all') : false;

        $change_return = $this->dummyPaymentLine;

        $types = [];
        if (auth()->user()->can('supplier.create')) {
            $types['supplier'] = __('report.supplier');
        }
        if (auth()->user()->can('customer.create')) {
            $types['customer'] = __('report.customer');
        }
        if (auth()->user()->can('supplier.create') && auth()->user()->can('customer.create')) {
            $types['both'] = __('lang_v1.both_supplier_customer');
        }
        $customer_groups = CustomerGroup::forDropdown($business_id);

        //Accounts
        $accounts = [];
        if ($this->moduleUtil->isModuleEnabled('account')) {
            $accounts = Account::forDropdown($business_id, true, false);
        }
        //Selling Price Group Dropdown
        $price_groups = SellingPriceGroup::forDropdown($business_id);

        //Department user

        $user = \Auth::user();
        $selected_contacts_array = [];
        $departmentUser = 0;
        $defaultCustomerId = '';
        $defaultCustomerName = '';
        $selected_contacts_array_name = [];
        if(count($user->contactAccess)) 
        {       
            foreach($user->contactAccess as $contact) 
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

        $customers = Contact::customersDropdownWithoutDamage($business_id);
        $suppliers = Contact::suppliersDropdown($business_id);

        
        return view('sale_pos.create')
            ->with(compact(
                'business_details',
                'taxes',
                'payment_types',
                'walk_in_customer',
                'payment_lines',
                'business_locations',
                'bl_attributes',
                'default_location',
                'shortcuts',
                'commission_agent',
                'categories',
                'brands',
                'pos_settings',
                'genral_store_settings',
                'change_return',
                'types',
                'customer_groups',
                'accounts',
                'price_groups',
                'selected_contacts_array',
                'departmentUser',
                'defaultCustomerId',
                'defaultCustomerName',
                'customers',
                'suppliers',
                'user'
            ));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        
        if (!auth()->user()->can('sell.create') && !auth()->user()->can('direct_sell.access')) {
            abort(403, 'Unauthorized action.');
        }

        $is_direct_sale = false;
        if (!empty($request->input('is_direct_sale'))) {
            $is_direct_sale = true;
        }

        //Check if there is a open register, if no then redirect to Create Register screen.
        if (!$is_direct_sale && $this->cashRegisterUtil->countOpenedRegister() == 0 ) {
            return redirect()->action('CashRegisterController@create');
        }

        try {
            $input = $request->except('_token');

            $storeProduct = [];
            $storeProduct2 = [];
            foreach($input['products'] as $productData)
            {
                if($productData['category_id'] == 21)
                {
                    $storeProduct2[] = $productData;
                }
                else
                {
                    $storeProduct[] = $productData;
                }
            }
            if(!empty($storeProduct))
            {
                $input['products'] = $storeProduct;
                //Check Customer credit limit
                $is_credit_limit_exeeded = $this->transactionUtil->isCustomerCreditLimitExeeded($input);

                if ($is_credit_limit_exeeded !== false) {
                    $credit_limit_amount = $this->transactionUtil->num_f($is_credit_limit_exeeded, true);
                    $output = ['success' => 0,
                                'msg' => __('lang_v1.cutomer_credit_limit_exeeded', ['credit_limit' => $credit_limit_amount])
                            ];
                    if (!$is_direct_sale) {
                        return $output;
                    } else {
                        return redirect()
                            ->action('SellController@index')
                            ->with('status', $output);
                    }
                }

                $input['is_quotation'] = 0;
                //status is send as quotation from Add sales screen.
                if ($input['status'] == 'quotation') {
                    $input['status'] = 'draft';
                    $input['is_quotation'] = 1;
                    
                }

                if (!empty($input['products'])) {
                    $business_id = $request->session()->get('user.business_id');

                    //Check if subscribed or not, then check for users quota
                    if (!$this->moduleUtil->isSubscribed($business_id)) {
                        return $this->moduleUtil->expiredResponse();
                    } elseif (!$this->moduleUtil->isQuotaAvailable('invoices', $business_id)) {
                        return $this->moduleUtil->quotaExpiredResponse('invoices', $business_id, action('SellPosController@index'));
                    }
            
                    $user_id = $request->session()->get('user.id');
                    $commsn_agnt_setting = $request->session()->get('business.sales_cmsn_agnt');

                    $discount = ['discount_type' => $input['discount_type'],
                                    'discount_amount' => $input['discount_amount']
                                ];
                    $invoice_total = $this->productUtil->calculateInvoiceTotal($input['products'], $input['tax_rate_id'], $discount);

                    DB::beginTransaction();

                    if (empty($request->input('transaction_date'))) {
                        $input['transaction_date'] =  \Carbon::now();
                    } else {
                        $input['transaction_date'] = $this->productUtil->uf_date($request->input('transaction_date'), true);
                    }
    
                    
                    if ($is_direct_sale) {
                        $input['is_direct_sale'] = 1;
                    }

                    $input['commission_agent'] = !empty($request->input('commission_agent')) ? $request->input('commission_agent') : null;
                    if ($commsn_agnt_setting == 'logged_in_user') {
                        $input['commission_agent'] = $user_id;
                    }

                    if (isset($input['exchange_rate']) && $this->transactionUtil->num_uf($input['exchange_rate']) == 0) {
                        $input['exchange_rate'] = 1;
                    }

                    //Customer group details
                    $contact_id = $request->get('contact_id', null);
                    $cg = $this->contactUtil->getCustomerGroup($business_id, $contact_id);
                    $input['customer_group_id'] = (empty($cg) || empty($cg->id)) ? null : $cg->id;

                    //set selling price group id
                    if ($request->has('price_group')) {
                        $input['selling_price_group_id'] = $request->input('price_group');
                    }

                    $input['is_suspend'] = isset($input['is_suspend']) && 1 == $input['is_suspend']  ? 1 : 0;
                    if ($input['is_suspend']) {
                        $input['sale_note'] = !empty($input['additional_notes']) ? $input['additional_notes'] : null;
                    }

                    //Generate reference number
                    if (!empty($input['is_recurring'])) {
                        //Update reference count
                        $ref_count = $this->transactionUtil->setAndGetReferenceCount('subscription');
                        $input['subscription_no'] = $this->transactionUtil->generateReferenceNumber('subscription', $ref_count);
                    }

                    // fetch user name
                    if (!empty($input['return_seva_by_customer_id'])) {
                        //Update reference count
                        $customer_details = Contact::find($input['return_seva_by_customer_id']);
                        $input['return_seva_by_customer_name'] = $customer_details->name;
                    }

                    $transaction = $this->transactionUtil->createSellTransaction($business_id, $input, $invoice_total, $user_id);

                    $this->transactionUtil->createOrUpdateSellLines($transaction, $input['products'], $input['location_id']);
                    
                    if (!$is_direct_sale) {
                        //Add change return
                        $change_return = $this->dummyPaymentLine;
                        $change_return['amount'] = $input['change_return'];
                        $change_return['is_return'] = 1;
                        $input['payment'][] = $change_return;
                    }

                    if (!$transaction->is_suspend && !empty($input['payment'])) {
                        $this->transactionUtil->createOrUpdatePaymentLines($transaction, $input['payment']);
                    }

                    $update_transaction = false;
                    if ($this->transactionUtil->isModuleEnabled('tables')) {
                        $transaction->res_table_id = request()->get('res_table_id');
                        $update_transaction = true;
                    }
                    if ($this->transactionUtil->isModuleEnabled('service_staff')) {
                        $transaction->res_waiter_id = request()->get('res_waiter_id');
                        $update_transaction = true;
                    }
                    if ($update_transaction) {
                        $transaction->save();
                    }

                    //Check for final and do some processing.
                    if ($input['status'] == 'final') {
                        //update product stock
                        foreach ($input['products'] as $product) {
                            if ($product['enable_stock']) 
                            {

                                $oldData = $this->productUtil->getDetailsFromVariation($product['variation_id'],$business_id,$input['location_id']);

                                if(is_object($oldData))
                                {
                                    $product['quantity']=$this->productUtil->num_uf($product['quantity']);
                                    if($oldData->qty_available<$product['quantity'])
                                    {
                                        DB::rollBack();
                                        $output = ['success' => 0,'msg' => trans($oldData->product_name."Product Out of stock"),'product_id'=>$oldData->product_id];
                                        return $output;
                                    }
                                    else
                                    {
                                        
                                        $decrease_qty = $this->productUtil->num_uf($product['quantity']);
                                        if (!empty($product['base_unit_multiplier'])) {
                                            $decrease_qty = $decrease_qty * $product['base_unit_multiplier'];
                                        }
                                        
                                        $this->productUtil->decreaseProductQuantity(
                                            $product['product_id'],
                                            $product['variation_id'],
                                            $input['location_id'],
                                            $decrease_qty
                                        );
                                    }
                                }
                                else
                                {
                                    DB::rollBack();
                                    $output = ['success' => 0,'msg' => trans("Product Out of stock"),'product_id'=>$product['product_id']];
                                    return $output;
                                }

                                // $decrease_qty = $this->productUtil->num_uf($product['quantity']);
                                // if (!empty($product['base_unit_multiplier'])) {
                                //     $decrease_qty = $decrease_qty * $product['base_unit_multiplier'];
                                // }
                                
                                // $this->productUtil->decreaseProductQuantity(
                                //     $product['product_id'],
                                //     $product['variation_id'],
                                //     $input['location_id'],
                                //     $decrease_qty
                                // );
                            }
                        }

                        //Add payments to Cash Register
                        if (!$is_direct_sale && !$transaction->is_suspend && !empty($input['payment'])) {
                            $this->cashRegisterUtil->addSellPayments($transaction, $input['payment']);
                        }

                        //Update payment status
                        $this->transactionUtil->updatePaymentStatus($transaction->id, $transaction->final_total);

                        //Allocate the quantity from purchase and add mapping of
                        //purchase & sell lines in
                        //transaction_sell_lines_purchase_lines table
                        $business_details = $this->businessUtil->getDetails($business_id);
                        $pos_settings = empty($business_details->pos_settings) ? $this->businessUtil->defaultPosSettings() : json_decode($business_details->pos_settings, true);

                        $business = ['id' => $business_id,
                                        'accounting_method' => $request->session()->get('business.accounting_method'),
                                        'location_id' => $input['location_id'],
                                        'pos_settings' => $pos_settings
                                    ];
                        //$this->transactionUtil->mapPurchaseSell($business, $transaction->sell_lines, 'purchase');

                        //Auto send notification
                        if(!empty($input['return_seva_by_customer_id'])){
                            if(isset($customer_details->mobile)){
                                $this->notificationUtil->autoSendWhatsappNotification($business_id, 'sell_return_genralstore', $transaction, $customer_details->mobile,'general_store_return_updated','gu');
                            }
                            if(isset($transaction->mobile)){
                                $this->notificationUtil->autoSendWhatsappNotification($business_id, 'sell_return_genralstore', $transaction, $transaction->mobile,'general_store_return_updated','gu');
                            }
                        }else{
                        // $this->notificationUtil->autoSendNotification($business_id, 'new_sale', $transaction, $transaction->contact);
                        $this->notificationUtil->autoSendWhatsappNotification($business_id, 'new_sale', $transaction, $transaction->contact->mobile,'general_store_updated','gu');
                        }
                        if(!is_null($transaction->contact->alternate_number))
                        {
                            $this->notificationUtil->autoSendWhatsappNotification($business_id, 'new_sale', $transaction, $transaction->contact->alternate_number,'general_store_updated','gu');
                        }
                        event(new \App\Events\RemoveCategoryList($input['random_no']));
                    }

                    //Set Module fields
                    if (!empty($input['has_module_data'])) {
                        $this->moduleUtil->getModuleData('after_sale_saved', ['transaction' => $transaction, 'input' => $input]);
                    }

                    Media::uploadMedia($business_id, $transaction, $request, 'documents');
                    
                    DB::commit();
                    
                    $msg = '';
                    $receipt = '';
                    if ($input['status'] == 'draft' && $input['is_quotation'] == 0) {
                        $msg = trans("sale.draft_added");
                    } elseif ($input['status'] == 'draft' && $input['is_quotation'] == 1) {
                        $msg = trans("lang_v1.quotation_added");
                        $demandListHtml = $this->checkProductExistInStore($transaction->id,$business_id);
                    
                        // $allowed_hosts = array('gs.esatsang.net');
                        
                        // if (isset($_SERVER['HTTP_HOST']) && in_array($_SERVER['HTTP_HOST'], $allowed_hosts)) {
                            if(!empty($demandListHtml['store']))
                            {
                                event(new \App\Events\SendMessage($demandListHtml['store']));
                            }
                            if(!empty($demandListHtml['store2']))
                            {
                                event(new \App\Events\SendMessageCat2($demandListHtml['store2']));
                            }
                            
                        // }
                        
                        if (!$is_direct_sale) {
                            $receipt = $this->receiptContent($business_id, $input['location_id'], $transaction->id);
                        } else {
                            $receipt = '';
                        }
                        $receipt = '';
                        //event(new SendMessage());
                        
                    } elseif ($input['status'] == 'final') {
                        if (empty($input['sub_type'])) {
                            $msg = trans("sale.pos_sale_added");
                            if (!$is_direct_sale && !$transaction->is_suspend) {
                                $receipt = $this->receiptContent($business_id, $input['location_id'], $transaction->id);
                            } else {
                                $receipt = '';
                            }
                        } else {
                            $msg = trans("sale.pos_sale_added");
                            $receipt = '';
                        }
                        
                    }

                    $output = ['success' => 1, 'msg' => $msg, 'receipt' => $receipt ];
                } else {
                    $output = ['success' => 0,
                                'msg' => trans("messages.something_went_wrong")
                            ];
                }
            }
            if(!empty($storeProduct2))
            {
                $input = $request->except('_token');
                $input['products'] = $storeProduct2;
                
                //Check Customer credit limit
                $is_credit_limit_exeeded = $this->transactionUtil->isCustomerCreditLimitExeeded($input);

                if ($is_credit_limit_exeeded !== false) {
                    $credit_limit_amount = $this->transactionUtil->num_f($is_credit_limit_exeeded, true);
                    $output = ['success' => 0,
                                'msg' => __('lang_v1.cutomer_credit_limit_exeeded', ['credit_limit' => $credit_limit_amount])
                            ];
                    if (!$is_direct_sale) {
                        return $output;
                    } else {
                        return redirect()
                            ->action('SellController@index')
                            ->with('status', $output);
                    }
                }

                $input['is_quotation'] = 0;
                
                //status is send as quotation from Add sales screen.
                if ($input['status'] == 'quotation') {
                    $input['status'] = 'draft';
                    $input['is_quotation'] = 1;
                }

                if (!empty($input['products'])) {
                    $business_id = $request->session()->get('user.business_id');

                    //Check if subscribed or not, then check for users quota
                    if (!$this->moduleUtil->isSubscribed($business_id)) {
                        return $this->moduleUtil->expiredResponse();
                    } elseif (!$this->moduleUtil->isQuotaAvailable('invoices', $business_id)) {
                        return $this->moduleUtil->quotaExpiredResponse('invoices', $business_id, action('SellPosController@index'));
                    }
            
                    $user_id = $request->session()->get('user.id');
                    $commsn_agnt_setting = $request->session()->get('business.sales_cmsn_agnt');

                    $discount = ['discount_type' => $input['discount_type'],
                                    'discount_amount' => $input['discount_amount']
                                ];
                    $invoice_total = $this->productUtil->calculateInvoiceTotal($input['products'], $input['tax_rate_id'], $discount);

                    DB::beginTransaction();

                    if (empty($request->input('transaction_date'))) {
                        $input['transaction_date'] =  \Carbon::now();
                    } else {
                        $input['transaction_date'] = $this->productUtil->uf_date($request->input('transaction_date'), true);
                    }
    
                    
                    if ($is_direct_sale) {
                        $input['is_direct_sale'] = 1;
                    }

                    $input['commission_agent'] = !empty($request->input('commission_agent')) ? $request->input('commission_agent') : null;
                    if ($commsn_agnt_setting == 'logged_in_user') {
                        $input['commission_agent'] = $user_id;
                    }

                    if (isset($input['exchange_rate']) && $this->transactionUtil->num_uf($input['exchange_rate']) == 0) {
                        $input['exchange_rate'] = 1;
                    }

                    //Customer group details
                    $contact_id = $request->get('contact_id', null);
                    $cg = $this->contactUtil->getCustomerGroup($business_id, $contact_id);
                    $input['customer_group_id'] = (empty($cg) || empty($cg->id)) ? null : $cg->id;

                    //set selling price group id
                    if ($request->has('price_group')) {
                        $input['selling_price_group_id'] = $request->input('price_group');
                    }

                    $input['is_suspend'] = isset($input['is_suspend']) && 1 == $input['is_suspend']  ? 1 : 0;
                    if ($input['is_suspend']) {
                        $input['sale_note'] = !empty($input['additional_notes']) ? $input['additional_notes'] : null;
                    }

                    //Generate reference number
                    if (!empty($input['is_recurring'])) {
                        //Update reference count
                        $ref_count = $this->transactionUtil->setAndGetReferenceCount('subscription');
                        $input['subscription_no'] = $this->transactionUtil->generateReferenceNumber('subscription', $ref_count);
                    }
                    
                    $transaction = $this->transactionUtil->createSellTransaction($business_id, $input, $invoice_total, $user_id);

                    $this->transactionUtil->createOrUpdateSellLines($transaction, $input['products'], $input['location_id']);
                    
                    if (!$is_direct_sale) {
                        //Add change return
                        $change_return = $this->dummyPaymentLine;
                        $change_return['amount'] = $input['change_return'];
                        $change_return['is_return'] = 1;
                        $input['payment'][] = $change_return;
                    }

                    if (!$transaction->is_suspend && !empty($input['payment'])) {
                        $this->transactionUtil->createOrUpdatePaymentLines($transaction, $input['payment']);
                    }

                    $update_transaction = false;
                    if ($this->transactionUtil->isModuleEnabled('tables')) {
                        $transaction->res_table_id = request()->get('res_table_id');
                        $update_transaction = true;
                    }
                    if ($this->transactionUtil->isModuleEnabled('service_staff')) {
                        $transaction->res_waiter_id = request()->get('res_waiter_id');
                        $update_transaction = true;
                    }
                    if ($update_transaction) {
                        $transaction->save();
                    }

                    //Check for final and do some processing.
                    if ($input['status'] == 'final') {
                        //update product stock
                        foreach ($input['products'] as $product) {
                            if ($product['enable_stock']) 
                            {

                                $oldData = $this->productUtil->getDetailsFromVariation($product['variation_id'],$business_id,$input['location_id']);
                                
                                if(is_object($oldData))
                                {
                                    $product['quantity']=$this->productUtil->num_uf($product['quantity']);
                                    if($oldData->qty_available<$product['quantity'])
                                    {
                                        DB::rollBack();
                                        $output = ['success' => 0,'msg' => trans($oldData->product_name."Product Out of stock"),'product_id'=>$oldData->product_id];
                                        return $output;
                                    }
                                    else
                                    {
                                        $decrease_qty = $this->productUtil->num_uf($product['quantity']);
                                        if (!empty($product['base_unit_multiplier'])) {
                                            $decrease_qty = $decrease_qty * $product['base_unit_multiplier'];
                                        }
                                        
                                        $this->productUtil->decreaseProductQuantity(
                                            $product['product_id'],
                                            $product['variation_id'],
                                            $input['location_id'],
                                            $decrease_qty
                                        );
                                    }
                                }
                                else
                                {
                                    DB::rollBack();
                                    $output = ['success' => 0,'msg' => trans("Product Out of stock"),'product_id'=>$product['product_id']];
                                    return $output;
                                }

                                // $decrease_qty = $this->productUtil->num_uf($product['quantity']);
                                // if (!empty($product['base_unit_multiplier'])) {
                                //     $decrease_qty = $decrease_qty * $product['base_unit_multiplier'];
                                // }
                                
                                // $this->productUtil->decreaseProductQuantity(
                                //     $product['product_id'],
                                //     $product['variation_id'],
                                //     $input['location_id'],
                                //     $decrease_qty
                                // );
                            }
                        }

                        //Add payments to Cash Register
                        if (!$is_direct_sale && !$transaction->is_suspend && !empty($input['payment'])) {
                            $this->cashRegisterUtil->addSellPayments($transaction, $input['payment']);
                        }

                        //Update payment status
                        $this->transactionUtil->updatePaymentStatus($transaction->id, $transaction->final_total);

                        //Allocate the quantity from purchase and add mapping of
                        //purchase & sell lines in
                        //transaction_sell_lines_purchase_lines table
                        $business_details = $this->businessUtil->getDetails($business_id);
                        $pos_settings = empty($business_details->pos_settings) ? $this->businessUtil->defaultPosSettings() : json_decode($business_details->pos_settings, true);

                        $business = ['id' => $business_id,
                                        'accounting_method' => $request->session()->get('business.accounting_method'),
                                        'location_id' => $input['location_id'],
                                        'pos_settings' => $pos_settings
                                    ];
                        //$this->transactionUtil->mapPurchaseSell($business, $transaction->sell_lines, 'purchase');

                        //Auto send notification
                        // $this->notificationUtil->autoSendNotification($business_id, 'new_sale', $transaction, $transaction->contact);
                        $this->notificationUtil->autoSendWhatsappNotification($business_id, 'new_sale', $transaction, $transaction->contact->mobile,'general_store_updated','gu');
                        if(!is_null($transaction->contact->alternate_number))
                        {
                            $this->notificationUtil->autoSendWhatsappNotification($business_id, 'new_sale', $transaction, $transaction->contact->alternate_number,'general_store_updated','gu');
                        }
                        event(new \App\Events\RemoveCategoryList($input['random_no']));
                    }

                    //Set Module fields
                    if (!empty($input['has_module_data'])) {
                        $this->moduleUtil->getModuleData('after_sale_saved', ['transaction' => $transaction, 'input' => $input]);
                    }

                    Media::uploadMedia($business_id, $transaction, $request, 'documents');
                    
                    DB::commit();
                    
                    $msg = '';
                    $receipt = '';
                    if ($input['status'] == 'draft' && $input['is_quotation'] == 0) {
                        $msg = trans("sale.draft_added");
                    } elseif ($input['status'] == 'draft' && $input['is_quotation'] == 1) {
                        $msg = trans("lang_v1.quotation_added");
                        $demandListHtml = $this->checkProductExistInStore($transaction->id,$business_id);
                    
                        // $allowed_hosts = array('gs.esatsang.net');
                        
                        // if (isset($_SERVER['HTTP_HOST']) && in_array($_SERVER['HTTP_HOST'], $allowed_hosts)) {
                            if(!empty($demandListHtml['store']))
                            {
                                event(new \App\Events\SendMessage($demandListHtml['store']));
                            }
                            if(!empty($demandListHtml['store2']))
                            {
                                event(new \App\Events\SendMessageCat2($demandListHtml['store2']));
                            }
                            
                        // }
                        
                        if (!$is_direct_sale) {
                            $receipt = $this->receiptContent($business_id, $input['location_id'], $transaction->id);
                        } else {
                            $receipt = '';
                        }
                        $receipt = '';
                        //event(new SendMessage());
                        
                    } elseif ($input['status'] == 'final') {
                        if (empty($input['sub_type'])) {
                            $msg = trans("sale.pos_sale_added");
                            if (!$is_direct_sale && !$transaction->is_suspend) {
                                $receipt = $this->receiptContent($business_id, $input['location_id'], $transaction->id);
                            } else {
                                $receipt = '';
                            }
                        } else {
                            $msg = trans("sale.pos_sale_added");
                            $receipt = '';
                        }
                        
                    }

                    $output = ['success' => 1, 'msg' => $msg, 'receipt' => $receipt ];
                } else {
                    $output = ['success' => 0,
                                'msg' => trans("messages.something_went_wrong")
                            ];
                }
            }
        } catch (\Exception $e) {
            DB::rollBack();

            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());
            $msg = trans("messages.something_went_wrong");
                
            if (get_class($e) == \App\Exceptions\PurchaseSellMismatch::class) {
                $msg = $e->getMessage();
            }

            $output = ['success' => 0,
                            'msg' => $msg
                        ];
        }

        if (!$is_direct_sale) {
            return $output;
        } 
        else 
        {
            if ($input['status'] == 'draft') {
                if (isset($input['is_quotation']) && $input['is_quotation'] == 1) {
                    return redirect()
                        ->action('SellController@getQuotations')
                        ->with('status', $output);
                } else {
                    return redirect()
                        ->action('SellController@getDrafts')
                        ->with('status', $output);
                }
            } else {
                if (!empty($input['sub_type']) && $input['sub_type'] == 'repair') {
                    $redirect_url = $input['print_label'] == 1 ? action('\Modules\Repair\Http\Controllers\RepairController@printLabel', [$transaction->id]) : action('\Modules\Repair\Http\Controllers\RepairController@index');
                    return redirect($redirect_url)
                        ->with('status', $output);
                }
                return redirect()
                    ->action('SellController@index')
                    ->with('status', $output);
            }
        }
            
            
    }

    /**
     * Returns the content for the receipt
     *
     * @param  int  $business_id
     * @param  int  $location_id
     * @param  int  $transaction_id
     * @param string $printer_type = null
     *
     * @return array
     */
    private function receiptContent(
        $business_id,
        $location_id,
        $transaction_id,
        $printer_type = null,
        $is_package_slip = false,
        $from_pos_screen = true
    ) {
        $output = ['is_enabled' => false,
                    'print_type' => 'browser',
                    'html_content' => null,
                    'printer_config' => [],
                    'data' => []
                ];


        $business_details = $this->businessUtil->getDetails($business_id);
        $location_details = BusinessLocation::find($location_id);
        
        if ($from_pos_screen && $location_details->print_receipt_on_invoice != 1) {
            return $output;
        }
        //Check if printing of invoice is enabled or not.
        //If enabled, get print type.
        $output['is_enabled'] = true;

        $invoice_layout = $this->businessUtil->invoiceLayout($business_id, $location_id, $location_details->invoice_layout_id);

        //Check if printer setting is provided.
        $receipt_printer_type = is_null($printer_type) ? $location_details->receipt_printer_type : $printer_type;

        $receipt_details = $this->transactionUtil->getReceiptDetails($transaction_id, $location_id, $invoice_layout, $business_details, $location_details, $receipt_printer_type);

        $currency_details = [
            'symbol' => $business_details->currency_symbol,
            'thousand_separator' => $business_details->thousand_separator,
            'decimal_separator' => $business_details->decimal_separator,
        ];
        $receipt_details->currency = $currency_details;
        
        if ($is_package_slip) {
            $output['html_content'] = view('sale_pos.receipts.packing_slip', compact('receipt_details'))->render();
            return $output;
        }
        //If print type browser - return the content, printer - return printer config data, and invoice format config
        if ($receipt_printer_type == 'printer') {
            $output['print_type'] = 'printer';
            $output['printer_config'] = $this->businessUtil->printerConfig($business_id, $location_details->printer_id);
            $output['data'] = $receipt_details;
        } else {
            $layout = !empty($receipt_details->design) ? 'sale_pos.receipts.' . $receipt_details->design : 'sale_pos.receipts.classic';

            $output['html_content'] = view($layout, compact('receipt_details'))->render();
        }
        
        return $output;
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        if (!auth()->user()->can('sell.update')) {
            abort(403, 'Unauthorized action.');
        }

        $departmentUser=0;
        
        if(count(auth()->user()->contactAccess) >0) 
        {
            $departmentUser=1;
        }
        //Check if the transaction can be edited or not.
        $edit_days = request()->session()->get('business.transaction_edit_days');
        if (!$this->transactionUtil->canBeEdited($id, $edit_days)) {
            return back()
                ->with('status', ['success' => 0,
                    'msg' => __('messages.transaction_edit_not_allowed', ['days' => $edit_days])]);
        }

        //Check if there is a open register, if no then redirect to Create Register screen.
        if ($this->cashRegisterUtil->countOpenedRegister() == 0) {
            return redirect()->action('CashRegisterController@create');
        }
        
        //Check if return exist then not allowed
        if ($this->transactionUtil->isReturnExist($id)) {
            return back()->with('status', ['success' => 0,
                    'msg' => __('lang_v1.return_exist')]);
        }

        $business_id = request()->session()->get('user.business_id');
        $walk_in_customer = $this->contactUtil->getWalkInCustomer($business_id);
        
        $business_details = $this->businessUtil->getDetails($business_id);
        $taxes = TaxRate::forBusinessDropdown($business_id, true, true);
        $payment_types = $this->productUtil->payment_types();

        $transaction = Transaction::where('business_id', $business_id)
                            ->where('type', 'sell')
                            ->findorfail($id);
        if($transaction->status == 'draft')
        {
            // $allowed_hosts = array('gs.esatsang.net');
                    
            // if (isset($_SERVER['HTTP_HOST']) && in_array($_SERVER['HTTP_HOST'], $allowed_hosts)) {
                event(new \App\Events\RemoveMessageFromQuoteList($transaction->id));
                
            // }
            
        }
        $location_id = $transaction->location_id;
        $location_printer_type = BusinessLocation::find($location_id)->receipt_printer_type;

        $sell_details = TransactionSellLine::
                        join(
                            'products AS p',
                            'transaction_sell_lines.product_id',
                            '=',
                            'p.id'
                        )
                        ->join(
                            'variations AS variations',
                            'transaction_sell_lines.variation_id',
                            '=',
                            'variations.id'
                        )
                        ->join(
                            'product_variations AS pv',
                            'variations.product_variation_id',
                            '=',
                            'pv.id'
                        )
                        ->leftjoin('variation_location_details AS vld', function ($join) use ($location_id) {
                            $join->on('variations.id', '=', 'vld.variation_id')
                                ->where('vld.location_id', '=', $location_id);
                        })
                        ->leftjoin('units', 'units.id', '=', 'p.unit_id')
                        ->where('transaction_sell_lines.transaction_id', $id)
                        ->select(
                            DB::raw("IF(pv.is_dummy = 0, CONCAT(p.name, ' (', pv.name, ':',variations.name, ')'), p.name) AS product_name"),
                            'p.id as product_id',
                            'p.enable_stock',
                            'p.name as product_actual_name',
                            'pv.name as product_variation_name',
                            'pv.is_dummy as is_dummy',
                            'variations.name as variation_name',
                            'variations.sub_sku',
                            'p.barcode_type',
                            'p.enable_sr_no',
                            'variations.id as variation_id',
                            'units.short_name as unit',
                            'units.allow_decimal as unit_allow_decimal',
                            'transaction_sell_lines.tax_id as tax_id',
                            'transaction_sell_lines.item_tax as item_tax',
                            'transaction_sell_lines.unit_price as default_sell_price',
                            'transaction_sell_lines.unit_price_before_discount as unit_price_before_discount',
                            'transaction_sell_lines.unit_price_inc_tax as sell_price_inc_tax',
                            'transaction_sell_lines.id as transaction_sell_lines_id',
                            'transaction_sell_lines.quantity as quantity_ordered',
                            'transaction_sell_lines.sell_line_note as sell_line_note',
                            'transaction_sell_lines.parent_sell_line_id',
                            'transaction_sell_lines.lot_no_line_id',
                            'transaction_sell_lines.line_discount_type',
                            'transaction_sell_lines.line_discount_amount',
                            'transaction_sell_lines.res_service_staff_id',
                            'units.id as unit_id',
                            'transaction_sell_lines.sub_unit_id',
                            DB::raw('vld.qty_available + transaction_sell_lines.quantity AS qty_available')
                        )
                        ->get();
        if (!empty($sell_details)) {
            foreach ($sell_details as $key => $value) {
                //If modifier sell line then unset
                if (!empty($sell_details[$key]->parent_sell_line_id)) {
                    unset($sell_details[$key]);
                } else {
                    if ($transaction->status != 'final') {
                        $actual_qty_avlbl = $value->qty_available - $value->quantity_ordered;
                        $sell_details[$key]->qty_available = $actual_qty_avlbl;
                        $value->qty_available = $actual_qty_avlbl;
                    }

                    $sell_details[$key]->formatted_qty_available = $this->productUtil->num_f($value->qty_available, false, null, true);

                    //Add available lot numbers for dropdown to sell lines
                    $lot_numbers = [];
                    if (request()->session()->get('business.enable_lot_number') == 1 || request()->session()->get('business.enable_product_expiry') == 1) {
                        $lot_number_obj = $this->transactionUtil->getLotNumbersFromVariation($value->variation_id, $business_id, $location_id);
                        foreach ($lot_number_obj as $lot_number) {
                            //If lot number is selected added ordered quantity to lot quantity available
                            if ($value->lot_no_line_id == $lot_number->purchase_line_id) {
                                $lot_number->qty_available += $value->quantity_ordered;
                            }

                            $lot_number->qty_formated = $this->productUtil->num_f($lot_number->qty_available);
                            $lot_numbers[] = $lot_number;
                        }
                    }
                    $sell_details[$key]->lot_numbers = $lot_numbers;
                    
                    if (!empty($value->sub_unit_id)) {
                        $value = $this->productUtil->changeSellLineUnit($business_id, $value);
                        $sell_details[$key] = $value;
                    }

                    $sell_details[$key]->formatted_qty_available = $this->productUtil->num_f($value->qty_available, false, null, true);

                    if ($this->transactionUtil->isModuleEnabled('modifiers')) {
                        //Add modifier details to sel line details
                        $sell_line_modifiers = TransactionSellLine::where('parent_sell_line_id', $sell_details[$key]->transaction_sell_lines_id)->get();
                        $modifiers_ids = [];
                        if (count($sell_line_modifiers) > 0) {
                            $sell_details[$key]->modifiers = $sell_line_modifiers;
                            foreach ($sell_line_modifiers as $sell_line_modifier) {
                                $modifiers_ids[] = $sell_line_modifier->variation_id;
                            }
                        }
                        $sell_details[$key]->modifiers_ids = $modifiers_ids;

                        //add product modifier sets for edit
                        $this_product = Product::find($sell_details[$key]->product_id);
                        if (count($this_product->modifier_sets) > 0) {
                            $sell_details[$key]->product_ms = $this_product->modifier_sets;
                        }
                    }
                }
                
            }
        }

        $payment_lines = $this->transactionUtil->getPaymentDetails($id);
        //If no payment lines found then add dummy payment line.
        if (empty($payment_lines)) {
            $payment_lines[] = $this->dummyPaymentLine;
        }

        $shortcuts = json_decode($business_details->keyboard_shortcuts, true);
        $pos_settings = empty($business_details->pos_settings) ? $this->businessUtil->defaultPosSettings() : json_decode($business_details->pos_settings, true);

        $commsn_agnt_setting = $business_details->sales_cmsn_agnt;
        $commission_agent = [];
        if ($commsn_agnt_setting == 'user') {
            $commission_agent = User::forDropdown($business_id, false);
        } elseif ($commsn_agnt_setting == 'cmsn_agnt') {
            $commission_agent = User::saleCommissionAgentsDropdown($business_id, false);
        }

        //If brands, category are enabled then send else false.
        $categories = (request()->session()->get('business.enable_category') == 1) ? Category::catAndSubCategories($business_id) : false;
        $brands = (request()->session()->get('business.enable_brand') == 1) ? Brands::where('business_id', $business_id)
                    ->pluck('name', 'id')
                    ->prepend(__('lang_v1.all_brands'), 'all') : false;

        $change_return = $this->dummyPaymentLine;

        $types = [];
        if (auth()->user()->can('supplier.create')) {
            $types['supplier'] = __('report.supplier');
        }
        if (auth()->user()->can('customer.create')) {
            $types['customer'] = __('report.customer');
        }
        if (auth()->user()->can('supplier.create') && auth()->user()->can('customer.create')) {
            $types['both'] = __('lang_v1.both_supplier_customer');
        }
        $customer_groups = CustomerGroup::forDropdown($business_id);

        //Accounts
        $accounts = [];
        if ($this->moduleUtil->isModuleEnabled('account')) {
            $accounts = Account::forDropdown($business_id, true, false);
        }
        //Selling Price Group Dropdown
        $price_groups = SellingPriceGroup::forDropdown($business_id);

        $waiters = null;
        if ($this->productUtil->isModuleEnabled('service_staff') && !empty($pos_settings['inline_service_staff'])) {
            $waiters_enabled = true;
            $waiters = $this->productUtil->serviceStaffDropdown($business_id);
        }
        $genral_store_settings = empty($business_details->genral_store_settings) ? $this->businessUtil->defaultGenralStoreSettings() : json_decode($business_details->genral_store_settings, true);
        $user=\Auth::user();
        $can_return='';
        return view('sale_pos.edit')
            ->with(compact('business_details', 'taxes', 'payment_types', 'walk_in_customer', 'sell_details', 'transaction', 'payment_lines', 'location_printer_type', 'shortcuts', 'commission_agent', 'categories', 'pos_settings', 'change_return', 'types', 'customer_groups', 'brands', 'accounts', 'price_groups', 'waiters','genral_store_settings','departmentUser','user','can_return'));
    }

    /**
     * Update the specified resource in storage.
     * TODO: Add edit log.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        // dd($request);
        if (!auth()->user()->can('sell.update') && !auth()->user()->can('direct_sell.access')) {
            abort(403, 'Unauthorized action.');
        }
        
        try {
            $input = $request->except('_token');

            //status is send as quotation from edit sales screen.
            $input['is_quotation'] = 0;
            if ($input['status'] == 'quotation') {
                $input['status'] = 'draft';
                $input['is_quotation'] = 1;
            }
            
            $is_direct_sale = false;
            if (!empty($input['products'])) {
                //Get transaction value before updating.
                $transaction_before = Transaction::find($id);
                $status_before =  $transaction_before->status;

                if ($transaction_before->is_direct_sale == 1) {
                    $is_direct_sale = true;
                }

                //Check Customer credit limit
                $is_credit_limit_exeeded = $this->transactionUtil->isCustomerCreditLimitExeeded($input, $id);

                if ($is_credit_limit_exeeded !== false) {
                    $credit_limit_amount = $this->transactionUtil->num_f($is_credit_limit_exeeded, true);
                    $output = ['success' => 0,
                                'msg' => __('lang_v1.cutomer_credit_limit_exeeded', ['credit_limit' => $credit_limit_amount])
                            ];
                    if (!$is_direct_sale) {
                        return $output;
                    } else {
                        return redirect()
                            ->action('SellController@index')
                            ->with('status', $output);
                    }
                }

                //Check if there is a open register, if no then redirect to Create Register screen.
                if (!$is_direct_sale && $this->cashRegisterUtil->countOpenedRegister() == 0) {
                    return redirect()->action('CashRegisterController@create');
                }

                $business_id = $request->session()->get('user.business_id');
                $user_id = $request->session()->get('user.id');
                $commsn_agnt_setting = $request->session()->get('business.sales_cmsn_agnt');

                $discount = ['discount_type' => $input['discount_type'],
                                'discount_amount' => $input['discount_amount']
                            ];
                $invoice_total = $this->productUtil->calculateInvoiceTotal($input['products'], $input['tax_rate_id'], $discount);

                if (!empty($request->input('transaction_date'))) {
                    $input['transaction_date'] = $this->productUtil->uf_date($request->input('transaction_date'), true);
                }

                $input['commission_agent'] = !empty($request->input('commission_agent')) ? $request->input('commission_agent') : null;
                if ($commsn_agnt_setting == 'logged_in_user') {
                    $input['commission_agent'] = $user_id;
                }

                if (isset($input['exchange_rate']) && $this->transactionUtil->num_uf($input['exchange_rate']) == 0) {
                    $input['exchange_rate'] = 1;
                }

                //Customer group details
                $contact_id = $request->get('contact_id', null);
                $cg = $this->contactUtil->getCustomerGroup($business_id, $contact_id);
                $input['customer_group_id'] = (empty($cg) || empty($cg->id)) ? null : $cg->id;
                $input['created_by'] = $user_id;
                
                //set selling price group id
                if ($request->has('price_group')) {
                    $input['selling_price_group_id'] = $request->input('price_group');
                }

                $input['is_suspend'] = isset($input['is_suspend']) && 1 == $input['is_suspend']  ? 1 : 0;
                if ($input['is_suspend']) {
                    $input['sale_note'] = !empty($input['additional_notes']) ? $input['additional_notes'] : null;
                }

                //Begin transaction
                DB::beginTransaction();

                $transaction = $this->transactionUtil->updateSellTransaction($id, $business_id, $input, $invoice_total, $user_id);
                
                //Update Sell lines
                $deleted_lines = $this->transactionUtil->createOrUpdateSellLines($transaction, $input['products'], $input['location_id'], true, $status_before);

                //Update update lines
                if (!$is_direct_sale && !$transaction->is_suspend) {
                    //Add change return
                    $change_return = $this->dummyPaymentLine;
                    $change_return['amount'] = $input['change_return'];
                    $change_return['is_return'] = 1;
                    if (!empty($input['change_return_id'])) {
                        $change_return['id'] = $input['change_return_id'];
                    }
                    $input['payment'][] = $change_return;

                    $this->transactionUtil->createOrUpdatePaymentLines($transaction, $input['payment']);

                    //Update cash register
                    $this->cashRegisterUtil->updateSellPayments($status_before, $transaction, $input['payment']);
                }

                //Update payment status
                $this->transactionUtil->updatePaymentStatus($transaction->id, $transaction->final_total);
                
                //Update product stock
                $this->productUtil->adjustProductStockForInvoice($status_before, $transaction, $input);

                //Allocate the quantity from purchase and add mapping of
                //purchase & sell lines in
                //transaction_sell_lines_purchase_lines table
                $business = ['id' => $business_id,
                                'accounting_method' => $request->session()->get('business.accounting_method'),
                                'location_id' => $input['location_id']
                            ];
                //$this->transactionUtil->adjustMappingPurchaseSell($status_before, $transaction, $business, $deleted_lines);
                
                if ($this->transactionUtil->isModuleEnabled('tables')) {
                    $transaction->res_table_id = request()->get('res_table_id');
                    $transaction->save();
                }
                if ($this->transactionUtil->isModuleEnabled('service_staff')) {
                    $transaction->res_waiter_id = request()->get('res_waiter_id');
                    $transaction->save();
                }
                $log_properties = [];
                if (isset($input['repair_completed_on'])) {
                    $completed_on = !empty($input['repair_completed_on']) ? $this->transactionUtil->uf_date($input['repair_completed_on'], true) : null;
                    if ($transaction->repair_completed_on != $completed_on) {
                        $log_properties['completed_on_from'] = $transaction->repair_completed_on;
                        $log_properties['completed_on_to'] = $completed_on;
                    }
                }

                //Set Module fields
                if (!empty($input['has_module_data'])) {
                    $this->moduleUtil->getModuleData('after_sale_saved', ['transaction' => $transaction, 'input' => $input]);
                }

                if (!empty($input['update_note'])) {
                    $log_properties['update_note'] = $input['update_note'];
                }

                Media::uploadMedia($business_id, $transaction, $request, 'documents');

                activity()
                ->performedOn($transaction)
                ->withProperties($log_properties)
                ->log('edited');

                DB::commit();
                    
                $msg = '';
                $receipt = '';

                if ($input['status'] == 'draft' && $input['is_quotation'] == 0) {
                    $msg = trans("sale.draft_added");
                } elseif ($input['status'] == 'draft' && $input['is_quotation'] == 1) {
                    $msg = trans("lang_v1.quotation_updated");
                    if (!$is_direct_sale) {
                        $receipt = $this->receiptContent($business_id, $input['location_id'], $transaction->id);
                    } else {
                        $receipt = '';
                    }
                } elseif ($input['status'] == 'final') {
                    $msg = trans("sale.pos_sale_updated");
                    if (!$is_direct_sale && !$transaction->is_suspend) {
                        $receipt = $this->receiptContent($business_id, $input['location_id'], $transaction->id);
                    } else {
                        $receipt = '';
                    }
                    $this->notificationUtil->autoSendWhatsappNotification($business_id, 'new_sale', $transaction, $transaction->contact->mobile,'general_store_updated','gu');
                    if(!is_null($transaction->contact->alternate_number))
                    {
                        $this->notificationUtil->autoSendWhatsappNotification($business_id, 'new_sale', $transaction, $transaction->contact->alternate_number,'general_store_updated','gu');
                    }
                    event(new \App\Events\RemoveCategoryList($input['random_no']));
                }

                $output = ['success' => 1, 'msg' => $msg, 'receipt' => $receipt ];
            } else {
                $output = ['success' => 0,
                            'msg' => trans("messages.something_went_wrong")
                        ];
            }
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());
            $output = ['success' => 0,
                            'msg' => __('messages.something_went_wrong')
                        ];
        }

        if (!$is_direct_sale) {
            return $output;
        } else {
            if ($input['status'] == 'draft') {
                if (isset($input['is_quotation']) && $input['is_quotation'] == 1) {
                    return redirect()
                        ->action('SellController@getQuotations')
                        ->with('status', $output);
                } else {
                    return redirect()
                        ->action('SellController@getDrafts')
                        ->with('status', $output);
                }
            } else {
                if (!empty($transaction->sub_type) && $transaction->sub_type == 'repair') {
                    return redirect()
                        ->action('\Modules\Repair\Http\Controllers\RepairController@index')
                        ->with('status', $output);
                }

                return redirect()
                    ->action('SellController@index')
                    ->with('status', $output);
            }
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if (!auth()->user()->can('sell.delete')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            try {
                //Check if return exist then not allowed
                if ($this->transactionUtil->isReturnExist($id)) {
                    $output = [
                        'success' => false,
                        'msg' => __('lang_v1.return_exist')
                    ];
                    return $output;
                }

                $business_id = request()->session()->get('user.business_id');
                $transaction = Transaction::where('id', $id)
                            ->where('business_id', $business_id)
                            ->where('type', 'sell')
                            ->with(['sell_lines'])
                            ->first();

                //Begin transaction
                DB::beginTransaction();

                if (!empty($transaction)) {
                    //If status is draft direct delete transaction
                    if ($transaction->status == 'draft') {
                        $transaction->delete();
                    } else {
                        $deleted_sell_lines = $transaction->sell_lines;
                        $deleted_sell_lines_ids = $deleted_sell_lines->pluck('id')->toArray();
                        $this->transactionUtil->deleteSellLines(
                            $deleted_sell_lines_ids,
                            $transaction->location_id
                        );

                        $transaction->status = 'draft';
                        $business = ['id' => $business_id,
                                'accounting_method' => request()->session()->get('business.accounting_method'),
                                'location_id' => $transaction->location_id
                            ];

                        $this->transactionUtil->adjustMappingPurchaseSell('final', $transaction, $business, $deleted_sell_lines_ids);

                        //Delete Cash register transactions
                        $transaction->cash_register_payments()->delete();

                        $transaction->delete();
                    }
                }

                //Delete account transactions
                AccountTransaction::where('transaction_id', $transaction->id)->delete();

                DB::commit();
                $output = [
                    'success' => true,
                    'msg' => __('lang_v1.sale_delete_success')
                ];
            } catch (\Exception $e) {
                DB::rollBack();
                \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());

                $output['success'] = false;
                $output['msg'] = trans("messages.something_went_wrong");
            }

            return $output;
        }
    }

    /**
     * Returns the HTML row for a product in POS
     *
     * @param  int  $variation_id
     * @param  int  $location_id
     * @return \Illuminate\Http\Response
     */
    public function getProductRow($variation_id, $location_id)
    {
        $output = [];

        try {

            $departmentUser=0;
        
            if(count(auth()->user()->contactAccess) >0) 
            {
                $departmentUser=1;
            }
            
            $row_count = request()->get('product_row');
            $row_count = $row_count + 1;
            $is_direct_sell = false;
            if (request()->get('is_direct_sell') == 'true') {
                $is_direct_sell = true;
            }
           
            $business_id = request()->session()->get('user.business_id');
            $product = $this->productUtil->getDetailsFromVariation($variation_id, $business_id, $location_id,true,$departmentUser);
            
            $damage_by_customer_id = request()->get('damage_by_customer_id');
            $return_seva_by_customer_id = request()->get('return_seva_by_customer_id');
            $product_qty = request()->get('product_qty');
            
            
            if($damage_by_customer_id)
            {
                
                // $contactList = Contact::select('id')->whereIn('name',['Walk-In Customer','General Store(Pu Nirmancharit Swami)'])->get()->pluck('id')->toArray();
                $contactList = [1,22];
                if(!in_array($damage_by_customer_id,$contactList))
                {
                    $totalSold = $this->transactionUtil->checkSellforDamage($variation_id, $business_id, $location_id,$damage_by_customer_id);
                    if($product_qty>$totalSold)
                    {
                        $output['success'] = false;
                        $output['msg'] = "Product not sold in this department";
                        return $output;
                    }
                }
                
            }
            $can_return = '';
            if($return_seva_by_customer_id)
            {
                $totalPurchase = $this->transactionUtil->checkTotalPurchase($variation_id, $business_id, $location_id,$return_seva_by_customer_id);
                $totalReturn = $this->transactionUtil->checkSellforReturn($variation_id, $business_id, $location_id,$return_seva_by_customer_id);
                $pendingProduct = $totalPurchase - $totalReturn;

                if(!$totalPurchase){
                    $output['success'] = false;
                    $output['msg'] = "Product not received by supplier";
                    return $output;
                }else if($pendingProduct <=0 ){
                    $output['success'] = false;
                    $output['msg'] = "Product already return";
                    return $output;   
                }else if($pendingProduct<$product_qty){
                    $output['success'] = false;
                    $output['msg'] = "Can not return more than $pendingProduct products";
                    return $output;
                }
            $can_return= $pendingProduct;
            }

            // Product not sold in this department

            // $product = $this->productUtil->getDetailsFromVariation($variation_id, $business_id, $location_id);
            $product->formatted_qty_available = $this->productUtil->num_f($product->qty_available, false, null, true);

            $sub_units = $this->productUtil->getSubUnits($business_id, $product->unit_id);

            //Get customer group and change the price accordingly
            $customer_id = request()->get('customer_id', null);
            $cg = $this->contactUtil->getCustomerGroup($business_id, $customer_id);
            $percent = (empty($cg) || empty($cg->amount)) ? 0 : $cg->amount;
            $product->default_sell_price = $product->default_sell_price + ($percent * $product->default_sell_price / 100);
            $product->sell_price_inc_tax = $product->sell_price_inc_tax + ($percent * $product->sell_price_inc_tax / 100);

            $tax_dropdown = TaxRate::forBusinessDropdown($business_id, true, true);

            $enabled_modules = $this->transactionUtil->allModulesEnabled();

            //Get lot number dropdown if enabled
            $lot_numbers = [];
            if (request()->session()->get('business.enable_lot_number') == 1 || request()->session()->get('business.enable_product_expiry') == 1) {
                $lot_number_obj = $this->transactionUtil->getLotNumbersFromVariation($variation_id, $business_id, $location_id, true);
                foreach ($lot_number_obj as $lot_number) {
                    $lot_number->qty_formated = $this->productUtil->num_f($lot_number->qty_available);
                    $lot_numbers[] = $lot_number;
                }
            }
            $product->lot_numbers = $lot_numbers;

            $price_group = request()->input('price_group');
            if (!empty($price_group)) {
                $variation_group_prices = $this->productUtil->getVariationGroupPrice($variation_id, $price_group, $product->tax_id);
                
                if (!empty($variation_group_prices['price_inc_tax'])) {
                    $product->sell_price_inc_tax = $variation_group_prices['price_inc_tax'];
                    $product->default_sell_price = $variation_group_prices['price_exc_tax'];
                }
            }

            $business_details = $this->businessUtil->getDetails($business_id);
            $pos_settings = empty($business_details->pos_settings) ? $this->businessUtil->defaultPosSettings() : json_decode($business_details->pos_settings, true);
            $genral_store_settings = empty($business_details->genral_store_settings) ? $this->businessUtil->defaultGenralStoreSettings() : json_decode($business_details->genral_store_settings, true);

            $output['success'] = true;

            $waiters = null;
            if ($this->productUtil->isModuleEnabled('service_staff') && !empty($pos_settings['inline_service_staff'])) {
                $waiters_enabled = true;
                $waiters = $this->productUtil->serviceStaffDropdown($business_id, $location_id);
            }

             // rack details enable
             $rack_enabled = (request()->session()->get('business.enable_racks') || request()->session()->get('business.enable_row') || request()->session()->get('business.enable_position'));
             $locationRackDetails= '';
             if($rack_enabled)
             {
                 $rackDetails = $this->productUtil->getRackDetails($business_id, $product->product_id);
                 $locationRackDetails = $rackDetails[$location_id];
                 
             }

            //Product demand check start
                $restrict_sell_with_sellorder = 0;
                $demandQty = 0;
                if($genral_store_settings['restrict_sell_with_sellorder'])
                {
                    $restrict_sell_with_sellorder = 1;
                    $productId  = $variation_id;
                    $locationId = $location_id;
                    $customerId = request()->input('customer_id');
        

                
                    $sellorderproducts = DB::table('transactions as trans')
                
                    ->leftjoin('sell_order_lines as sol', 'sol.transaction_id', '=', 'trans.id')

                    ->leftjoin('sell_return_lines as srl', 'srl.transaction_id', '=', 'trans.id')

                    ->leftjoin('transaction_sell_lines as transsellline', 'transsellline.transaction_id', '=', 'trans.id')

                    ->whereIn('trans.type',array('sellorder','sell','sell_return_genralstore'))
                    
                    ->where('trans.location_id',$locationId)->where('trans.business_id',$business_id)->where('trans.contact_id',$customerId)

                    ->where(function($q) use ($productId) {
                        $q->where('sol.product_id', $productId)
                        ->orWhere('transsellline.product_id', $productId)
                        ->orWhere('srl.product_id', $productId);
                    });
                    
                    //->where('trans.transaction_date', '=', date('Y-m-d').' 00:00:00');
                    //$sellorderproducts->orwhereRaw("(sol.sell_order_date = ".date('Y-m-d').")");

                    $sellorderproducts->select(
                        //DB::raw("COALESCE(SUM(CASE WHEN (sol.sell_order_date ='".date('Y-m-d')."') THEN sol.quantity ELSE 0 END),0) as soquantity"),
                        DB::raw("COALESCE(SUM(sol.quantity),0) as soquantity"),
                        DB::raw('COALESCE(SUM(srl.quantity),0) as sellreturnquantity_genralstore'),
                        DB::raw('COALESCE(SUM(transsellline.quantity),0) as sellquantity'),
                        DB::raw('COALESCE(SUM(transsellline.quantity_returned),0) as sellreturnquantity')
                    );

                    $data = $sellorderproducts->get()->first();
                   

                    $demandQty=$data->soquantity-($data->sellquantity-$data->sellreturnquantity_genralstore);
                   
                    if($demandQty<$product_qty)
                    {
                        $product->quantity_ordered=0;
                        $product_qty=0;
                        
                    }
                    
                
                }              
            //product demand check end



            if (request()->get('type') == 'sell-return') {
                $output['html_content'] =  view('sell_return.partials.product_row')
                            ->with(compact('product', 'row_count', 'tax_dropdown', 'enabled_modules', 'sub_units'))
                            ->render();
            } else {
                $is_cg = !empty($cg->id) ? true : false;
                $is_pg = !empty($price_group) ? true : false;
                $discount = $this->productUtil->getProductDiscount($product, $business_id, $location_id, $is_cg, $is_pg);
                
                $output['html_content'] =  view('sale_pos.product_row')
                            ->with(compact('product', 'row_count', 'tax_dropdown', 'enabled_modules', 'pos_settings', 'sub_units', 
                            'discount', 'waiters','departmentUser','locationRackDetails','demandQty','restrict_sell_with_sellorder','can_return',))
                            ->render();
            }
           
            
            $output['enable_sr_no'] = $product->enable_sr_no;

            if ($this->transactionUtil->isModuleEnabled('modifiers')  && !$is_direct_sell) {
                $this_product = Product::where('business_id', $business_id)
                                        ->find($product->product_id);
                if (count($this_product->modifier_sets) > 0) {
                    $product_ms = $this_product->modifier_sets;
                    $output['html_modifier'] =  view('restaurant.product_modifier_set.modifier_for_product')
                    ->with(compact('product_ms', 'row_count'))->render();
                }
            }
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());

            $output['success'] = false;
            $output['msg'] = __('lang_v1.item_out_of_stock');
        }

        return $output;
    }

    /**
     * Returns the HTML row for a payment in POS
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function getPaymentRow(Request $request)
    {
        $business_id = request()->session()->get('user.business_id');
        
        $row_index = $request->input('row_index');
        $removable = true;
        $payment_types = $this->productUtil->payment_types();

        $payment_line = $this->dummyPaymentLine;

        //Accounts
        $accounts = [];
        if ($this->moduleUtil->isModuleEnabled('account')) {
            $accounts = Account::forDropdown($business_id, true, false);
        }

        return view('sale_pos.partials.payment_row')
            ->with(compact('payment_types', 'row_index', 'removable', 'payment_line', 'accounts'));
    }

    /**
     * Returns recent transactions
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function getRecentTransactions(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');
        $user_id = $request->session()->get('user.id');
        $transaction_status = $request->get('status');

        $register = $this->cashRegisterUtil->getCurrentCashRegister($user_id);

        $query = Transaction::where('business_id', $business_id)
                        ->where('transactions.created_by', $user_id)
                        ->where('transactions.type', 'sell')
                        ->where('is_direct_sale', 0);

        if ($transaction_status == 'final') {
            if (!empty($register->id)) {
                $query->leftjoin('cash_register_transactions as crt', 'transactions.id', '=', 'crt.transaction_id')
                ->where('crt.cash_register_id', $register->id);
            }
        }

        if ($transaction_status == 'quotation') {
            $query->where('transactions.status', 'draft')
                ->where('is_quotation', 1);
        } elseif ($transaction_status == 'draft') {
            $query->where('transactions.status', 'draft')
                ->where('is_quotation', 0);
        } else {
            $query->where('transactions.status', $transaction_status);
        }

        $transactions = $query->orderBy('transactions.created_at', 'desc')
                            ->groupBy('transactions.id')
                            ->select('transactions.*')
                            ->with(['contact'])
                            ->limit(10)
                            ->get();

        return view('sale_pos.partials.recent_transactions')
            ->with(compact('transactions'));
    }

    /**
     * Prints invoice for sell
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function printInvoice(Request $request, $transaction_id)
    {
        if (request()->ajax()) {
            try {
                $output = ['success' => 0,
                        'msg' => trans("messages.something_went_wrong")
                        ];

                $business_id = $request->session()->get('user.business_id');
            
                $transaction = Transaction::where('business_id', $business_id)
                                ->where('id', $transaction_id)
                                ->with(['location'])
                                ->first();

                if (empty($transaction)) {
                    return $output;
                }

                $printer_type = 'browser';
                if (!empty(request()->input('check_location')) && request()->input('check_location') == true) {
                    $printer_type = $transaction->location->receipt_printer_type;
                }

                $is_package_slip = !empty($request->input('package_slip')) ? true : false;

                $receipt = $this->receiptContent($business_id, $transaction->location_id, $transaction_id, $printer_type, $is_package_slip, false);

                if (!empty($receipt)) {
                    $output = ['success' => 1, 'receipt' => $receipt];
                }
            } catch (\Exception $e) {
                \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());
                
                $output = ['success' => 0,
                        'msg' => trans("messages.something_went_wrong")
                        ];
            }

            return $output;
        }
    }

    /**
     * Gives suggetion for product based on category
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function getProductSuggestion(Request $request)
    {
        if ($request->ajax()) {
            $category_id = $request->get('category_id');
            $brand_id = $request->get('brand_id');
            $location_id = $request->get('location_id');
            $product = $request->get('product');
            $term = $request->get('term');

            $check_qty = false;
            $business_id = $request->session()->get('user.business_id');

            $products = Product::join(
                'variations',
                'products.id',
                '=',
                'variations.product_id'
            )
                        ->leftjoin(
                            'variation_location_details AS VLD',
                            function ($join) use ($location_id) {
                                $join->on('variations.id', '=', 'VLD.variation_id');

                                //Include Location
                                if (!empty($location_id)) {
                                    $join->where(function ($query) use ($location_id) {
                                        $query->where('VLD.location_id', '=', $location_id);
                                        //Check null to show products even if no quantity is available in a location.
                                        //TODO: Maybe add a settings to show product not available at a location or not.
                                        $query->orWhereNull('VLD.location_id');
                                    });
                                    ;
                                }
                            }
                        )
                ->active()
                ->where('products.business_id', $business_id)
                ->where('products.type', '!=', 'modifier');

            //Include search
            if (!empty($term)) {
                $products->where(function ($query) use ($term) {
                    $query->where('products.name', 'like', '%' . $term .'%');
                    $query->orWhere('sku', 'like', '%' . $term .'%');
                    $query->orWhere('sub_sku', 'like', '%' . $term .'%');
                });
            }

            //Include check for quantity
            if ($check_qty) {
                $products->where('VLD.qty_available', '>', 0);
            }
            
            if ($category_id != 'all') {
                $products->where(function ($query) use ($category_id) {
                    $query->where('products.category_id', $category_id);
                    $query->orWhere('products.sub_category_id', $category_id);
                });
            }
            if ($brand_id != 'all') {
                $products->where('products.brand_id', $brand_id);
            }

            $products = $products->select(
                'products.id as product_id',
                'products.name',
                'products.type',
                'products.enable_stock',
                'variations.id as variation_id',
                'variations.name as variation',
                'VLD.qty_available',
                'variations.default_sell_price as selling_price',
                'variations.sub_sku',
                'products.image'
            )
            ->orderBy('products.name', 'asc')
            ->groupBy('variations.id')
            ->paginate(20);

            return view('sale_pos.partials.product_list')
                    ->with(compact('products'));
        }
    }

    /**
     * Shows invoice url.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function showInvoiceUrl($id)
    {
        if (!auth()->user()->can('sell.update')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');
            $transaction = Transaction::where('business_id', $business_id)
                                   ->findorfail($id);
            $url = $this->transactionUtil->getInvoiceUrl($id, $business_id);

            return view('sale_pos.partials.invoice_url_modal')
                    ->with(compact('transaction', 'url'));
        }
    }

    /**
     * Shows invoice to guest user.
     *
     * @param  string  $token
     * @return \Illuminate\Http\Response
     */
    public function showInvoice($token)
    {
        $transaction = Transaction::where('invoice_token', $token)->with(['business'])->first();

        if (!empty($transaction)) {
            $receipt = $this->receiptContent($transaction->business_id, $transaction->location_id, $transaction->id, 'browser');

            $title = $transaction->business->name . ' | ' . $transaction->invoice_no;
            return view('sale_pos.partials.show_invoice')
                    ->with(compact('receipt', 'title'));
        } else {
            die(__("messages.something_went_wrong"));
        }
    }

    /**
     * Display a listing of the recurring invoices.
     *
     * @return \Illuminate\Http\Response
     */
    public function listSubscriptions()
    {
        if (!auth()->user()->can('sell.view') && !auth()->user()->can('direct_sell.access')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');

            $sells = Transaction::leftJoin('contacts', 'transactions.contact_id', '=', 'contacts.id')
                ->leftJoin('transaction_payments as tp', 'transactions.id', '=', 'tp.transaction_id')
                ->join(
                    'business_locations AS bl',
                    'transactions.location_id',
                    '=',
                    'bl.id'
                )
                ->where('transactions.business_id', $business_id)
                ->where('transactions.type', 'sell')
                ->where('transactions.status', 'final')
                ->where('transactions.is_recurring', 1)
                ->select(
                    'transactions.id',
                    'transactions.transaction_date',
                    'transactions.is_direct_sale',
                    'transactions.invoice_no',
                    'contacts.name',
                    'transactions.subscription_no',
                    'bl.name as business_location',
                    'transactions.recur_parent_id',
                    'transactions.recur_stopped_on',
                    'transactions.is_recurring',
                    'transactions.recur_interval',
                    'transactions.recur_interval_type',
                    'transactions.recur_repetitions'
                )->with(['subscription_invoices']);



            $permitted_locations = auth()->user()->permitted_locations();
            if ($permitted_locations != 'all') {
                $sells->whereIn('transactions.location_id', $permitted_locations);
            }

            if (!empty(request()->start_date) && !empty(request()->end_date)) {
                $start = request()->start_date;
                $end =  request()->end_date;
                $sells->whereDate('transactions.transaction_date', '>=', $start)
                            ->whereDate('transactions.transaction_date', '<=', $end);
            }
            $datatable = Datatables::of($sells)
                ->addColumn(
                    'action',
                    function ($row) {
                        $html = '' ;

                        if ($row->is_recurring == 1 && auth()->user()->can("sell.update")) {
                            $link_text = !empty($row->recur_stopped_on) ? __('lang_v1.start_subscription') : __('lang_v1.stop_subscription');
                            $link_class = !empty($row->recur_stopped_on) ? 'btn-success' : 'btn-danger';

                            $html .= '<a href="' . action('SellPosController@toggleRecurringInvoices', [$row->id]) . '" class="toggle_recurring_invoice btn btn-xs ' . $link_class . '"><i class="fa fa-power-off"></i> ' . $link_text . '</a>';

                            if ($row->is_direct_sale == 0) {
                                $html .= '<a target="_blank" class="btn btn-xs btn-primary" href="' . action('SellPosController@edit', [$row->id]) . '"><i class="glyphicon glyphicon-edit"></i> ' . __("messages.edit") . '</a>';
                            } else {
                                $html .= '<a target="_blank" class="btn btn-xs btn-primary" href="' . action('SellController@edit', [$row->id]) . '"><i class="glyphicon glyphicon-edit"></i> ' . __("messages.edit") . '</a>';
                            }
                        }

                        return $html;
                    }
                )
                ->removeColumn('id')
                ->editColumn('transaction_date', '{{@format_date($transaction_date)}}')
                ->editColumn('recur_interval', function ($row) {
                    $type = $row->recur_interval == 1 ? str_singular(__('lang_v1.' . $row->recur_interval_type)) : __('lang_v1.' . $row->recur_interval_type);
                    return $row->recur_interval . $type;
                })
                ->addColumn('subscription_invoices', function ($row) {
                    $invoices = [];
                    if (!empty($row->subscription_invoices)) {
                        $invoices = $row->subscription_invoices->pluck('invoice_no')->toArray();
                    }

                    $html = '';
                    $count = 0;
                    if (!empty($invoices)) {
                        $imploded_invoices = '<span class="label bg-info">' . implode('</span>, <span class="label bg-info">', $invoices) . '</span>';
                        $count = count($invoices);
                        $html .= '<small>' . $imploded_invoices . '</small>';
                    }
                    if ($count > 0) {
                        $html .= '<br><small class="text-muted">' .
                    __('sale.total') . ': ' . $count . '</small>';
                    }

                    return $html;
                })
                ->addColumn('last_generated', function ($row) {
                    if (!empty($row->subscription_invoices)) {
                        $last_generated_date = $row->subscription_invoices->max('created_at');
                    }
                    return !empty($last_generated_date) ? $last_generated_date->diffForHumans() : '';
                })
                ->addColumn('upcoming_invoice', function ($row) {
                    if (empty($row->recur_stopped_on)) {
                        $last_generated = !empty($row->subscription_invoices) ? \Carbon::parse($row->subscription_invoices->max('transaction_date')) : \Carbon::parse($row->transaction_date);
                        if ($row->recur_interval_type == 'days') {
                            $upcoming_invoice = $last_generated->addDays($row->recur_interval);
                        } elseif ($row->recur_interval_type == 'months') {
                            $upcoming_invoice = $last_generated->addMonths($row->recur_interval);
                        } elseif ($row->recur_interval_type == 'years') {
                            $upcoming_invoice = $last_generated->addYears($row->recur_interval);
                        }
                    }
                    return !empty($upcoming_invoice) ? $this->transactionUtil->format_date($upcoming_invoice) : '';
                })
                ->rawColumns(['action', 'subscription_invoices'])
                ->make(true);
                
            return $datatable;
        }
        return view('sale_pos.subscriptions');
    }

    /**
     * Starts or stops a recurring invoice.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function toggleRecurringInvoices($id)
    {
        if (!auth()->user()->can('sell.create')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = request()->session()->get('user.business_id');
            $transaction = Transaction::where('business_id', $business_id)
                            ->where('type', 'sell')
                            ->where('is_recurring', 1)
                            ->findorfail($id);

            if (empty($transaction->recur_stopped_on)) {
                $transaction->recur_stopped_on = \Carbon::now();
            } else {
                $transaction->recur_stopped_on = null;
            }
            $transaction->save();

            $output = ['success' => 1,
                    'msg' => trans("lang_v1.updated_success")
                ];
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());
            
            $output = ['success' => 0,
                            'msg' => trans("messages.something_went_wrong")
                        ];
        }

        return $output;
    }

    public function getQuoteList()
    {
        return view('sale_pos.quote_list');
    }

    public function getQuoteListStore2()
    {
        return view('sale_pos.quote_list_store_2');
    }
    public function getCategoryWiseProductList($name)
    {
        $catId = '';
        $business_id = 1;
        $category = Category::where('name', $name)->get()->first();
        
        if(is_object($category))
        {
            $catId = $category->id;
            $name  = $category->name;
        }
        
        return view('sale_pos.categorywise_product_list_using_socket')->with(compact('name','catId'));
        // return view('sale_pos.product_list_using_socket')->with(compact('name','catId'));
    }

    public function getDemandFromCounter(Request $request)
    {
        
        $business_id = request()->session()->get('user.business_id');
        $counter = $request->session()->get('user.custom_field_1');
        $random_no=$request->get('srno');
        $procuctCategory=[];
        event(new \App\Events\RemoveCategoryList($random_no));
        
        if(!empty($request->get('product_row')))
        {
            foreach($request->get('product_row') as $data)
            {
                    $products = $this->productUtil->getDetailsFromProduct($business_id, $data['product']);
                
                if(array_key_exists($products[0]['category_id'], $procuctCategory))
                {
                    $count =count($procuctCategory[$products[0]['category_id']]);
                    $procuctCategory[$products[0]['category_id']][$count]['product_name'] = $products[0]['product_name'];
                    $procuctCategory[$products[0]['category_id']][$count]['quantity'] = $data['quantity'];
                    $procuctCategory[$products[0]['category_id']][$count]['counter'] = 1;
                }
                else{
                    $procuctCategory[$products[0]['category_id']][0]['product_name'] = $products[0]['product_name'];
                    $procuctCategory[$products[0]['category_id']][0]['quantity'] = $data['quantity'];
                    $procuctCategory[$products[0]['category_id']][0]['counter'] = 1;
                }
                    
            }
            if(array_key_exists(12, $procuctCategory))
            {
                if(array_key_exists(14, $procuctCategory))
                {
                    $combineCat = '';
                    $combineCat = array_merge($procuctCategory[12],$procuctCategory[14]);
                    $procuctCategory[12] = $combineCat;
                    $procuctCategory[14] = $combineCat;
                }
                else
                {
                    $procuctCategory[12] = $procuctCategory[12];
                    $procuctCategory[14] = $procuctCategory[12];
                }
            }
            elseif(array_key_exists(14, $procuctCategory))
            {
                if(array_key_exists(12, $procuctCategory))
                {
                    $combineCat = '';
                    $combineCat = array_merge($procuctCategory[12],$procuctCategory[14]);
                    $procuctCategory[12] = $combineCat;
                    $procuctCategory[14] = $combineCat;
                }
                else
                {
                    $procuctCategory[12] = $procuctCategory[14];
                    $procuctCategory[14] = $procuctCategory[14];
                }
            }
            if(array_key_exists(7, $procuctCategory))
            {
                if(array_key_exists(18, $procuctCategory))
                {
                    $combineCat = '';
                    $combineCat = array_merge($procuctCategory[7],$procuctCategory[18]);
                    $procuctCategory[7] = $combineCat;
                    $procuctCategory[18] = $combineCat;
                }
                else
                {
                    $procuctCategory[7]  = $procuctCategory[7];
                    $procuctCategory[18] = $procuctCategory[7];
                }
            }
            elseif(array_key_exists(18, $procuctCategory))
            {
                if(array_key_exists(7, $procuctCategory))
                {
                    $combineCat = '';
                    $combineCat = array_merge($procuctCategory[7],$procuctCategory[18]);
                    $procuctCategory[7] = $combineCat;
                    $procuctCategory[18] = $combineCat;
                }
                else
                {
                    $procuctCategory[7]  = $procuctCategory[18];
                    $procuctCategory[18] = $procuctCategory[18];
                }
            }
            

            if(array_key_exists(17, $procuctCategory))
            {
                if(array_key_exists(19, $procuctCategory))
                {
                    $combineCat = '';
                    $combineCat = array_merge($procuctCategory[17],$procuctCategory[19]);
                    $procuctCategory[17] = $combineCat;
                    $procuctCategory[19] = $combineCat;
                }
                else
                {
                    $procuctCategory[17]  = $procuctCategory[17];
                    $procuctCategory[19] = $procuctCategory[17];
                }
            }
            elseif(array_key_exists(19, $procuctCategory))
            {
                if(array_key_exists(17, $procuctCategory))
                {
                    $combineCat = '';
                    $combineCat = array_merge($procuctCategory[17],$procuctCategory[19]);
                    $procuctCategory[17] = $combineCat;
                    $procuctCategory[19] = $combineCat;
                }
                else
                {
                    $procuctCategory[17]  = $procuctCategory[19];
                    $procuctCategory[19]  = $procuctCategory[19];
                }
            }
            
            
            foreach($procuctCategory as $key => $value)
            {
                    $demandListHtml = $this->categoryProductListHtml($value,$counter,$random_no);
                    
                    
                    event(new \App\Events\SendMessageCategory($demandListHtml,$key,$random_no));
            }
        }
       
       return true;
    }

    public function checkProductExistInStore($transactionId,$business_id)
    {
        $transaction = Transaction::where('business_id', $business_id)
                            ->where('type', 'sell')
                            ->where('status', 'draft')
                            ->with(['sell_lines','contact'])
                            ->where('id',$transactionId)->get()->first()->toArray();
        
        return $this->demandListHtml($transaction,$business_id);
    }

    public function demandListHtml($transaction,$business_id)
    {
        $departmentUser=0;
        
        if(count(auth()->user()->contactAccess) >0) 
        {
            $departmentUser=1;
        }
        $socketData=[];

        $demandHtmlPart1= '';
        $demandHtmlPart2= '';
            $demandHtmlPart1.='<div class="card col-md-3 col-lg-3" id="quote_'.$transaction['id'].'">';
                $demandHtmlPart1.='<div class="card-body">';
                    if(isset($transaction['contact']['name']))
                    {
                        $demandHtmlPart1.='<h3 class="card-title">'.$transaction['contact']['name'].' ('.$transaction['contact']['mobile'].')</h3>';
                    }
                    
                    $demandHtmlPart1.='<ul class="list-group">';
                    $store2Html = '';
                    $storeHtml  = '';
                    $i=0;
                    $store2=0;
                    foreach($transaction['sell_lines'] as $transProduct)
                    {
                        
                        //dd($transProduct['product_id'],$business_id);
                        $products = $this->productUtil->getDetailsFromVariation($transProduct['product_id'], $business_id, $transaction['location_id'],true,$departmentUser);
                        
                        if($products->category_id == 21)
                        {
                            $store2Html .= '<li class="list-group-item list-group-item-success">'.$products->product_name.' ('.$products->sub_sku.') <b>'.$transProduct['quantity'].'</b></li>';
                            $store2 = 1;
                        }
                        else
                        {
                            $storeHtml.='<li class="list-group-item list-group-item-success">'.$products->product_name.' ('.$products->sub_sku.') <b>'.$transProduct['quantity'].'</b></li>';
                            $store2 = 0;
                        }
                        $i++;
                    }
                    $demandHtmlPart2.='</ul>';
                $demandHtmlPart2.='</div>';
                $demandHtmlPart2.='<div class="card-footer">';
                    $demandHtmlPart2.='<a target="_blank" href="'.action('SellPosController@edit', [$transaction['id']]).'?store2='.$store2.'" class="btn" id="left-panel-link">Edit</button>';
                    //$demandHtml.='<button type="button" class="btn" data-toggle="modal" data-target="#exampleModal1" id="right-panel-link">Learn More</button>';
                $demandHtmlPart2.='</div>';
            $demandHtmlPart2.='</div>';

        if(!empty($store2Html))
        {
            $socketData['store2']= $demandHtmlPart1.$store2Html.$demandHtmlPart2;
        }
        if(!empty($storeHtml))
        {
            $socketData['store']= $demandHtmlPart1.$storeHtml.$demandHtmlPart2;
        }
        
        return $socketData;
    }

    public function categoryProductListHtml($value,$counter,$random_no)
    {
        $font_size = '22px';
        $demandHtml= '';
        $lists  = array_chunk($value, 12);

        foreach ($lists as $items) {
        
            $demandHtml.='<div class="card col-sm-3 col-md-3 col-lg-3 product-'.$random_no.'" id="'.$random_no.'">';
                $demandHtml.='<div class="card-body">';
                   
                    $demandHtml.='<h3 style="font-size:30px; color:#000;" class="card-title">Counter '.$counter.'</h3>';
                    
                    $demandHtml.='<ul class="list-group">';
                        foreach($items as $transProduct)
                        {
                        $demandHtml.='<li style="color:#000; font-size:'.$font_size.'; padding:0px 15px;" class="list-group-item list-group-item-success">'.$transProduct['product_name'].' <b>'.$transProduct['quantity'].'</b></li>';
                        }
                     
                    $demandHtml.='</ul>';
                $demandHtml.='</div>';
            $demandHtml.='</div>';
        }
        return $demandHtml;
    }


    public function categoryProductListHtml2($value,$counter,$random_no)
    {
        $font_size = '22px';
        $demandHtml= '';
        $lists  = array_chunk($value, 12);

        foreach ($lists as $items) {
        
            $demandHtml.='<div class="card col-sm-12 col-md-12 col-lg-12 product-'.$random_no.'" id="'.$random_no.'">';
                $demandHtml.='<div class="card-body">';
                   
                    $demandHtml.='<h3 style="font-size:30px; color:#000;" class="card-title">Counter '.$counter.'</h3>';
                    
                    $demandHtml.='<ul class="list-group">';
                        foreach($items as $transProduct)
                        {
                        $demandHtml.='<li style="color:#000; font-size:'.$font_size.'; padding:0px 15px;" class="list-group-item list-group-item-success">'.$transProduct['product_name'].' <b>'.$transProduct['quantity'].'</b></li>';
                        }
                     
                    $demandHtml.='</ul>';
                $demandHtml.='</div>';
            $demandHtml.='</div>';
        }
        return $demandHtml;
    }
    public function displayOrder()
    {
        $business_id = 1;
        $category = Category::where('business_id', $business_id)->get();
        return view('sale_pos.categorywise_list')->with(compact('category'));
    }
}
