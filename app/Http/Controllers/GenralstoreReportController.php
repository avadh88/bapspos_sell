<?php

namespace App\Http\Controllers;

use App\Brands;
use App\BusinessLocation;
use App\CashRegister;
use App\Category;

use App\Contact;
use App\CustomerGroup;

use App\ExpenseCategory;
use App\Product;
use App\PurchaseLine;
use App\Restaurant\ResTable;
use App\SellingPriceGroup;
use App\Transaction;
use App\TransactionPayment;
use App\TransactionSellLine;
use App\TransactionSellLinesPurchaseLines;
use App\Unit;
use App\User;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use App\Variation;
use App\VariationLocationDetails;
use App\SellOrderLines;
use Charts;
use Datatables;
use DB;
use Illuminate\Http\Request;
use App\Utils\NotificationUtil;
use Illuminate\Support\Facades\Log;

class GenralstoreReportController extends Controller
{
    /**
     * All Utils instance.
     *
     */
    protected $transactionUtil;
    protected $productUtil;
    protected $notificationUtil;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(TransactionUtil $transactionUtil, ProductUtil $productUtil,NotificationUtil $notificationUtil)
    {
        $this->transactionUtil  = $transactionUtil;
        $this->productUtil      = $productUtil;
        $this->notificationUtil = $notificationUtil;
    }

    
    /**
     * get Departmentwise demand report
     *
     * @return \Illuminate\Http\Response
     */
    public function getDepartmentWiseDemandReport(Request $request)
    {
        if (!auth()->user()->can('genralstore_report.departmentwisedemandreport')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');

        
        //Return the details in ajax call
        if ($request->ajax()) {
            
            
            $products = DB::table('sell_order_lines as sol')
            ->join('transactions as trans', 'trans.id', '=', 'sol.transaction_id')
            ->join('contacts','contacts.id','=','trans.contact_id')
            ->join('products','products.id','=','sol.product_id');

            $products->select(DB::raw('SUM(sol.quantity) as quantity'),'products.name as product_name','products.sku as product_sku','trans.transaction_date','trans.ref_no','contacts.name as customer','sol.purchase_price','sol.sell_order_date');
            if (!empty($business_id)) 
            {
                $products->where('trans.business_id', $business_id);
            }
            if (!empty($request->input('location_id'))) 
            {
                $products->where('trans.location_id', $request->input('location_id'));
            }
            if (!empty($request->input('ir_customer_id'))) 
            {
                $products->where('trans.contact_id', $request->input('ir_customer_id'));
            }
            if (!empty($request->input('date_range'))) {
                $date_range = $request->input('date_range');
                $date_range_array = explode('~', $date_range);
                $start = $filters['start_date'] = $this->transactionUtil->uf_date(trim($date_range_array[0]));
                $end   = $filters['end_date'] = $this->transactionUtil->uf_date(trim($date_range_array[1]));

                $products->whereDate('sol.sell_order_date', '>=', $start)
                        ->whereDate('sol.sell_order_date', '<=', $end);
            }
            if (!empty($request->input('search_product')))
            {
                $products->where('sol.product_id',$request->input('search_product'));
            }
            
            $products->groupBy('sol.product_id','trans.ref_no');
            // ->orderBY('trans.transaction_date','trans.ref_no')
            
            
            
            return Datatables::of($products)
                ->editColumn('billnumber', function ($row) {
                    return $row->ref_no;
                })
                ->editColumn('transaction_date', function ($row) {
                    // return $this->productUtil->format_date($row->transaction_date, true);
                    return $this->productUtil->format_date($row->sell_order_date, true);
                })
                ->editColumn('quantity', function ($row) {
                    return '<span class="quantity" data-orig-value="' . (float)$row->quantity . '" >' . (float)$row->quantity . '</span>';
                })
                ->editColumn('purchase_price', function ($row) {
                    return '<span class="purchase_price" data-orig-value="' . $row->purchase_price . '" >' . $row->purchase_price . '</span>';
                })
                
                ->editColumn('subtotal', function ($row) {
                    $subtotal = $row->purchase_price * (float)$row->quantity;
                    return '<span class="display_currency subtotal" data-currency_symbol=true data-orig-value="' . $subtotal . '">' . $subtotal . '</span>';
                })
                ->rawColumns(['quantity','purchase_price','subtotal'])
                ->make(true);
        }

        $categories = Category::where('business_id', $business_id)
                            ->where('parent_id', 0)
                            ->pluck('name', 'id');
        $business_locations = BusinessLocation::forDropdown($business_id, true);

        $customers = Contact::customersDropdown($business_id, false);

        return view('genralstore_reports.departmentwise_demand_report')
                ->with(compact('categories','business_locations','customers'));
    }

    /**
     * get Total demand report
     *
     * @return \Illuminate\Http\Response
     */
    public function getTotalDemandReport (Request $request)
    {
        if (!auth()->user()->can('genralstore_report.totaldemandreport')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');
        $date_range   = '';
        $query_string = '';
        $location_id  = '';
        
        $permitted_locations = auth()->user()->permitted_locations();

        //Return the details in ajax call
        if ($request->ajax()) {
            $location_filter = '';
            $customer_filter = '';
            $product_filter  = '';
            $date_filter     = '';

            $products = DB::table('sell_order_lines as sol')
            ->join('transactions as trans', 'trans.id', '=', 'sol.transaction_id')
            ->join('contacts','contacts.id','=','trans.contact_id')
            ->join('products','products.id','=','sol.product_id')
            ->join('units','products.unit_id','=','units.id');
            //->leftjoin('variation_location_details','sol.product_id','=','variation_location_details.product_id');

            //$products->whereRaw('trans.location_id=variation_location_details.location_id');
            //$products->select(DB::raw('SUM(sol.quantity) as quantity'),DB::raw('SUM(variation_location_details.qty_available) as current_stock'),'products.name as product_name','products.id as product_id','sol.purchase_price');
            

            // $products->select(DB::raw('SUM(sol.quantity) as quantity'),DB::raw('SUM(variation_location_details.qty_available) as current_stock'),'products.name as product_name','products.id as product_id','trans.transaction_date','trans.ref_no','contacts.name as customer','sol.purchase_price');
            if (!empty($business_id)) 
            {
                $products->where('trans.business_id', $business_id);
            }
            
            if ($permitted_locations != 'all') 
            {
            
                $locations_imploded = implode(', ', $permitted_locations);
                $products->whereIn('trans.location_id',[$locations_imploded]);
                $location_filter .= "AND transactions.location_id IN ($locations_imploded) ";
                
            }

            if (!empty($request->input('location_id'))) {
                $location_id = $request->input('location_id');
                $location_filter .= "AND transactions.location_id=$location_id";
            }
            $customer_id = $request->input('ir_customer_id');
            if ($customer_id) {
                $customer_filter .= "AND transactions.contact_id=$customer_id";
            }
            

            if (!empty($request->input('date_range'))) {
                $date_range = $request->input('date_range');
                $date_range_array = explode('~', $date_range);
                $start = $filters['start_date'] = $this->transactionUtil->uf_date(trim($date_range_array[0]));
                $end   = $filters['end_date'] = $this->transactionUtil->uf_date(trim($date_range_array[1]));

                $date_filter .= "AND (transactions.transaction_date >= '$start 00:00:00' and transactions.transaction_date <= '$end 23:59:59')";
            }

            // if (!empty($request->input('location_id'))) 
            // {
            //     $location_id = $request->input('location_id');
            //     $products->where('trans.location_id',$location_id);
            //     $query_string .= $query_string == '' ? "?location_id=".$request->input('location_id') : "&location_id=".$request->input('location_id');
                
            // }
            if (!empty($request->input('location_id'))) 
            {
                $location_id = $request->input('location_id');
                $products->where('trans.location_id', $request->input('location_id'));
                //$products->where('variation_location_details.location_id', $request->input('location_id'));
                $query_string .= $query_string == '' ? "?location_id=".$request->input('location_id') : "&location_id=".$request->input('location_id'); 
            }
            
            
            if (!empty($request->input('ir_customer_id'))) 
            {
                $products->where('trans.contact_id', $request->input('ir_customer_id'));
                $query_string .= $query_string == '' ? "?ir_customer_id=".$request->input('ir_customer_id') : "&ir_customer_id=".$request->input('ir_customer_id');
            }
            
            if (!empty($request->input('date_range'))) {
                $date_range = $request->input('date_range');
                $date_range_array = explode('~', $date_range);
                $start = $filters['start_date'] = $this->transactionUtil->uf_date(trim($date_range_array[0]));
                $end   = $filters['end_date'] = $this->transactionUtil->uf_date(trim($date_range_array[1]));

                $products->whereDate('sol.sell_order_date', '>=', $start)
                        ->whereDate('sol.sell_order_date', '<=', $end);
            }
            if (!empty($request->input('category_id'))) 
            {
                $products->where('products.category_id',$request->input('category_id'));
                $query_string .= $query_string == '' ? "?category_id=".$request->input('category_id') : "&category_id=".$request->input('category_id');
            }
            if (!empty($request->input('search_product')))
            {
                $products->where('sol.product_id',$request->input('search_product'));
                $query_string .= $query_string == '' ? "?search_product=".$request->input('search_product') : "&search_product=".$request->input('search_product');
            }
            $products->select(DB::raw("(SELECT COALESCE(SUM(TSL.quantity - TSL.quantity_returned),0) FROM transactions 
            JOIN transaction_sell_lines AS TSL ON transactions.id=TSL.transaction_id
            WHERE transactions.status='final' AND transactions.type='sell' $location_filter $customer_filter $date_filter 
            AND TSL.variation_id=sol.product_id) as total_sold"),

            DB::raw("(SELECT COALESCE(SUM(SRL.quantity),0) FROM transactions 
                        JOIN sell_return_lines AS SRL ON transactions.id=SRL.transaction_id
                        WHERE transactions.type='sell_return_genralstore' $location_filter $customer_filter $date_filter
                        AND SRL.variation_id=sol.product_id) as total_returned"),

            DB::raw('SUM(sol.quantity) as quantity'),'products.sku as sku','products.name as product_name','products.id as product_id','sol.purchase_price','units.actual_name as units'
            );

            $products->where('trans.type','sellorder');
            // $products->select(DB::raw("(SELECT COALESCE(SUM(TSL.quantity - TSL.quantity_returned),0) FROM transactions 
            // JOIN transaction_sell_lines AS TSL ON transactions.id=TSL.transaction_id
            // WHERE transactions.status='final' AND transactions.type='sell' AND transactions.permanent_sell='0' $location_filter $customer_filter $date_filter
            // AND TSL.variation_id=sol.product_id) as total_sold"));
            $products->groupBy('sol.product_id');
            
            $data = $products->toSql();
            
            $remainProduct=0;
            $outstanding=0;
            $purchasable=0;
            return Datatables::of($products)
                
                ->editColumn('quantity', function ($row) use ($date_range,$query_string) {
                    return '<span class="quantity" data-orig-value="' . (float)$row->quantity . '" ><a data-href="' . action('GenralstoreReportController@getTotalDemandDetailReport', [$row->product_id,$date_range])
                            .$query_string.'" href="#" data-container=".view_modal" class="btn-modal">' . (float)$row->quantity . '</a>';
                    
                    return '<span class="quantity" data-orig-value="' . (float)$row->quantity . '" ><a href="/genralstore_reports/total_demand_deatil_report/'.$row->product_id.'/'.$date_range.'/print">' . (float)$row->quantity . '</a></span>';
                })
                ->addColumn('purchase_qty', function ($row) use ($permitted_locations,$location_id,$request,$remainProduct,$purchasable) { 

                    
                    $products = DB::table('sell_order_lines as sol')
                    ->join('transactions as trans', 'trans.id', '=', 'sol.transaction_id')
                    ->select('trans.contact_id')->distinct();
                    if (!empty($business_id)) 
                    {
                        $products->where('trans.business_id', $business_id);
                    }
                    if (!empty($request->input('location_id'))) 
                    {
                        $products->where('trans.location_id', $request->input('location_id'));
                    }
                    if (!empty($request->input('ir_customer_id'))) 
                    {
                        $products->where('trans.contact_id', $request->input('ir_customer_id'));
                    }
                    $products->where('sol.product_id',$row->product_id);
                    if (!empty($date_range)) {
                        $date_range = $date_range;
                        $date_range_array = explode('~', $date_range);
                        $start = $filters['start_date'] = $this->transactionUtil->uf_date(trim($date_range_array[0]));
                        $end   = $filters['end_date'] = $this->transactionUtil->uf_date(trim($date_range_array[1]));

                        $products->whereDate('sol.sell_order_date', '>=', $start)
                                ->whereDate('sol.sell_order_date', '<=', $end);
                    }
                    //$productsData = $products->groupBy('trans.contact_id')->get();
                    $contactIds = $products->groupBy('trans.contact_id')->pluck('contact_id')->toArray();
                    


                    $products = DB::table('purchase_lines as pl')
                    ->join('transactions as trans', 'trans.id', '=', 'pl.transaction_id');

                    $products->select(DB::raw('(COALESCE(sum(pl.quantity),0)) as quantity'));
                    if (!empty($business_id)) 
                    {
                        $products->where('trans.business_id', $business_id);
                    }
                    if (!empty($request->input('location_id'))) 
                    {
                        $products->where('trans.location_id', $request->input('location_id'));
                    }
                    // // if (!empty($request->input('ir_customer_id'))) 
                    // // {
                        // $products->whereIn('trans.contact_id', $contactIds);
                        $products->where('trans.type','purchase')->where('trans.status','received');
                    // // }
                    if (!empty($request->input('date_range'))) {
                        $date_range = $request->input('date_range');
                        $date_range_array = explode('~', $date_range);
                        $start = $filters['start_date'] = $this->transactionUtil->uf_date(trim($date_range_array[0]));
                        $end   = $filters['end_date'] = $this->transactionUtil->uf_date(trim($date_range_array[1]));

                        $products->whereDate('trans.transaction_date', '>=', $start)
                                ->whereDate('trans.transaction_date', '<=', $end);
                    }
                    
                    $products->where('pl.product_id',$row->product_id);
                    
                    
                    $products->groupBy('pl.product_id');

                    
                    $sellQty   = empty($products->pluck('quantity')->toArray()) ? 0 : $products->pluck('quantity')->first();
                    $purchasable=$sellQty;
                    return $sellQty;
                    // return '<span class="current_stock" data-orig-value="0" >0</span>';
                })
                ->addColumn('current_stock', function ($row) use ($permitted_locations,$location_id) {      
                    $products = DB::table('variation_location_details as vld');
                    $products->select(DB::raw('COALESCE(SUM(vld.qty_available),0) as current_stock'));

                    if ($permitted_locations != 'all') 
                    {
                    
                        $locations_imploded = implode(', ', $permitted_locations);
                        $products->whereIn('vld.location_id',[$locations_imploded]);
                        
                    }
                    if (!empty($location_id)) 
                    {
                        $products->where('vld.location_id', $location_id);
                    }
                    
                    $products->where('vld.product_id',$row->product_id);
                    
                    $current_stock = $products->get()->first();

                    return '<span class="current_stock" data-orig-value="'.$current_stock->current_stock.'" >'.$current_stock->current_stock.'</span>';
                    // if($current_stock)
                    // {echo "ok";
                    //     var_dump($current_stock);
                    //     exit;
                        
                    // }
                    // else
                    // {
                    //     return '<span class="current_stock" data-orig-value="0" >0</span>';
                    // }
                    
                })
                ->addColumn('current_stock', function ($row) use ($permitted_locations,$location_id) {      
                    $products = DB::table('variation_location_details as vld');
                    $products->select(DB::raw('COALESCE(SUM(vld.qty_available),0) as current_stock'));

                    if ($permitted_locations != 'all') 
                    {
                    
                        $locations_imploded = implode(', ', $permitted_locations);
                        $products->whereIn('vld.location_id',[$locations_imploded]);
                        
                    }
                    if (!empty($location_id)) 
                    {
                        $products->where('vld.location_id', $location_id);
                    }
                    
                    $products->where('vld.product_id',$row->product_id);
                    
                    $current_stock = $products->get()->first();

                    return '<span class="current_stock" data-orig-value="'.$current_stock->current_stock.'" >'.$current_stock->current_stock.'</span>';
                    // if($current_stock)
                    // {echo "ok";
                    //     var_dump($current_stock);
                    //     exit;
                        
                    // }
                    // else
                    // {
                    //     return '<span class="current_stock" data-orig-value="0" >0</span>';
                    // }
                    
                })
                ->addColumn('delivered', function ($row) use ($permitted_locations,$location_id,$request,$remainProduct,$purchasable) { 

                    
                    $products = DB::table('sell_order_lines as sol')
                    ->join('transactions as trans', 'trans.id', '=', 'sol.transaction_id')
                    ->select('trans.contact_id')->distinct();
                    if (!empty($business_id)) 
                    {
                        $products->where('trans.business_id', $business_id);
                    }
                    if (!empty($request->input('location_id'))) 
                    {
                        $products->where('trans.location_id', $request->input('location_id'));
                    }
                    if (!empty($request->input('ir_customer_id'))) 
                    {
                        $products->where('trans.contact_id', $request->input('ir_customer_id'));
                    }
                    $products->where('sol.product_id',$row->product_id);
                    if (!empty($date_range)) {
                        $date_range = $date_range;
                        $date_range_array = explode('~', $date_range);
                        $start = $filters['start_date'] = $this->transactionUtil->uf_date(trim($date_range_array[0]));
                        $end   = $filters['end_date'] = $this->transactionUtil->uf_date(trim($date_range_array[1]));

                        $products->whereDate('sol.sell_order_date', '>=', $start)
                                ->whereDate('sol.sell_order_date', '<=', $end);
                    }
                    //$productsData = $products->groupBy('trans.contact_id')->get();
                    $contactIds = $products->groupBy('trans.contact_id')->pluck('contact_id')->toArray();
                    


                    $products = DB::table('transaction_sell_lines as tsl')
                    ->join('transactions as trans', 'trans.id', '=', 'tsl.transaction_id');

                    $products->select(DB::raw('(COALESCE(sum(tsl.quantity),0)) as quantity'));
                    if (!empty($business_id)) 
                    {
                        $products->where('trans.business_id', $business_id);
                    }
                    if (!empty($request->input('location_id'))) 
                    {
                        $products->where('trans.location_id', $request->input('location_id'));
                    }
                    // if (!empty($request->input('ir_customer_id'))) 
                    // {
                        $products->whereIn('trans.contact_id', $contactIds);
                        $products->where('trans.type','sell')->where('trans.status','final');
                    // }
                    if (!empty($request->input('date_range'))) {
                        $date_range = $request->input('date_range');
                        $date_range_array = explode('~', $date_range);
                        $start = $filters['start_date'] = $this->transactionUtil->uf_date(trim($date_range_array[0]));
                        $end   = $filters['end_date'] = $this->transactionUtil->uf_date(trim($date_range_array[1]));

                        $products->whereDate('trans.transaction_date', '>=', $start)
                                ->whereDate('trans.transaction_date', '<=', $end);
                    }
                    
                    $products->where('tsl.product_id',$row->product_id);
                    
                    
                    $products->groupBy('tsl.product_id');

                    //sell return qty

                    $productsReturn = DB::table('sell_return_lines as srl')
                    ->join('transactions as trans', 'trans.id', '=', 'srl.transaction_id');

                    $productsReturn->select(DB::raw('(COALESCE(sum(srl.quantity),0)) as quantity'));
                    if (!empty($business_id)) 
                    {
                        $productsReturn->where('trans.business_id', $business_id);
                    }
                    if (!empty($request->input('location_id'))) 
                    {
                        $productsReturn->where('trans.location_id', $request->input('location_id'));
                    }
                    // if (!empty($request->input('ir_customer_id'))) 
                    // {
                        $productsReturn->whereIn('trans.contact_id', $contactIds);
                        $productsReturn->where('trans.type','sell_return_genralstore')->where('trans.status','received');
                    // }
                    if (!empty($request->input('date_range'))) {
                        $date_range = $request->input('date_range');
                        $date_range_array = explode('~', $date_range);
                        $start = $filters['start_date'] = $this->transactionUtil->uf_date(trim($date_range_array[0]));
                        $end   = $filters['end_date'] = $this->transactionUtil->uf_date(trim($date_range_array[1]));

                        $productsReturn->whereDate('trans.transaction_date', '>=', $start)
                                ->whereDate('trans.transaction_date', '<=', $end);
                    }
                    
                    $productsReturn->where('srl.product_id',$row->product_id);
                    
                    
                    $productsReturn->groupBy('srl.product_id');

                    //sell return qty end
                    $sellQty   = empty($products->pluck('quantity')->toArray()) ? 0 : $products->pluck('quantity')->first();
                    $returnQty = empty($productsReturn->pluck('quantity')->toArray()) ? 0 : $productsReturn->pluck('quantity')->first();
                    $deliveredQty = $sellQty-$returnQty;
                    $remainProduct=0;
                    $outstanding=0;
                    $remainProduct=$row->quantity-$deliveredQty;
                    
                    $_SESSION['remainProduct']=$remainProduct;
                    $_SESSION['outstanding']=$purchasable-$row->quantity;
                    $outstanding=$purchasable-$row->quantity;
                    
                    return $sellQty-$returnQty;
                    // return '<span class="current_stock" data-orig-value="0" >0</span>';
                })
                ->addColumn('outstanding', function ($row) use ($remainProduct,$outstanding) { 
                    
                    return '<span class="pending" data-orig-value="'.$_SESSION['outstanding'].'" >'.$_SESSION['outstanding'].'</span>';
                })
                ->addColumn('purchasable', function ($row) use ($permitted_locations,$location_id) { 
                    $pending = $row->total_sold - $row->total_returned;
                    $diff = $row->quantity-$pending;

                    $products = DB::table('variation_location_details as vld');
                    $products->select(DB::raw('COALESCE(SUM(vld.qty_available),0) as current_stock'));

                    if ($permitted_locations != 'all') 
                    {
                    
                        $locations_imploded = implode(', ', $permitted_locations);
                        $products->whereIn('vld.location_id',[$locations_imploded]);
                        
                    }
                    if (!empty($location_id)) 
                    {
                        $products->where('vld.location_id', $location_id);
                    }
                    
                    $products->where('vld.product_id',$row->product_id);
                    
                    $current_stock = $products->pluck('current_stock')->first();
                    
                    return $diff > $current_stock ? 'Yes':'No';
                   
                })
                ->addColumn('remaning_purchase', function ($row) use ($permitted_locations,$location_id,$request) { 
                    // delivered qty

                    $products = DB::table('sell_order_lines as sol')
                    ->join('transactions as trans', 'trans.id', '=', 'sol.transaction_id')
                    ->select('trans.contact_id')->distinct();
                    if (!empty($business_id)) 
                    {
                        $products->where('trans.business_id', $business_id);
                    }
                    if (!empty($request->input('location_id'))) 
                    {
                        $products->where('trans.location_id', $request->input('location_id'));
                    }
                    if (!empty($request->input('ir_customer_id'))) 
                    {
                        $products->where('trans.contact_id', $request->input('ir_customer_id'));
                    }
                    $products->where('sol.product_id',$row->product_id);
                    if (!empty($date_range)) {
                        $date_range = $date_range;
                        $date_range_array = explode('~', $date_range);
                        $start = $filters['start_date'] = $this->transactionUtil->uf_date(trim($date_range_array[0]));
                        $end   = $filters['end_date'] = $this->transactionUtil->uf_date(trim($date_range_array[1]));

                        $products->whereDate('sol.sell_order_date', '>=', $start)
                                ->whereDate('sol.sell_order_date', '<=', $end);
                    }
                    //$productsData = $products->groupBy('trans.contact_id')->get();
                    $contactIds = $products->groupBy('trans.contact_id')->pluck('contact_id')->toArray();
                    


                    $products = DB::table('transaction_sell_lines as tsl')
                    ->join('transactions as trans', 'trans.id', '=', 'tsl.transaction_id');

                    $products->select(DB::raw('(COALESCE(sum(tsl.quantity),0)) as quantity'));
                    if (!empty($business_id)) 
                    {
                        $products->where('trans.business_id', $business_id);
                    }
                    if (!empty($request->input('location_id'))) 
                    {
                        $products->where('trans.location_id', $request->input('location_id'));
                    }
                    // if (!empty($request->input('ir_customer_id'))) 
                    // {
                        $products->whereIn('trans.contact_id', $contactIds);
                        $products->where('trans.type','sell')->where('trans.status','final');
                    // }
                    if (!empty($request->input('date_range'))) {
                        $date_range = $request->input('date_range');
                        $date_range_array = explode('~', $date_range);
                        $start = $filters['start_date'] = $this->transactionUtil->uf_date(trim($date_range_array[0]));
                        $end   = $filters['end_date'] = $this->transactionUtil->uf_date(trim($date_range_array[1]));

                        $products->whereDate('trans.transaction_date', '>=', $start)
                                ->whereDate('trans.transaction_date', '<=', $end);
                    }
                    
                    $products->where('tsl.product_id',$row->product_id);
                    
                    
                    $products->groupBy('tsl.product_id');
                    
                    $productsSell = empty($products->pluck('quantity')->first()) ? 0 : $products->pluck('quantity')->first();

                     //sell return qty

                     $productsReturn = DB::table('sell_return_lines as srl')
                     ->join('transactions as trans', 'trans.id', '=', 'srl.transaction_id');
 
                     $productsReturn->select(DB::raw('(COALESCE(sum(srl.quantity),0)) as quantity'));
                     if (!empty($business_id)) 
                     {
                         $productsReturn->where('trans.business_id', $business_id);
                     }
                     if (!empty($request->input('location_id'))) 
                     {
                         $productsReturn->where('trans.location_id', $request->input('location_id'));
                     }
                     // if (!empty($request->input('ir_customer_id'))) 
                     // {
                         $productsReturn->whereIn('trans.contact_id', $contactIds);
                         $productsReturn->where('trans.type','sell_return_genralstore')->where('trans.status','received');
                     // }
                     if (!empty($request->input('date_range'))) {
                         $date_range = $request->input('date_range');
                         $date_range_array = explode('~', $date_range);
                         $start = $filters['start_date'] = $this->transactionUtil->uf_date(trim($date_range_array[0]));
                         $end   = $filters['end_date'] = $this->transactionUtil->uf_date(trim($date_range_array[1]));
 
                         $productsReturn->whereDate('trans.transaction_date', '>=', $start)
                                 ->whereDate('trans.transaction_date', '<=', $end);
                     }
                     
                     $productsReturn->where('srl.product_id',$row->product_id);
                     
                     
                     $productsReturn->groupBy('srl.product_id');
 
                     //sell return qty end
                     //sell return qty end
                    
                    $returnQty = empty($productsReturn->pluck('quantity')->toArray()) ? 0 : $productsReturn->pluck('quantity')->first();
                    $delivered =$productsSell-$returnQty;
                //delivered qty end



                    $products = DB::table('variation_location_details as vld');
                    $products->select(DB::raw('COALESCE(SUM(vld.qty_available),0) as current_stock'));

                    if ($permitted_locations != 'all') 
                    {
                    
                        $locations_imploded = implode(', ', $permitted_locations);
                        $products->whereIn('vld.location_id',[$locations_imploded]);
                        
                    }
                    if (!empty($location_id)) 
                    {
                        $products->where('vld.location_id', $location_id);
                    }
                    
                    $products->where('vld.product_id',$row->product_id);
                    
                    $current_stock = $products->pluck('current_stock')->first();
                    $orderQty = $row->quantity;
                    //dd($orderQty,$current_stock,$delivered,$row->product_name);
                    $total = $orderQty-$current_stock-$delivered;
                    
                    return $total<=0 ? 0 : $total.' '.$row->units;
                })
                
                ->editColumn('purchase_price', function ($row) {
                    return '<span class="purchase_price" data-orig-value="' . $row->purchase_price . '" >' . $row->purchase_price . '</span>';
                })
                
                ->editColumn('subtotal', function ($row) {
                    $subtotal = $row->purchase_price * (float)$row->quantity;
                    return '<span class="display_currency subtotal" data-currency_symbol=true data-orig-value="' . $subtotal . '">' . $subtotal . '</span>';
                })
                ->addColumn('pending',function($row){
                    $pending = $row->total_sold - $row->total_returned;
                    return '<span class="pending" data-orig-value="'.$pending.'" >'.$pending.'</span>';
                })
                
                ->rawColumns(['quantity','purchase_price','subtotal','current_stock','pending','delivered','outstanding','purchase_qty'])
                ->make(true);
        }

        $categories = Category::where('business_id', $business_id)
                            ->where('parent_id', 0)
                            ->pluck('name', 'id');
        $business_locations = BusinessLocation::forDropdown($business_id, true);

        $customers = Contact::customersDropdown($business_id, false);

        $departmentUser=0;
        
        if(count(auth()->user()->contactAccess) >0) 
        {
            $departmentUser=1;
        }
        return view('genralstore_reports.total_demand_report')
                ->with(compact('categories','business_locations','customers','departmentUser'));
    }

    /**
     * get Departmentwise pending report
     *
     * @return \Illuminate\Http\Response
     */
    public function getDepartmentWisePendingReport (Request $request)
    {
        if (!auth()->user()->can('genralstore_report.departmentwisependingreport')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');
        $show_only_pending_product=0;
        $date_range = '';
        $product_data = '';
        if ($request->ajax()) {
            
            
            $query = Variation::join('products as p', 'p.id', '=', 'variations.product_id')
                    ->join('units', 'p.unit_id', '=', 'units.id')
                    ->leftjoin('variation_location_details as vld', 'variations.id', '=', 'vld.variation_id')
                    ->join('product_variations as pv', 'variations.product_variation_id', '=', 'pv.id')
                    ->where('p.business_id', $business_id)
                    ->whereIn('p.type', ['single', 'variable']);

            $permitted_locations = auth()->user()->permitted_locations();
            $location_filter = '';
            $customer_filter = '';
            $product_filter  = '';
            $date_filter     = ''; 
            $send_sms        = '';

            $customer_id = $request->input('customer_id');
            if ($permitted_locations != 'all') {
                $query->whereIn('vld.location_id', $permitted_locations);

                $locations_imploded = implode(', ', $permitted_locations);
                $location_filter .= "AND transactions.location_id IN ($locations_imploded) ";
            }

            if (!empty($request->input('location_id'))) {
                $location_id = $request->input('location_id');

                $query->where('vld.location_id', $location_id);

                $location_filter .= "AND transactions.location_id=$location_id";
            }

            if ($customer_id) {
                

                $customer_filter .= "AND transactions.contact_id=$customer_id";
            }
            else
            {
                $customer_filter .= "AND transactions.contact_id=''";
            }

            if (!empty($request->input('date_range'))) {
                $date_range = $request->input('date_range');
                $date_range_array = explode('~', $date_range);
                $start = $filters['start_date'] = $this->transactionUtil->uf_date(trim($date_range_array[0]));
                $end   = $filters['end_date'] = $this->transactionUtil->uf_date(trim($date_range_array[1]));

                $date_filter .= "AND (transactions.transaction_date >= '$start 00:00:00' and transactions.transaction_date <= '$end 23:59:59')";
            }
            
            if (!empty($request->input('search_product'))) {
                $query->where('p.id', $request->input('search_product'));
            }
            if (!empty($request->input('category_id'))) {
                $query->where('p.category_id', $request->input('category_id'));
            }
            if (!empty($request->input('sub_category_id'))) {
                $query->where('p.sub_category_id', $request->input('sub_category_id'));
            }
            if (!empty($request->input('brand_id'))) {
                $query->where('p.brand_id', $request->input('brand_id'));
            }
            if (!empty($request->input('unit_id'))) {
                $query->where('p.unit_id', $request->input('unit_id'));
            }
            if (!empty($request->input('send_sms'))) {
                $send_sms = $request->input('send_sms');
            }
            if (!empty($request->input('show_disposable_product')) && $request->input('show_disposable_product') == 1) {

                
            }
            else
            {
                $query->where('p.disposable_item',0);
            }

            if (!empty($request->input('show_only_pending_product'))) {
               $show_only_pending_product = $request->input('show_only_pending_product');
            }
            
            $tax_id = request()->get('tax_id', null);
            if (!empty($tax_id)) {
                $query->where('p.tax', $tax_id);
            }

            $type = request()->get('type', null);
            if (!empty($type)) {
                $query->where('p.type', $type);
            }

            //TODO::Check if result is correct after changing LEFT JOIN to INNER JOIN
            
            $products = $query->select(
                // DB::raw("(SELECT SUM(quantity) FROM transaction_sell_lines LEFT JOIN transactions ON transaction_sell_lines.transaction_id=transactions.id WHERE transactions.status='final' $location_filter AND
                //     transaction_sell_lines.product_id=products.id) as total_sold"),

                DB::raw("(SELECT COALESCE(SUM(TSL.quantity - TSL.quantity_returned),0) FROM transactions 
                        JOIN transaction_sell_lines AS TSL ON transactions.id=TSL.transaction_id
                        WHERE transactions.status='final' AND transactions.type='sell' AND transactions.permanent_sell='0' $location_filter $customer_filter $date_filter
                        AND TSL.variation_id=variations.id) as total_sold"),
                
                DB::raw("(SELECT COALESCE(SUM(SRL.quantity),0) FROM transactions 
                        JOIN sell_return_lines AS SRL ON transactions.id=SRL.transaction_id
                        WHERE transactions.type='sell_return_genralstore' $location_filter $customer_filter $date_filter
                        AND SRL.variation_id=variations.id) as total_returned"),

                DB::raw("SUM(vld.qty_available) as stock"),
                'variations.sub_sku as sku',
                'p.name as product',
                'p.type',
                'p.id as product_id',
                'p.id as DT_RowId',
                'p.disposable_item as disposable',
                'units.short_name as unit',
                'p.enable_stock as enable_stock',
                'variations.sell_price_inc_tax as unit_price',
                'pv.name as product_variation',
                'variations.name as variation_name'
            )->groupBy('variations.id');

            
            
            // $data = $products->toSql();
            $product_data =DB::table(DB::raw("({$products->toSql()}) as sub"))->mergeBindings($products->getQuery())->where(function ($query) use ($show_only_pending_product) {

                
                
                // $query->where('total_sold', '!=', 0)
                //     ->orWhere('total_returned', '!=', 0);
                //$query->whereRaw("total_sold!=total_returned");
                if($show_only_pending_product == 1)
                {
                    $query->whereRaw('(`total_sold` != 0 or `total_returned` != 0)  and total_sold!=total_returned');
                }
                else
                {
                    $query->whereRaw('(`total_sold` != 0 or `total_returned` != 0)');
                }
            });
           
            if($send_sms == 1)
            {
                $customer_data = Contact::where('id',$customer_id)->first();
                $this->notificationUtil->autoSendNotification($business_id, 'departmentwise_pending', $product_data, $customer_data);
            }
            
            return Datatables::of($product_data)
                ->editColumn('stock', function ($row) {
                    if ($row->enable_stock) {
                        $stock = $row->stock ? $row->stock : 0 ;
                        return  '<span data-is_quantity="true" class="current_stock display_currency" data-orig-value="' . (float)$stock . '" data-unit="' . $row->unit . '" data-currency_symbol=false > ' . (float)$stock . '</span>' . ' ' . $row->unit ;
                    } else {
                        return 'N/A';
                    }
                })
                ->addColumn('customer', function ($row) use ($customer_id) {
                    $customer_name = Contact::where('id',$customer_id)->pluck('name')->first();
                    return $customer_name;
                })
                
                ->editColumn('disposable', function ($row) {
                    if ($row->disposable) {
                    return 'Yes';
                    }
                    return 'No';
                })
                ->editColumn('product', function ($row) {
                    $name = $row->product;
                    if ($row->type == 'variable') {
                        $name .= ' - ' . $row->product_variation . '-' . $row->variation_name;
                    }
                    return $name;
                })
                ->editColumn('total_sold', function ($row) {
                    $total_sold = 0;
                    if ($row->total_sold) {
                        $total_sold =  (float)$row->total_sold;
                    }

                    return '<span data-is_quantity="true" class="display_currency total_sold" data-currency_symbol=false data-orig-value="' . $total_sold . '" data-unit="' . $row->unit . '" >' . $total_sold . '</span> ' . $row->unit;
                })
                ->editColumn('total_returned', function ($row) {
                    $total_returned = 0;
                    if ($row->total_returned) {
                        $total_returned =  (float)$row->total_returned;
                    }

                    return '<span data-is_quantity="true" class="display_currency total_returned" data-currency_symbol=false data-orig-value="' . $total_returned . '" data-unit="' . $row->unit . '" >' . $total_returned . '</span> ' . $row->unit;
                })
                ->addColumn('remain', function ($row) {
                    $total_remain = 0;
                    $total_returned = 0;
                    $total_sold = 0;
                    if ($row->total_returned) {
                        $total_returned =  (float)$row->total_returned;
                    }
                    if ($row->total_sold) {
                        $total_sold =  (float)$row->total_sold;
                    }
                    $total_remain = ($total_sold)- ($total_returned);

                    $border_color = '';
                    if($total_remain>0)
                    {
                        $border_color = 'red';
                    }
                    else if($total_remain<0)
                    {
                        $border_color = 'black';
                    }
                    else if($total_remain==0)
                    {
                        $border_color = 'green';
                    }
                    else if($total_remain==0)
                    {
                        $border_color = 'green';
                    }
                    return '<span data-is_quantity="true" class="display_currency total_remain '.$border_color.'" data-currency_symbol=false data-orig-value="' . $total_remain . '" data-unit="' . $row->unit . '" >' . $total_remain . '</span> ' . $row->unit;
                })
                ->addColumn('subtotal', function ($row) {
                    $total_remain = 0;
                    $total_returned = 0;
                    $total_sold = 0;
                    if ($row->total_returned) {
                        $total_returned =  (float)$row->total_returned;
                    }
                    if ($row->total_sold) {
                        $total_sold =  (float)$row->total_sold;
                    }
                    $total_remain = ($total_sold)- ($total_returned);

                    $subtotal = $total_remain*$row->unit_price;
                    return '<span class="display_currency row_subtotal" data-currency_symbol = true data-orig-value="' . $subtotal . '">' . $subtotal . '</span>';
                })
                ->editColumn('unit_price', function ($row) {
                    $html = '';
                    if (auth()->user()->can('access_default_selling_price')) {
                        $html .= '<span class="display_currency" data-currency_symbol=true >'
                        . $row->unit_price . '</span>';
                    }

                    // if ($allowed_selling_price_group) {
                    //     $html .= ' <button type="button" class="btn btn-primary btn-xs btn-modal no-print" data-container=".view_modal" data-href="' . action('ProductController@viewGroupPrice', [$row->product_id]) .'">' . __('lang_v1.view_group_prices') . '</button>';
                    // }

                    return $html;
                })
                ->addColumn('action', function ($row) {
                    $html = '<button type="button" title="' . __("Detail Pending Report") . '" class="btn btn-primary btn-xs view_detail_pending"><i class="fa fa-eye-slash" aria-hidden="true"></i></button>';
                    return $html;
                })
                ->removeColumn('enable_stock')
                ->removeColumn('unit')
                ->removeColumn('id')
                ->rawColumns(['unit_price', 'total_transfered', 'total_sold','total_returned',
                    'remain', 'stock','subtotal','action'])
                ->make(true);
        }

        $categories = Category::where('business_id', $business_id)
                            ->where('parent_id', 0)
                            ->pluck('name', 'id');
        $brands = Brands::where('business_id', $business_id)
                            ->pluck('name', 'id');
        $units = Unit::where('business_id', $business_id)
                            ->pluck('short_name', 'id');
        $business_locations = BusinessLocation::forDropdown($business_id, true);

        $customers = Contact::customersDropdown($business_id);

        $report_title = "Departmentwise Pending Report Date".$date_range;
        return view('genralstore_reports.departmentwise_pending_report')
                ->with(compact('categories','business_locations','customers','report_title'));
    }

    public function getDepartmentwiseProductPendingReport($productId,Request $request)
    {
        // if ($request->ajax()) {

            $customer_id = $request->input('customer_id');
            $productData = DB::table('transactions as trans')
                    ->leftjoin('sell_return_lines as SRL', 'SRL.transaction_id', '=', 'trans.id')
                    ->leftjoin('transaction_sell_lines as TSL', 'TSL.transaction_id', '=', 'trans.id');

            $permitted_locations = auth()->user()->permitted_locations();
            
            if ($permitted_locations != 'all') 
            {

                $locations_imploded = implode(', ', $permitted_locations);
                $productData->whereIn('trans.location_id',$permitted_locations);
                
            }

            if (!empty($request->input('location_id'))) {
            $location_id = $request->input('location_id');

            $productData->where('trans.location_id', $location_id);

            }

            if ($customer_id) 
            {
                $productData->where('trans.contact_id',$customer_id);
                
            }
            else
            {
                $productData->where('trans.contact_id','');
                
            }

            if (!empty($request->input('date_range'))) 
            {
                $date_range = $request->input('date_range');
                $date_range_array = explode('~', $date_range);
                $start = $filters['start_date'] = $this->transactionUtil->uf_date(trim($date_range_array[0]));
                $end   = $filters['end_date'] = $this->transactionUtil->uf_date(trim($date_range_array[1]));

                $productData->whereDate('trans.transaction_date', '>=', $start)
                        ->whereDate('trans.transaction_date', '<=', $end);
            }
        
                $productData->where(function ($q) use ($productId) {
                        $q->where('SRL.product_id',$productId)
                        ->orwhere('TSL.product_id',$productId);
                    })
                    ->select('trans.id','trans.type','trans.invoice_no','trans.ref_no','trans.transaction_date','trans.additional_notes','trans.created_by','TSL.quantity as sale_qty','SRL.quantity as return_qty')
                    ->whereIn('trans.type', ['sell_return_genralstore', 'sell'])
                    ->whereIn('trans.status',['final','received']);
                    
                $products = $productData->get()->toarray();
                
            return view('genralstore_reports.partials.departmentwiseproductpending')->with(compact('products'));
        // }
        
    }

    /**
     * Get Departmentwise Product Pending Report
     */
    

    /**
     * get Total pending report
     *
     * @return \Illuminate\Http\Response
     */
    public function getTotalPendingReport (Request $request)
    {
        if (!auth()->user()->can('genralstore_report.totalpendingreport')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');
        $date_range  = '';
        $date_range_filter = '';
        if ($request->ajax()) {
            
            
            $query = Variation::join('products as p', 'p.id', '=', 'variations.product_id')
                    ->join('units', 'p.unit_id', '=', 'units.id')
                    ->leftjoin('variation_location_details as vld', 'variations.id', '=', 'vld.variation_id')
                    ->join('product_variations as pv', 'variations.product_variation_id', '=', 'pv.id')
                    ->where('p.business_id', $business_id)
                    ->whereIn('p.type', ['single', 'variable']);

            $permitted_locations = auth()->user()->permitted_locations();
            $location_filter = '';
            $customer_filter = '';
            $product_filter  = '';
            $date_filter    = ''; 
            $query_string   = '';
            $customer_id = $request->input('customer_id');
            if ($permitted_locations != 'all') {
                $query->whereIn('vld.location_id', $permitted_locations);

                $locations_imploded = implode(', ', $permitted_locations);
                $location_filter .= "AND transactions.location_id IN ($locations_imploded) ";
            }

            if (!empty($request->input('location_id'))) {
                $location_id = $request->input('location_id');

                $query->where('vld.location_id', $location_id);

                $location_filter .= "AND transactions.location_id=$location_id ";
                $query_string .= '&location_id='.$location_id;
            }

            if ($customer_id) {
                

                $location_filter .= "AND transactions.contact_id=$customer_id ";
                $query_string .= '&customer_id='.$customer_id;
            }
            else
            {
                $customer_filter .= "AND transactions.contact_id=''";
            }

            if (!empty($request->input('date_range'))) {
                $date_range = $request->input('date_range');
                $date_range_array = explode('~', $date_range);
                $start = $filters['start_date'] = $this->transactionUtil->uf_date(trim($date_range_array[0]));
                $end   = $filters['end_date'] = $this->transactionUtil->uf_date(trim($date_range_array[1]));

                $date_filter .= "AND (transactions.transaction_date >= '$start 00:00:00' and transactions.transaction_date <= '$end 23:59:59')";
                $date_range_filter='date_range='.$date_range;
            }
            
            if (!empty($request->input('search_product'))) {
                $query->where('p.id', $request->input('search_product'));
                $query_string .= '&search_product='.$request->input('search_product');
            }
            if (!empty($request->input('category_id'))) {
                $query->where('p.category_id', $request->input('category_id'));
                $query_string .= '&category_id='.$request->input('category_id');
            }
            if (!empty($request->input('sub_category_id'))) {
                $query->where('p.sub_category_id', $request->input('sub_category_id'));
                $query_string .= '&sub_category_id='.$request->input('sub_category_id');
            }
            if (!empty($request->input('brand_id'))) {
                $query->where('p.brand_id', $request->input('brand_id'));
            }
            if (!empty($request->input('unit_id'))) {
                $query->where('p.unit_id', $request->input('unit_id'));
            }
            if($request->input('disposable_product')!='')
            {
                $query->where('p.disposable_item', $request->input('disposable_product'));
                $query_string .= '&disposable_product='.$request->input('disposable_product');
            }
            $tax_id = request()->get('tax_id', null);
            if (!empty($tax_id)) {
                $query->where('p.tax', $tax_id);
            }

            $type = request()->get('type', null);
            if (!empty($type)) {
                $query->where('p.type', $type);
            }

            //TODO::Check if result is correct after changing LEFT JOIN to INNER JOIN
            
            $products = $query->select(
                // DB::raw("(SELECT SUM(quantity) FROM transaction_sell_lines LEFT JOIN transactions ON transaction_sell_lines.transaction_id=transactions.id WHERE transactions.status='final' $location_filter AND
                //     transaction_sell_lines.product_id=products.id) as total_sold"),

                DB::raw("(SELECT SUM(TSL.quantity - TSL.quantity_returned) FROM transactions 
                        JOIN transaction_sell_lines AS TSL ON transactions.id=TSL.transaction_id
                        WHERE transactions.status='final' AND transactions.type='sell' AND transactions.permanent_sell='0' $location_filter  $date_filter
                        AND TSL.variation_id=variations.id) as total_sold"),
                
                DB::raw("(SELECT SUM(SRL.quantity) FROM transactions 
                        JOIN sell_return_lines AS SRL ON transactions.id=SRL.transaction_id
                        WHERE transactions.type='sell_return_genralstore' $location_filter  $date_filter
                        AND SRL.variation_id=variations.id) as total_returned"),

                DB::raw("SUM(vld.qty_available) as stock"),
                'variations.sub_sku as sku',
                'p.name as product',
                'p.type',
                'p.id as product_id',
                'p.disposable_item as disposable',
                'units.short_name as unit',
                'p.enable_stock as enable_stock',
                'variations.sell_price_inc_tax as unit_price',
                'pv.name as product_variation',
                'variations.name as variation_name'
            )->groupBy('variations.id')->orderBy('disposable','ASC');

            
            
            // $data = $products->toSql();
            $product_data =DB::table(DB::raw("({$products->toSql()}) as sub"))->mergeBindings($products->getQuery())->where(function ($query) {
                $query->where('total_sold', '!=', 'null')
                    ->orWhere('total_returned', '!=', 'null');
            });

            
            
            return Datatables::of($product_data)
                ->editColumn('stock', function ($row) {
                    if ($row->enable_stock) {
                        $stock = $row->stock ? $row->stock : 0 ;
                        return  '<span data-is_quantity="true" class="current_stock display_currency" data-orig-value="' . (float)$stock . '" data-unit="' . $row->unit . '" data-currency_symbol=false > ' . (float)$stock . '</span>' . ' ' . $row->unit ;
                    } else {
                        return 'N/A';
                    }
                })
                ->addColumn('customer', function ($row) use ($customer_id) {
                    $customer_name = Contact::where('id',$customer_id)->pluck('name')->first();
                    return $customer_name;
                })
                
                ->editColumn('disposable', function ($row) {
                    if ($row->disposable) {
                    return 'Yes';
                    }
                    return 'No';
                })
                ->editColumn('product', function ($row) {
                    $name = $row->product;
                    if ($row->type == 'variable') {
                        $name .= ' - ' . $row->product_variation . '-' . $row->variation_name;
                    }
                    return $name;
                })
                ->editColumn('total_sold', function ($row) {
                    $total_sold = 0;
                    if ($row->total_sold) {
                        $total_sold =  (float)$row->total_sold;
                    }

                    return '<span data-is_quantity="true" class="display_currency total_sold" data-currency_symbol=false data-orig-value="' . $total_sold . '" data-unit="' . $row->unit . '" >' . $total_sold . '</span> ' . $row->unit;
                })
                ->editColumn('total_returned', function ($row) {
                    $total_returned = 0;
                    if ($row->total_returned) {
                        $total_returned =  (float)$row->total_returned;
                    }

                    return '<span data-is_quantity="true" class="display_currency total_returned" data-currency_symbol=false data-orig-value="' . $total_returned . '" data-unit="' . $row->unit . '" >' . $total_returned . '</span> ' . $row->unit;
                })
                ->addColumn('remain', function ($row) use ($query_string,$date_range_filter) {
                    $total_remain = 0;
                    $total_returned = 0;
                    $total_sold = 0;
                    if ($row->total_returned) {
                        $total_returned =  (float)$row->total_returned;
                    }
                    if ($row->total_sold) {
                        $total_sold =  (float)$row->total_sold;
                    }
                    $total_remain = ($total_sold)- ($total_returned);

                    $border_color = '';
                    if($total_remain>0)
                    {
                        $border_color = 'red';
                    }
                    else if($total_remain<0)
                    {
                        $border_color = 'black';
                    }
                    else if($total_remain==0)
                    {
                        $border_color = 'green';
                    }
                    else if($total_remain==0)
                    {
                        $border_color = 'green';
                    }
                    return '<a data-orig-value="' . (float)$total_remain . '" data-unit="' . $row->unit . '" data-href="' . action('GenralstoreReportController@getTotalPendingReportDetail', [$row->product_id,$date_range_filter])
                            .$query_string.'" href="#" data-container=".view_modal" class="btn-modal total_remain">' . (float)$total_remain . '</a>';
                    //return '<span data-is_quantity="true"  data-currency_symbol=false data-orig-value="' . $total_remain . '" data-unit="' . $row->unit . '" >' . $total_remain . '</span> ' . $row->unit;
                })
                ->addColumn('subtotal', function ($row) {
                    $total_remain = 0;
                    $total_returned = 0;
                    $total_sold = 0;
                    if ($row->total_returned) {
                        $total_returned =  (float)$row->total_returned;
                    }
                    if ($row->total_sold) {
                        $total_sold =  (float)$row->total_sold;
                    }
                    $total_remain = ($total_sold)- ($total_returned);

                    $subtotal = $total_remain*$row->unit_price;
                    return '<span class="display_currency row_subtotal" data-currency_symbol = true data-orig-value="' . $subtotal . '">' . $subtotal . '</span>';
                })
                ->editColumn('unit_price', function ($row) {
                    $html = '';
                    if (auth()->user()->can('access_default_selling_price')) {
                        $html .= '<span class="display_currency" data-currency_symbol=true >'
                        . $row->unit_price . '</span>';
                    }

                    // if ($allowed_selling_price_group) {
                    //     $html .= ' <button type="button" class="btn btn-primary btn-xs btn-modal no-print" data-container=".view_modal" data-href="' . action('ProductController@viewGroupPrice', [$row->product_id]) .'">' . __('lang_v1.view_group_prices') . '</button>';
                    // }

                    return $html;
                })
                ->removeColumn('enable_stock')
                ->removeColumn('unit')
                ->removeColumn('id')
                ->rawColumns(['unit_price', 'total_transfered', 'total_sold','total_returned',
                    'remain', 'stock','subtotal'])
                ->make(true);
        }

        $categories = Category::where('business_id', $business_id)
                            ->where('parent_id', 0)
                            ->pluck('name', 'id');
        $brands = Brands::where('business_id', $business_id)
                            ->pluck('name', 'id');
        $units = Unit::where('business_id', $business_id)
                            ->pluck('short_name', 'id');
        $business_locations = BusinessLocation::forDropdown($business_id, true);

        $customers = Contact::customersDropdown($business_id,false);
        

        $departmentUser=0;
        
        if(count(auth()->user()->contactAccess) >0) 
        {
            $departmentUser=1;
        }

        return view('genralstore_reports.total_pending_report')
                ->with(compact('categories','business_locations','customers','departmentUser'));
    }

    /**
     * get Total pending report detail
     *
     * @return \Illuminate\Http\Response
     */
    public function getTotalPendingReportDetail (Request $request,$product_id)
    {
        if (!auth()->user()->can('genralstore_report.totalpendingreport')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');
        

        if ($request->ajax()) {
            
            
            $query =  DB::table('transactions as trans')
                    ->leftjoin('transaction_sell_lines as tsl', 'trans.id', '=', 'tsl.transaction_id')
                    ->leftjoin('sell_return_lines as srl', 'trans.id', '=', 'srl.transaction_id')
                    ->join('contacts','contacts.id','=','trans.contact_id');
                    // ->join('products','products.id','=','sol.product_id');

            $permitted_locations = auth()->user()->permitted_locations();
            $location_filter = '';
            $customer_filter = '';
            $product_filter  = '';
            $date_filter    = ''; 
            $customer_id = $request->input('customer_id');
            if ($permitted_locations != 'all') {
                $query->whereIn('trans.location_id', $permitted_locations);
            }

            if (!empty($request->input('location_id'))) {
                $location_id = $request->input('location_id');

                $query->where('trans.location_id', $location_id);
            }

            if ($customer_id) {
                
                $query->where('trans.contact_id', $customer_id);
            }
                $query->whereIn('trans.status',['final','received']);

            if (!empty($request->input('date_range'))) {
                $date_range = $request->input('date_range');
                $date_range_array = explode('~', $date_range);
                $start = $filters['start_date'] = $this->transactionUtil->uf_date(trim($date_range_array[0]));
                $end   = $filters['end_date'] = $this->transactionUtil->uf_date(trim($date_range_array[1]));
                
                $query->whereDate('trans.transaction_date', '>=', $start)
                        ->whereDate('trans.transaction_date', '<=', $end);


            }
            
            
            if(!empty($product_id))
            {
                $query->whereRaw('(srl.product_id ='.$product_id.' or tsl.product_id = '.$product_id.')');
            }
            

            //TODO::Check if result is correct after changing LEFT JOIN to INNER JOIN
            
            $product = $query->select(
               
                DB::raw("(COALESCE(sum(tsl.quantity),0)-COALESCE(sum(srl.quantity),0)) as remain,trans.contact_id,contacts.name"))
                
                ->groupBy('trans.contact_id');
            
        }

        //  var_dump($product->toSql());
        //     exit;
        
        $product_data = $product->get()->toarray();
        $categories = Category::where('business_id', $business_id)
                            ->where('parent_id', 0)
                            ->pluck('name', 'id');
        $brands = Brands::where('business_id', $business_id)
                            ->pluck('name', 'id');
        $units = Unit::where('business_id', $business_id)
                            ->pluck('short_name', 'id');
        $business_locations = BusinessLocation::forDropdown($business_id, true);

        $customers = Contact::customersDropdown($business_id);
        
        // var_dump($products->toSql());
        //     exit;
        return view('genralstore_reports.detail_pending_show') ->with(compact('product_data'));
        return view('genralstore_reports.total_pending_report')
                ->with(compact('categories','business_locations','customers'));
    }
    /**
     * get Departmentwise summary report
     *
     * @return \Illuminate\Http\Response
     */
    public function getDepartmentwiseSummaryReport (Request $request)
    {
        if (!auth()->user()->can('genralstore_report.departmentwisesummaryreport')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');
        
        $customer_id = '';
        $damage_customer_id = 'NULL';
        if ($request->ajax()) {
            
            
            $query = Variation::join('products as p', 'p.id', '=', 'variations.product_id')
                    ->join('units', 'p.unit_id', '=', 'units.id')
                    ->leftjoin('variation_location_details as vld', 'variations.id', '=', 'vld.variation_id')
                    ->join('product_variations as pv', 'variations.product_variation_id', '=', 'pv.id')
                    ->where('p.business_id', $business_id)
                    ->whereIn('p.type', ['single', 'variable']);

            $permitted_locations = auth()->user()->permitted_locations();
            $location_filter = '';
            $customer_filter = '';
            $product_filter  = '';
            $date_filter    = ''; 
            $customer_id = $request->input('customer_id');
            if ($permitted_locations != 'all') {
                $query->whereIn('vld.location_id', $permitted_locations);

                $locations_imploded = implode(', ', $permitted_locations);
                $location_filter .= "AND transactions.location_id IN ($locations_imploded) ";
            }

            if (!empty($request->input('location_id'))) {
                $location_id = $request->input('location_id');

                $query->where('vld.location_id', $location_id);

                $location_filter .= "AND transactions.location_id=$location_id";
            }

            if ($customer_id) {
                
                $damage_customer_id = $customer_id;
                $customer_filter .= "AND transactions.contact_id=$customer_id";
            }
            else
            {
                $customer_filter .= "AND transactions.contact_id=''";
            }

            if (!empty($request->input('date_range'))) {
                $date_range = $request->input('date_range');
                $date_range_array = explode('~', $date_range);
                $start = $filters['start_date'] = $this->transactionUtil->uf_date(trim($date_range_array[0]));
                $end   = $filters['end_date'] = $this->transactionUtil->uf_date(trim($date_range_array[1]));

                $date_filter .= "AND (transactions.transaction_date >= '$start 00:00:00' and transactions.transaction_date <= '$end 23:59:59')";

                $date_filter_order = "AND (SOL.sell_order_date >= '$start' and SOL.sell_order_date <= '$end')";
            }
            
            if (!empty($request->input('search_product'))) {
                $query->where('p.id', $request->input('search_product'));
            }
            if (!empty($request->input('category_id'))) {
                $query->where('p.category_id', $request->input('category_id'));
            }
            if (!empty($request->input('sub_category_id'))) {
                $query->where('p.sub_category_id', $request->input('sub_category_id'));
            }
            if (!empty($request->input('brand_id'))) {
                $query->where('p.brand_id', $request->input('brand_id'));
            }
            if (!empty($request->input('unit_id'))) {
                $query->where('p.unit_id', $request->input('unit_id'));
            }
            //$query->where('p.disposable_item', 0);
            $tax_id = request()->get('tax_id', null);
            if (!empty($tax_id)) {
                $query->where('p.tax', $tax_id);
            }

            $type = request()->get('type', null);
            if (!empty($type)) {
                $query->where('p.type', $type);
            }

            
            //TODO::Check if result is correct after changing LEFT JOIN to INNER JOIN
            
            $products = $query->select(
                // DB::raw("(SELECT SUM(quantity) FROM transaction_sell_lines LEFT JOIN transactions ON transaction_sell_lines.transaction_id=transactions.id WHERE transactions.status='final' $location_filter AND
                //     transaction_sell_lines.product_id=products.id) as total_sold"),

                DB::raw("(SELECT SUM(TSL.quantity - TSL.quantity_returned) FROM transactions 
                        JOIN transaction_sell_lines AS TSL ON transactions.id=TSL.transaction_id
                        WHERE transactions.status='final' AND transactions.type='sell' AND transactions.permanent_sell='0' $location_filter $customer_filter $date_filter
                        AND TSL.variation_id=variations.id) as total_sold"),

                DB::raw("(SELECT SUM(TSL.quantity) FROM transactions 
                        JOIN transaction_sell_lines AS TSL ON transactions.id=TSL.transaction_id
                        WHERE transactions.status='final' AND transactions.type='sell' AND p.disposable_item='1' $location_filter $customer_filter $date_filter
                        AND TSL.variation_id=variations.id) as total_disposable_qty"),

                DB::raw("(SELECT SUM(TSL.quantity - TSL.quantity_returned) FROM transactions 
                        JOIN transaction_sell_lines AS TSL ON transactions.id=TSL.transaction_id
                        WHERE transactions.status='final' AND transactions.type='sell' AND transactions.permanent_sell='1' $location_filter $customer_filter $date_filter
                        AND TSL.variation_id=variations.id) as total_non_refundable"),
                
                DB::raw("(SELECT SUM(SOL.quantity) FROM transactions 
                        JOIN sell_order_lines AS SOL ON transactions.id=SOL.transaction_id
                        WHERE transactions.type='sellorder' $location_filter $customer_filter $date_filter_order
                        AND SOL.variation_id=variations.id) as total_order"),
                
                DB::raw("(SELECT SUM(SRL.quantity) FROM transactions 
                        JOIN sell_return_lines AS SRL ON transactions.id=SRL.transaction_id
                        WHERE transactions.type='sell_return_genralstore' $location_filter $customer_filter $date_filter
                        AND SRL.variation_id=variations.id) as total_returned"),
                
                DB::raw("(SELECT SUM(TSL.quantity) FROM transactions 
                JOIN transaction_sell_lines AS TSL ON transactions.id=TSL.transaction_id
                WHERE transactions.status='final' AND transactions.type='sell' AND transactions.damage_by_customer_id=$damage_customer_id  $location_filter $date_filter
                AND TSL.variation_id=variations.id) as total_damage_qty"),

                DB::raw("SUM(vld.qty_available) as stock"),
                'variations.sub_sku as sku',
                'p.name as product',
                'p.type',
                'p.id as product_id',
                'p.disposable_item as disposable',
                'units.short_name as unit',
                'p.enable_stock as enable_stock',
                'variations.sell_price_inc_tax as unit_price',
                'pv.name as product_variation',
                'variations.name as variation_name'
            )->groupBy('variations.id');
            
            // $data = $products->toSql();
            
            $product_data =DB::table(DB::raw("({$products->toSql()}) as sub"))->mergeBindings($products->getQuery())->where(function ($query) {
                $query
                    ->where('total_sold', '!=', 'null')
                    ->orWhere('total_returned', '!=', 'null')
                    ->orWhere('total_damage_qty', '!=', 'null')
                    ->orWhere('total_order', '!=', 'null')
                    ->orWhere('total_disposable_qty', '!=', 'null');
                    // ->orWhere('total_non_refundable', '!=', 'null');
            });

            //dd($products->get()->toArray());
            
            return Datatables::of($product_data)
                ->editColumn('stock', function ($row) {
                    if ($row->enable_stock) {
                        $stock = $row->stock ? $row->stock : 0 ;
                        return  '<span data-is_quantity="true" class="current_stock display_currency" data-orig-value="' . (float)$stock . '" data-unit="' . $row->unit . '" data-currency_symbol=false > ' . (float)$stock . '</span>' . ' ' . $row->unit ;
                    } else {
                        return 'N/A';
                    }
                })
                ->addColumn('customer', function ($row) use ($customer_id) {
                    $customer_name = Contact::where('id',$customer_id)->pluck('name')->first();
                    return $customer_name;
                })
                
                ->editColumn('disposable', function ($row) {
                    if ($row->disposable) {
                    return 'Yes';
                    }
                    return 'No';
                })
                ->editColumn('total_disposable_qty', function ($row) {
                    $total_disposable_qty = 0;
                    if ($row->total_disposable_qty) {
                        $total_disposable_qty = $row->total_disposable_qty;
                    }
                    return '<span data-is_quantity="true" class="display_currency total_disposable_qty" data-currency_symbol=false data-orig-value="' . $total_disposable_qty . '" data-unit="' . $row->unit . '" >' . $total_disposable_qty . '</span> ' . $row->unit;
                })
                
                ->editColumn('product', function ($row) {
                    $name = $row->product;
                    if ($row->type == 'variable') {
                        $name .= ' - ' . $row->product_variation . '-' . $row->variation_name;
                    }
                    return $name;
                })
                ->editColumn('total_order', function ($row) {
                    $total_order = 0;
                    if ($row->total_order) {
                        $total_order =  (float)$row->total_order;
                    }

                    return '<span data-is_quantity="true" class="display_currency total_order" data-currency_symbol=false data-orig-value="' . $total_order . '" data-unit="' . $row->unit . '" >' . $total_order . '</span> ' . $row->unit;
                })
                
                ->editColumn('total_non_refundable', function ($row) {
                    $total_non_refundable = 0;
                    if ($row->total_non_refundable) {
                        $total_non_refundable =  (float)$row->total_non_refundable;
                    }

                    return '<span data-is_quantity="true" class="display_currency total_non_refundable" data-currency_symbol=false data-orig-value="' . $total_non_refundable . '" data-unit="' . $row->unit . '" >' . $total_non_refundable . '</span> ' . $row->unit;
                })
                ->editColumn('total_sold', function ($row) {
                    $total_sold = 0;
                    if ($row->total_sold) {
                        $total_sold =  (float)$row->total_sold;
                    }

                    return '<span data-is_quantity="true" class="display_currency total_sold" data-currency_symbol=false data-orig-value="' . $total_sold . '" data-unit="' . $row->unit . '" >' . $total_sold . '</span> ' . $row->unit;
                })
                ->editColumn('total_damage_qty', function ($row) {
                    $total_damage_qty = 0;
                    if ($row->total_damage_qty) {
                        $total_damage_qty =  (float)$row->total_damage_qty;
                    }

                    return '<span data-is_quantity="true" class="display_currency total_damage_qty" data-currency_symbol=false data-orig-value="' . $total_damage_qty . '" data-unit="' . $row->unit . '" >' . $total_damage_qty . '</span> ' . $row->unit;
                })
                ->editColumn('total_returned', function ($row) {
                    $total_returned = 0;
                    if ($row->total_returned) {
                        $total_returned =  (float)$row->total_returned;
                    }

                    return '<span data-is_quantity="true" class="display_currency total_returned" data-currency_symbol=false data-orig-value="' . $total_returned . '" data-unit="' . $row->unit . '" >' . $total_returned . '</span> ' . $row->unit;
                })
                ->addColumn('remain', function ($row) {
                    $total_remain = 0;
                    $total_returned = 0;
                    $total_sold = 0;
                    if ($row->total_returned) {
                        $total_returned =  (float)$row->total_returned;
                    }
                    if ($row->total_sold) {
                        $total_sold =  (float)$row->total_sold;
                    }
                    $total_remain = ($total_sold)- ($total_returned);

                    $border_color = '';
                    if($total_remain>0)
                    {
                        $border_color = 'red';
                    }
                    else if($total_remain<0)
                    {
                        $border_color = 'black';
                    }
                    else if($total_remain==0)
                    {
                        $border_color = 'green';
                    }
                    else if($total_remain==0)
                    {
                        $border_color = 'green';
                    }
                    return '<span data-is_quantity="true" class="display_currency total_remain '.$border_color.'" data-currency_symbol=false data-orig-value="' . $total_remain . '" data-unit="' . $row->unit . '" >' . $total_remain . '</span> ' . $row->unit;
                })
                ->addColumn('subtotal', function ($row) {
                    $total_remain = 0;
                    $total_returned = 0;
                    $total_sold = 0;
                    if ($row->total_returned) {
                        $total_returned =  (float)$row->total_returned;
                    }
                    if ($row->total_sold) {
                        $total_sold =  (float)$row->total_sold;
                    }
                    $total_remain = ($total_sold)- ($total_returned);

                    $subtotal = $total_remain*$row->unit_price;
                    return '<span class="display_currency row_subtotal" data-currency_symbol = true data-orig-value="' . $subtotal . '">' . $subtotal . '</span>';
                })
                ->addColumn('total_demand_cost', function ($row) {
                   
                    $total_order = 0;
                    if ($row->total_order) 
                    {
                        $total_order =  (float)$row->total_order;
                    }

                    $total_demand_cost = $total_order*$row->unit_price;
                    return '<span class="display_currency row_total_order" data-currency_symbol = true data-orig-value="' . $total_demand_cost . '">' . $total_demand_cost . '</span>';
                })
                ->addColumn('total_return_cost', function ($row) {
                   
                    $total_returned = 0;
                    if ($row->total_returned) 
                    {
                        $total_returned =  (float)$row->total_returned;
                    }

                    $total_return_cost = $total_returned*$row->unit_price;
                    return '<span class="display_currency total_returned_cost" data-currency_symbol = true data-orig-value="' . $total_return_cost . '">' . $total_return_cost . '</span>';
                })
                ->addColumn('total_usage', function ($row) {
                   
                    $total_sold = 0;
                    if ($row->total_sold) 
                    {
                        $total_sold =  (float)$row->total_sold;
                    }

                    $total_usage = $total_sold*$row->unit_price;
                    return '<span class="display_currency row_total_usage" data-currency_symbol = true data-orig-value="' . $total_usage . '">' . $total_usage . '</span>';
                })
                ->addColumn('total_non_refundable_cost', function ($row) {
                   
                    $total_non_refundable = 0;
                    if ($row->total_non_refundable) 
                    {
                        $total_non_refundable =  (float)$row->total_non_refundable;
                    }

                    $total_non_refundable_cost = $total_non_refundable*$row->unit_price;
                    return '<span class="display_currency row_total_non_refundable" data-currency_symbol = true data-orig-value="' . $total_non_refundable_cost . '">' . $total_non_refundable_cost . '</span>';
                })
                ->addColumn('difference', function ($row) {
                   
                    $difference = 0;
                    $total_order= 0;
                    $total_non_refundable = 0;
                    $total_returned = 0;
                    $total_sold = 0;
                    if ($row->total_order) 
                    {
                        $total_order =  (float)$row->total_order;
                    }
                    if ($row->total_sold) 
                    {
                        $total_sold =  (float)$row->total_sold;
                    }
                    if ($row->total_non_refundable) 
                    {
                        $total_non_refundable =  (float)$row->total_non_refundable;
                    }
                    if ($row->total_returned) 
                    {
                        $total_returned =  (float)$row->total_returned;
                    }

                    // $difference = ($total_order - ($total_returned+$total_non_refundable));
                    $difference = ($total_order - ($total_sold));

                    return '<span class="display_currency difference" data-currency_symbol = true data-orig-value="' . $difference . '">' . $difference . '</span>';
                })
                ->addColumn('total_refundabale', function ($row) {
                    $total_refundabale      = 0;
                    $total_non_refundable   = 0;
                    $total_sold             = 0;
                    if ($row->total_non_refundable) {
                        $total_non_refundable =  (float)$row->total_non_refundable;
                    }
                    if ($row->total_sold) {
                        $total_sold =  (float)$row->total_sold;
                    }
                    $total_refundabale = $total_sold -$total_non_refundable;
                    return '<span data-is_quantity="true" class="display_currency total_refundabale" data-currency_symbol=false data-orig-value="' . $total_refundabale . '" data-unit="' . $row->unit . '" >' . $total_refundabale . '</span> ' . $row->unit;
                })
                ->addColumn('pending', function ($row) {
                    $total_refundabale      = 0;
                    $total_non_refundable   = 0;
                    $total_sold             = 0;
                    $total_returned         = 0;
                    $pending                = 0;
                    if (!$row->disposable) 
                    {
                        if ($row->total_non_refundable) {
                            $total_non_refundable =  (float)$row->total_non_refundable;
                        }
                        if ($row->total_sold) {
                            $total_sold =  (float)$row->total_sold;
                        }
                        if ($row->total_returned) {
                            $total_returned =  (float)$row->total_returned;
                        }
                        $pending = $total_sold - ($total_non_refundable+$total_returned);
                    }
                    // $pending = ($total_returned - $total_refundabale);
                    return '<span data-is_quantity="true" class="display_currency total_pending" data-currency_symbol=false data-orig-value="' . $pending . '" data-unit="' . $row->unit . '" >' . $pending . '</span> ' . $row->unit;
                })
                ->addColumn('nuksani', function ($row) {
                    $total_refundabale      = 0;
                    $total_non_refundable   = 0;
                    $total_sold             = 0;
                    $total_returned         = 0;
                    $nuksani                = 0;
                    if (!$row->disposable) 
                    {
                        if ($row->total_non_refundable) {
                            $total_non_refundable =  (float)$row->total_non_refundable;
                        }
                        if ($row->total_sold) {
                            $total_sold =  (float)$row->total_sold;
                        }
                        if ($row->total_returned) {
                            $total_returned =  (float)$row->total_returned;
                        }
                        $pending = $total_sold - ($total_non_refundable+$total_returned);
                        $nuksani = $pending*$row->unit_price;
                    }
                    return '<span class="display_currency row_total_nuksani" data-currency_symbol = true data-orig-value="' . $nuksani . '">' . $nuksani . '</span>';
                })
                ->addColumn('total_damage_value', function ($row) {
                    $damage_value      = 0;
                    if ($row->total_damage_qty) {
                        $damage_value =  (float)$row->total_damage_qty*$row->unit_price;
                    }
                    
                    return '<span data-is_quantity="true" class="display_currency total_damage_value" data-currency_symbol=true data-orig-value="' . $damage_value . '"  >' . $damage_value . '</span> ';
                })
                ->addColumn('total_refundabale_amount', function ($row) {
                    $total_refundabale      = 0;
                    $total_non_refundable   = 0;
                    $total_sold             = 0;
                    $total_refundabale_amount = 0;
                    if ($row->total_non_refundable) {
                        $total_non_refundable =  (float)$row->total_non_refundable;
                    }
                    if ($row->total_sold) {
                        $total_sold =  (float)$row->total_sold;
                    }
                    $total_refundabale = $total_sold -$total_non_refundable;
                    $total_refundabale_amount = $total_refundabale*$row->unit_price;
                    return '<span class="display_currency row_total_refundabale_amount" data-currency_symbol = true data-orig-value="' . $total_refundabale_amount . '">' . $total_refundabale_amount . '</span>';
                })
                ->addColumn('total_disposable_value', function ($row) {
                    $total_disposable_value = 0;
                    $total_disposable_qty = 0;

                    if ($row->total_disposable_qty) {
                        $total_disposable_qty = $row->total_disposable_qty;
                    }
                    $total_disposable_value = $total_disposable_qty*$row->unit_price;
                    return '<span class="display_currency row_total_disposable_value" data-currency_symbol = true data-orig-value="' . $total_disposable_value . '">' . $total_disposable_value . '</span>';
                })
                
                ->editColumn('unit_price', function ($row) {
                    $html = '';
                    if (auth()->user()->can('access_default_selling_price')) {
                        $html .= '<span class="display_currency" data-currency_symbol=true >'
                        . $row->unit_price . '</span>';
                    }

                    // if ($allowed_selling_price_group) {
                    //     $html .= ' <button type="button" class="btn btn-primary btn-xs btn-modal no-print" data-container=".view_modal" data-href="' . action('ProductController@viewGroupPrice', [$row->product_id]) .'">' . __('lang_v1.view_group_prices') . '</button>';
                    // }

                    return $html;
                })
                ->removeColumn('enable_stock')
                ->removeColumn('unit')
                ->removeColumn('id')
                ->rawColumns(['unit_price', 'total_transfered', 'total_sold','total_returned','total_order','total_demand_cost',
                    'remain', 'stock','subtotal','total_non_refundable','total_non_refundable_cost','difference','total_usage','total_refundabale',
                    'total_refundabale_amount','total_return_cost','pending','nuksani','total_disposable_qty','total_disposable_value','total_damage_qty','total_damage_value'])
                ->make(true);
        }

        $categories = Category::where('business_id', $business_id)
                            ->where('parent_id', 0)
                            ->pluck('name', 'id');
        $brands = Brands::where('business_id', $business_id)
                            ->pluck('name', 'id');
        $units = Unit::where('business_id', $business_id)
                            ->pluck('short_name', 'id');
        $business_locations = BusinessLocation::forDropdown($business_id, true);

        $customers = Contact::customersDropdown($business_id);

        if($customer_id)
        {
            $customer_name = Contact::where('id',$row->contact_id)->pluck('name')->first();
        }
        else
        {
            $customer_name = '';
        }

        return view('genralstore_reports.departmentwise_summary')
                ->with(compact('categories','business_locations','customers','customer_name'));
    }


    /**
     * get Overall summary report
     *
     * @return \Illuminate\Http\Response
     */
    public function getOverallSummaryReport (Request $request)
    {
        if (!auth()->user()->can('genralstore_report.overallsummaryreport')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');
        

        if ($request->ajax()) {
            
            $location_filter     = '';
            $customer_id         = $request->input('customer_id');
            $date_range          = $request->input('date_range'); 
            $permitted_locations = auth()->user()->permitted_locations();
            $search_product      = $request->input('search_product'); 
            if (!empty($date_range)) {
                $date_range = $date_range;
                $date_range_array = explode('~', $date_range);
                $start = $filters['start_date'] = $this->transactionUtil->uf_date(trim($date_range_array[0]));
                $end   = $filters['end_date'] = $this->transactionUtil->uf_date(trim($date_range_array[1]));
            }
            $productFilterSOL = '';
            $productFilterReturn = '';
            $productFilterTransaction = "";
            if(!empty($search_product))
            {
                $productFilterSOL = ' and SOL.product_id="'.$search_product.'"';
                $productFilterReturn = ' and SRL.product_id="'.$search_product.'"';
                $productFilterTransaction = ' and TSL.product_id="'.$search_product.'"';
            }

            
            $query = DB::table('transactions as trans')
            ->leftjoin('transaction_sell_lines as TSL', 'trans.id', '=', 'TSL.transaction_id')
            ->leftjoin('sell_return_lines as SRL','SRL.transaction_id','=','trans.id')
            ->leftjoin('sell_order_lines as SOL','SOL.transaction_id','=','trans.id')
            ->leftjoin('products as pro','pro.id','=','TSL.product_id')
            ->leftjoin('products as pro2','pro2.id','=','SRL.product_id');

            $query->select('trans.contact_id',

                DB::raw("SUM(CASE WHEN (SOL.sell_order_date >='".$start."' and SOL.sell_order_date <= '".$end."' $productFilterSOL) THEN SOL.quantity ELSE 0 END ) as demand"),
                DB::raw("SUM(CASE WHEN (trans.status='final' and trans.type='sell' $productFilterTransaction) THEN TSL.quantity ELSE 0 END ) as issue"),
                DB::raw("SUM( CASE WHEN (pro2.disposable_item=0 $productFilterReturn) THEN SRL.quantity ELSE 0 END) AS qty_return"),
                //SUM(SRL.quantity) AS qty_return
                DB::raw("SUM( CASE WHEN (trans.permanent_sell = '0' and pro.disposable_item=0 $productFilterTransaction) THEN TSL.quantity ELSE 0 END) AS qty_refundabale"),
                DB::raw("SUM( CASE WHEN trans.permanent_sell = '1' $productFilterTransaction THEN TSL.quantity ELSE 0 END) AS qty_non_refundable"),

                //DB::raw("(SUM( CASE WHEN (trans.damage = '1' $productFilterTransaction) THEN TSL.quantity ELSE 0 END)) as qty_damage"),
                DB::raw("SUM( CASE WHEN (trans.damage = 1 and trans.damage_by_customer_id=trans.contact_id $productFilterTransaction ) THEN TSL.quantity ELSE 0 END) AS qty_damage"),

                DB::raw("SUM(CASE WHEN (SOL.sell_order_date >='".$start."' and SOL.sell_order_date <= '".$end."' $productFilterSOL) THEN SOL.quantity*SOL.purchase_price ELSE 0 END ) as demand_value"),
                DB::raw("SUM(TSL.quantity*TSL.unit_price) as issue_value"),
                DB::raw("SUM(SRL.quantity*SRL.unit_price) as return_value"),
                DB::raw("(SUM( CASE WHEN (trans.permanent_sell = '0' and pro.disposable_item=0 $productFilterTransaction) THEN TSL.quantity*TSL.unit_price ELSE 0 END)-
                (SUM( CASE WHEN (trans.permanent_sell = '0' and pro.disposable_item=0 $productFilterReturn) THEN SRL.quantity*SRL.unit_price ELSE 0 END))) as damage_value"),
                DB::raw("SUM( CASE WHEN (trans.permanent_sell = '0' and pro.disposable_item=0 $productFilterTransaction) THEN (TSL.quantity*TSL.unit_price) ELSE 0 END) AS refundabale_value"),
                DB::raw("SUM( CASE WHEN trans.permanent_sell = '1' $productFilterTransaction THEN TSL.quantity*TSL.unit_price ELSE 0 END) AS non_refundable_value"),
                DB::raw("(SUM( CASE WHEN (trans.permanent_sell = '0' and pro.disposable_item=1 $productFilterTransaction) THEN TSL.quantity*TSL.unit_price ELSE 0 END)-
                (SUM( CASE WHEN (trans.permanent_sell = '0' and pro.disposable_item=1 $productFilterReturn) THEN SRL.quantity*SRL.unit_price ELSE 0 END))) as disposable_value"),
                DB::raw("SUM( CASE WHEN (trans.permanent_sell = '0' and pro.disposable_item=1 $productFilterTransaction) THEN TSL.quantity ELSE 0 END) AS qty_sold_disposable"),
                DB::raw("SUM( CASE WHEN (pro2.disposable_item=1 $productFilterReturn) THEN SRL.quantity ELSE 0 END) AS qty_return_disposable"),
                DB::raw("SUM( CASE WHEN (pro2.disposable_item=1 $productFilterReturn) THEN SRL.quantity*SRL.unit_price ELSE 0 END) AS disposable_return_value"),
                DB::raw("SUM( CASE WHEN trans.permanent_sell = '1' $productFilterTransaction THEN (TSL.quantity*TSL.unit_price) ELSE 0 END) AS qty_sold_value")

            );
            
            $query->whereIn('trans.type',['sell','sell_return_genralstore','sellorder']);

            if ($permitted_locations != 'all') 
            {
            
                $locations_imploded = implode(', ', $permitted_locations);
                $query->whereIn('trans.location_id',[$locations_imploded]);
                
            }

            if (!empty($request->input('location_id'))) 
            {
                $location_id = $request->input('location_id');
                $query->where('trans.location_id',$location_id);
            }

            if (!empty($date_range)) {
                $date_range = $date_range;
                $date_range_array = explode('~', $date_range);
                $start = $filters['start_date'] = $this->transactionUtil->uf_date(trim($date_range_array[0]));
                $end   = $filters['end_date'] = $this->transactionUtil->uf_date(trim($date_range_array[1]));

                $query->whereDate('trans.transaction_date', '>=', $start)
                        ->whereDate('trans.transaction_date', '<=', $end);

                $query->orwhereRaw('(SOL.sell_order_date >= "'.$start.'" and SOL.sell_order_date <= "'.$end.'")');
            }


            if ($customer_id) 
            {
                $query->where('trans.contact_id',$customer_id);
            }

            $query->groupBy('trans.contact_id');

            // var_dump($query->toSql());
            // exit;
            
            return Datatables::of($query)
                ->addColumn('customer', function ($row)  {
                    $customer_name = Contact::where('id',$row->contact_id)->pluck('name')->first();
                    return $customer_name;
                })
                
                ->editColumn('demand', function ($row) {
                    $demand = 0;
                    if($row->demand)
                    {
                        $demand = $row->demand;
                    }
                    return '<span data-is_quantity="true" class="display_currency total_demand_qty" data-currency_symbol=false data-orig-value="' . (float)$demand . '" >' . (float)$demand . '</span> ';
                })

                ->editColumn('issue', function ($row) {
                    $issue = 0;
                    if($row->issue)
                    {
                        $issue = $row->issue;
                    }
                    return '<span data-is_quantity="true" class="display_currency total_issue_qty" data-currency_symbol=false data-orig-value="' . (float)$issue . '" >' . (float)$issue . '</span> ';
                })

                ->addColumn('difference', function ($row) {
                    $issue = 0;
                    $demand = 0;
                    if($row->issue)
                    {
                        $issue = $row->issue;
                    }
                    if($row->demand)
                    {
                        $demand = $row->demand;
                    }
                    $difference = $demand -$issue;
                    return '<span data-is_quantity="true" class="display_currency total_difference_qty" data-currency_symbol=false data-orig-value="' . (float)$difference . '" >' . (float)$difference . '</span> ';
                })
                ->editColumn('qty_refundabale', function ($row) {
                    $qty_refundabale = 0;
                    if($row->qty_refundabale)
                    {
                        $qty_refundabale = $row->qty_refundabale;
                    }
                    return '<span data-is_quantity="true" class="display_currency total_qty_refunbable" data-currency_symbol=false data-orig-value="' . (float)$qty_refundabale . '" >' . (float)$qty_refundabale . '</span> ';
                })
                ->editColumn('qty_non_refundable', function ($row) {
                    $qty_non_refundable = 0;
                    if($row->qty_non_refundable)
                    {
                        $qty_non_refundable = $row->qty_non_refundable;
                    }
                    return '<span data-is_quantity="true" class="display_currency total_qty_non_refunbable" data-currency_symbol=false data-orig-value="' . (float)$qty_non_refundable . '" >' . (float)$qty_non_refundable . '</span> ';
                })
                ->editColumn('qty_sold_disposable', function ($row) {
                    $qty_sold_disposable = 0;
                    if($row->qty_sold_disposable)
                    {
                        $qty_sold_disposable = $row->qty_sold_disposable;
                    }
                    return '<span data-is_quantity="true" class="display_currency total_qty_sold_disposable" data-currency_symbol=false data-orig-value="' . (float)$qty_sold_disposable . '" >' . (float)$qty_sold_disposable . '</span> ';
                })
                ->editColumn('qty_return_disposable', function ($row) {
                    $qty_return_disposable = 0;
                    if($row->qty_return_disposable)
                    {
                        $qty_return_disposable = $row->qty_return_disposable;
                    }
                    return '<span data-is_quantity="true" class="display_currency total_qty_return_disposable" data-currency_symbol=false data-orig-value="' . (float)$qty_return_disposable . '" >' . (float)$qty_return_disposable . '</span> ';
                })
                ->editColumn('qty_return', function ($row) {
                    $qty_return = 0;
                    if($row->qty_return)
                    {
                        $qty_return = $row->qty_return;
                    }
                    return '<span data-is_quantity="true" class="display_currency total_qty_return" data-currency_symbol=false data-orig-value="' . (float)$qty_return . '" >' . (float)$qty_return . '</span> ';
                })
                ->editColumn('qty_damage', function ($row) {
                    $qty_damage = 0;
                    if($row->qty_damage)
                    {
                        $qty_damage = $row->qty_damage;
                    }
                    return '<span data-is_quantity="true" class="display_currency total_qty_damage" data-currency_symbol=false data-orig-value="' . (float)$qty_damage . '" >' . (float)$qty_damage . '</span> ';
                })
                ->editColumn('demand_value', function ($row) {
                    $demand_value = 0;
                    if($row->demand_value)
                    {
                        $demand_value = $row->demand_value;
                    }
                    
                    return '<span class="display_currency total_demand_value" data-currency_symbol=true data-orig-value="' . (float)$demand_value . '" >' . (float)$demand_value . '</span> ';
                })
                ->editColumn('issue_value', function ($row) {
                    $issue_value = 0;
                    if($row->issue_value)
                    {
                        $issue_value = $row->issue_value;
                    }
                    return '<span class="display_currency total_issue_value" data-currency_symbol=true data-orig-value="' . (float)$issue_value . '" >' . (float)$issue_value . '</span> ';
                })
                ->editColumn('refundabale_value', function ($row) {
                    $refundabale_value = 0;
                    if($row->refundabale_value)
                    {
                        $refundabale_value = $row->refundabale_value;
                    }
                    return '<span class="display_currency total_refundabale_value" data-currency_symbol=true data-orig-value="' . (float)$refundabale_value . '" >' . (float)$refundabale_value . '</span> ';
                })
                ->editColumn('non_refundable_value', function ($row) {
                    $non_refundable_value = 0;
                    if($row->non_refundable_value)
                    {
                        $non_refundable_value = $row->non_refundable_value;
                    }
                    return '<span class="display_currency total_non_refundable_value" data-currency_symbol=true data-orig-value="' . (float)$non_refundable_value . '" >' . (float)$non_refundable_value . '</span> ';
                })
                ->editColumn('disposable_value', function ($row) {
                    $disposable_value = 0;
                    if($row->disposable_value)
                    {
                        $disposable_value = $row->disposable_value;
                    }
                    return '<span class="display_currency total_disposable_value" data-currency_symbol=true data-orig-value="' . (float)$disposable_value . '" >' . (float)$disposable_value . '</span> ';
                })
                ->editColumn('disposable_return_value', function ($row) {
                    $disposable_return_value = 0;
                    if($row->disposable_return_value)
                    {
                        $disposable_return_value = $row->disposable_return_value;
                    }
                    return '<span class="display_currency total_disposable_return_value" data-currency_symbol=true data-orig-value="' . (float)$disposable_return_value . '" >' . (float)$disposable_return_value . '</span> ';
                })
                ->editColumn('return_value', function ($row) {
                    $return_value = 0;
                    if($row->return_value)
                    {
                        $return_value = $row->return_value;
                    }
                    return '<span class="display_currency total_return_value" data-currency_symbol=true data-orig-value="' . (float)$return_value . '" >' . (float)$return_value . '</span> ';
                })
                ->editColumn('damage_value', function ($row) {
                    $damage_value = 0;
                    if($row->damage_value)
                    {
                        $damage_value = $row->damage_value;
                    }
                    return '<span class="display_currency total_damage_value" data-currency_symbol=true data-orig-value="' . (float)$damage_value . '" >' . (float)$damage_value . '</span> ';
                })
                
                ->removeColumn('enable_stock')
                ->removeColumn('unit')
                ->removeColumn('id')
                ->rawColumns(['demand','issue','difference','qty_return','demand_value','issue_value','return_value','qty_refundabale',
                'qty_non_refundable','qty_sold_disposable','qty_return_disposable','qty_damage','refundabale_value','non_refundable_value','disposable_value','disposable_return_value','damage_value'])
                ->make(true);
        }

        $categories = Category::where('business_id', $business_id)
                            ->where('parent_id', 0)
                            ->pluck('name', 'id');
        $brands = Brands::where('business_id', $business_id)
                            ->pluck('name', 'id');
        $units = Unit::where('business_id', $business_id)
                            ->pluck('short_name', 'id');
        $business_locations = BusinessLocation::forDropdown($business_id, true);
        
        $customers = Contact::customersDropdown($business_id,false);

        return view('genralstore_reports.overall_summary')
                ->with(compact('categories','business_locations','customers'));
    }
    
    /**
     * get Overall product summary report
     *
     * @return \Illuminate\Http\Response
     */
    public function getOverallProductSummaryReport (Request $request)
    {
        
        if (!auth()->user()->can('genralstore_report.overallproductsummaryreport')) {
            abort(403, 'Unauthorized action.');
        }
        $business_id   = $request->session()->get('user.business_id');
        $customersList = '';
        $customerData  = '';
        $customerDataColumn = '';
        
        
        if (!empty($request->input('location_id'))) 
        {
            
            $location_filter     = '';
            $location_id         = '';
            $data                = '';
            $customer_id         = $request->input('customer_id');
            $date_range          = $request->input('date_range'); 
            $permitted_locations = auth()->user()->permitted_locations();

            

            $pre_query = DB::table('transactions as trans')
            ->join('transaction_sell_lines as TSL', 'trans.id', '=', 'TSL.transaction_id')
            ->join('contacts', 'trans.contact_id', '=', 'contacts.id');
            $pre_query->select('trans.contact_id','contacts.name');
            
            $pre_query->whereIn('trans.type',['sell']);
            
            if ($permitted_locations != 'all') 
            {
            
                $locations_imploded = implode(', ', $permitted_locations);
                $pre_query->whereIn('trans.location_id',[$locations_imploded]);
                
            }

            if (!empty($request->input('location_id'))) 
            {
                $location_id = $request->input('location_id');
                $pre_query->where('trans.location_id',$location_id);
                $pre_query->orderby('trans.contact_id');
                $pre_query->groupby('trans.contact_id');
            }
            
            $start = '';
            $end   = '';
            if (!empty($date_range)) {
                $date_range = $date_range;
                $date_range_array = explode('~', $date_range);
                $start = $filters['start_date'] = $this->transactionUtil->uf_date(trim($date_range_array[0]));
                $end   = $filters['end_date'] = $this->transactionUtil->uf_date(trim($date_range_array[1]));

                $pre_query->whereDate('trans.transaction_date', '>=', $start)
                        ->whereDate('trans.transaction_date', '<=', $end);
            }
            
            if($pre_query->get()->count()>0 && $location_id!='')
            {
                
                $customerData = DB::select('CALL overall_summary_product(?,?,?,?)',array($location_id,'sell',$start,$end));
                
                //$data = DB::select("exec overall_summary_product($location_id,'sell','".$start."','".$end."')");
                // var_dump($customerData);
                // exit;
                return Datatables::of($customerData)
                ->addColumn('total',function ($rowData) {
                    $total = 0;
                    //$rowData = array_splice($row,0,2);
                    
                   foreach((array)$rowData as $key => $value)
                   {
                        
                        if($key!='id' && $key!='sku')
                        {
                            if($value>0)
                            {   
                                $total = $total+$value;
                            }
                        }
                   }
                   
                   return $total;
                })
                ->make(true);
            }
            

            $categories = Category::where('business_id', $business_id)
                                ->where('parent_id', 0)
                                ->pluck('name', 'id');
            $brands = Brands::where('business_id', $business_id)
                                ->pluck('name', 'id');
            $units = Unit::where('business_id', $business_id)
                                ->pluck('short_name', 'id');
            
            //$customersList = $pre_query->get();
            //var_dump($data); exit;
        }
        $business_locations = BusinessLocation::forDropdown($business_id, true);
        $customers = Contact::customersDropdown($business_id,false);
        
        $categories = Category::where('business_id', $business_id)
                                ->where('parent_id', 0)
                                ->pluck('name', 'id');
        
        return view('genralstore_reports.overall_product_summary')
                ->with(compact('categories','business_locations','customers','customersList','customerData'));
    }


    /**
     * get Overall product summary report
     *
     * @return \Illuminate\Http\Response
     */
    public function getOverallProductSummaryReportDemand (Request $request)
    {
        
        if (!auth()->user()->can('genralstore_report.overallproductsummaryreport')) {
            abort(403, 'Unauthorized action.');
        }
        $business_id   = $request->session()->get('user.business_id');
        $customersList = '';
        $customerData  = '';
        $customerDataColumn = '';
        
        
        if (!empty($request->input('location_id'))) 
        {
            
            $location_filter     = '';
            $location_id         = '';
            $data                = '';
            $customer_id         = $request->input('customer_id');
            $date_range          = $request->input('date_range'); 
            $permitted_locations = auth()->user()->permitted_locations();

            

            $pre_query = DB::table('transactions as trans')
            ->join('sell_order_lines as SOL', 'trans.id', '=', 'SOL.transaction_id')
            ->join('contacts', 'trans.contact_id', '=', 'contacts.id');
            $pre_query->select('trans.contact_id','contacts.name');
            
            $pre_query->whereIn('trans.type',['sellorder']);
            
            if ($permitted_locations != 'all') 
            {
            
                $locations_imploded = implode(', ', $permitted_locations);
                $pre_query->whereIn('trans.location_id',[$locations_imploded]);
                
            }

            if (!empty($request->input('location_id'))) 
            {
                $location_id = $request->input('location_id');
                $pre_query->where('trans.location_id',$location_id);
                $pre_query->orderby('trans.contact_id');
                $pre_query->groupby('trans.contact_id');
            }
            
            $start = '';
            $end   = '';
            if (!empty($date_range)) {
                $date_range = $date_range;
                $date_range_array = explode('~', $date_range);
                $start = $filters['start_date'] = $this->transactionUtil->uf_date(trim($date_range_array[0]));
                $end   = $filters['end_date'] = $this->transactionUtil->uf_date(trim($date_range_array[1]));

                $pre_query->whereDate('trans.transaction_date', '>=', $start)
                        ->whereDate('trans.transaction_date', '<=', $end);
            }
            
            if($pre_query->get()->count()>0 && $location_id!='')
            {
                
                $customerData = DB::select('CALL overall_summary_demand_product(?,?,?,?)',array($location_id,'sellorder',$start,$end));
                
                //$data = DB::select("exec overall_summary_product($location_id,'sell','".$start."','".$end."')");
                // var_dump($customerData);
                // exit;
                return Datatables::of($customerData)
                ->addColumn('total',function ($rowData) {
                    $total = 0;
                    //$rowData = array_splice($row,0,2);
                    
                   foreach((array)$rowData as $key => $value)
                   {
                        
                        if($key!='id' && $key!='sku')
                        {
                            if($value>0)
                            {   
                                $total = $total+$value;
                            }
                        }
                   }
                   
                   return $total;
                })
                ->make(true);
            }
            

            $categories = Category::where('business_id', $business_id)
                                ->where('parent_id', 0)
                                ->pluck('name', 'id');
            $brands = Brands::where('business_id', $business_id)
                                ->pluck('name', 'id');
            $units = Unit::where('business_id', $business_id)
                                ->pluck('short_name', 'id');
            
            //$customersList = $pre_query->get();
            //var_dump($data); exit;
        }
        $business_locations = BusinessLocation::forDropdown($business_id, true);
        $customers = Contact::customersDropdown($business_id,false);
        
        $categories = Category::where('business_id', $business_id)
                                ->where('parent_id', 0)
                                ->pluck('name', 'id');
        
        return view('genralstore_reports.overall_product_demand_summary')
                ->with(compact('categories','business_locations','customers','customersList','customerData'));
    }

    /**
     * get Overall product summary report
     *
     * @return \Illuminate\Http\Response
     */
    public function getOverallProductSummaryReportReturn (Request $request)
    {
        
        if (!auth()->user()->can('genralstore_report.overallproductsummaryreport')) {
            abort(403, 'Unauthorized action.');
        }
        $business_id   = $request->session()->get('user.business_id');
        $customersList = '';
        $customerData  = '';
        $customerDataColumn = '';
        
        
        if (!empty($request->input('location_id'))) 
        {
            
            $location_filter     = '';
            $location_id         = '';
            $data                = '';
            $customer_id         = $request->input('customer_id');
            $date_range          = $request->input('date_range'); 
            $permitted_locations = auth()->user()->permitted_locations();

            

            $pre_query = DB::table('transactions as trans')
            ->join('sell_return_lines as SRL', 'trans.id', '=', 'SRL.transaction_id')
            ->join('contacts', 'trans.contact_id', '=', 'contacts.id');
            $pre_query->select('trans.contact_id','contacts.name');
            
            $pre_query->whereIn('trans.type',['sell_return_genralstore']);
            
            if ($permitted_locations != 'all') 
            {
            
                $locations_imploded = implode(', ', $permitted_locations);
                $pre_query->whereIn('trans.location_id',[$locations_imploded]);
                
            }

            if (!empty($request->input('location_id'))) 
            {
                $location_id = $request->input('location_id');
                $pre_query->where('trans.location_id',$location_id);
                $pre_query->orderby('trans.contact_id');
                $pre_query->groupby('trans.contact_id');
            }
            
            $start = '';
            $end   = '';
            if (!empty($date_range)) {
                $date_range = $date_range;
                $date_range_array = explode('~', $date_range);
                $start = $filters['start_date'] = $this->transactionUtil->uf_date(trim($date_range_array[0]));
                $end   = $filters['end_date'] = $this->transactionUtil->uf_date(trim($date_range_array[1]));

                $pre_query->whereDate('trans.transaction_date', '>=', $start)
                        ->whereDate('trans.transaction_date', '<=', $end);
            }
            
            if($pre_query->get()->count()>0 && $location_id!='')
            {
                
                $customerData = DB::select('CALL overall_summary_return_product(?,?,?,?)',array($location_id,'sell_return_genralstore',$start,$end));
                
                //$data = DB::select("exec overall_summary_product($location_id,'sell','".$start."','".$end."')");
                // var_dump($customerData);
                // exit;
                return Datatables::of($customerData)
                ->addColumn('total',function ($rowData) {
                    $total = 0;
                    //$rowData = array_splice($row,0,2);
                    
                   foreach((array)$rowData as $key => $value)
                   {
                        
                        if($key!='id' && $key!='sku')
                        {
                            if($value>0)
                            {   
                                $total = $total+$value;
                            }
                        }
                   }
                   
                   return $total;
                })
                ->make(true);
            }
            

            $categories = Category::where('business_id', $business_id)
                                ->where('parent_id', 0)
                                ->pluck('name', 'id');
            $brands = Brands::where('business_id', $business_id)
                                ->pluck('name', 'id');
            $units = Unit::where('business_id', $business_id)
                                ->pluck('short_name', 'id');
            
            //$customersList = $pre_query->get();
            //var_dump($data); exit;
        }
        $business_locations = BusinessLocation::forDropdown($business_id, true);
        $customers = Contact::customersDropdown($business_id,false);
        
        $categories = Category::where('business_id', $business_id)
                                ->where('parent_id', 0)
                                ->pluck('name', 'id');
        
        return view('genralstore_reports.overall_product_return_summary')
                ->with(compact('categories','business_locations','customers','customersList','customerData'));
    }

    /**
     * Prints invoice for sell
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function getTotalDemandDetailReport(Request $request, $product_id,$date_range)
    {
        if (!auth()->user()->can('genralstore_report.totaldemandreport')) {
            abort(403, 'Unauthorized action.');
        }
        
        $business_id = $request->session()->get('user.business_id');
        if (request()->ajax()) 
        {
            $products = DB::table('sell_order_lines as sol')
            ->join('transactions as trans', 'trans.id', '=', 'sol.transaction_id')
            ->join('contacts','contacts.id','=','trans.contact_id')
            ->join('products','products.id','=','sol.product_id');

            $products->select(DB::raw('SUM(sol.quantity) as quantity'),'products.name as product_name','products.id as product_id','trans.transaction_date','trans.ref_no','contacts.name as customer','sol.purchase_price','sol.sell_order_date');
            if (!empty($business_id)) 
            {
                $products->where('trans.business_id', $business_id);
            }
            if (!empty($request->input('location_id'))) 
            {
                $products->where('trans.location_id', $request->input('location_id'));
            }
            if (!empty($request->input('ir_customer_id'))) 
            {
                $products->where('trans.contact_id', $request->input('ir_customer_id'));
            }
            if (!empty($product_id)) 
            {
                $products->where('sol.product_id', $product_id);
            }
            
            if (!empty($date_range)) {
                $date_range = $date_range;
                $date_range_array = explode('~', $date_range);
                $start = $filters['start_date'] = $this->transactionUtil->uf_date(trim($date_range_array[0]));
                $end   = $filters['end_date'] = $this->transactionUtil->uf_date(trim($date_range_array[1]));

                $products->whereDate('sol.sell_order_date', '>=', $start)
                        ->whereDate('sol.sell_order_date', '<=', $end);
            }
            $productsData = $products->groupBy('sol.product_id','trans.ref_no')->get();

            return view('genralstore_reports.detail_demand_show') ->with(compact('productsData'));
        }
        
    }

    /**
     * Get overall summary column data
     */
    
     public function overallSummaryProductColumnName(Request $request)
     {
        if (!empty($request->input('location_id'))) 
        {
            
            $location_filter     = '';
            $location_id         = '';
            $data                = '';
            $customer_id         = $request->input('customer_id');
            $date_range          = $request->input('date_range'); 
            $permitted_locations = auth()->user()->permitted_locations();

            $pre_query = DB::table('transactions as trans')
            ->join('transaction_sell_lines as TSL', 'trans.id', '=', 'TSL.transaction_id')
            ->join('contacts', 'trans.contact_id', '=', 'contacts.id');
            $pre_query->select('contacts.contact_id as data','contacts.name','contacts.name as title');
            
            $pre_query->whereIn('trans.type',['sell']);
            
            if ($permitted_locations != 'all') 
            {
            
                $locations_imploded = implode(', ', $permitted_locations);
                $pre_query->whereIn('trans.location_id',[$locations_imploded]);
                
            }

            if(!empty($customer_id))
            {
                $pre_query->where('contacts.id',$customer_id);
            }

            if (!empty($request->input('location_id'))) 
            {
                $location_id = $request->input('location_id');
                $pre_query->where('trans.location_id',$location_id);
                $pre_query->orderby('trans.contact_id');
                $pre_query->groupby('trans.contact_id');
            }
            
            $start = '';
            $end   = '';
            if (!empty($date_range)) {
                $date_range = $date_range;
                $date_range_array = explode('~', $date_range);
                $start = $filters['start_date'] = $this->transactionUtil->uf_date(trim($date_range_array[0]));
                $end   = $filters['end_date'] = $this->transactionUtil->uf_date(trim($date_range_array[1]));

                $pre_query->whereDate('trans.transaction_date', '>=', $start)
                        ->whereDate('trans.transaction_date', '<=', $end);
            }
            // var_dump($pre_query->tosql());
            // exit;
            $data = array ();
            $data[0]['data'] = "id";
            $data[0]['name'] = "id";
            $data[0]['title'] = "id";
            // $data[1]['data'] = "name";
            // $data[1]['name'] = "name";
            $data[1]['data'] = "sku";
            $data[1]['name'] = "sku";
            $data[1]['title'] = "sku";
            $customersList = $pre_query->get()->toarray();
            $countData = count($customersList)+1;
            $customersList[$countData]['data'] = 'total';
            $customersList[$countData]['name'] = 'Total';
            $customersList[$countData]['title'] = 'Total';
            // foreach($arr as $value => $helper){
            //     echo $value;
            // }
            $updated_data = array_merge($data,$customersList);
            
            // $columndata= json_decode(json_encode($updated_data));
            
            return json_encode($updated_data);
        }
    }

    public function overallSummaryProductDemandColumnName(Request $request)
     {
        if (!empty($request->input('location_id'))) 
        {
            
            $location_filter     = '';
            $location_id         = '';
            $data                = '';
            $customer_id         = $request->input('customer_id');
            $date_range          = $request->input('date_range'); 
            $permitted_locations = auth()->user()->permitted_locations();

            $pre_query = DB::table('transactions as trans')
            ->join('sell_order_lines as SOL', 'trans.id', '=', 'SOL.transaction_id')
            ->join('contacts', 'trans.contact_id', '=', 'contacts.id');
            $pre_query->select('contacts.contact_id as data','contacts.name','contacts.name as title');
            
            $pre_query->whereIn('trans.type',['sellorder']);
            
            if ($permitted_locations != 'all') 
            {
            
                $locations_imploded = implode(', ', $permitted_locations);
                $pre_query->whereIn('trans.location_id',[$locations_imploded]);
                
            }

            if(!empty($customer_id))
            {
                $pre_query->where('contacts.id',$customer_id);
            }

            if (!empty($request->input('location_id'))) 
            {
                $location_id = $request->input('location_id');
                $pre_query->where('trans.location_id',$location_id);
                $pre_query->orderby('trans.contact_id');
                $pre_query->groupby('trans.contact_id');
            }
            
            $start = '';
            $end   = '';
            if (!empty($date_range)) {
                $date_range = $date_range;
                $date_range_array = explode('~', $date_range);
                $start = $filters['start_date'] = $this->transactionUtil->uf_date(trim($date_range_array[0]));
                $end   = $filters['end_date'] = $this->transactionUtil->uf_date(trim($date_range_array[1]));

                $pre_query->whereDate('trans.transaction_date', '>=', $start)
                        ->whereDate('trans.transaction_date', '<=', $end);
            }
            // var_dump($pre_query->tosql());
            // exit;
            $data = array ();
            $data[0]['data'] = "id";
            $data[0]['name'] = "id";
            $data[0]['title'] = "id";
            // $data[1]['data'] = "name";
            // $data[1]['name'] = "name";
            $data[1]['data'] = "sku";
            $data[1]['name'] = "sku";
            $data[1]['title'] = "sku";
            $customersList = $pre_query->get()->toarray();
            $countData = count($customersList)+1;
            $customersList[$countData]['data'] = 'total';
            $customersList[$countData]['name'] = 'Total';
            $customersList[$countData]['title'] = 'Total';
            // foreach($arr as $value => $helper){
            //     echo $value;
            // }
            $updated_data = array_merge($data,$customersList);
            
            // $columndata= json_decode(json_encode($updated_data));
            
            return json_encode($updated_data);
        }
    }

    public function overallSummaryProductReturnColumnName(Request $request)
     {
        if (!empty($request->input('location_id'))) 
        {
            
            $location_filter     = '';
            $location_id         = '';
            $data                = '';
            $customer_id         = $request->input('customer_id');
            $date_range          = $request->input('date_range'); 
            $permitted_locations = auth()->user()->permitted_locations();

            $pre_query = DB::table('transactions as trans')
            ->join('sell_return_lines as SRL', 'trans.id', '=', 'SRL.transaction_id')
            ->join('contacts', 'trans.contact_id', '=', 'contacts.id');
            $pre_query->select('contacts.contact_id as data','contacts.name','contacts.name as title');
            
            $pre_query->whereIn('trans.type',['sell_return_genralstore']);
            
            if ($permitted_locations != 'all') 
            {
            
                $locations_imploded = implode(', ', $permitted_locations);
                $pre_query->whereIn('trans.location_id',[$locations_imploded]);
                
            }

            if(!empty($customer_id))
            {
                $pre_query->where('contacts.id',$customer_id);
            }

            if (!empty($request->input('location_id'))) 
            {
                $location_id = $request->input('location_id');
                $pre_query->where('trans.location_id',$location_id);
                $pre_query->orderby('trans.contact_id');
                $pre_query->groupby('trans.contact_id');
            }
            
            $start = '';
            $end   = '';
            if (!empty($date_range)) {
                $date_range = $date_range;
                $date_range_array = explode('~', $date_range);
                $start = $filters['start_date'] = $this->transactionUtil->uf_date(trim($date_range_array[0]));
                $end   = $filters['end_date'] = $this->transactionUtil->uf_date(trim($date_range_array[1]));

                $pre_query->whereDate('trans.transaction_date', '>=', $start)
                        ->whereDate('trans.transaction_date', '<=', $end);
            }
            // var_dump($pre_query->tosql());
            // exit;
            $data = array ();
            $data[0]['data'] = "id";
            $data[0]['name'] = "id";
            $data[0]['title'] = "id";
            // $data[1]['data'] = "name";
            // $data[1]['name'] = "name";
            $data[1]['data'] = "sku";
            $data[1]['name'] = "sku";
            $data[1]['title'] = "sku";
            $customersList = $pre_query->get()->toarray();
            // foreach($arr as $value => $helper){
            //     echo $value;
            // }
            $countData = count($customersList)+1;
            $customersList[$countData]['data'] = 'total';
            $customersList[$countData]['name'] = 'Total';
            $customersList[$countData]['title'] = 'Total';
            $updated_data = array_merge($data,$customersList);
            
            
            // $columndata= json_decode(json_encode($updated_data));
            
            return json_encode($updated_data);
        }
    }

    
    public function getStockReport(Request $request)
    {
        $business_id   = $request->session()->get('user.business_id');
        $business_locations = BusinessLocation::forDropdown($business_id, true);
        return view('genralstore_reports.stock_report')->with(compact('business_locations'));
    }

    public function productSummary(Request $request)
    {
        $business_id   = $request->session()->get('user.business_id');
        $business_locations = BusinessLocation::forDropdown($business_id, true);
        if ($request->ajax()) {
            
            if (!empty($request->input('location_id'))) 
            {
                $location_filter     = '';
                $customer_id         = $request->input('customer_id');
                $date_range          = $request->input('date_range'); 
                $permitted_locations = auth()->user()->permitted_locations();
                $query_string   = '';

                if (!empty($date_range)) {
                    $date_range = $date_range;
                    $date_range_array = explode('~', $date_range);
                    $start = $filters['start_date'] = $this->transactionUtil->uf_date(trim($date_range_array[0]));
                    $end   = $filters['end_date'] = $this->transactionUtil->uf_date(trim($date_range_array[1]));
                    
                    $date_range_filter='date_range='.$date_range;
                }

            
                    $location_id = $request->input('location_id');
                    $query_string .= '&location_id='.$location_id;
                
                $query = DB::table('variations')
                ->join('products as p', 'p.id', '=', 'variations.product_id')
                ->join('units','p.unit_id','=','units.id')
                ->join('product_variations as pv','variations.product_variation_id','=','pv.id')
                ->join('variation_location_details as vld','p.id','=','vld.product_id')
                ->join('variations as var','p.id','=','var.product_variation_id');
                
                if (!empty($request->input('category_id'))) {
                    $query->where('p.category_id', $request->input('category_id'));
                    $query_string .= '&category_id='.$request->input('category_id');
                }

                $query->select(

                    DB::raw("(SELECT SUM(pl.quantity) FROM transactions JOIN purchase_lines AS pl ON transactions.id=pl.transaction_id WHERE transactions.status='received' AND transactions.type='purchase' AND transactions.purchase_seva=0   
                    AND pl.variation_id=vld.product_id AND transactions.location_id='".$location_id."' AND (transactions.transaction_date >= '".$start." 00:00:00' and transactions.transaction_date <= '".$end." 23:59:59')) as total_purchase"),
                    
                    DB::raw("(SELECT SUM(pl.quantity) FROM transactions JOIN purchase_lines AS pl ON transactions.id=pl.transaction_id
                    WHERE transactions.status='received' AND transactions.type='purchase' AND transactions.purchase_seva=1 AND pl.variation_id=vld.product_id AND transactions.location_id='".$location_id."' 
                    AND (transactions.transaction_date >= '".$start." 00:00:00' and transactions.transaction_date <= '".$end." 23:59:59')) as total_seva"),

                    DB::raw("(SELECT SUM(pl.quantity) FROM transactions JOIN purchase_lines AS pl ON transactions.id=pl.transaction_id
                    WHERE transactions.status='received' AND transactions.type='purchase_transfer'  
                    AND pl.variation_id=vld.product_id AND transactions.location_id='".$location_id."'
                    AND (transactions.transaction_date >= '".$start." 00:00:00' and transactions.transaction_date <= '".$end." 23:59:59')) as total_transfer_from"),

                    DB::raw("SUM(`vld`.`qty_available`) as current_stock"),

                    DB::raw("(SELECT SUM(tsl.quantity) FROM transactions JOIN transaction_sell_lines AS tsl ON transactions.id=tsl.transaction_id
                    WHERE transactions.status='final' AND transactions.type='sell_transfer'  
                    AND tsl.variation_id=vld.product_id AND transactions.location_id='".$location_id."'
                    AND (transactions.transaction_date >= '".$start." 00:00:00' and transactions.transaction_date <= '".$end." 23:59:59')) as total_transfer_to"),

                    DB::raw("(SELECT SUM(tsl.quantity) FROM transactions JOIN transaction_sell_lines AS tsl ON transactions.id=tsl.transaction_id
                    WHERE transactions.status='final' AND transactions.type='sell'  AND transactions.permanent_sell=1
                    AND tsl.variation_id=vld.product_id AND transactions.location_id='".$location_id."'
                    AND (transactions.transaction_date >= '".$start." 00:00:00' and transactions.transaction_date <= '".$end." 23:59:59')) as total_permanent_sell"),

                    DB::raw("variations.sub_sku, p.name, p.type, p.id as product_id, p.disposable_item as disposable, 
                    units.short_name as unit, p.enable_stock as enable_stock, variations.sell_price_inc_tax as unit_price, pv.name as product_variation, 
                    variations.name as variation_name, var.default_sell_price")
                    

                );
                
                
                $query->where('vld.location_id',$location_id);
                $query->groupBy('variations.id');
                
                
                return Datatables::of($query)
                    ->editColumn('total_purchase', function ($row) {
                        $total_purchase = 0;
                        if ($row->total_purchase) {
                            $total_purchase = $row->total_purchase;
                        }
                        return '<span data-is_quantity="true" class="total_purchase" data-currency_symbol=false data-orig-value="' . $total_purchase . '"  >' . $total_purchase . '</span> ';
                    })
                    ->editColumn('total_seva', function ($row) {
                        $total_seva = 0;
                        if ($row->total_seva) {
                            $total_seva = $row->total_seva;
                        }
                        return '<span data-is_quantity="true" class="total_seva" data-currency_symbol=false data-orig-value="' . $total_seva . '"  >' . $total_seva . '</span> ';
                    })
                    ->editColumn('total_transfer_from', function ($row) use ($query_string,$date_range_filter) {
                        $total_transfer_from = 0;
                        if ($row->total_transfer_from) {
                            $total_transfer_from = $row->total_transfer_from;
                        }
                        return '<a data-is_quantity="true" data-orig-value="' . (float)$total_transfer_from . '"data-currency_symbol=false data-href="' . action('GenralstoreReportController@productSummaryDetailsFrom', [$row->product_id,$date_range_filter])
                        .$query_string.'" href="#" data-container=".view_modal" class="btn-modal total_transfer_from">' . (float)$total_transfer_from . '</a>';
                        

                        // return '<span data-is_quantity="true" class="total_transfer_from" data-currency_symbol=false data-orig-value="' . $total_transfer_from . '"  >' . $total_transfer_from . '</span> ';
                    })
                    ->editColumn('total_transfer_to', function ($row) use ($query_string,$date_range_filter) {
                        $total_transfer_to = 0;
                        if ($row->total_transfer_to) {
                            $total_transfer_to = $row->total_transfer_to;
                        }
                        return '<a data-is_quantity="true" data-orig-value="' . (float)$total_transfer_to . '"data-currency_symbol=false data-unit="' . $row->unit . '" data-href="' . action('GenralstoreReportController@productSummaryDetailsTo', [$row->product_id,$date_range_filter])
                        .$query_string.'" href="#" data-container=".view_modal" class="btn-modal total_transfer_to">' . (float)$total_transfer_to . '</a>';
                        
                        // return '<span data-is_quantity="true" class="total_transfer_to" data-currency_symbol=false data-orig-value="' . $total_transfer_to . '"  >' . $total_transfer_to . '</span> ';
                    })
                    ->editColumn('total_permanent_sell', function ($row) {
                        $total_permanent_sell = 0;
                        if ($row->total_permanent_sell) {
                            $total_permanent_sell = $row->total_permanent_sell;
                        }
                        return '<span data-is_quantity="true" class="total_permanent_sell" data-currency_symbol=false data-orig-value="' . $total_permanent_sell . '"  >' . $total_permanent_sell . '</span> ';
                    })
                    ->editColumn('current_stock', function ($row) {
                        $current_stock = 0;
                        if ($row->current_stock) {
                            $current_stock = $row->current_stock;
                        }
                        return '<span data-is_quantity="true" class="current_stock" data-currency_symbol=false data-orig-value="' . $current_stock . '"  >' . $current_stock . '</span> ';
                    })
                    ->editColumn('default_sell_price', function ($row) {
                        $default_sell_price = 0;
                        if ($row->default_sell_price) {
                            $price = $row->default_sell_price;
                        }
                        return '<span class="price display_currency" data-currency_symbol=true data-orig-value="' . $price . '"  >' . $price . '</span> ';
                    })
                    ->removeColumn('enable_stock')
                    // ->removeColumn('unit')
                    ->removeColumn('id')
                    ->rawColumns(['total_purchase','total_seva','total_transfer_from','total_transfer_to','total_permanent_sell','current_stock','default_sell_price'])
                    ->make(true);
            }
            else
            {
                
            
                $data = array ();
                $data['data'][0]['name'] = "";
                $data['data'][0]['sub_sku'] = "";
                $data['data'][0]['total_purchase'] = "";
                $data['data'][0]['total_seva'] = "";
                $data['data'][0]['total_transfer_from'] = "";
                $data['data'][0]['total_transfer_to'] = "";
                $data['data'][0]['total_permanent_sell'] = "";
                $data['data'][0]['current_stock'] = "";
                $data['data'][0]['default_sell_price'] = "";
                
                return json_encode($data);
            }
        }
        $categories = Category::where('business_id', $business_id)
                                ->where('parent_id', 0)
                                ->pluck('name', 'id');
        return view('genralstore_reports.productSummary')->with(compact('business_locations','categories'));
    }

    public function productSummaryDetailsTo(Request $request, $product_id){

        $business_id = $request->session()->get('user.business_id');
        $location_id = $request->get('location_id'); 
        $category_id = $request->get('category_id'); 

        if ($request->ajax()) {
        
            $query1 = Transaction::select('id')
                    ->where('type','sell_transfer')
                    ->where('location_id',$location_id);

            $query =  DB::table('transactions as trans')
            ->join('purchase_lines as pl','trans.id','=','pl.transaction_id')
            ->join('business_locations as BS','trans.location_id','=','BS.id')
            ->join('products','products.id','=','pl.product_id');
            
            if (!empty($product_id)) {
                $query->where('pl.variation_id', $product_id);
            }

            if (!empty($category_id)) {
                $query->where('products.category_id', $category_id);
            }

            if (!empty($request->input('date_range'))) {
                $date_range = $request->input('date_range');
                $date_range_array = explode('~', $date_range);
                $start = $filters['start_date'] = $this->transactionUtil->uf_date(trim($date_range_array[0]));
                $end   = $filters['end_date'] = $this->transactionUtil->uf_date(trim($date_range_array[1]));

                $query1->whereDate('transaction_date', '>=', $start)
                    ->whereDate('transaction_date', '<=', $end)
                    ->get()->toArray();

                $query->whereDate('trans.transaction_date', '>=', $start)
                ->whereDate('trans.transaction_date', '<=', $end);
            }
            $query1->get()->toArray();

            $query->whereIn('trans.transfer_parent_id', $query1);

            $product = $query->select(
                DB::raw("SUM(pl.quantity) as transfer_to"),
                'trans.ref_no',
                'BS.name as name',
                'trans.transaction_date'
            )->groupBy('trans.ref_no','BS.name','trans.transaction_date');

            $product_data = $product->get()->toarray();
            return view('genralstore_reports.detail_product_summary') ->with(compact('product_data'));
        }
    
    }

    public function productSummaryDetailsFrom(Request $request, $product_id){
        $business_id = $request->session()->get('user.business_id');
        $location_id = $request->get('location_id'); 
        $category_id = $request->get('category_id'); 

        if ($request->ajax()) {
        
            $query1 = Transaction::select('transfer_parent_id')
                    ->where('type','purchase_transfer')
                    ->where('location_id',$location_id);

            $query =  DB::table('transactions as trans')
            ->join('transaction_sell_lines as tsl','trans.id','=','tsl.transaction_id')
            ->join('business_locations as BS','trans.location_id','=','BS.id')
            ->join('products','products.id','=','tsl.product_id');
            
            if (!empty($product_id)) {
                $query->where('tsl.variation_id', $product_id);
            }

            if (!empty($category_id)) {
                $query->where('products.category_id', $category_id);
            }

            if (!empty($request->input('date_range'))) {
                $date_range = $request->input('date_range');
                $date_range_array = explode('~', $date_range);
                $start = $filters['start_date'] = $this->transactionUtil->uf_date(trim($date_range_array[0]));
                $end   = $filters['end_date'] = $this->transactionUtil->uf_date(trim($date_range_array[1]));

                $query1->whereDate('transaction_date', '>=', $start)
                    ->whereDate('transaction_date', '<=', $end)
                    ->get()->toArray();

                $query->whereDate('trans.transaction_date', '>=', $start)
                ->whereDate('trans.transaction_date', '<=', $end);
            }
            $query1->get()->toArray();
            $query->whereIn('trans.id', $query1);

            $product = $query->select(
                DB::raw("SUM(tsl.quantity) as transfer_to"),
                'trans.ref_no',
                'BS.name as name',
                'trans.transaction_date'
            )->groupBy('trans.ref_no','BS.name','trans.transaction_date');

            $product_data = $product->get()->toarray();

            return view('genralstore_reports.detail_product_summary') ->with(compact('product_data'));
        }
    }
}
