<?php

namespace App\Http\Controllers;

use App\NotificationTemplate;
use Illuminate\Http\Request;
use App\GatePass;
use App\Contact;
use App\GatePassItems;
use App\Utils\ModuleUtil;
use App\Utils\ProductUtil;
use Datatables;
use Illuminate\Support\Facades\DB;

class GatePassController extends Controller
{
    /**
     * All Utils instance.
     *
     */
    protected $moduleUtil;
    protected $productUtil;

    /**
     * Constructor
     *
     * @param ProductUtils $product
     * @return void
     */
    public function __construct(ProductUtil $productUtil, ModuleUtil $moduleUtil)
    {
        $this->productUtil = $productUtil;
        $this->moduleUtil = $moduleUtil;
    }

    /**
     * Display a listing of the gate pass.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (!auth()->user()->can('gate_pass.view') && !auth()->user()->can('gate_pass.create')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {

            $query = GatePass::select(
                'vibhag_name',
                'gate_pass.id',
                'driver_name',
                'driver_mobile_number',
                'vehicle_number',
                'deliever_to',
                'date',
                'check_in',
                'check_out',
                'serial_no'
            );

            if (request()->has('serial_no')) {
                $serial_no = request()->get('serial_no');
                if (!empty($serial_no)) {
                    $query->where('serial_no', $serial_no);
                    // $query->where('serial_no', 'like', ["%{$serial_no}%"]);
                }
            }

            if (request()->has('type')) {
                $type = request()->get('type');
                if (!empty($type)) {
                    $query->where('type', $type);
                   
                }
            }

            if (!empty(request()->start_date) && !empty(request()->end_date)) {
                $start = request()->start_date;
                $end =  request()->end_date;
                $query->whereDate('date', '>=', $start)
                    ->whereDate('date', '<=', $end);
            }

            $gatePassData = $query->orderBy('id', "DESC");

            return Datatables::of($gatePassData)
                ->addColumn('action', function ($row) {
                    $html = '<div class="btn-group">
                        <button type="button" class="btn btn-info dropdown-toggle btn-xs" 
                            data-toggle="dropdown" aria-expanded="false">' .
                        __("messages.actions") .
                        '<span class="caret"></span><span class="sr-only">Toggle Dropdown
                            </span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-right" role="menu">';
                    if (auth()->user()->can("gate_pass.view")) {
                        $html .= '<li><a href="#" class="print-invoice" data-href="' . action('GatePassController@printInvoice', [$row->id]) . '"><i class="fa fa-print" aria-hidden="true"></i>' . __("messages.print") . '</a></li>';
                    }
                    if (auth()->user()->can("gate_pass.update")) {
                        $html .= '<li><a href="' . action('GatePassController@edit', [$row->id]) . '"><i class="glyphicon glyphicon-edit"></i>' . __("messages.edit") . '</a></li>';
                    }
                    if (auth()->user()->can("gate_pass.delete")) {
                        $html .= '<li><a href="' . action('GatePassController@destroy', [$row->id]) . '" class="delete-gate_pass"><i class="fa fa-trash"></i>' . __("messages.delete") . '</a></li>';
                    }

                    $html .=  '</ul></div>';
                    return $html;
                })
                ->editColumn('date', '{{@format_date($date)}}')
                ->editColumn('check_in', '{{@format_datetime($check_in)}}')
                ->editColumn(
                    'check_out',
                    '@if($check_out == "0000-00-00 00:00:00")  @else {{@format_datetime($check_out)}} @endif'
                )
                ->rawColumns(['action', 'check_out'])
                ->make(true);
        }
        return view('gate_pass.index');
    }

    /**
     * Show the form for creating a new resource for gate pass.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        if (!auth()->user()->can('gate_pass.create')) {
            abort(403, 'Unauthorized action.');
        }

        return view('gate_pass.create');
    }

    /**
     * Store a newly created gate pass in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // $a = "aa";
        // return $a;
        if (!auth()->user()->can('gate_pass.create')) {
            abort(403, 'Unauthorized action.');
        }
        try {

            $gate_pass_data = $request->only(['reference_no', 'vibhag_name', 'driver_name', 'driver_mobile_number', 'vehicle_number', 'deliever_to', 'sign_of_gate_pass_approval', 'sign_of_secutiry_person', 'date', 'document', 'serial_no','getpass_type','additional_notes']);

            $request->validate([
                'vibhag_name' => 'required',
                'driver_name' => 'required',
                'driver_mobile_number' => 'required',
                'vehicle_number' => 'required',
                'deliever_to' => 'required',
                'sign_of_gate_pass_approval' => 'required',
                'document' => 'file|max:' . (config('constants.document_size_limit') / 1000)
            ]);

            $gate_pass_data['date'] = $this->productUtil->uf_date($gate_pass_data['date'], true);

            $gate_pass_data['check_in'] = \Carbon::now()->toDateTimeString();
            $gate_pass_data['status'] = 0;

            DB::beginTransaction();

            //Update reference count
            $serial_no_count = $this->productUtil->setAndGetReferenceCount('gate_pass_prefix');
            //Generate reference number
            if (empty($gate_pass_data['serial_no'])) {
                $gate_pass_data['serial_no'] = $this->productUtil->generateReferenceNumber('gate_pass_prefix', $serial_no_count);
            }
            $gate_pass_data['created_by'] =request()->session()->get('user.id');
            $gatePass = GatePass::create($gate_pass_data);

            $gate_pass_data['document'] = $this->productUtil->uploadFile($request, 'document', 'documents');
            // SAVE FOR MULTIPLE ITEMS AND QUANTITY
            // if (!empty($request->input('items')) && (!empty($request->input('qtys')))) {
            //     $items = $request->input('items');
            //     $qtys = $request->input('qtys');
            //     $data = [];
            //     foreach ($items as $key => $value) {
            //         if (!empty($value)) {
            //             $data[] = ['name' => $value, 'qty' => $qtys[$key]];
            //         }
            //     }

            //     $gatePass->values()->createMany($data);
            // }

            // CODE FOR SAVING ONLY ITEMS.
            if (!empty($request->input('items'))) {
                $items = $request->input('items');
                $data = [];
                foreach ($items as $key => $value) {
                    if (!empty($value)) {
                        $data[] = ['name' => $value];
                    }
                }

                $gatePass->values()->createMany($data);
            }
            DB::commit();
            $lastId = $gatePass->id;
            $receipt = $this->receiptContent($lastId);

            $output = [
                'success' => 1,
                'msg' => __('gate_pass.gate_pass_add_success'),
                'receipt' => $receipt
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            $output = [
                'success' => 0,
                'msg' => __('messages.something_went_wrong')
            ];
        }
        return $output;
        // return redirect('gate-pass')->with('status', $output);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        if (!auth()->user()->can('gate_pass.update')) {
            abort(403, 'Unauthorized action.');
        }
        $gatePassData = GatePass::where('gate_pass.id', $id)
            ->with(['values'])->first();

        return view('gate_pass.edit')
            ->with(compact(
                'gatePassData',
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

        if (!auth()->user()->can('gate_pass.update')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            //Validate document size
            $request->validate([
                'document' => 'file|max:' . (config('constants.document_size_limit') / 1000)
            ]);

            $gatePassData = GatePass::findOrFail($id);

            $update_data = $request->only(['reference_no','vibhag_name', 'driver_name', 'driver_mobile_number', 'vehicle_number', 'deliever_to', 'sign_of_gate_pass_approval', 'sign_of_secutiry_person', 'date', 'document','getpass_type','serial_no','additional_notes']);

            $update_data['date'] = $this->productUtil->uf_date($update_data['date'], true);

            DB::beginTransaction();

            //update gatePass
            $doc_name = $this->productUtil->uploadFile($request, 'document', 'documents');

            if (!empty($doc_name)) {
                $update_data['document'] = $doc_name;
            }
            $gatePassData->update($update_data);
            $data = [];
            // QUANTITY AND ITEMS SAVE
            // if (!empty($request->input('edit_items')) && !empty($request->input('edit_qtys'))) {
            //     $edit_items = $request->input('edit_items');
            //     $edit_qtys = $request->input('edit_qtys');
            //     foreach ($edit_items as $key => $value) {
            //         if (!empty($value)) {
            //             $gatePassItem = GatePassItems::find($key);

            //             if ($gatePassItem->name != $value) {
            //                 $gatePassItem->name = $value;
            //                 $data[] = $gatePassItem;
            //             }
            //             if ($gatePassItem->qty != $edit_qtys[$key]) {
            //                 $gatePassItem->qty = $edit_qtys[$key];
            //                 $data[] = $gatePassItem;
            //             }
            //         }
            //     }
            //     $gatePassData->values()->saveMany($data);
            // }

            // if (!empty($request->input('items')) && !empty($request->input('qtys'))) {
            //     $items = $request->input('items');
            //     $qtys = $request->input('qtys');

            //     foreach ($items as $key => $value) {
            //         if (!empty($value)) {
            //             $data[] = new GatePassItems(['name' => $value, 'qty' => $qtys[$key]]);
            //         }
            //     }
            // }

            // SAVE MULTIPLE ITEMS
            if (!empty($request->input('edit_items'))) {
                $edit_items = $request->input('edit_items');
                foreach ($edit_items as $key => $value) {
                    $gatePassItem = GatePassItems::find($key);
                    if (!empty($value)) {

                        if ($gatePassItem->name != $value) {
                            $gatePassItem->name = $value;
                            $data[] = $gatePassItem;
                        }
                    } else {
                        $gatePassItem->delete();
                    }
                }
                $gatePassData->values()->saveMany($data);
            }

            if (!empty($request->input('items'))) {
                $items = $request->input('items');
                foreach ($items as $key => $value) {
                    if (!empty($value)) {
                        $data[] = new GatePassItems(['name' => $value]);
                    }
                }
                $gatePassData->values()->saveMany($data);
            }

            DB::commit();

            $output = [
                'success' => 1,
                'msg' => __('gate_pass.gate_pass_update_success')
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            $output = [
                'success' => 0,
                'msg' => $e->getMessage()
            ];
            return back()->with('status', $output);
        }

        return redirect('gate-pass')->with('status', $output);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if (!auth()->user()->can('gate_pass.delete')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            if (request()->ajax()) {

                $gatePassData = GatePass::where('id', $id)
                    ->first();


                DB::beginTransaction();

                $gatePassData->delete();

                DB::commit();

                $output = [
                    'success' => true,
                    'msg' => __('gate_pass.gate_pass_delete_success')
                ];
            }
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            $output = [
                'success' => false,
                'msg' => $e->getMessage()
            ];
        }

        return $output;
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
    private function receiptContent($id)
    {
        $gatePassData = GatePass::where('gate_pass.id', $id)
            ->with(['values'])->first();

        $output['html_content'] =  view('gate_pass.print', compact('gatePassData'))->render();

        return $output;
    }

    /**
     * Checks if ref_number and supplier combination already exists.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function printInvoice($id, $duplicate = '')
    {

        try {
            if (!auth()->user()->can('gate_pass.view')) {
                abort(403, 'Unauthorized action.');
            }

            $gatePassData = GatePass::where('gate_pass.id', $id)
                ->with(['values'])->first();
            $gatePassData->duplicate = $duplicate ? "" : 'Duplicate';

            $output = ['success' => 1, 'receipt' => []];
            $output['receipt']['html_content'] = view('gate_pass.print', compact('gatePassData'))->render();
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            $output = [
                'success' => 0,
                'msg' => __('messages.something_went_wrong')
            ];
        }

        return $output;
    }

    public function checkOutIndex()
    {
        if (!auth()->user()->can('gate_pass.verify')) {
            abort(403, 'Unauthorized action.');
        }

        return view('gate_pass.gate_pass_check_out');
    }

    public function getCheckOutDetail(Request $request)
    {
        if (!auth()->user()->can('gate_pass.verify')) {
            abort(403, 'Unauthorized action.');
        }
        try {
            $input = $request->only(['serial_no', 'prefix']);
            $data = $input['prefix'] . $input['serial_no'];

            $gatePassData = GatePass::where('serial_no', $data)
                ->with(['values'])->first();

            if (!empty($gatePassData)) {
                if ($gatePassData['check_out'] != "0000-00-00 00:00:00") {
                    $output = [
                        'success' => 1,
                        'msg' => __('gate_pass.gate_pass_already_checkout')
                    ];
                    return $output;
                }
            } else {
                $output = [
                    'success' => 0,
                    'msg' => __('gate_pass.data_not_found')
                ];
                return $output;
            }
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            $output = [
                'success' => 0,
                'msg' => $e->getMessage()
            ];
            return $output;
        }
        return view('gate_pass.ajax_checkout', compact('gatePassData'));
    }

    public function checkOut(Request $request)
    {
        if (!auth()->user()->can('gate_pass.verify')) {
            abort(403, 'Unauthorized action.');
        }
        try {
            $input = $request->only(['serial_no']);

            $gatePassData = GatePass::where('serial_no', $input['serial_no'])->firstOrFail();

            DB::beginTransaction();

            $input['check_out'] = \Carbon::now()->toDateTimeString();
            $input['gate_pass_approval_id'] = request()->session()->get('user.id');
            $input['status'] = 1;

            $gatePassData->update($input);
            DB::commit();

            $output = [
                'success' => 1,
                'msg' => __('gate_pass.gate_pass_checkout_success')
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            $output = [
                'success' => 0,
                'msg' => $e->getMessage()
            ];
            return back()->with('status', $output);
        }
        return redirect('gate-pass/check-out')->with('status', $output);
    }
}
