<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\{Stock, Customer, User, Product, Transaction};
use Illuminate\Support\Facades\Auth;
use \PDF;
use DB;
use Arr;


class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
        // $this->user_address = (Auth::check()) ? User::select('address')->where('name',Auth::user())->get() : "";
    }

    public function validate_cname(Request $request){
        $cname = $request->cname;
        $c_mobile = $request->c_mobile;
        $cust_id = $request->cust_id;
        $data = Customer::select('id','name','mobile')->where('name',$cname)->where('mobile',$c_mobile)->get();
        
        if($data->count() == 0){
            echo "Success";
        }
        else{
            if($data[0]->id == $cust_id){
                echo "Success";
            }
            else{
                echo "Failed";
                error_log("FAIL");
            }
        }
    }

    public function validate_pname(Request $request){
        $pname = $request->pname;
        $prod_id = $request->prod_id;
        $data = Product::select('id','name')->where('name',$pname)->get();
        
        if($data->count() == 0){
            echo "Success";
        }
        else{
            if($data[0]->id == $prod_id){
                echo "Success";
            }
            else{
                echo "Failed";
                error_log("FAIL");
            }
        }
    }

    public function report_data($request){        
        // error_log("FD: ".$request->ft_date);
        // error_log("TD: ".$request->tt_date);
        // error_log("C: ".$request->cust_id);
        // error_log("P: ".$request->p_name);
        $cust_id = $request->cust_id;
        $pcode = $request->p_name;
        $from = $request->ft_date;
        $to = $request->tt_date;
        
        $cust_data = Customer::select('customers.id as id','customers.name as name')->where('active','1');
        $cust_data = $cust_data->join('transactions','customers.id','=','transactions.cid');
        $cust_data = $cust_data->whereBetween('transactions.t_date',[$from, $to]);
        if($cust_id != "all"){
            $cust_data = $cust_data->where('customers.id',$cust_id);
        }
        if($pcode != "all"){
            $cust_data = $cust_data->where('transactions.pid',$pcode);
        }
        $cust_data = $cust_data->distinct();
        $cust_data = $cust_data->get();
        
        $prod_data = Product::select('customers.id as cid','products.id as pid','products.name as pname','products.quantity as pq')->where('products.active','1');
        $prod_data = $prod_data->join('transactions','products.id','=','transactions.pid');
        $prod_data = $prod_data->join('customers','customers.id','=','transactions.cid');
        if($pcode !== "all"){
            $prod_data = $prod_data->where('products.id',$pcode);
        }
        if($cust_id !== "all"){
            $prod_data = $prod_data->where('transactions.cid',$cust_id);
        }
        else{
        }
        $prod_data = $prod_data->whereBetween('transactions.t_date',[$from, $to]);
        // $prod_data = $prod_data->distinct();
        $prod_data = $prod_data->distinct()->get();
        
        // dd($cust_data);
        
        /*
        if($cust_id === "all"){
            if($pcode === "all"){
                $tquery = 'SELECT 
                    cid, pid, issue, receive, t_date, vehicle_number 
                FROM transactions 
                WHERE t_date BETWEEN ? AND ? 
                ORDER BY t_date, pid';
                $tparam = [$from, $to];
                
                $oquery = 'SELECT 
                    customers.id as cid, 
                    products.id as pid, 
                    ( 
                        stocks.quantity 
                        + opening_stock.open_issue 
                        - opening_stock.open_receive 
                    ) AS opening 
                FROM customers, products, stocks, 
                ( 
                    SELECT 
                        cid, pid, 
                        SUM(issue) as open_issue, 
                        SUM(receive) as open_receive 
                    FROM transactions 
                    WHERE t_date < ? 
                    GROUP BY cid, pid 
                ) AS opening_stock 
                WHERE products.id = opening_stock.pid 
                AND customers.id = opening_stock.cid 
                AND stocks.cid = customers.id 
                AND stocks.pid = products.id';
                $oparam = [$from];
            }
            else{
                $tquery = 'SELECT 
                    cid, pid, issue, receive, t_date, vehicle_number 
                FROM transactions 
                WHERE t_date >= ? 
                AND t_date <= ? 
                AND pid = ? 
                ORDER BY t_date, pid';
                $tparam = [$from, $to, $pcode];
                
                $oquery = 'SELECT 
                    customers.id as cid, 
                    products.id as pid, 
                    ( 
                        stocks.quantity 
                        + opening_stock.open_issue 
                        - opening_stock.open_receive 
                    ) AS opening 
                FROM customers, products, stocks, 
                ( 
                    SELECT 
                        cid, pid, 
                        SUM(issue) as open_issue, 
                        SUM(receive) as open_receive 
                    FROM transactions 
                    WHERE t_date < ? 
                    GROUP BY cid, pid 
                ) AS opening_stock 
                WHERE products.id = opening_stock.pid 
                AND customers.id = opening_stock.cid 
                AND stocks.cid = customers.id 
                AND stocks.pid = products.id 
                AND opening_stock.pid = ?';
                $oparam = [$from, $pcode];
            }
        }
        else{
            if($pcode === "all"){
                $tquery = 'SELECT 
                    cid, pid, issue, receive, t_date, vehicle_number 
                FROM transactions 
                WHERE t_date >= ? 
                AND t_date <= ? 
                AND cid = ? 
                ORDER BY t_date, pid';
                $tparam = [$from, $to, $cust_id];
                
                $oquery = 'SELECT 
                    customers.id as cid, 
                    products.id as pid, 
                    ( 
                        stocks.quantity 
                        + opening_stock.open_issue 
                        - opening_stock.open_receive 
                    ) AS opening 
                FROM customers, products, stocks, 
                ( 
                    SELECT 
                        cid, pid, 
                        SUM(issue) as open_issue, 
                        SUM(receive) as open_receive 
                    FROM transactions 
                    WHERE t_date < ? 
                    GROUP BY cid, pid 
                ) AS opening_stock 
                WHERE products.id = opening_stock.pid 
                AND customers.id = opening_stock.cid 
                AND stocks.cid = customers.id 
                AND stocks.pid = products.id 
                AND opening_stock.cid = ?';
                $oparam = [$from, $cust_id];
            }
            else{   
                $tquery = 'SELECT 
                    cid, pid, issue, receive, t_date, vehicle_number 
                FROM transactions 
                WHERE t_date >= ? 
                AND t_date <= ? 
                AND pid = ? 
                AND cid = ? 
                ORDER BY t_date, pid';
                $tparam = [$from, $to, $pcode, $cust_id];
                
                $oquery = 'SELECT 
                    customers.id as cid, 
                    products.id as pid, 
                    ( 
                        stocks.quantity 
                        + opening_stock.open_issue 
                        - opening_stock.open_receive 
                    ) AS opening 
                FROM customers, products, stocks, 
                ( 
                    SELECT 
                        cid, pid, 
                        SUM(issue) as open_issue, 
                        SUM(receive) as open_receive 
                    FROM transactions 
                    WHERE t_date < ? 
                    GROUP BY cid, pid 
                ) AS opening_stock 
                WHERE products.id = opening_stock.pid 
                AND customers.id = opening_stock.cid 
                AND stocks.cid = customers.id 
                AND stocks.pid = products.id 
                AND opening_stock.cid = ? 
                AND opening_stock.pid = ?';
                $oparam = [$from, $cust_id, $pcode];
            }
        }
        */
        
        $tparam = [$from, $to];
        $tquery = 'SELECT 
            cid, pid, issue, receive, t_date, vehicle_number 
        FROM transactions 
        WHERE t_date BETWEEN ? AND ?'; 
        if($cust_id != "all"){
            $tquery = $tquery.'AND cid = ?';
            array_push($tparam, $cust_id);
        }
        if($pcode != "all"){
            $tquery = $tquery.'AND pid = ?';
            array_push($tparam, $pcode);
        }
        $tquery = $tquery.' ORDER BY t_date, pid';

        $oparam = [$from,$from];
        $oquery = "SELECT 
            c.id AS cid, p.id AS pid, 
            IF(? <= MIN(t_date), 
                (
                    SELECT                
                    quantity 
                    FROM stocks
                    WHERE cid = c.id AND pid = p.id
                ),
                (
                    SELECT
                        s.quantity + IFNULL(SUM(t.issue),0) - IFNULL(SUM(t.receive),0) AS opening 
                    FROM stocks s, transactions t  
                    WHERE c.id = t.cid AND p.id = t.pid 
                    AND s.cid = c.id AND s.pid = p.id
                    AND t.t_date < ?
                    GROUP BY s.quantity
                )
            ) AS opening
        FROM transactions t, customers c, products p
        WHERE c.id = t.cid AND p.id = t.pid";
        if($cust_id != "all"){
            $oquery = $oquery."AND c.id = ?";
            array_push($oparam, $cust_id);
        }
        if($pcode != "all"){
            $oquery = $oquery."AND p.id = ?";
            array_push($oparam, $pcode);
        }
        $oquery = $oquery." GROUP BY c.id, p.id";
        // dd($oquery);
        $transaction_data = DB::select($tquery, $tparam);
        $opening_data = DB::select($oquery, $oparam);
        
        return ['cust_id' => $cust_id, 'pcode' => $pcode, 'from_date' => $from, 'to_date' => $to, 'cust_data' => $cust_data, 'prod_data' => $prod_data, 'transaction_data' => $transaction_data, 'opening_data' => $opening_data];
    }
    
    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function report(Request $request)
    {
        $request->cust_id = ($request->cust_id == "") ? "all" : $request->cust_id;
        $request->p_name = ($request->p_name == "") ? "all" : $request->p_name;
        $request->ft_date = ($request->ft_date == "") ? date("Y-m-d") : $request->ft_date;
        $request->tt_date = ($request->tt_date == "") ? date("Y-m-d") : $request->tt_date;
        
        $cust_id = $request->cust_id;
        $pcode = $request->p_name;
        $from = $request->ft_date;
        $to = $request->tt_date;

        error_log('cust_id - '.$request->cust_id);
        error_log('p_name - '.$request->p_name);
        error_log('ft_date - '.$request->ft_date);
        error_log('tt_date - '.$request->tt_date);
        
        $cust_data = Customer::select('id','mobile','name')->where('active','1')->get();
        
        if($request->cust_id == "all"){
            $prod_data = DB::select('SELECT
            DISTINCT transactions.cid, products.id, products.name
            FROM transactions, products
            WHERE transactions.cid = 
            (
                SELECT 
                    customers.id 
                FROM customers 
                LIMIT 1
            )
            AND transactions.pid = products.id');
        }
        else{
            $prod_data = DB::select('SELECT
            DISTINCT transactions.cid, products.id, products.name
            FROM transactions, products
            WHERE transactions.cid = ?
            AND transactions.pid = products.id', [$cust_id]);
        }
        $data = $this->report_data($request);
        $data["form_cust_data"] = $cust_data;
        $data["form_prod_data"] = $prod_data;
        error_log(json_encode($data));
        return view('report', $data);
    }
    
    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function export_pdf(Request $request)
    {
        // error_log('cust_id - '.$request->cust_id);
        // error_log('p_name - '.$request->p_name);
        // error_log('ft_date - '.$request->ft_date);
        // error_log('tt_date - '.$request->tt_date);
        
        $data = $this->report_data($request);
        // dd($data);
        
        $pdf = PDF::loadView('reports._report_view', $data);
        $dom_pdf = $pdf->getDomPDF();
        $canvas = $dom_pdf ->get_canvas();
        $canvas->page_text(500,810, "Page {PAGE_NUM} of {PAGE_COUNT}", null, 10, array(0, 0, 0));
        return $pdf->download('customer_report.pdf');
    }
    
    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function transaction_report(Request $request)
    {
        $cust_id = $request->cust_id;
        $pcode = $request->pid;
        $from = $request->from_date;
        $to = $request->to_date;

        $opening_data = DB::select("SELECT 
            products.id AS pid, 
            (products.quantity +  transaction_stock.s_stock) AS opening_stock
        FROM products,
        (
            SELECT
                pid, 
                SUM(receive - issue) AS s_stock
            FROM transactions
            WHERE t_date >= ?
            AND t_date < ?
            GROUP BY pid
        ) AS transaction_stock
        WHERE products.id = transaction_stock.pid",[$from, $to]);
        
        // return $request;
        // $opening_data = DB::select("SELECT products.id AS pid, (products.quantity + transaction_stock.s_stock) AS opening_stock FROM products, ( SELECT pid, SUM(receive - issue) AS s_stock FROM transactions WHERE t_date >= '2020-07-10' AND t_date < '2020-07-23' GROUP BY pid ) AS transaction_stock WHERE products.id = transaction_stock.pid");
        
        if($pcode == "all"){
            $prod_data = Product::select('products.id','products.name')
            ->distinct()
            ->join('transactions','products.id','=','transactions.pid')
            ->whereBetween('transactions.t_date',[$from,$to])
            ->get();
        }
        else{
            $prod_data = Product::select('products.id','products.name')
            ->distinct()
            ->join('transactions','products.id','=','transactions.pid')
            ->whereBetween('transactions.t_date',[$from,$to])
            ->where('products.id',$pcode)
            ->get();
        }
        
        $transaction_data = Transaction::select('transactions.t_date','products.name as pname','customers.name as cname','transactions.pid','transactions.issue','transactions.receive','transactions.vehicle_number');
        $transaction_data = $transaction_data->join('customers','customers.id','=','transactions.cid');
        $transaction_data = $transaction_data->join('products','products.id','=','transactions.pid');
        $transaction_data = $transaction_data->whereBetween('transactions.t_date',[$from,$to]);
        if($pcode != "all"){
            $transaction_data = $transaction_data->where('transactions.pid',$pcode);
        }
        $transaction_data = $transaction_data->orderBy('transactions.t_date');
        $transaction_data = $transaction_data->orderBy('customers.name','desc');
        $transaction_data = $transaction_data->orderBy('products.name');
        $transaction_data = $transaction_data->get();
        
        // dd($transaction_data, $prod_data, $opening_data);
        
        $data = ["transaction_data" => $transaction_data, "prod_data" => $prod_data, "opening_data" => $opening_data];
        $pdf = PDF::loadView('reports._report_transaction_view', $data);
        $dom_pdf = $pdf->getDomPDF();
        $canvas = $dom_pdf ->get_canvas();
        $canvas->page_text(500,810, "Page {PAGE_NUM} of {PAGE_COUNT}", null, 10, array(0, 0, 0));
        return $pdf->download('transaction_report.pdf');        
    }
    
    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function get_prod($id)
    {
        // error_log($id);        
        if($id !== "all"){
            $prod_data = Transaction::distinct()->select('customers.id','transactions.pid as id','products.name')
            ->join('products','products.id','=','transactions.pid')
            ->join('customers','customers.id','=','transactions.cid')
            ->where('products.active','1')
            ->where('customers.id',$id)
            ->get();
        }
        else{
            $prod_data = Transaction::distinct()->select('transactions.pid as id','products.name')
            ->join('products','products.id','=','transactions.pid')
            ->where('products.active','1')
            ->get();
        }
        // error_log($prod_data);
        return json_encode($prod_data);
    }
    
    public function transaction_dashboard(){
        /*
        $transaction_data = DB::select('SELECT 
            products.name, 
            ( 
                (
                    products.quantity 
                    + transaction.open_receive
                )
                - transaction.open_issue 
            ) AS opening_stock, 
            transaction.s_issue, 
            transaction.s_receive
            -- ( 
            --     (
            --         (
            --             products.quantity 
            --             + transaction.open_receive) 
            --         - transaction.open_issue
            --     ) 
            --     + transaction.s_receive 
            --     - transaction.s_issue 
            -- ) AS closing_stock
        FROM products, 
        ( 
            SELECT
                s_open.pid, 
                s_open.open_issue, 
                s_open.open_receive, 
                s_close.s_issue, 
                s_close.s_receive 
            FROM 
            ( 
                SELECT 
                pid AS pid, 
                SUM(issue) AS open_issue, 
                SUM(receive) AS open_receive 
                FROM transactions 
                WHERE t_date <= DATE_FORMAT(NOW(), "%Y-%m-%d") 
                GROUP BY pid 
            ) AS s_open 
            LEFT JOIN 
            ( 
                SELECT
                pid AS pidd, 
                SUM(issue) AS s_issue, 
                SUM(receive) AS s_receive 
                FROM transactions 
                WHERE t_date = DATE_FORMAT(NOW(), "%Y-%m-%d") 
                GROUP BY pid ) AS s_close 
            ON s_open.pid = s_close.pidd 
        ) AS transaction 
        WHERE products.id = transaction.pid
        AND products.active = 1');
        */

        $transaction_data = DB::select('SELECT
            p.name,
            (p.quantity + (
                SELECT 
                    IFNULL(SUM(receive) - SUM(issue),0)
                FROM transactions
                WHERE t_date < DATE_FORMAT(NOW(), "%Y-%m-%d")
                AND pid = p.id
            )) AS opening_stock,
            IFNULL(t.issue,0) AS s_issue,
            IFNULL(t.receive,0) AS s_receive,
            (p.quantity+ (
                SELECT 
                    IFNULL(SUM(receive) - SUM(issue),0)
                FROM transactions
                WHERE t_date <= DATE_FORMAT(NOW(), "%Y-%m-%d")
                AND pid = p.id
            )) AS closing_stock
        FROM products p LEFT JOIN 
        (
            SELECT 
                pid, IFNULL(SUM(issue),0) as issue, IFNULL(SUM(receive),0) as receive
            FROM transactions
            WHERE t_date = DATE_FORMAT(NOW(), "%Y-%m-%d")
            GROUP BY pid
        ) t
        ON p.id = t.pid
        WHERE p.active = 1');

        return $transaction_data;
    }       
    
    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        $transaction_data = $this->transaction_dashboard();
        return view('home', ['transaction_data' => $transaction_data]);
    }
    
    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function dashboard_summary()
    {
        $data = ["dashboard_data" => $this->transaction_dashboard()];
        // dd($data);
        
        $pdf = PDF::loadView('reports._report_dashboard', $data);
        $dom_pdf = $pdf->getDomPDF();
        $canvas = $dom_pdf ->get_canvas();
        $canvas->page_text(500,810, "Page {PAGE_NUM} of {PAGE_COUNT}", null, 10, array(0, 0, 0));
        return $pdf->download('dashboard_summary.pdf');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function fetch_id()
    {
        $cust_data = Customer::count();
        $prod_data = Product::count();

        return json_encode(array('cust_id' => $cust_data+1, 'prod_id' => $prod_data+1));
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function fetch_stock_products($id)
    {
        // error_log("/stock-prod - ".$id);
        $data = DB::select('SELECT 
            DISTINCT products.name, products.id
        FROM products
        WHERE products.id NOT IN (
            SELECT 
                DISTINCT products.id
            FROM stocks, products
            WHERE stocks.pid = products.id
            AND stocks.cid = ?
        )
        AND products.active = 1',[$id]);
        return json_encode($data);
    }
}
