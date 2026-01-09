<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use App\Models\HsdMaterial;
use App\Models\HsdUpah;
use App\Models\AhspHeader;
use App\Models\AhspDetail;
use App\Models\ExternalDbConfig;

class DataSyncController extends Controller
{
    /**
     * Setup koneksi database eksternal secara dinamis
     */
    private function setupExternalConnection()
    {
        $config = ExternalDbConfig::getActive();
        
        if (!$config) {
            throw new \Exception('Konfigurasi database eksternal belum diatur. Silakan atur terlebih dahulu.');
        }

        Config::set('database.connections.external', [
            'driver' => 'mysql',
            'host' => $config->host,
            'port' => $config->port,
            'database' => $config->database,
            'username' => $config->username,
            'password' => $config->password,
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
            'engine' => null,
        ]);

        // Purge existing connection to force reconnect
        DB::purge('external');
    }
    /**
     * Get current external DB config
     */
    public function getConfig()
    {
        $config = ExternalDbConfig::getActive();
        
        if (!$config) {
            return response()->json([
                'success' => false,
                'message' => 'Belum ada konfigurasi'
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $config->id,
                'name' => $config->name,
                'host' => $config->host,
                'port' => $config->port,
                'database' => $config->database,
                'username' => $config->username,
                'notes' => $config->notes,
            ]
        ]);
    }

