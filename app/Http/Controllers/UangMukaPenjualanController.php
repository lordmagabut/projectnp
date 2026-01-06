<?php

namespace App\Http\Controllers;

use App\Models\UangMukaPenjualan;
use App\Models\SalesOrder;
use App\Models\Proyek;
use Illuminate\Http\Request;

class UangMukaPenjualanController extends Controller
{
    public function index()
    {
        $query = UangMukaPenjualan::with(['salesOrder', 'proyek', 'creator']);

        // Filter by proyek if specified
        if (request('proyek_id')) {
            $query->where('proyek_id', request('proyek_id'));
        }

        // Filter by status if specified
        if (request('status')) {
            $query->where('status', request('status'));
        }

        $list = $query->latest()->paginate(20);
        $proyeks = Proyek::orderBy('nama_proyek')->get();

        return view('uang-muka-penjualan.index', compact('list', 'proyeks'));
    }

    public function create(Request $request)
    {
        // Get sales orders that don't have UM penjualan yet
        $salesOrders = SalesOrder::with('penawaran.proyek')
            ->doesntHave('uangMuka')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function($so) {
                $so->persen_dp = optional($so->penawaran)->proyek->persen_dp ?? 0;
                $so->nominal_dp = $so->total * ($so->persen_dp / 100);
                return $so;
            });

        $proyeks = Proyek::orderBy('nama_proyek')->get();
        
        // Pre-fill SO if passed via URL
        $prefillSoId = $request->query('sales_order_id');

        return view('uang-muka-penjualan.create', compact('salesOrders', 'proyeks', 'prefillSoId'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'sales_order_id' => 'required|exists:sales_orders,id|unique:uang_muka_penjualan,sales_order_id',
            'proyek_id'      => 'required|exists:proyek,id',
            'tanggal'        => 'required|date',
            'nominal'        => 'required|numeric|min:0',
            'keterangan'     => 'nullable|string',
        ]);

        $data['created_by'] = auth()->id();
        $data['status'] = 'diterima';
        $data['payment_status'] = 'belum_dibayar';
        $data['nominal_digunakan'] = 0;

        UangMukaPenjualan::create($data);

        return redirect()->route('uang-muka-penjualan.index')->with('success', 'Uang Muka Penjualan berhasil dibuat.');
    }

    public function show($id)
    {
        $um = UangMukaPenjualan::with([
            'salesOrder.penawaran', 
            'proyek.pemberiKerja', 
            'creator'
        ])->findOrFail($id);
        return view('uang-muka-penjualan.show', compact('um'));
    }

    public function edit($id)
    {
        $um = UangMukaPenjualan::findOrFail($id);
        $salesOrders = SalesOrder::with('penawaran.proyek')->orderBy('created_at', 'desc')->get();
        $proyeks = Proyek::orderBy('nama_proyek')->get();

        return view('uang-muka-penjualan.edit', compact('um', 'salesOrders', 'proyeks'));
    }

    public function update(Request $request, $id)
    {
        $um = UangMukaPenjualan::findOrFail($id);

        $data = $request->validate([
            'nomor_bukti'       => 'required|string|max:255|unique:uang_muka_penjualan,nomor_bukti,' . $id,
            'tanggal'           => 'required|date',
            'nominal'           => 'required|numeric|min:0',
            'metode_pembayaran' => 'nullable|string|max:100',
            'keterangan'        => 'nullable|string',
        ]);

        // Prevent updating nominal if already in use
        if ($um->nominal_digunakan > 0 && $data['nominal'] < $um->nominal_digunakan) {
            return back()->withErrors(['nominal' => 'Nominal tidak boleh lebih kecil dari yang sudah digunakan (' . $um->nominal_digunakan . ')']);
        }

        $um->update($data);

        return redirect()->route('uang-muka-penjualan.show', $um->id)->with('success', 'Uang Muka Penjualan berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $um = UangMukaPenjualan::findOrFail($id);

        // Prevent deletion if in use
        if ($um->nominal_digunakan > 0) {
            return back()->withErrors(['error' => 'Tidak dapat menghapus UM yang sudah digunakan.']);
        }

        $um->delete();

        return redirect()->route('uang-muka-penjualan.index')->with('success', 'Uang Muka Penjualan berhasil dihapus.');
    }

    public function pay($id)
    {
        $um = UangMukaPenjualan::with('salesOrder.penawaran.proyek', 'proyek')->findOrFail($id);

        if ($um->payment_status === 'dibayar') {
            return back()->with('error', 'Uang Muka sudah dibayar.');
        }

        return view('uang-muka-penjualan.pay', compact('um'));
    }

    public function processPay(Request $request, $id)
    {
        $um = UangMukaPenjualan::findOrFail($id);

        if ($um->payment_status === 'dibayar') {
            return back()->with('error', 'Uang Muka sudah dibayar.');
        }

        $data = $request->validate([
            'tanggal_bayar' => 'required|date',
            'metode_pembayaran' => 'required|string|max:100',
            'keterangan_bayar' => 'nullable|string',
        ]);

        $um->update([
            'payment_status' => 'dibayar',
            'tanggal_bayar' => $data['tanggal_bayar'],
            'metode_pembayaran' => $data['metode_pembayaran'],
            'keterangan' => ($um->keterangan ? $um->keterangan . "\n" : '') . ($data['keterangan_bayar'] ?? ''),
        ]);

        // TODO: Create GL Journal Entry here

        return redirect()->route('uang-muka-penjualan.show', $um->id)
            ->with('success', 'Pembayaran Uang Muka berhasil dicatat.');
    }

    public function unpay($id)
    {
        $um = UangMukaPenjualan::findOrFail($id);

        if ($um->payment_status !== 'dibayar') {
            return back()->with('error', 'Uang Muka belum dibayar.');
        }

        if ($um->nominal_digunakan > 0) {
            return back()->with('error', 'Tidak dapat membatalkan pembayaran. Uang Muka sudah digunakan.');
        }

        $um->update([
            'payment_status' => 'belum_dibayar',
            'tanggal_bayar' => null,
        ]);

        // TODO: Delete GL Journal Entry here

        return redirect()->route('uang-muka-penjualan.show', $um->id)
            ->with('success', 'Pembayaran Uang Muka berhasil dibatalkan.');
    }
}
