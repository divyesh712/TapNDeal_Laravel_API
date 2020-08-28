<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\User;
use App\CustomerKnock;
use App\CustomerCategoryRelationship;
use Validator;

class customerKnockController extends Controller
{
        public function create(Request $req,$id)
        {
            $validator = Validator::make($req->all(), [
                'cust_id' => 'required',
            ]);
            if ($validator->fails()) {
                return response()->json(['error' => true ,'message'=>$validator->errors()], 401);
            }

            $knock_data=new CustomerKnock;
            $knock_seller=User::find($id);

            if(!empty($knock_seller))
            {
                $record=CustomerKnock::where('cust_id',$req->cust_id)->where('seller_id',$knock_seller->id)->first();
                $relrecord=CustomerCategoryRelationship::where('cust_id',$req->cust_id)->where('seller_id',$knock_seller->id)->first();
                if($relrecord != null && $relrecord->isBlocked == 1)
                {
                    return response()->json(['error' => true ,'message'=>'User Blocked By Seller']);
                }
                if($record == null)
                {
                    $knock_data->cust_id=$req->cust_id;
                    $knock_data->seller_id=$knock_seller->id;
                }
                else if($record->isActive == 1 && $record->isApproved == 0)
                {
                    return response()->json(['error' => true ,'knock' => true ,'message'=>'Knock Already Exist']);
                }
                else if($record->isActive == 0 && $record->isApproved == 1)
                {
                    $update_data=[
                        'cust_id'=>$req->cust_id,
                        'seller_id'=>$knock_seller->id,
                        'isApproved'=>0,
                        'isActive'=>1
                    ];
                    $status=CustomerKnock::where('id',$record->id)->update($update_data);
                    if($status ==1)
                    {
                    return response()->json(['error' => false ,'message'=>'Knock Successfull'],200);
                    }
                }
                else if($record->isActive == 0 && $record->isApproved == 0)
                {
                    $update_data=[
                        'cust_id'=>$req->cust_id,
                        'seller_id'=>$knock_seller->id,
                        'isApproved'=>0,
                        'isActive'=>1
                    ];
                    $status=CustomerKnock::where('id',$record->id)->update($update_data);
                    if($status ==1)
                    {
                    return response()->json(['error' => false ,'message'=>'Knock Successfull'],200);
                    }
                }
            }
            else{
                return response()->json(['error' => true ,'message'=>'Seller not found']);
            }
            if($knock_data->save())
            {
                return response()->json(['error' => false ,'message'=>'insert Successfully'],200);
            }
            else
            {
                return response()->json(['error' => true ,'message'=>'something went wrong'],500);
            }
        }

        public function approve(Request $req,$id)
        {
            $validator = Validator::make($req->all(), [
                'seller_id' => 'required',
                'category' => 'required'
            ]);
            if ($validator->fails()) {
                return response()->json(['error' => true ,'message'=>$validator->errors()], 401);
            }
            $knockrecord=CustomerKnock::where('cust_id',$id)->where('seller_id',$req->seller_id)->first();
            $relrecord=CustomerCategoryRelationship::where('cust_id',$id)->where('seller_id',$req->seller_id)->first();
            $knock_data=[
                'cust_id'=>$id,
                'seller_id'=>$req->seller_id,
                'isApproved'=>1,
                'isActive'=>0
            ];
            if($knockrecord != null)
            {
                    if($relrecord == null)
                    {
                        $relation_data = new CustomerCategoryRelationship;
                        $relation_data->cust_id = $id;
                        $relation_data->seller_id=$req->seller_id;
                        $relation_data->category = $req->category;

                        $knock_update=CustomerKnock::where('id',$knockrecord->id)->update($knock_data);
                        if($knock_update==1 && $relation_data->save())
                        {
                        return response()->json(['error' => false ,'message'=>' Customer Approved Successfully'],200);
                        }
                        return response()->json(['error' => true ,'message'=>'Record not found'],500);
                    }
                    if($relrecord->isBlocked == 1 )
                    {
                        return response()->json(['error' => true ,'message'=>'Remove User from Blocked']);
                    }
                    if($relrecord != null && $relrecord->isBlocked == 0)
                    {
                        $rel_data=[
                            'cust_id'=>$id,
                            'seller_id'=>$req->seller_id,
                            'category'=>$req->category,
                            'isBlocked'=> 0
                        ];
                        $knockstatus=CustomerKnock::where('id',$knockrecord->id)->update($knock_data);
                        $relstatus=CustomerCategoryRelationship::where('id',$relrecord->id)->update($rel_data);
                        if($relstatus == 1 && $knockstatus == 1 )
                        {
                        return response()->json(['error' => false ,'message'=>'Approved with new category'],200);
                        }
                        else {
                            return response()->json(['error' => true ,'message'=>'Something went wrong'],500);
                        }
                    }
            }
            else
            {
                return response()->json(['error' => true ,'message'=>'Record not found']);
            }
        }

        public function reject(Request $req,$id)
        {
            $validator = Validator::make($req->all(), [
                'seller_id' => 'required',
            ]);
            if ($validator->fails()) {
                return response()->json(['error' => true ,'message'=>$validator->errors()], 401);
            }
            $knockrecord=CustomerKnock::where('cust_id',$id)->where('seller_id',$req->seller_id)->first();
            if($knockrecord!=null)
            {
                $knock_data=[
                    'cust_id'=>$id,
                    'seller_id'=>$req->seller_id,
                    'isApproved'=>0,
                    'isActive'=>0
                ];

                $knock_update=CustomerKnock::where('id',$record->id)->update($knock_data);
                if($knock_update==1)
                {
                    return response()->json(['error' => false ,'message'=>' Customer Rejected Successfully'],200);
                }
                    return response()->json(['error' => true ,'message'=>'Record not found or Already Updated '],500);
            }
            else
            {
                return response()->json(['error' => true ,'message'=>'Record not found']);
            }
        }

        public function show($id)
        {
            $knocks=CustomerKnock::where('seller_id',$id)->where('isActive',1)->where('isApproved',0)->get()->toarray();
            $knockreturn = DB::table('cust_sel_knock_rel')
                            ->join('users','users.id','cust_sel_knock_rel.cust_id')
                            ->select('users.name','cust_sel_knock_rel.*')
                            ->where('cust_sel_knock_rel.seller_id',$id)
                            ->where('cust_sel_knock_rel.isActive',1)->where('cust_sel_knock_rel.isApproved',0)
                            ->get()->toarray();
            if(!empty($knockreturn))
            {
                return response()->json(['error' => false ,'data'=>$knockreturn],200);
            }
            else{
                return response()->json(['error' => false ,'data'=>null]);
            }
        }

}