<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\BusinessLocation;
use App\Transaction;
use App\TaxRate;
use App\Product;
use App\Variation;
use App\CustomerGroup;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use App\Utils\ContactUtil;
use App\Utils\BusinessUtil;
use App\Utils\ModuleUtil;
use App\Business;
use App\SellReturnLines;
use App\Utils\NotificationUtil;
use App\Contact;
use App\User;

use Yajra\DataTables\Facades\DataTables;

class SellReturnGenralstoreController extends Controller
{
    /**
     * All Utils instance.
     *
     */
    protected $productUtil;
    protected $transactionUtil;
    protected $contactUtil;
    protected $businessUtil;
    protected $moduleUtil;
    protected $notificationUtil;

    /**
     * Constructor
     *
     * @param ProductUtils $product
     * @return void
     */
    public function __construct(ProductUtil $productUtil, TransactionUtil $transactionUtil, ContactUtil $contactUtil, BusinessUtil $businessUtil, ModuleUtil $moduleUtil,NotificationUtil $notificationUtil)
    {
        $this->productUtil = $productUtil;
        $this->transactionUtil = $transactionUtil;
        $this->contactUtil = $contactUtil;
        $this->businessUtil = $businessUtil;
        $this->moduleUtil = $moduleUtil;
        $this->notificationUtil = $notificationUtil;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (!auth()->user()->can('sellreturn.view') && !auth()->user()->can('sellreturn.create')) {
            abort(403, 'Unauthorized action.');
        }
        $business_id = request()->session()->get('user.business_id');
        if (request()->ajax()) {
            

            $sells = Transaction::leftJoin('contacts', 'transactions.contact_id', '=', 'contacts.id')
                    
                    ->join(
                        'business_locations AS bl',
                        'transactions.location_id',
                        '=',
                        'bl.id'
                    )
                    // ->join(
                    //     'transactions as T1',
                    //     'T1.id',
                    //     '=',
                    //     'T1.id'
                    // )
                    ->leftJoin(
                        'transaction_payments AS TP',
                        'transactions.id',
                        '=',
                        'TP.transaction_id'
                    )
                    ->where('transactions.business_id', $business_id)
                    ->where('transactions.type', 'sell_return_genralstore')
                    // ->where('transactions.status', 'final')
                    ->select(
                        'transactions.id',
                        'transactions.transaction_date',
                        'transactions.ref_no as invoice_no',
                        'contacts.name',
                        'transactions.final_total',
                        'transactions.payment_status',
                        'bl.name as business_location',
                        // 'T1.invoice_no as parent_sale',
                        // 'T1.id as parent_sale_id',
                        DB::raw('SUM(TP.amount) as amount_paid')
                    );

            $permitted_locations = auth()->user()->permitted_locations();
            if ($permitted_locations != 'all') {
                $sells->whereIn('transactions.location_id', $permitted_locations);
            }

            //Add condition for created_by,used in sales representative sales report
            if (request()->has('created_by')) {
                $created_by = request()->get('created_by');
                if (!empty($created_by)) {
                    $sells->where('transactions.created_by', $created_by);
                }
            }

            //Add condition for location,used in sales representative expense report
            if (request()->has('location_id')) {
                $location_id = request()->get('location_id');
                if (!empty($location_id)) {
                    $sells->where('transactions.location_id', $location_id);
                }
            }

            if (!empty(request()->customer_id)) {
                $customer_id = request()->customer_id;
                $sells->where('contacts.id', $customer_id);
            }
            if (!empty(request()->start_date) && !empty(request()->end_date)) {
                $start = request()->start_date;
                $end =  request()->end_date;
                $sells->whereDate('transactions.transaction_date', '>=', $start)
                        ->whereDate('transactions.transaction_date', '<=', $end);
            }

            $sells->groupBy('transactions.id');

           
            return Datatables::of($sells)
                ->addColumn(
                    'action',
                    '<div class="btn-group">
                    <button type="button" class="btn btn-info dropdown-toggle btn-xs" 
                        data-toggle="dropdown" aria-expanded="false">' .
                        __("messages.actions") .
                        '<span class="caret"></span><span class="sr-only">Toggle Dropdown
                        </span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-right" role="menu">
                    @if(auth()->user()->can("sellreturn.view") || auth()->user()->can("direct_sell.access") )
                        <li><a href="#" class="btn-modal" data-container=".view_modal" data-href="{{action(\'SellReturnGenralstoreController@show\', [$id])}}"><i class="fa fa-external-link" aria-hidden="true"></i> @lang("messages.view")</a></li>
                        <li><a href="{{action(\'SellReturnGenralstoreController@edit\', [$id])}}" ><i class="fa fa-edit" aria-hidden="true"></i> @lang("messages.edit")</a></li>
                    @endif

                    @if(auth()->user()->can("sellreturn.view") || auth()->user()->can("direct_sell.access") )
                        <li><a href="#" class="print-invoice" data-href="{{route(\'sellreturngenralstore.printInvoice\', [$id])}}"><i class="fa fa-print" aria-hidden="true"></i> @lang("messages.print")</a></li>
                    @endif

                    @if(auth()->user()->can("sellreturn.delete") || auth()->user()->can("direct_sell.access") )
                        <li><a  class="delete-sellreturn" href="{{action(\'SellReturnGenralstoreController@destroy\', [$id])}}" ><i class="fa fa-trash" aria-hidden="true"></i> @lang("messages.delete")</a></li>
                    @endif
                    </ul>
                    </div>'
                )
                // ->removeColumn('id')
                ->editColumn(
                    'final_total',
                    '<span class="display_currency final_total" data-currency_symbol="true" data-orig-value="{{$final_total}}">{{$final_total}}</span>'
                )
                ->editColumn('parent_sale', function ($row) {
                    return '<button type="button" class="btn btn-link btn-modal" data-container=".view_modal" data-href="' . action('SellController@show', [$row->id]) . '">' . $row->parent_sale . '</button>';
                })
                ->editColumn('transaction_date', '{{@format_date($transaction_date)}}')
                ->editColumn(
                    'payment_status',
                    '<a href="{{ action("TransactionPaymentController@show", [$id])}}" class="view_payment_modal payment-status payment-status-label" data-orig-value="{{$payment_status}}" data-status-name="{{__(\'lang_v1.\' . $payment_status)}}"><span class="label @payment_status($payment_status)">{{__(\'lang_v1.\' . $payment_status)}}</span></a>'
                )
                ->addColumn('payment_due', function ($row) {
                    $due = $row->final_total - $row->amount_paid;
                    return '<span class="display_currency payment_due" data-currency_symbol="true" data-orig-value="' . $due . '">' . $due . '</sapn>';
                })
                ->setRowAttr([
                    'data-href' => function ($row) {
                        if (auth()->user()->can("sell.view")) {
                            return  action('SellReturnGenralstoreController@show', [$row->id]) ;
                        } else {
                            return '';
                        }
                    }])
                ->rawColumns(['final_total', 'action', 'parent_sale', 'payment_status', 'payment_due'])
                ->make(true);
        }

        $business_locations = BusinessLocation::forDropdown($business_id, false);
        $customers = Contact::customersDropdown($business_id, false);

        $userId = ''; 
        
        if (auth()->user()->can('sellreturn.view_own_sell') && auth()->user()->roles->first()->name != 'Admin#'.$business_id)
        {
            $users = User::allUsersDropdown($business_id, false,session()->get('user.id'));
            $userId = session()->get('user.id');
        }
        else
        {
            $users = User::allUsersDropdown($business_id, false);
        }

        $departmentUser=0;
        
        if(count(auth()->user()->contactAccess) >0) 
        {
            $departmentUser=1;
        }

        return view('sell_return_genralstore.index')->with(compact('business_locations', 'customers','users','userId','departmentUser'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        if (!auth()->user()->can('sellreturn.create')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        //Check if subscribed or not
        if (!$this->moduleUtil->isSubscribed($business_id)) {
            return $this->moduleUtil->expiredResponse(action('SellReturnController@index'));
        }

        $business_locations = BusinessLocation::forDropdown($business_id);
        //$walk_in_customer = $this->contactUtil->getWalkInCustomer($business_id);
        $currency_details   = $this->transactionUtil->sellorderCurrencyDetails($business_id);
        
        $orderStatuses      = $this->productUtil->orderStatuses();

        $default_sellreturn_status = null;
        if (request()->session()->get('business.enable_purchase_status') != 1) {
            $default_sellreturn_status = 'received';
        }

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

        return view('sell_return_genralstore.create')
            ->with(compact('business_locations','currency_details','orderStatuses','default_sellreturn_status','types','customer_groups'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function add($id)
    {
        if (!auth()->user()->can('sellreturn.create')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        //Check if subscribed or not
        if (!$this->moduleUtil->isSubscribed($business_id)) {
            return $this->moduleUtil->expiredResponse();
        }

        $sell = Transaction::where('business_id', $business_id)
                            ->with(['sell_lines', 'location', 'return_parent', 'contact', 'tax', 'sell_lines.sub_unit', 'sell_lines.product', 'sell_lines.product.unit'])
                            ->find($id);

        foreach ($sell->sell_lines as $key => $value) {
            if (!empty($value->sub_unit_id)) {
                $formated_sell_line = $this->transactionUtil->recalculateSellLineTotals($business_id, $value);
                $sell->sell_lines[$key] = $formated_sell_line;
            }

            $sell->sell_lines[$key]->formatted_qty = $this->transactionUtil->num_f($value->quantity, false, null, true);
        }

        return view('sell_return.add')
            ->with(compact('sell'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        
        if (!auth()->user()->can('sellreturn.create')) {
            abort(403, 'Unauthorized action.');
        }

        try 
        {
            $input = $request->except('_token');

            if (!empty($input['products'])) 
            {
                $business_id = $request->session()->get('user.business_id');

                //Check if subscribed or not
                if (!$this->moduleUtil->isSubscribed($business_id)) {
                    return $this->moduleUtil->expiredResponse(action('SellReturnController@index'));
                }
        
                $user_id = $request->session()->get('user.id');

                

                $transaction_data = $request->only([ 'ref_no', 'contact_id', 'transaction_date', 'total_before_tax', 'location_id','discount_type', 'discount_amount','tax_id', 'tax_amount', 'shipping_details', 'shipping_charges', 'final_total', 'additional_notes', 'exchange_rate']);

            
                $exchange_rate = $transaction_data['exchange_rate'];

                //TODO: Check for "Undefined index: total_before_tax" issue
                //Adding temporary fix by validating
                $request->validate([
                    //'status'            => 'required',
                    'contact_id'        => 'required',
                    'transaction_date'  => 'required',
                    // 'total_before_tax'  => 'required',
                    'location_id'       => 'required',
                    'final_total'       => 'required',
                    'document'          => 'file|max:'. (config('constants.document_size_limit') / 1000)
                ]);
            
                $enable_product_editing = $request->session()->get('business.enable_editing_product_from_purchase');

                //Update business exchange rate.
                Business::update_business($business_id, ['p_exchange_rate' => ($transaction_data['exchange_rate'])]);
            
                $currency_details = $this->transactionUtil->purchaseCurrencyDetails($business_id);
            
                //unformat input values
                $transaction_data['total_before_tax'] = $this->productUtil->num_uf($transaction_data['total_before_tax'], $currency_details)*$exchange_rate;
            
            
                // If discount type is fixed them multiply by exchange rate, else don't
                $transaction_data['discount_amount']    = 0;
                $transaction_data['tax_amount']         = 0;
                $transaction_data['shipping_charges']   = 0;
                $transaction_data['tax_amount']         = $this->productUtil->num_uf($transaction_data['tax_amount'], $currency_details)*$exchange_rate;
                $transaction_data['shipping_charges']   = $this->productUtil->num_uf($transaction_data['shipping_charges'], $currency_details)*$exchange_rate;
                $transaction_data['final_total']        = $this->productUtil->num_uf($transaction_data['final_total'], $currency_details)*$exchange_rate;
                $transaction_data['business_id']        = $business_id;
                $transaction_data['created_by']         = $user_id;
                $transaction_data['type']               = 'sell_return_genralstore';
                $transaction_data['payment_status']     = 'paid';
                $transaction_data['transaction_date']   = $this->productUtil->uf_date($transaction_data['transaction_date'], true);
                //upload document
                $transaction_data['document']           = $this->transactionUtil->uploadFile($request, 'document', 'documents');
            
            
                DB::beginTransaction();

                //Update reference count
                $ref_count = $this->productUtil->setAndGetReferenceCount($transaction_data['type']);
                //Generate reference number
                if (empty($transaction_data['ref_no'])) 
                {
                    $transaction_data['ref_no'] = $this->productUtil->generateReferenceNumber($transaction_data['type'], $ref_count);
                }
                //var_dump($transaction_data); exit;
                $transaction     = Transaction::create($transaction_data);
                $sellorder_lines = [];
                $sellreturns     = $request->input('products');
                

                $this->productUtil->createOrUpdateSellReturnsGenralstoreLines($transaction, $sellreturns, $currency_details, $enable_product_editing);

                //update payment status
                $this->transactionUtil->updatePaymentStatus($transaction->id, $transaction->final_total);

                        //Update quantity returned in sell line
                    $returns = [];
                    $product_lines = $request->input('products');
                    
                    foreach ($product_lines as $sell_line) 
                    {
                        $multiplier = 1;
                        if (!empty($sell_line->sub_unit)) {
                            $multiplier = $sell_line->sub_unit->base_unit_multiplier;
                        }

                        $quantity = $this->transactionUtil->num_uf($sell_line['quantity']) * $multiplier;

                        $quantity_before   = 0;
                        $quantity_formated = $this->transactionUtil->num_f($quantity);

                        // Update quantity in variation location details
                        $this->productUtil->updateProductQuantity($request->input('location_id'), $sell_line['product_id'], $sell_line['variation_id'], $quantity_formated, $quantity_before);
                    }
            
            
                DB::commit();
                
                //$this->notificationUtil->autoSendNotification($business_id, 'sell_return_genralstore', $transaction, $transaction->contact);
                $this->notificationUtil->autoSendWhatsappNotification($business_id, 'sell_return_genralstore', $transaction, $transaction->contact->mobile,'general_store_return_updated','gu');
                if(!is_null($transaction->contact->alternate_number))
                {
                    $this->notificationUtil->autoSendWhatsappNotification($business_id, 'sell_return_genralstore', $transaction, $transaction->contact->alternate_number,'general_store_return_updated','gu');
                }
                $receipt = $this->receiptContent($business_id, $request->input('location_id'), $transaction->id);
                $output = ['success' => 1, 'msg' => __('sellreturn.sellreturn_add_success'), 'receipt' => $receipt ];
                // $output = ['success' => 1,
                //             'msg' => __('sellreturn.sellreturn_add_success')
                //         ];
            }
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());
            
            $output = ['success' => 0,
                            'msg' => __('messages.something_went_wrong')
                        ];
        }
        //route(\'sellreturngenralstore.printInvoice\', [$id])
        // return redirect()->route('sellreturngenralstore.printInvoice', ['id' => $transaction->id]);
        return $output;
        return redirect('sellreturngenralstore')->with('status', $output);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        if (!auth()->user()->can('sellreturn.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $sell_return = Transaction::where('business_id', $business_id)
                                ->where('id', $id)
                                ->with(
                                    'contact',
                                    'return_parent',
                                    'tax',
                                    'sellreturngenralstore_lines',
                                    'sellreturngenralstore_lines.product',
                                    'sellreturngenralstore_lines.variations',
                                    'sellreturngenralstore_lines.sub_unit',
                                    'sellreturngenralstore_lines.product',
                                    'sellreturngenralstore_lines.product.unit',
                                    'location'
                                )
                                ->first();
        
        foreach ($sell_return->sellreturngenralstore_lines as $key => $value) {
            if (!empty($value->sub_unit_id)) {
                $formated_sell_line = $this->transactionUtil->recalculateSellLineTotals($business_id, $value);
                $sell_return->sellreturngenralstore_lines[$key] = $formated_sell_line;
            }
        }

        $sell_taxes = [];
        if (!empty($sell_return->return_parent->tax)) {
            if ($sell_return->return_parent->tax->is_tax_group) {
                $sell_taxes = $this->transactionUtil->sumGroupTaxDetails($this->transactionUtil->groupTaxDetails($sell_return->return_parent->tax, $sell_return->return_parent->tax_amount));
            } else {
                $sell_taxes[$sell_return->return_parent->tax->name] = $sell_return->return_parent->tax_amount;
            }
        }

        $total_discount = 0;
        // if ($sell->return_parent->discount_type == 'fixed') {
        //     $total_discount = $sell->return_parent->discount_amount;
        // } elseif ($sell->return_parent->discount_type == 'percentage') {
        //     $total_after_discount = $sell->return_parent->final_total - $sell->return_parent->tax_amount;
        //     $total_before_discount = $total_after_discount * 100 / (100 - $sell->return_parent->discount_amount);
        //     $total_discount = $total_before_discount - $total_after_discount;
        // }
        
        return view('sell_return_genralstore.show')
            ->with(compact('sell_return', 'sell_taxes', 'total_discount'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        if (!auth()->user()->can('sellreturn.update')) {
            abort(403, 'Unauthorized action.');
        }
        
        $business_id = request()->session()->get('user.business_id');

        //Check if subscribed or not
        if (!$this->moduleUtil->isSubscribed($business_id)) {
            return $this->moduleUtil->expiredResponse(action('PurchaseController@index'));
        }

        //Check if the transaction can be edited or not.
        $edit_days = request()->session()->get('business.transaction_edit_days');
        if (!$this->transactionUtil->canBeEdited($id, $edit_days)) {
            return back()
                ->with('status', ['success' => 0,
                    'msg' => __('messages.transaction_edit_not_allowed', ['days' => $edit_days])]);
        }

        //Check if return exist then not allowed
        if ($this->transactionUtil->isReturnExist($id)) {
            return back()->with('status', ['success' => 0,
                    'msg' => __('lang_v1.return_exist')]);
        }

        $business = Business::find($business_id);

        $currency_details = $this->transactionUtil->sellreturnCurrencyDetails($business_id);
        //echo "<pre>"; var_dump($currency_details); exit;
        $taxes = TaxRate::where('business_id', $business_id)
                            ->get();
        $sellreturn = Transaction::where('business_id', $business_id)
                    ->where('id', $id)
                    ->with(
                        'contact',
                        'sellreturngenralstore_lines',
                        'sellreturngenralstore_lines.product',
                        'sellreturngenralstore_lines.product.unit',
                        'sellreturngenralstore_lines.variations',
                        'sellreturngenralstore_lines.variations.product_variation',
                        'sellreturngenralstore_lines.sub_unit',
                        'location',
                        'payment_lines',
                        'tax'
                    )
                    ->first();
        // echo "<pre>";
        // var_dump($sell_return); 
        // exit;
        foreach ($sellreturn->sellreturngenralstore_lines as $key => $value) {
            if (!empty($value->sub_unit_id)) {
                $formated_sellorder_line = $this->productUtil->changeSellorderLineUnit($value);
                $sellreturn->sellreturngenralstore_lines[$key] = $formated_sellorder_line;
            }
        }
        
        $taxes = TaxRate::where('business_id', $business_id)
                            ->get();
        $orderStatuses = $this->productUtil->orderStatuses();

        $business_locations = BusinessLocation::forDropdown($business_id);

        $default_purchase_status = null;
        if (request()->session()->get('business.enable_purchase_status') != 1) {
            $default_purchase_status = 'received';
        }

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

        $business_details = $this->businessUtil->getDetails($business_id);
        $shortcuts = json_decode($business_details->keyboard_shortcuts, true);

        return view('sell_return_genralstore.edit')
            ->with(compact(
                'taxes',
                'sellreturn',
                // 'sellreturngenralstore_lines',
                'taxes',
                'orderStatuses',
                'business_locations',
                'business',
                'currency_details',
                'default_purchase_status',
                'customer_groups',
                'types',
                'shortcuts'
            ));
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
        if (!auth()->user()->can('sellreturn.update')) {
            abort(403, 'Unauthorized action.');
        }

        try 
        {
                $input = $request->except('_token');
                          
                $business_id = $request->session()->get('user.business_id');

                //Check if subscribed or not
                if (!$this->moduleUtil->isSubscribed($business_id)) {
                    return $this->moduleUtil->expiredResponse(action('SellReturnController@index'));
                }
        
                $user_id = $request->session()->get('user.id');
    
                $update_data = $request->only([ 'ref_no', 'contact_id', 'transaction_date', 'total_before_tax', 'location_id','discount_type', 'discount_amount','tax_id', 'tax_amount', 'shipping_details', 'shipping_charges', 'final_total', 'additional_notes', 'exchange_rate']);
        
                $exchange_rate = $update_data['exchange_rate'];

                //TODO: Check for "Undefined index: total_before_tax" issue
                //Adding temporary fix by validating
                $transaction = Transaction::findOrFail($id);

                //Validate document size
                $request->validate([
                    'document' => 'file|max:'. (config('constants.document_size_limit') / 1000)
                ]);
            
                $enable_product_editing = $request->session()->get('business.enable_editing_product_from_purchase');

                //Update business exchange rate.
                //Business::update_business($business_id, ['p_exchange_rate' => ($update_data['exchange_rate'])]);
            
                $currency_details = $this->transactionUtil->purchaseCurrencyDetails($business_id);
            
                //unformat input values
                $update_data['total_before_tax'] = $this->productUtil->num_uf($update_data['total_before_tax'], $currency_details)*$exchange_rate;
            
            
                // If discount type is fixed them multiply by exchange rate, else don't
                $update_data['discount_amount']    = 0;
                $update_data['tax_amount']         = 0;
                $update_data['shipping_charges']   = 0;
                $update_data['tax_amount']         = $this->productUtil->num_uf($update_data['tax_amount'], $currency_details)*$exchange_rate;
                $update_data['shipping_charges']   = $this->productUtil->num_uf($update_data['shipping_charges'], $currency_details)*$exchange_rate;
                $update_data['final_total']        = $this->productUtil->num_uf($update_data['final_total'], $currency_details)*$exchange_rate;
                $update_data['business_id']        = $business_id;
                $update_data['created_by']         = $user_id;
                $update_data['type']               = 'sell_return_genralstore';
                $update_data['payment_status']     = 'paid';
                $update_data['transaction_date']   = $this->productUtil->uf_date($update_data['transaction_date'], true);
                //upload document
                $update_data['document']           = $this->transactionUtil->uploadFile($request, 'document', 'documents');
            
            
                DB::beginTransaction();

                //Update reference count
                $ref_count = $this->productUtil->setAndGetReferenceCount($update_data['type']);
                //Generate reference number
                if (empty($update_data['ref_no'])) 
                {
                    $update_data['ref_no'] = $this->productUtil->generateReferenceNumber($update_data['type'], $ref_count);
                }

                //var_dump($update_data); exit;
                //$transaction     = Transaction::create($update_data);
                $transaction->update($update_data);
                $sellorder_lines = [];
                $sellreturns     = $request->input('products');
                
                

                

                //update payment status
                $this->transactionUtil->updatePaymentStatus($transaction->id, $transaction->final_total);

                //         //Update quantity returned in sell line
                $returns = [];
                $product_lines      = $request->input('products');
                $quantity_before    = 0;
                $non_deleted_items  = [];
                foreach ($product_lines as $sell_line) 
                {
                    $multiplier = 1;
                    if (!empty($sell_line->sub_unit)) {
                        $multiplier = $sell_line->sub_unit->base_unit_multiplier;
                    }

                    if (isset($sell_line['sellreturn_line_id'])) 
                    {
                        $quantity = SellReturnLines::where('transaction_id', $id)
                        ->where('id', $sell_line['sellreturn_line_id'])
                        ->select('quantity_returned')->first();

                        $quantity_before   = $quantity['quantity_returned'];
                        $non_deleted_items[] = $sell_line['sellreturn_line_id'];
                        
                    }

                    $quantity = $this->transactionUtil->num_uf($sell_line['quantity']) * $multiplier;

                    
                    $quantity_formated = $this->transactionUtil->num_f($quantity);

                    // Update quantity in variation location details
                    $this->productUtil->updateProductQuantity($request->input('location_id'), $sell_line['product_id'], $sell_line['variation_id'], $quantity_formated, $quantity_before);
                }

                if(!empty($non_deleted_items))
                {   
                    $deletedproducts = SellReturnLines::where('transaction_id', $id)
                        ->whereNotIn('id', $non_deleted_items)
                        ->get();

                    foreach ($deletedproducts as $deletedproduct) 
                    {
                        
                        $quantity_before   = $deletedproduct['quantity_returned'];
                        $quantity_formated = 0;
                        $this->productUtil->updateProductQuantity($request->input('location_id'), $sell_line['product_id'], $sell_line['variation_id'], $quantity_formated, $quantity_before);
                    }
            
                }
                else
                {
                    $deletedproducts = SellReturnLines::where('transaction_id', $id)
                        ->get();

                    foreach ($deletedproducts as $deletedproduct) 
                    {
                        // var_dump($deletedproduct['product_id']);
                        // exit;
                        $quantity_before   = $deletedproduct['quantity_returned'];
                        $quantity_formated = 0;
                        
                        $this->productUtil->updateProductQuantity($request->input('location_id'), $deletedproduct['product_id'], $deletedproduct['variation_id'], $quantity_formated, $quantity_before);
                        
                    }
                }
                
                $this->productUtil->createOrUpdateSellReturnsGenralstoreLines($transaction, $sellreturns, $currency_details, $enable_product_editing);
                DB::commit();
                $this->notificationUtil->autoSendNotification($business_id, 'sell_return_genralstore', $transaction, $transaction->contact);
                $receipt = $this->receiptContent($business_id, $request->input('location_id'), $transaction->id);
                $output = ['success' => 1, 'msg' => __('sellreturn.sellreturn_add_success'), 'receipt' => $receipt ];
                
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());
            
            $output = ['success' => 0,
                            'msg' => __('messages.something_went_wrong')
                        ];
        }
        
        return $output;
        return redirect('sellreturngenralstore')->with('status', $output);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if (!auth()->user()->can('sellreturn.update')) {
            abort(403, 'Unauthorized action.');
        }

        try 
        {
                //TODO: Check for "Undefined index: total_before_tax" issue
                //Adding temporary fix by validating
                $transaction = Transaction::findOrFail($id)->first();
                $location_id = $transaction['location_id'];

                DB::beginTransaction();

                $deletedproducts = SellReturnLines::where('transaction_id', $id)
                ->get();

                foreach ($deletedproducts as $deletedproduct) 
                {
                    
                    $quantity_before   = 0;
                    $quantity_formated = $deletedproduct['quantity_returned'];
                    $this->productUtil->updateProductQuantity($location_id, $deletedproduct['product_id'], $deletedproduct['variation_id'], $quantity_formated, $quantity_before);
                }
                
                SellReturnLines::where('transaction_id', $id)->delete();
                Transaction::where('id', $id)->delete();
                DB::commit();
            
                $output = ['success' => 1,
                            'msg' => __('sellreturn.sellreturn_delete_success')
                        ];
        } 
        catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());
            
            $output = ['success' => 0,
                            'msg' => __('messages.something_went_wrong')
                        ];
        }
        
        return $output;
    }

    /**
     * Return the row for the product
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function getProductRow()
    {
    }

    /**
     * Retrieves products list.
     *
     * @return \Illuminate\Http\Response
     */
    public function getProducts()
    {
        if (request()->ajax()) {
            $term = request()->term;

            $check_enable_stock = true;
            if (isset(request()->check_enable_stock)) {
                $check_enable_stock = filter_var(request()->check_enable_stock, FILTER_VALIDATE_BOOLEAN);
            }

            if (empty($term)) {
                return json_encode([]);
            }

            $business_id = request()->session()->get('user.business_id');
            $q = Product::leftJoin(
                'variations',
                'products.id',
                '=',
                'variations.product_id'
            )
                ->where(function ($query) use ($term) {
                    $query->where('products.name', 'like', '%' . $term .'%');
                    $query->orWhere('sku', 'like', '%' . $term .'%');
                    $query->orWhere('sub_sku', 'like', '%' . $term .'%');
                })
                ->active()
                ->where('business_id', $business_id)
                ->whereNull('variations.deleted_at')
                ->select(
                    'products.id as product_id',
                    'products.name',
                    'products.type',
                    // 'products.sku as sku',
                    'variations.id as variation_id',
                    'variations.name as variation',
                    'variations.sub_sku as sub_sku'
                )
                ->groupBy('variation_id');

            if ($check_enable_stock) {
                $q->where('enable_stock', 1);
            }

            if(auth()->user()->selected_category)
            {
                $categoryIds = auth()->user()->categoryAccess->pluck('id')->toArray();
                $q->whereIn('category_id', $categoryIds);
            }
            $products = $q->get();
                
            $products_array = [];
            foreach ($products as $product) {
                $products_array[$product->product_id]['name'] = $product->name;
                $products_array[$product->product_id]['sku'] = $product->sub_sku;
                $products_array[$product->product_id]['type'] = $product->type;
                $products_array[$product->product_id]['variations'][]
                = [
                        'variation_id' => $product->variation_id,
                        'variation_name' => $product->variation,
                        'sub_sku' => $product->sub_sku
                        ];
            }

            $result = [];
            $i = 1;
            $no_of_records = $products->count();
            if (!empty($products_array)) {
                foreach ($products_array as $key => $value) {
                    if ($no_of_records > 1 && $value['type'] != 'single') {
                        $result[] = [ 'id' => $i,
                                    'text' => $value['name'] . ' - ' . $value['sku'],
                                    'variation_id' => 0,
                                    'product_id' => $key
                                ];
                    }
                    $name = $value['name'];
                    foreach ($value['variations'] as $variation) {
                        $text = $name;
                        if ($value['type'] == 'variable') {
                            $text = $text . ' (' . $variation['variation_name'] . ')';
                        }
                        $i++;
                        $result[] = [ 'id' => $i,
                                            'text' => $text . ' - ' . $variation['sub_sku'],
                                            'product_id' => $key ,
                                            'variation_id' => $variation['variation_id'],
                                        ];
                    }
                    $i++;
                }
            }
            
            return json_encode($result);
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
        $printer_type = null
    ) {
        $output = ['is_enabled' => false,
                    'print_type' => 'browser',
                    'html_content' => null,
                    'printer_config' => [],
                    'data' => []
                ];

        $business_details = $this->businessUtil->getDetails($business_id);
        $location_details = BusinessLocation::find($location_id);

        //Check if printing of invoice is enabled or not.
        if ($location_details->print_receipt_on_invoice == 1) {
            //If enabled, get print type.
            $output['is_enabled'] = true;

            $invoice_layout = $this->businessUtil->invoiceLayout($business_id, $location_id, $location_details->invoice_layout_id);

            //Check if printer setting is provided.
            $receipt_printer_type = is_null($printer_type) ? $location_details->receipt_printer_type : $printer_type;

            $receipt_details = $this->transactionUtil->getReceiptDetails($transaction_id, $location_id, $invoice_layout, $business_details, $location_details, $receipt_printer_type);
            
            //If print type browser - return the content, printer - return printer config data, and invoice format config
            if ($receipt_printer_type == 'printer') {
                $output['print_type'] = 'printer';
                $output['printer_config'] = $this->businessUtil->printerConfig($business_id, $location_details->printer_id);
                $output['data'] = $receipt_details;
            } else {
                $output['html_content'] = view('sell_return_genralstore.receipt', compact('receipt_details'))->render();
            }
        }

        return $output;
    }

    /**
     * Prints invoice for sell
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function printInvoice(Request $request, $transaction_id)
    {
        // if (request()->ajax()) {
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

                // var_dump($receipt);
                // exit;
                if (!empty($receipt)) {
                    $output = ['success' => 1, 'receipt' => $receipt];
                }

                $receipt = $this->receiptContent($business_id, $transaction->location_id, $transaction_id, 'browser');

                if (!empty($receipt)) {
                    $output = ['success' => 1, 'receipt' => $receipt];
                }
            } catch (\Exception $e) {
                $output = ['success' => 0,
                        'msg' => trans("messages.something_went_wrong")
                        ];
            }

            return $output;
        // }
    }

    /**
     * Retrieves products list.
     *
     * @return \Illuminate\Http\Response
     */
    public function getSellReturnEntryRow(Request $request)
    {
        if (request()->ajax()) {
            $product_id = $request->input('product_id');
            $variation_id = $request->input('variation_id');
            $business_id = request()->session()->get('user.business_id');
            $product_qty = $request->input('product_qty');

            $hide_tax = 'hide';
            if ($request->session()->get('business.enable_inline_tax') == 1) {
                $hide_tax = '';
            }

            $currency_details = $this->transactionUtil->purchaseCurrencyDetails($business_id);
            
            if (!empty($product_id)) {
                $row_count = $request->input('row_count');
                $product = Product::where('id', $product_id)
                                    ->with(['unit'])
                                    ->first();

                $sub_units = $this->productUtil->getSubUnits($business_id, $product->unit->id);

                $query = Variation::where('product_id', $product_id)
                                        ->with(['product_variation']);
                if ($variation_id !== '0') {
                    $query->where('id', $variation_id);
                }

                $variations =  $query->get();
                
                $taxes = TaxRate::where('business_id', $business_id)
                            ->get();

                return view('sell_return_genralstore.partials.sellreturn_entry_row')
                    ->with(compact(
                        'product',
                        'variations',
                        'row_count',
                        'variation_id',
                        'taxes',
                        'currency_details',
                        'hide_tax',
                        'sub_units',
                        'product_qty'
                    ));
            }
        }
    }

    /**
     * Checks if ref_number and supplier combination already exists.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function checkRefNumber(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');
        $contact_id = $request->input('contact_id');
        $ref_no = $request->input('ref_no');
        $sellreturn_id = $request->input('sellreturn_id');

        $count = 0;
        if (!empty($contact_id) && !empty($ref_no)) {
            //check in transactions table
            $query = Transaction::where('business_id', $business_id)
                            ->where('ref_no', $ref_no)
                            ->where('contact_id', $contact_id);
            if (!empty($sellreturn_id)) {
                $query->where('id', '!=', $sellreturn_id);
            }
            $count = $query->count();
        }
        if ($count == 0) {
            echo "true";
            exit;
        } else {
            echo "false";
            exit;
        }
    }
}
