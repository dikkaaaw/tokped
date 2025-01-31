<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Product;

class PageController extends Controller
{
    public function page($page)
    {
        return view('public.' . $page);
    }

    public function index()
    {
        // Ambil semua produk
        $products = Product::all();

        // Panggil showCart untuk mendapatkan data keranjang
        $cartData = $this->showCart();

        // Pastikan dataOrder adalah koleksi dan filter dilakukan dengan benar
        $dataOrder = collect($cartData['dataOrder'])  // Gunakan collect() untuk mengubah array menjadi Collection
            ->filter(function ($order) {
                return !$order->is_checkout;  // Hanya pilih order yang is_checkout = 0
            })
            ->map(function ($order) {
                // Mengambil produk terkait berdasarkan id_product pada order
                $product = Product::find($order->id_product);  // Menggunakan -> untuk mengakses properti objek

                // Jika produk ditemukan, tambahkan nama produk pada order
                if ($product) {
                    $order->product_name = $product->name;  // Menambahkan field 'product_name' pada order
                } else {
                    $order->product_name = 'Product not found'; // Jika produk tidak ditemukan
                }

                // Kembalikan order yang sudah ditambahkan field 'product_name'
                return $order;
            });

        // Kirim data produk dan data keranjang ke view
        return view('public.homepage', [
            'products' => $products,
            'dataOrder' => $dataOrder,
            'totalPrice' => $cartData['totalPrice'],
            'totalQuantity' => $cartData['totalQuantity'],
            'message' => $cartData['message'] ?? null
        ]);
    }


    // GET: Menampilkan detail produk berdasarkan ID
    public function show($id)
    {
        $product = Product::find($id); // Mencari produk berdasarkan ID

        // Jika produk tidak ditemukan, kembalikan error
        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        return response()->json($product); // Mengembalikan detail produk dalam format JSON
    }

    // POST: Menambahkan produk baru ke keranjang
    public function storeToCart(Request $request)
    {
        // Validasi input
        $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric',
            'category' => 'required|string|max:255',
            'quantity' => 'required|integer',
        ]);

        // Buat data order baru
        $order = Order::create([
            'id_user' => 2,
            'id_product' => $request->id,
            'is_checkout' => 0,
            'quantity' => $request->quantity,
        ]);

        // Mengarahkan ke homepage setelah menambahkan ke keranjang
        return redirect()->route('homepage');
    }

    public function showCart()
    {
        // Ambil data order untuk user yang sedang login
        $userId = 2;  // Atau sesuaikan dengan ID user yang sedang login
        $dataOrder = Order::where('id_user', $userId)
            ->get();  // Mengambil koleksi (Collection)

        // Cek apakah ada data order yang ditemukan
        if ($dataOrder->isEmpty()) {
            return [
                'dataOrder' => [],
                'totalPrice' => 0,
                'totalQuantity' => 0,
                'message' => 'Your cart is empty.'
            ];
        }

        // Menghitung total harga dan jumlah item dalam keranjang
        $totalPrice = 0;
        $totalQuantity = 0;

        // Filter untuk mengambil hanya yang is_checkout = 0, lalu map untuk memproses produk
        $dataOrder = $dataOrder->filter(function ($order) {
            return !$order->is_checkout;  // Pastikan hanya yang belum dicheckout
        })->map(function ($order) use (&$totalPrice, &$totalQuantity) {
            // Mengambil produk terkait berdasarkan id_product pada order
            $product = Product::find($order->id_product);

            // Menghitung harga per produk dan total harga per order
            $pricePerProduct = $product ? (int)$product->price * (int)$order->quantity : 0;
            $totalPricePerOrder = $pricePerProduct;

            // Menambahkan harga per produk dan total harga per order
            $order->price_per_product = $pricePerProduct;
            $order->total_price = $totalPricePerOrder;

            // Menambahkan ke total harga dan total quantity
            $totalPrice += $totalPricePerOrder;
            $totalQuantity += $order->quantity;

            return $order;
        });

        // Kembalikan data yang diperlukan
        return collect([
            'dataOrder' => $dataOrder,
            'totalPrice' => $totalPrice,
            'totalQuantity' => $dataOrder->count(), // Menggunakan count() pada koleksi
            'message' => null
        ]);
    }

    // PUT/PATCH: Memperbarui data produk berdasarkan ID
    public function update(Request $request)
    {
        // Mengambil dataOrder yang dikirimkan dalam bentuk JSON
        $dataOrder = json_decode($request->input('dataOrder'), true); // Mengubah JSON menjadi array

        // Iterasi setiap order dalam dataOrder
        foreach ($dataOrder as $order) {
            // Ambil order berdasarkan id dari request
            $order = Order::where('id', $order['id'])
                ->where('is_checkout', 0)  // Pastikan hanya yang is_checkout = 0
                ->first();

            // Jika order ditemukan, ubah is_checkout menjadi 1
            if ($order) {
                $order->is_checkout = 1;
                $order->save();  // Simpan perubahan
            }
        }

        // Kembalikan respons yang sesuai
        return redirect()->route('homepage');
    }


    // DELETE: Menghapus produk berdasarkan ID
    public function destroy($id)
    {
        $product = Product::find($id); // Mencari produk berdasarkan ID

        // Jika produk tidak ditemukan, kembalikan error
        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        $product->delete(); // Menghapus produk

        return response()->json(['message' => 'Product deleted successfully']); // Menyatakan penghapusan berhasil
    }
}
