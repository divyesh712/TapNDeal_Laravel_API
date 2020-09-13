<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User;   
use Illuminate\Support\Facades\Session;
use App\com_info;
use App\Order;
use App\emp_sel_rel;
use App\Product;

class manufactureController extends Controller
{
    public function generateRefCode($name)
    {
        $i=0;
        $ref = strtoupper(substr($name, 0, 2)).date('dmy').strtoupper(substr($name, -1, 1));
        $u=User::where('ref_code',$ref)->count();
        while($u>0){
            $name=$name."".$i++;
            $ref = strtoupper(substr($name, 0, 2)).date('dmy').strtoupper(substr($name, -1, 1));
            $u=User::where('ref_code',$ref)->count();
        }
        return $ref;
    }
    public function login(Request $req)
    {
        $user=User::where('mobile',$req->mob)->select('users.*','citys.*','states.*','users.id as uid','company_info.*')
        ->join('citys','citys.id','users.city_id')
        ->join('company_info','sid','users.id')
        ->join('states','states.id','users.state_id')
        ->first();
        session()->put('manufacture',$user['name']);
        session()->put('name',$user['name']);
        session()->put('email',$user['email']);
        session()->put('mobile',$user['mobile']);
        session()->put('city',$user['city_name']);
        session()->put('uid',$user['uid']);
        session()->put('gst',$user['gst']);
        session()->put('address',$user['address']);
        session()->put('ta',$user['acc_allow']);
        session()->put('state',$user['state_name']);
        return redirect('/manufacture/index');
    }
    public function register(Request $req)
    {
        $validatedData = $req->validate([
            'name' => 'required',
            'address'=>'required',
            'email'=>'required|email|unique:users,email',
            'mobile'=>'required|digits:10|unique:users,mobile',
            'city'=>'required',
            'pincode'=>'required',
            'gst_no'=>'required',
            
        ]);   
        
        $state=\DB::table('citys')->where('id',$req->city)->first();
        
        session()->put('name',$req->name);
        session()->put('email',$req->email);
        session()->put('mobile',$req->mobile);
        session()->put('city',$req->city);
        session()->put('pincode',$req->pincode);
        session()->put('gst',$req->gst_no);
        session()->put('address',$req->address);
        session()->put('state',$state->state_id);
        
        return redirect('confirmMob');
        
    }
    public function dashboard()
    {
        $ref=$this->generateRefCode(session()->get('name'));
        $usr=new User;
        $usr->name=session()->get('name');
        $usr->email=session()->get('email');
        $usr->mobile=session()->get('mobile');
        $usr->city_id=session()->get('city');
        
        $usr->state_id=session()->get('state');
        $usr->type_id="1";
        $usr->isVerified="1";
        $usr->ref_code=$ref;
        $usr->acc_allow="4";
        if($usr->save())
        {
            $c=new com_info;
            $c->sid=$usr->id;
            $c->gst=session()->get('gst');
            $c->address=session()->get('address');
            $c->pincode=session()->get('pincode');
            $c->save();
            $user=User::where('users.id',$usr->id)->select('users.*','citys.*','states.*','users.id as uid','company_info.*')
            ->join('citys','citys.id','users.city_id')
            ->join('company_info','sid','users.id')
            ->join('states','states.id','users.state_id')
            ->first();
            session()->put('manufacture',$user['name']);
            session()->put('name',$user['name']);
            session()->put('email',$user['email']);
            session()->put('mobile',$user['mobile']);
            session()->put('city',$user['city_name']);
            session()->put('uid',$user['uid']);
            session()->put('gst',$user['gst']);
            session()->put('address',$user['address']);
            session()->put('ta',$user['acc_allow']);
            session()->put('state',$user['state_name']);
            return redirect('/manufacture/index');
        }
        
    }
    public function index()
    {
        $used_acc=emp_sel_rel::where('seller_id',session()->get('uid'))->count();
        $orders=Order::where('seller_id',session()->get('uid'))->count();
        $ern=Order::where([['isDelivered',1],['seller_id',session()->get('uid')]])->sum('total_price');
        $op=Order::where([['isApproved',0],['seller_id',session()->get('uid')]])->count();
        $od=Order::where([['isDelivered',1],['seller_id',session()->get('uid')]])->count();
        $odi=Order::where([['status_id',1],['seller_id',session()->get('uid')]])->count();
        return view('manufacture.index',['used'=>$used_acc+1,'torder'=>$orders,'ern'=>$ern,'op'=>$op,'od'=>$od,'odi'=>$odi]);
    }
    public function logout()
    {
        Session::flush();
        return redirect('/login');
    }
    public function mobCheck(Request $req)
    {
        $u=User::where('mobile',$req->mo)->join('user_type','type_id','user_type.id')->where('user_type.user_type','seller')->count();
        return response()->json(['co'=>$u]);
    }
    public function orders()
    {
        $orders=Order::where('seller_id',session()->get('uid'))
        ->join('users','users.id','cust_id')
        ->join('order_status','order_status.id','orders.status_id')
        ->select('orders.*','order_status.*','orders.id as oid','users.*')->get();
        $status=\DB::table('order_status')->get();
        return view('manufacture.orders',["list"=>$orders,'status'=>$status]);
    }
    public function fullorder($oid)
    {
        $orders=Order::where('orders.id',$oid)
        ->join('users','users.id','cust_id')
        ->select('orders.products','orders.agent_reference','orders.total_price','users.*')->first();
        return view('manufacture.FullOrder',['ord'=>$orders]);
    }
    public function Products()
    {
        $product=Product::where('seller_id',session()->get('uid'))
        ->join('users','products.agents_id','users.id')
        ->select('products.*','products.id as pid','users.name as aname')
        ->get();
        return view('manufacture.products',['products'=>$product]);
    }
}