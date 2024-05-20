<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Faq;
use App\Models\Rbc;
use App\Models\Bank;
use App\Models\User;
use App\Models\About;
use App\Models\Admin;
use App\Models\Blood;
use App\Models\Drive;
use App\Models\Group;
use App\Models\Staff;
use App\Models\Plasma;
use App\Models\Donation;
use App\Models\Hospital;
use App\Models\Platelet;
use Barryvdh\DomPDF\PDF;
use App\Models\IssuedRbc;
use App\Models\IssuedBlood;
use App\Models\DiscardedRbc;
use App\Models\IssuedPlasma;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Models\DiscardedBlood;
use App\Models\IssuedPlatelet;
use App\Models\DiscardedPlasma;
use App\Models\HospitalRequest;
use App\Models\DiscardedPlatelet;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use App\Notifications\DonorNewDriveNotification;
use LaravelDaily\LaravelCharts\Classes\LaravelChart;

class AdminController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth:admin');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        $donors = User::all()->count();
        $platelets = Platelet::whereNull('issued_at')->whereNull('discarded_at')->count();
        $plasma = Plasma::whereNull('issued_at')->whereNull('discarded_at')->count();
        $rbc = Rbc::whereNull('issued_at')->whereNull('discarded_at')->count();
        return view('admin.admin', compact('donors', 'platelets', 'plasma', 'rbc'));
    }

    /******************** ADMIN BANK - MANAGEMENT *****************************/
    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function addBank()
    {
        return view('admin.add_bank');
    }

    public function storeBank(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:banks'],
            'phone' => ['required'],
            'county' => ['required', 'string', 'max:255'],
        ]);
        $data = new Bank();
        $data->admin_id = Auth::user()->id;
        $data['name'] = $request->name;
        $data['email'] = $request->email;
        $data['phone'] = $request->phone;
        $data['county'] = $request->county;
        // dd($data);
        $data->save();
        return redirect('admin/all-banks/')->with('success', 'Bank Created Successfully!');
    }

    public function allBanks()
    {
        $banks = Bank::all();
        return view('admin.banks.index', compact('banks'));
    }

    public function edit_bank($id)
    {
        $bank = Bank::findOrFail($id);
        return view('admin.banks.edit', compact('bank'));
    }

    public function update_bank(Request $request, $id)
    {
        $bank = Bank::findOrFail($id);
        $constraints = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'phone' => ['required'],
            'county' => ['required', 'string', 'max:255'],
        ];
        $input = $request->only(['name', 'email', 'phone', 'county']);
        
        // Validate the request data
        $validator = Validator::make($input, $constraints);
    
        // Check if validation fails
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }
    
        // Validation passed, update the bank
        $bank->update($input);
    
        return redirect('admin/all-banks')->with('success', 'Bank updated successfully!');
    }

    public function delete_bank($id)
    {
        $bank = Bank::findOrFail($id);
        $bank->delete();
        return redirect('admin/all-banks/')->with('success', 'Bank deleted Successfully!');
    }

    /******************** ADMIN BlOOD-GROUP - MANAGEMENT *****************************/
    public function add_blood_group()
    {
        return view('admin.add_blood_group');
    }

    public function store_blood_group(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);
        $data = new Group();
        $data->admin_id = Auth::user()->id;
        $data['name'] = $request->name;
        // dd($data);
        $data->save();
        return redirect('admin/all-blood-groups/')->with('success', 'Blood Group Created Successfully!');
    }

    public function all_blood_groups()
    {
        $blood_groups = Group::all();
        return view('admin.blood_groups.index', compact('blood_groups'));
    }

    public function edit_blood_group($id)
    {
        $blood_group = Group::findOrFail($id);
        return view('admin.blood_groups.edit', compact('blood_group'));
    }

    public function update_blood_group(Request $request, $id)
    {
        $blood_group = Group::findOrFail($id);
        $constraints = [
            'name' => 'required',
        ];
        $input = [
            'name' => $request['name'],
        ];
        $validator = Validator::make($input, $constraints);
    
        // Check if validation fails
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }
        Group::where('id', $id)
            ->update($input);
        return redirect('admin/all-blood-groups/')
            ->with('success', 'Blood Group updated successfully');
    }

    public function delete_blood_group($id)
    {
        $blood_group = Group::findOrFail($id);
        $blood_group->delete();
        return redirect('admin/all-blood-groups/')->with('success', 'Blood Group deleted Successfully!');
    }

    /******************** ADMIN STAFF - MANAGEMENT *****************************/
    public function all_staff()
    {
        $staffs = Staff::whereNotNull('bank_id')->get();
        return view('admin.staff.index', compact('staffs'));
    }

    public function create_staff()
    {
        $banks = Bank::all();
        return view('admin.create_staff', compact('banks')); // Assuming you have a separate view for creating staff
    }
    

    public function store_staff(Request $request)
    {
        $request->validate([
            'bank_id' => ['required'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:staff'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        Staff::create([
            'bank_id' => $request['bank_id'],
            'name' => $request['name'],
            'email' => $request['email'],
            'password' => bcrypt($request['password']),
        ]);
        // dd($request);
        return redirect()->intended('/admin/all-staff')->with('success', 'Staff Created Successfully!');
    }

    public function all_unassigned_staff()
    {
        $staffs = Staff::whereNull('bank_id')->get();
        return view('admin.all_unassigned_staff', compact('staffs'));
    }
    public function assign_bank($id)
    {
        $banks = Bank::all();
        $staff = Staff::findOrFail($id);
        return view('admin.assign_bank', compact('banks', 'staff'));
    }

    public function save_assigned_bank(Request $request, $id)
    {
        $staff = Staff::findOrFail($id);
        $constraints = [
            'bank_id' => 'required|max:255',
        ];
        $input = [
            'bank_id' => $request['bank_id'],
        ];
        $request->validate($constraints);
        Staff::where('id', $id)
            ->update($input);
        return redirect()->route('admin.staff.index')->withMessage('Staff Assigned Bank successfully!');
    }

    public function edit_staff($id)
{
    $banks = Bank::all();
    $staff = Staff::findOrFail($id);
    return view('admin.staff.edit', compact('banks', 'staff'));
}


    public function update_staff(Request $request, $id)
    {
        $staff = Staff::findOrFail($id);
        $constraints = [
            'bank_id' => 'required|max:255',
            'name' => 'required|max:255',
            'email' => 'required|max:255',
        ];
        $input = [
            'bank_id' => $request['bank_id'],
            'name' => $request['name'],
            'email' => $request['email'],
        ];
        $request->validate($constraints);
        Staff::where('id', $id)
            ->update($input);
        return redirect('/admin/all-staff')->with('success', 'Staff Updated Successfully!');
    }

    public function delete_staff($id)
    {
        $staff = Staff::findOrFail($id);
        $staff->delete();
        return redirect('/admin/all-staff')->with('success', 'Staff Deleted Successfully!');
    }

    /******************** ADMIN DONOR - MANAGEMENT *****************************/
    public function all_donors(Request $request)
    {
        $users = User::all();
        return view('admin.donors.index', compact('users'));
    }

    public function search_donor(Request $request)
    {
        $fromDate = $request->input('fromDate');
        $toDate   = $request->input('toDate');

        $users = DB::table('users')->select()
            ->where('created_at', '>=', $fromDate)
            ->where('created_at', '<=', $toDate)
            ->get();
        return view('admin.donors.index', compact('users'));
    }

    public function edit_donor($id)
    {
        $user = User::findOrFail($id);
        $blood_groups = Group::all();
        return view('admin.donors.edit', compact('user', 'blood_groups'));
    }

    public function update_donor(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $constraints = [
            'name' => 'required|max:255',
            'email' => 'required|max:255',
            'gender' => 'required|max:255',
            'unique_no' => 'required|max:255',
            'birth_date' => 'required|max:255',
            'address' => 'max:255',
            'phone' => 'required|max:255',
            'blood_group' => 'required|max:255',
            'county' => 'required|max:255',
        ];
        $input = [
            'name' => $request['name'],
            'email' => $request['email'],
            'gender' => $request['gender'],
            'unique_no' => $request['unique_no'],
            'birth_date' => $request['birth_date'],
            'address' => $request['address'],
            'phone' => $request['phone'],
            'blood_group' => $request['blood_group'],
            'county' => $request['county'],
        ];
        $request->validate($constraints);
        User::where('id', $id)
            ->update($input);

        return redirect('/admin/all-donors')->with('success', 'Donor Updated Successfully!');
    }

    public function delete_donor($id)
    {
        $user = User::findOrFail($id);
        $user->delete();
        return redirect('/admin/all-donors')->with('success', 'Donor Deleted Successfully!');
    }

    /******************** ADMIN DONATION - MANAGEMENT *****************************/
    public function all_donations()
    {
        $donations = Donation::all();
        return view('admin.donations.index', compact('donations'));
    }

    /******************** ADMIN STOCK- MANAGEMENT *****************************/
    public function banks_stock()
    {
        $banks = Bank::all();
        return view('admin.stock.index',compact('banks'));
    }

    public function bank_stock($id)
    {
        $blood_groups = Group::all();
        $blood = Blood::get()->where('bank_id',$id)->whereNull('issued_at')->whereNull('discarded_at');
        $platelets = Platelet::get()->where('bank_id',$id)->whereNull('issued_at')->whereNull('discarded_at');
        $plasma = Plasma::get()->where('bank_id',$id)->whereNull('issued_at')->whereNull('discarded_at');
        $rbcs = Rbc::get()->where('bank_id',$id)->whereNull('issued_at')->whereNull('discarded_at');
        return view('admin.stock.show',compact('blood_groups','blood','platelets','plasma','rbcs'));
    }

    public function blood()
    {
        $bloods = Blood::whereNull('issued_at')->whereNull('discarded_at')->get();
        return view('admin.stock.blood',compact('bloods'));
    }

    public function plasma()
    {
        $plasma = Plasma::whereNull('issued_at')->whereNull('discarded_at')->get();
        return view('admin.stock.plasma',compact('plasma'));
    }

    public function platelets()
    {
        $platelets = Platelet::whereNull('issued_at')->whereNull('discarded_at')->get();
        return view('admin.stock.platelets',compact('platelets'));
    }

    public function rbc()
    {
        $rbc = Rbc::whereNull('issued_at')->whereNull('discarded_at')->get();
        return view('admin.stock.rbc',compact('rbc'));
    }

    public function issued_blood()
    {
        $blood = IssuedBlood::all();
        return view('admin.stock.issued_blood',compact('blood'));
    }

    public function issued_plasma()
    {
        $plasma = IssuedPlasma::all();
        return view('admin.stock.issued_plasma',compact('plasma'));
    }

    public function issued_platelets()
    {
        $platelets = IssuedPlatelet::all();
        return view('admin.stock.issued_platelets',compact('platelets'));
    }

    public function issued_rbc()
    {
        $rbc = IssuedRbc::all();
        return view('admin.stock.issued_rbc',compact('rbc'));
    }

    public function discarded_blood()
    {
        $blood  = DiscardedBlood::all();
        return view('admin.stock.discarded_blood',compact('blood'));
    }

    public function discarded_plasma()
    {
        $plasma = DiscardedPlasma::all();
        return view('admin.stock.discarded_plasma',compact('plasma'));
    }

    public function discarded_platelets()
    {
        $platelets = DiscardedPlatelet::all();
        return view('admin.stock.discarded_platelets',compact('platelets'));
    }

    public function discarded_rbc()
    {
        $rbc = DiscardedRbc::all();
        return view('admin.stock.discarded_rbc',compact('rbc'));
    }


    /******************** ADMIN DRIVES- MANAGEMENT *****************************/
    public function unapproved_drives()
    {
        $unapproved_drives = Drive::whereNull('approved_at')->get();
        $approved_drives = Drive::whereNotNull('approved_at')->get();
        return view('admin.drives.unapproved', compact('unapproved_drives','approved_drives'));
    }
    public function approve_drive($id)
    {
        $unapproved_drive = Drive::findOrFail($id);

        $admin_id=Auth::user()->id;
        $approved_at = Carbon::now();

        $input = [
            'admin_id' => $admin_id,
            'approved_at' => $approved_at,
        ];

        // dd($input);
        Drive::where('id', $id)
            ->update($input);

        $donors = User::all();
        foreach ($donors as $donor) {
            $donor->notify(new DonorNewDriveNotification($unapproved_drive));
        }
        return redirect('admin/unapproved-drives')->withMessage('Drive Approved successfully!');
    }

   

    //     /********************ADMIN BBMS-SITE - MANAGEMENT *****************************/
    public function faqs()
    {
        $faqs = Faq::all();
        return view('admin.site.faq.index', compact('faqs'));
    }

    public function store_faq(Request $request)
    {
        $request->validate([
            'question' => ['required', 'string', 'max:255'],
            'answer' => ['required', 'string', 'max:255'],
        ]);

        Faq::create([
            'question' => $request['question'],
            'answer' => $request['answer'],
        ]);
        return redirect('admin/faqs/')->with('success', 'Faq Created Successfully!');
    }

    public function update_faq_status(Request $request)
    {
        $faq = Faq::find($request->faq_id);
        $faq->status = $request->status;
        $faq->save();

        return response()->json(['success' => 'Status changed successfully!']);
    }

    public function edit_about()
    {
        $about = About::find(1);
        return view('admin.site.about.index', compact('about'));
    }

    public function update_about(Request $request)
    {
        $constraints = [
            'history' => 'required',
        ];
        $input = [
            'history' => $request['history'],
            'vision' => $request['vision'],
            'mission' => $request['mission'],
            'values' => $request['values'],
            'objectives' => $request['objectives'],
        ];
        $request->validate($constraints);
        About::find(1)
            ->update($input);
        return redirect('admin/about/')->with('success', 'About updated successfully!');
    }
}
