<?php

namespace App\Http\Controllers\Admin;

use App\Models\Order;
use App\Models\User;
use App\Models\Product;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class TransactionController extends Controller
{
    public function index()
    {
        $allOrders = Order::where('is_checkout', 1)->get();

        foreach ($allOrders as $order) {
            // get user info
            $user = User::where('id', $order->id_user)->first();
            if ($user) {
                $order->userName = $user->name;
            }

            // get product info
            $product = Product::where('id', $order->id_product)->first();
            if ($product) {
                $order->productName = $product->name;

                $order->totalAmount = $product->price * $order->quantity;
            }
        }

        return view('admin.transactions.transaction', [
            'title' => 'Transaction',
            'allTransaction' => $allOrders
        ]);
    }

    public function delete(Order $transaction)
    {
        $transaction->delete();

        return redirect('admin/transaction');
    }

    public function search(Request $request)
    {
        $searchQuery = $request->query('search'); // Ambil parameter dari URL

        $allOrders = Order::where('is_checkout', 1)->get();

        foreach ($allOrders as $order) {
            // get user info
            $user = User::where('id', $order->id_user)->first();
            if ($user) {
                $order->userName = $user->name;
            }

            // get product info
            $product = Product::where('id', $order->id_product)->first();
            if ($product) {
                $order->productName = $product->name;

                $order->totalAmount = $product->price * $order->quantity;
            }
        }

        // Filter hasil berdasarkan pencarian di userName dan productName
        $allOrders = $allOrders->filter(function ($order) use ($searchQuery) {
            return strpos(strtolower($order->userName ?? ''), $searchQuery) !== false ||
                strpos(strtolower($order->productName ?? ''), $searchQuery) !== false ||
                strpos(strtolower('TRANSACTION' . $order->id ?? ''), $searchQuery) !== false;
        });

        // Reset index array agar pagination (jika ada) tidak bermasalah
        $allTransaction = $allOrders->values();

        return view('admin.transactions.transaction', compact('allTransaction'));
    }
}
