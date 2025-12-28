<?php

namespace App\Http\Controllers;

use App\DataTables\CustomerDataTable;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class CustomerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if(Auth::user()->isAbleTo('customer manage'))
        {
            $customers = Customer::select('customers.*', 'users.name as user_name', 'users.email as user_email',)->join('users', 'users.id', 'customers.user_id')->where('customers.created_by',creatorId())->where('customers.business_id',getActiveBusiness());
            if ($request->name) {
                $customers->where('users.name', 'like', '%' . $request->name . '%');
            }
            if($request->email)
            {
                $customers->where('users.email', 'like', '%' . $request->email . '%');
            }
            $customers = $customers->paginate(11);
            return view('customer.index',compact('customers'));
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        if(Auth::user()->isAbleTo('customer create'))
        {
            return view('customer.create');
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

        if(Auth::user()->isAbleTo('customer create'))
        {
            $validator = \Validator::make(
                $request->all(), [
                    'name' => 'required',
                    'email' => 'required',
                    'gender' => 'required',
                    'dob' => 'required',
                    'mobile_no' => 'required',
                    ]
                );

                if($validator->fails())
                {
                    $messages = $validator->getMessageBag();

                    return redirect()->back()->with('error', $messages->first());
                }
                $roles = Role::where('name','customer')->where('created_by',creatorId())->first();

                if($roles)
                {
                    $url = 'uploads/users-avatar/avatar.png';
                    if ($request->hasFile('image'))
                    {
                        $filenameWithExt = $request->file('image')->getClientOriginalName();
                        $filename        = pathinfo($filenameWithExt, PATHINFO_FILENAME);
                        $extension       = $request->file('image')->getClientOriginalExtension();
                        $fileNameToStore = $filename . '_' . time() . '.' . $extension;

                        $uplaod = upload_file($request,'image',$fileNameToStore,'Customer');
                        if($uplaod['flag'] == 1)
                        {
                            $url = $uplaod['url'];
                        }
                        else
                        {
                            return redirect()->back()->with('error',$uplaod['msg']);
                        }
                    }

                    $user = User::create(
                    [
                        'name' => !empty($request->name) ? $request->name : null,
                        'email' => !empty($request->email) ? $request->email : null,
                        'mobile_no' => !empty($request->mobile_no) ? $request->mobile_no : null,
                        'email_verified_at' => date('Y-m-d h:i:s'),
                        'password' => !empty($request->password) ? Hash::make($request->password) : null,
                        'avatar' => !empty($request->image) ? $url : 'uploads/users-avatar/avatar.png',
                        'type' => $roles->name,
                        'lang' => 'en',
                        'business_id' => getActiveBusiness(),
                        'created_by' => creatorId(),
                    ]);
                    $user->save();
                    $user->addRole($roles);

                    $customer                           = new Customer();
                    $customer->name                     = $request->name;
                    $customer->user_id                  = $user->id;
                    $customer->gender                 = $request->gender;
                    $customer->dob                 = $request->dob;
                    $customer->description              = !empty($request->description) ? $request->description : '';
                    $customer->business_id              = $user->business_id;
                    $customer->created_by              = creatorId();
                    $customer->save();

                    return redirect()->back()->with('success', __('Customer successfully created.'));
                }
                else
                {
                    return redirect()->back()->with('error', __('Please create customer role.'));
                }

        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function customerList(CustomerDataTable $dataTable)
    {
        if(Auth::user()->isAbleTo('customer manage'))
        {
            return $dataTable->render('customer.list');
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Customer $customer)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Customer $customer)
    {
        if(Auth::user()->isAbleTo('customer edit'))
        {
            return view('customer.edit',compact('customer'));
        }
        else
        {
           return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Customer $customer)
    {
        if(Auth::user()->isAbleTo('customer edit'))
        {

            $validator = \Validator::make(
                $request->all(), [
                    'name' => 'required',
                    'email' => 'required',
                    'mobile_no' => 'required',
                ]
            );

            if($validator->fails())
            {
                $messages = $validator->getMessageBag();

                return redirect()->back()->with('error', $messages->first());
            }

            $roles = Role::where('name','customer')->where('created_by',creatorId())->first();
            if($roles)
            {
                $customer->name         = $request->name;
                $customer->gender  = $request->gender;
                $customer->dob   = $request->dob;
                $customer->description  = !empty($request->description) ? $request->description : '';
                $customer->save();

                $user = User::where('id',$customer->user_id)->first();
                if($user)
                {
                    if ($request->hasFile('image')) {

                        $filenameWithExt = $request->file('image')->getClientOriginalName();
                        $filename        = pathinfo($filenameWithExt, PATHINFO_FILENAME);
                        $extension       = $request->file('image')->getClientOriginalExtension();
                        $fileNameToStore = $filename . '_' . time() . '.' . $extension;

                        $path = upload_file($request, 'image', $fileNameToStore, 'customer');

                        if($path['flag'] == 1){
                            // old img delete
                            if (!empty($user->avatar) && strpos($user->avatar, 'avatar.png') == false && check_file($user->avatar)) {
                                delete_file($user->avatar);
                            }
                            if (!empty($request->image) && isset($path['url'])) {
                                $user->avatar =  $path['url'];
                            }
                        }else{
                            return redirect()->back()->with('error',$path['msg']);
                        }
                    }

                    $user->name                     = $request->name;
                    $user->email                     = $request->email;
                    $user->mobile_no                     = $request->mobile_no;
                    $user->password                     = !empty($request->password) ? Hash::make($request->password) : null;
                    $user->type = $roles->name;
                    $user->save();
                }

                return redirect()->back()->with('success', __('Customer updated successfully!'));
            }
            else
            {
                return redirect()->back()->with('error', __('Please create customer role.'));
            }

        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Customer $customer)
    {
        if(Auth::user()->isAbleTo('customer delete'))
        {
            $user = User::find($customer->user_id);
            if($user)
            {
                if (!empty($user->avatar)) {
                    delete_file($user->avatar);
                }

                $user->delete();
                $customer->delete();
            }
            return redirect()->back()->with('error', __('Customer successfully delete.'));
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }
}