    /**
     * Save external DB config
     */
    public function saveConfig(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'host' => 'required|string|max:255',
            'port' => 'required|string|max:10',
            'database' => 'required|string|max:255',
            'username' => 'required|string|max:255',
            'password' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        try {
            // Deactivate all existing configs
            ExternalDbConfig::query()->update(['is_active' => false]);

            // Create new config
            $config = ExternalDbConfig::create(array_merge($validated, ['is_active' => true]));

            return response()->json([
                'success' => true,
                'message' => 'Konfigurasi database eksternal berhasil disimpan',
                'data' => $config
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test koneksi ke database eksternal
     */
    public function testConnection()
    {
        try {
            $this->setupExternalConnection();
            DB::connection('external')->getPdo();
            
            $config = ExternalDbConfig::getActive();
            return response()->json([
                'success' => true,
                'message' => 'Koneksi berhasil ke database: ' . $config->database . ' @ ' . $config->host
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Koneksi gagal: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Tampilkan halaman utama sync
     */
    public function index()
    {
        return view('datasync.index');
    }

    /**
     * Bandingkan HSD Material
     */
    public function compareHsdMaterial(Request $request)
    {
        try {
            $this->setupExternalConnection();
            
            // Data lokal
            $local = HsdMaterial::select('id', 'kode', 'nama', 'satuan', 'harga_satuan', 'keterangan', 'updated_at')
                ->orderBy('kode')
                ->get();

            // Data eksternal
            $external = DB::connection('external')
                ->table('hsd_material')
                ->select('id', 'kode', 'nama', 'satuan', 'harga_satuan', 'keterangan', 'updated_at')
                ->orderBy('kode')
                ->get();

            // Bandingkan
            $comparison = $this->buildComparison($local, $external, 'kode');

            return response()->json([
                'success' => true,
                'data' => $comparison
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bandingkan HSD Upah
     */
    public function compareHsdUpah(Request $request)
    {
        try {
            $this->setupExternalConnection();
            
            $local = HsdUpah::select('id', 'kode', 'jenis_pekerja', 'satuan', 'harga_satuan', 'keterangan', 'updated_at')
                ->orderBy('kode')
                ->get();

            $external = DB::connection('external')
                ->table('hsd_upah')
                ->select('id', 'kode', 'jenis_pekerja', 'satuan', 'harga_satuan', 'keterangan', 'updated_at')
                ->orderBy('kode')
                ->get();

            $comparison = $this->buildComparison($local, $external, 'kode');

            return response()->json([
                'success' => true,
                'data' => $comparison
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bandingkan AHSP
     */
    public function compareAhsp(Request $request)
    {
        try {
            $this->setupExternalConnection();
            
            $local = AhspHeader::with('details')
                ->select('id', 'kode_pekerjaan', 'nama_pekerjaan', 'satuan', 'total_harga', 'updated_at')
                ->orderBy('kode_pekerjaan')
                ->get();

            $external = DB::connection('external')
                ->table('ahsp_header')
                ->select('id', 'kode_pekerjaan', 'nama_pekerjaan', 'satuan', 'total_harga', 'updated_at')
                ->orderBy('kode_pekerjaan')
                ->get();

            $comparison = $this->buildComparison($local, $external, 'kode_pekerjaan');

            return response()->json([
                'success' => true,
                'data' => $comparison
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Copy HSD Material dari eksternal
     */
    public function copyHsdMaterial(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'required|integer'
        ]);

        try {
            $this->setupExternalConnection();
            DB::beginTransaction();

            $copied = 0;
            foreach ($validated['ids'] as $externalId) {
                $externalData = DB::connection('external')
                    ->table('hsd_material')
                    ->where('id', $externalId)
                    ->first();

                if (!$externalData) continue;

                // Cek apakah kode sudah ada
                $existing = HsdMaterial::where('kode', $externalData->kode)->first();

                if ($existing) {
                    // Update
                    $existing->update([
                        'nama' => $externalData->nama,
                        'satuan' => $externalData->satuan,
                        'harga_satuan' => $externalData->harga_satuan,
                        'keterangan' => $externalData->keterangan,
                    ]);
                } else {
                    // Insert baru
                    HsdMaterial::create([
                        'kode' => $externalData->kode,
                        'nama' => $externalData->nama,
                        'satuan' => $externalData->satuan,
                        'harga_satuan' => $externalData->harga_satuan,
                        'keterangan' => $externalData->keterangan,
                    ]);
                }
                $copied++;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Berhasil menyalin {$copied} item HSD Material"
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Copy HSD Material error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Copy HSD Upah dari eksternal
     */
    public function copyHsdUpah(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'required|integer'
        ]);

        try {
            $this->setupExternalConnection();
            DB::beginTransaction();

            $copied = 0;
            foreach ($validated['ids'] as $externalId) {
                $externalData = DB::connection('external')
                    ->table('hsd_upah')
                    ->where('id', $externalId)
                    ->first();

                if (!$externalData) continue;

                $existing = HsdUpah::where('kode', $externalData->kode)->first();

                if ($existing) {
                    $existing->update([
                        'jenis_pekerja' => $externalData->jenis_pekerja,
                        'satuan' => $externalData->satuan,
                        'harga_satuan' => $externalData->harga_satuan,
                        'keterangan' => $externalData->keterangan,
                    ]);
                } else {
                    HsdUpah::create([
                        'kode' => $externalData->kode,
                        'jenis_pekerja' => $externalData->jenis_pekerja,
                        'satuan' => $externalData->satuan,
                        'harga_satuan' => $externalData->harga_satuan,
                        'keterangan' => $externalData->keterangan,
                    ]);
                }
                $copied++;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Berhasil menyalin {$copied} item HSD Upah"
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Copy HSD Upah error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Copy AHSP dari eksternal (termasuk detail)
     */
    public function copyAhsp(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'required|integer'
        ]);

        try {
            $this->setupExternalConnection();
            DB::beginTransaction();

            $copied = 0;
            foreach ($validated['ids'] as $externalId) {
                $externalHeader = DB::connection('external')
                    ->table('ahsp_header')
                    ->where('id', $externalId)
                    ->first();

                if (!$externalHeader) continue;

                // Cek existing
                $existing = AhspHeader::where('kode_pekerjaan', $externalHeader->kode_pekerjaan)->first();

                if ($existing) {
                    $existing->update([
                        'nama_pekerjaan' => $externalHeader->nama_pekerjaan,
                        'satuan' => $externalHeader->satuan,
                        'kategori_id' => $externalHeader->kategori_id,
                        'total_harga' => $externalHeader->total_harga,
                        'total_harga_pembulatan' => $externalHeader->total_harga_pembulatan ?? null,
                        'is_locked' => $externalHeader->is_locked ?? 0,
                    ]);
                    $headerId = $existing->id;

                    // Hapus detail lama
                    AhspDetail::where('ahsp_id', $headerId)->delete();
                } else {
                    $newHeader = AhspHeader::create([
                        'kode_pekerjaan' => $externalHeader->kode_pekerjaan,
                        'nama_pekerjaan' => $externalHeader->nama_pekerjaan,
                        'satuan' => $externalHeader->satuan,
                        'kategori_id' => $externalHeader->kategori_id,
                        'total_harga' => $externalHeader->total_harga,
                        'total_harga_pembulatan' => $externalHeader->total_harga_pembulatan ?? null,
                        'is_locked' => $externalHeader->is_locked ?? 0,
                    ]);
                    $headerId = $newHeader->id;
                }

                // Copy details
                $externalDetails = DB::connection('external')
                    ->table('ahsp_detail')
                    ->where('ahsp_id', $externalId)
                    ->get();

                foreach ($externalDetails as $detail) {
                    AhspDetail::create([
                        'ahsp_id' => $headerId,
                        'tipe' => $detail->tipe,
                        'referensi_id' => $detail->referensi_id,
                        'koefisien' => $detail->koefisien,
                        'harga_satuan' => $detail->harga_satuan,
                        'subtotal' => $detail->subtotal,
                    ]);
                }

                $copied++;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Berhasil menyalin {$copied} item AHSP beserta detailnya"
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Copy AHSP error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Helper untuk membandingkan data
     */
    private function buildComparison($local, $external, $keyField)
    {
        $result = [
            'only_local' => [],      // Ada di lokal, tidak di eksternal
            'only_external' => [],   // Ada di eksternal, tidak di lokal
            'different' => [],       // Ada di kedua, tapi berbeda
            'same' => [],           // Sama persis
        ];

        $localKeys = $local->pluck($keyField)->toArray();
        $externalKeys = $external->pluck($keyField)->toArray();

        // Only in local
        foreach ($local as $item) {
            if (!in_array($item->{$keyField}, $externalKeys)) {
                $result['only_local'][] = $item;
            }
        }

        // Only in external & Different
        foreach ($external as $extItem) {
            $localItem = $local->firstWhere($keyField, $extItem->{$keyField});

            if (!$localItem) {
                $result['only_external'][] = $extItem;
            } else {
                // Compare semua field (kecuali id dan timestamps)
                $isDifferent = false;
                foreach ((array)$extItem as $key => $value) {
                    if (in_array($key, ['id', 'created_at', 'updated_at'])) continue;

                    if ($localItem->{$key} != $value) {
                        $isDifferent = true;
                        break;
                    }
                }

                if ($isDifferent) {
                    $result['different'][] = [
                        'local' => $localItem,
                        'external' => $extItem
                    ];
                } else {
                    $result['same'][] = $localItem;
                }
            }
        }

        return $result;
    }
}
