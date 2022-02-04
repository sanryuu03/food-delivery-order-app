<?php

namespace App\Http\Controllers\API;

use Exception;
use Midtrans\Snap;
use Midtrans\Config;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class TransactionController extends Controller
{
    public function all(Request $request)
    {
        $id = $request->input('id');
        $limit = $request->input('limit', 6);
        $food_id = $request->input('food_id');
        $status = $request->input('status');


        if($id)
        {
            $transaction = Transaction::with(['user', 'food'])->find($id);
            if($transaction)
            {
                return ResponseFormatter::success(
                    $transaction, 'Data transaction berhasil diambil'
                );
            }
            else
            {
                return ResponseFormatter::error(
                    null, 'Data transaction tidak ada', 404
                );
            }
        }

        $transaction = Transaction::with(['user','food'])->where('userID', Auth::user()->id);
        if($food_id)
        {
            $transaction->where('food_id', $food_id);
        }

        if($status)
        {
            $transaction->where('status', $status);
        }


        return ResponseFormatter::success(
            $transaction->paginate($limit),
            'Data list transaction berhasil diambil'
        );
    }

    public function update(Request $request, $id)
    {
        $transaction = Transaction::findOrFail($id);
        $transaction->update($request->all());

        return ResponseFormatter::success($transaction, 'Transaction berhasil diperbarui');
    }

    public function checkout(Request $request)
    {
        $request->validate([
            'foodID' => 'required|exist:food,id',
            'userID' => 'required|exist:user,id',
            'quantity' => 'required',
            'total' => 'required',
            'status' => 'required'
        ]);

        $transaction =Transaction::create([
            'foodID' => $request->foodID,
            'userID' => $request->userID,
            'quantity' => $request->quantity,
            'total' => $request->total,
            'status' => $request->status,
            'paymentUrl' => ''
        ]);

        // konfigurasi Midtrans
        Config::$serverkey = config('services.midtrans.serverKey');
        Config::$isProduction = config('services.midtrans.isProduction');
        Config::$isSanitized = config('services.midtrans.isSanitized');
        Config::$is3ds = config('services.midtrans.is3ds');

        // panggil transaksi yang tadi dibuat
        $transaction = Transaction::with(['user', 'food'])->find($transaction->id);

        // membuat transaksi midtrans
        $midtrans = [
            'transaction_details' => [
                'order_id' => $transaction->id,
                'gross_amount' => (int) $transaction->total,
            ],
            'customer_details' => [
                'first_name' => $transaction->user->name,
                'email' => $transaction->user->email,
            ],
            'enabled_payments' => [
                'gopay', 'bank_transfer'
            ],
            'vtweb' => []
        ];

        // memanggil midtrans
        try {
            //Ambil Halaman payment midtrans
            $paymentUrl = Snap::createTransaction($midtrans)->redirect_url;
            $transaction->payment_url = $paymentUrl;
            $transaction->save();
            // mengembalikan data ke API
            return ResponseFormatter::success($transaction, 'Transaksi berhasil');

        } catch (Exception $error) {
            return ResponseFormatter::error($error->getMessage(), 'Transaksi gagal');
        }
    }
}
