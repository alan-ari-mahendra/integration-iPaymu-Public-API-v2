<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class IpaymuController extends Controller
{
    public function single_product()
    {

        $product = DB::table('products')->first();

        $payload = [
            'key' => 'SANDBOXC07429A5-5AAC-473E-BE28-23DD00ECE98E',
            'action' => 'payment',
            'product' => $product->name,
            'price' => $product->price,
            'description' => $product->description,
            'quantity' => '1',
            'ureturn' => 'https://ipaymu.com/return',
            'unotify' => route('notify') ,
            // 'unotify' => 'https://ipaymu.com/return',
            'ucancel' => 'https://ipaymu.com/cancel',
            // 'notifyUrl' => 'https://ipaymu.com/notify',
            // 'cancelUrl' => 'https://ipaymu.com/cancel',
            // 'format' => 'json',
        ];

        $data = http_build_query($payload);

        $curl = curl_init();

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://sandbox.ipaymu.com/payment.htm',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => http_build_query($payload),
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/x-www-form-urlencoded',
            ),
        ));

        $response = simplexml_load_string(curl_exec($curl));

        curl_close($curl);
        echo $response;

        // dd($response);
        $transaction = new Transaction;
        $transaction->sessionID = $response->sessionID;
        $transaction->url = $response->url;
        $transaction->status = 'pending';

        $transaction->save();
        // dd($response->url);
        return redirect($response->url);
    }

    public function notify(Request $request)
    {
        $trx_id = $request->trx_id;
        $sid = $request->sid;
        $status = $request->status;

        $cek = Transaction::where('sessionID', $sid)->first();

        if ($cek) {
            if ($status == 'berhasil') {
                $cek->payed_at = now();
                $cek->status = $status;
                $cek->trx_id = $trx_id;
            } else {
                $cek->status = $status;
            }

            $cek->update();
            return response()->json(['status' => 'ok']);
        } else {
            return response()->json(['status' => 'error', 'message' => 'Transaksi tidak ditemukan.']);
        }
    }

    public function multi_product()
    {

        $payload = array(
            'key' => 'SANDBOXC07429A5-5AAC-473E-BE28-23DD00ECE98E',
            'action' => 'payment',
            'ureturn' => 'http://127.0.0.1:8000/dashboard',
            'unotify' => route('notify') ,
            // 'unotify' => 'https://ipaymu.com/return',
            'ucancel' => 'https://ipaymu.com/cancel',
            // 'format' => 'json',
        );


        
        $product = DB::table('products')->get();

        $i = 0;
        foreach ($product as $p ) {
            $payload['product['. $i .']'] = $p->name;
            $payload['price['. $i .']'] = $p->price;
            $payload['description['. $i .']'] = $p->description;
            $payload['quantity['. $i .']'] = 1;

            $i++;
        }

        $body = http_build_query($payload);

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://sandbox.ipaymu.com/payment.htm',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/x-www-form-urlencoded',
            ),
        ));

        $response = simplexml_load_string(curl_exec($curl));
        // dd($response);
        curl_close($curl);
        print_r( $response);
        // dd($response);

        // dd($response);
        $transaction = new Transaction;
        $transaction->sessionID = $response->sessionID;
        $transaction->url = $response->url;
        $transaction->status = 'pending';

        $transaction->save();
        return redirect($response->url);
    }
}
