<?php

namespace App\Http\Controllers;

use App\Models\BuyProduct;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class HomeController extends Controller
{
    public function __construct()
    {
        // $this->middleware(['role:admin']);
    }

    public function dashboard()
    {
        $data = User::get();
        $product = Product::get();
        $buyproduct = BuyProduct::get();
        return view('dashboard', compact('data', 'product', 'buyproduct'));

        // if(auth()->user()->can('view_dashboard')){
        //     $data = User::get();
        //     return view('dashboard', compact('data'));
        // }
        // return abort(403);
    }

    public function user_profile()
    {
        $user = User::find(auth()->user()->id);
        return view('tables.userprofile', compact('user'));
    }

    public function user_profile_update(Request $request)
    {
        // Mendapatkan pengguna yang saat ini masuk
        $user = User::find(auth()->user()->id);

        // Validasi input
        $request->validate([
            'username' => 'nullable|unique:users',
            'email' => 'nullable|email|unique:users,email',
            'password' => 'nullable|min:6',
            'photo' => 'nullable|mimes:png,jpg,jpeg|max:2048' // Sesuaikan validasi file
        ]);

        // Update informasi pengguna
        $user->username = $request->filled('username') ? $request->input('username') : $user->username;
        $user->email = $request->filled('email') ? $request->input('email') : $user->email;
        $user->password = $request->filled('password') ? Hash::make($request->input('password')) : $user->password;

        // Cek apakah ada foto profil yang diunggah
        if ($request->hasFile('photo')) {
            // Hapus foto profil lama jika ada
            Storage::delete('photo-profile/' . $user->photo);

            // Simpan foto profil baru dengan nama yang sesuai
            $photo = $request->file('photo');
            $filename = now()->format('Y-m-d') . $photo->getClientOriginalName(); // Format nama file
            $path = 'photo-profile/' . $filename;

            Storage::disk('public')->put($path, file_get_contents($photo));

            // Update nama file di database
            $user->photo = $filename; // Ganti 'profile_photo' menjadi 'photo'
        }

        // Simpan perubahan
        $user->save();

        // Redirect atau memberikan respons sesuai kebutuhan
        return redirect()->route('admin.profile.user-profile')->with('success_edit_profile', 'Profile updated successfully.');
    }

    public function detail_user(Request $request, $id)
    {
        $user = User::find($id);
        return view('tables.userslistdetail', compact('user'));
    }

    public function user_manage()
    {
        $data = User::get();
        return view('tables.usermanage', compact('data'));
    }
    public function user_delete(Request $request, $id)
    {
        $data = User::find($id);

        if ($data) {
            $data->delete();
        }

        return redirect()->route('admin.profile.user-manage')->with('success_delete', 'Account deleted successfully.');
    }

    public function buy(Request $request, $id)
    {
        // Temukan produk berdasarkan ID
        $product = Product::findOrFail($id);

        // Mendapatkan pengguna yang saat ini masuk
        $user = Auth::user();

        // Tambahkan data ke tabel productbuys
        BuyProduct::create([
            'product_id' => $product->id,
            'user_id' => $user->id, // Menambahkan user_id ke dalam tabel productbuys
            'product_code' => $product->product_code,
            'title' => $product->title,
            'description' => $product->description,
            'price' => $product->price,
            'photo' => $product->photo,
        ]);

        // Redirect atau kembalikan respons yang sesuai
        return redirect()->route('admin.dashboard')->with('success_buy', 'Product buying successfully.');
    }
    public function detail_buy(Request $request, $id)
    {
        $buyproduct = BuyProduct::find($id);
        return view('tables.detailbuyinglist', compact('buyproduct'));
    }
    public function delete_buy(Request $request, $id)
    {
        $buyproduct = BuyProduct::find($id);

        if ($buyproduct) {
            $buyproduct->delete();
        }

        return redirect()->route('admin.dashboard')->with('success_delete', 'Product deleted successfully.');
    }

    public function show()
    {
        $product = Product::get();
        return view('products.show', compact('product'));
    }

    public function create()
    {
        return view('products.create');
    }
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_code' => 'required',
            'title' => 'required',
            'description' => 'required',
            'price' => 'required|numeric',
            'photo' => 'nullable|mimes:png,jpg,jpeg|max:2048' // Ubah required menjadi nullable
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withInput()->withErrors($validator);
        }

        $product['product_code'] = $request->product_code;
        $product['title'] = $request->title;
        $product['description'] = $request->description;
        $product['price'] = $request->price;

        if ($request->hasFile('photo')) {
            $photo = $request->file('photo');
            $filename = date('Y-m-d') . $photo->getClientOriginalName();
            $path = 'photo-product/' . $filename;

            Storage::disk('public')->put($path, file_get_contents($photo));
            $product['photo'] = $filename;
        }

        Product::create($product);
        return redirect()->route('admin.product.show')->with('success_create', 'Product created successfully.');
    }

    public function edit(Request $request, $id)
    {
        $product = Product::find($id);
        return view('products.edit', compact('product'));
    }
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'product_code' => 'nullable|string',
            'title' => 'nullable|string',
            'description' => 'nullable|string',
            'price' => 'nullable|numeric',
            'photo' => 'nullable|mimes:png,jpg,jpeg|max:2048'
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withInput()->withErrors($validator);
        }

        $product = Product::findOrFail($id);

        // Update data produk
        $product->product_code = $request->filled('product_code') ? $request->product_code : $product->product_code;
        $product->title = $request->filled('title') ? $request->title : $product->title;
        $product->description = $request->filled('description') ? $request->description : $product->description;
        $product->price = $request->filled('price') ? $request->price : $product->price;

        // Cek apakah ada gambar baru yang diunggah
        if ($request->hasFile('photo')) {
            // Hapus gambar lama jika ada
            Storage::delete('photo-product/' . $product->photo);

            // Simpan gambar baru dengan nama yang sesuai
            $photo = $request->file('photo');
            $filename = now()->format('Y-m-d') . $photo->getClientOriginalName(); // Format nama file
            $path = 'photo-product/' . $filename;

            Storage::disk('public')->put($path, file_get_contents($photo));

            // Update nama file di database
            $product->photo = $filename;
        }

        $product->save();
        return redirect()->route('admin.product.show')->with('success_update', 'Product updated successfully.');
    }


    public function delete(Request $request, $id)
    {
        $product = Product::find($id);

        if ($product) {
            $product->delete();
        }

        return redirect()->route('admin.product.show')->with('success_delete', 'Product deleted successfully.');
    }

    public function index()
    {
        $data = User::get();
        return view('index', compact('data'));
    }
}
