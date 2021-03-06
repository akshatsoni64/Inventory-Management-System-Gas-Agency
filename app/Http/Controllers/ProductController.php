<?php

namespace App\Http\Controllers;

use App\{Stock, Customer, User, Product, Transaction};
use Illuminate\Http\Request;
use DB;

class ProductController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {        
        $data = Product::select('id','name as Product Name', 'quantity as Opening Stock')
        ->where('active','1')
        ->orderBy('id','desc')
        ->get();
        return view('product', ["data" => $data]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    // public function create()
    // {
    //     //
    // }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        unset($request['_token']);
        // ($request->active == "on") ? ($request['active']='1') : ($request['active']='0'); // Set active status by translating checbox value
        $res = Product::create($request->all());
        // error_log($res);

        return \Redirect::route('product.index');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function show(Product $product)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        // error_log('Edit: '.$id);
        $data = Product::select('id','name as Product Name', 'quantity as Opening Stock')
        ->where('active','1')
        ->orderBy('id','desc')
        ->get();
        $formdata = Product::find($id);
        return view('product', ["id" => $id, "data" => $data, "formdata" => $formdata]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        unset($request['_token']);
        unset($request['_method']);
        // ($request->active == "on") ? ($request['active']='1') : ($request['active']='0'); // Set active status by translating checbox value
        if($request->active == "on"){
            $count = DB::select('SELECT COUNT(*) AS count FROM transactions WHERE pid = ?',[$id]);
            if($count[0]->count > 0){
                echo "Failed";
                $request['active']='1';
            }
            else{
                $res = Product::where('id',$id)->update(['active' => '0']); //Update the active status
                $res = Product::where('id',$id)->update(['end_date' => \DB::raw('now()')]); //Update the end_date
                $request['active']='0';
            }
        }
        else{
            $request['active']='1';
        }
        $res = Product::find($id)->update($request->all());

        return \Redirect::route('product.index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $count = DB::select('SELECT COUNT(*) AS count FROM transactions WHERE pid = ?',[$id]);
        error_log($count[0]->count);
        if($count[0]->count > 0){
            return "Failed";
        }
        else{
            $res = Product::where('id',$id)->update(['active' => '0']); //Update the active status
            $res = Product::where('id',$id)->update(['end_date' => \DB::raw('now()')]); //Update the end_date
        }
        // error_log(response()->json($res));

    }
}
