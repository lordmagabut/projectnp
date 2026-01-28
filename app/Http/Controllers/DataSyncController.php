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

            // Build comparison dengan special handling untuk AHSP
            $comparison = [
                'only_local' => [],
                'only_external' => [],
                'different' => [],
                'same' => [],
                'suspicious' => [],  // Kode sama tapi nama berbeda - PERLU REVIEW!
            ];

            $externalKeys = $external->pluck('kode_pekerjaan')->toArray();
            $localKeys = $local->pluck('kode_pekerjaan')->toArray();

            // Only in local
            foreach ($local as $item) {
                if (!in_array($item->kode_pekerjaan, $externalKeys)) {
                    $comparison['only_local'][] = $item;
                }
            }

            // Check external & compare with local
            foreach ($external as $extItem) {
                $localItem = $local->firstWhere('kode_pekerjaan', $extItem->kode_pekerjaan);

                if (!$localItem) {
                    $comparison['only_external'][] = $extItem;
                } else {
                    // Check apakah nama pekerjaan sama
                    // Jika nama berbeda JAUH, ini suspicious - mungkin kode di-reuse untuk pekerjaan berbeda
                    $nameSimilarity = similar_text(
                        strtolower($localItem->nama_pekerjaan),
                        strtolower($extItem->nama_pekerjaan),
                        $percent
                    );

                    // Jika similaritas < 60%, masuk suspicious (PERLU REVIEW MANUAL)
                    if ($percent < 60) {
                        $comparison['suspicious'][] = [
                            'local' => $localItem,
                            'external' => $extItem,
                            'similarity' => round($percent, 2)
                        ];
                    } else {
                        // Nama cukup mirip, lanjut compare content seperti biasa
                        $isDifferent = false;

                        // Compare header fields
                        if ($localItem->nama_pekerjaan != $extItem->nama_pekerjaan ||
                            $localItem->satuan != $extItem->satuan ||
                            $localItem->total_harga != $extItem->total_harga) {
                            $isDifferent = true;
                        }

                        // Compare detail count
                        if (!$isDifferent) {
                            $localDetailCount = $localItem->details->count();
                            $externalDetailCount = DB::connection('external')
                                ->table('ahsp_detail')
                                ->where('ahsp_id', $extItem->id)
                                ->count();

                            if ($localDetailCount != $externalDetailCount) {
                                $isDifferent = true;
                            }
                        }

                        // If same count, compare detail content
                        if (!$isDifferent && $localItem->details->count() > 0) {
                            $externalDetails = DB::connection('external')
                                ->table('ahsp_detail')
                                ->where('ahsp_id', $extItem->id)
                                ->get()
                                ->keyBy(function($item) {
                                    return $item->tipe . '-' . $item->referensi_id;
                                });

                            foreach ($localItem->details as $localDetail) {
                                $key = $localDetail->tipe . '-' . $localDetail->referensi_id;
                                $extDetail = $externalDetails->get($key);

                                if (!$extDetail) {
                                    $isDifferent = true;
                                    break;
                                }

                                // Compare detail fields
                                if ($localDetail->koefisien != $extDetail->koefisien ||
                                    $localDetail->harga_satuan != $extDetail->harga_satuan ||
                                    ($localDetail->subtotal_final ?? $localDetail->subtotal) != ($extDetail->subtotal_final ?? $extDetail->subtotal)) {
                                    $isDifferent = true;
                                    break;
                                }
                            }
                        }

                        if ($isDifferent) {
                            $comparison['different'][] = [
                                'local' => $localItem,
                                'external' => $extItem
                            ];
                        } else {
                            $sameItem = (array)$extItem;
                            $sameItem['local_id'] = $localItem->id;
                            $comparison['same'][] = (object)$sameItem;
                        }
                    }
                }
            }

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

                // Copy details - dengan remapping referensi_id berdasarkan kode (bukan ID eksternal)
                $externalDetails = DB::connection('external')
                    ->table('ahsp_detail')
                    ->where('ahsp_id', $externalId)
                    ->get();

                foreach ($externalDetails as $detail) {
                    $localReferensiId = $detail->referensi_id; // default: ID eksternal

                    // Jika tipe adalah material atau upah, lakukan remapping via kode
                    if ($detail->tipe === 'material') {
                        // Ambil kode dari HSD Material eksternal berdasarkan referensi_id
                        $externalSource = DB::connection('external')
                            ->table('hsd_material')
                            ->where('id', $detail->referensi_id)
                            ->first(['kode']);

                        if ($externalSource) {
                            // Cari ID lokal yang punya kode yang sama
                            $localSource = HsdMaterial::where('kode', $externalSource->kode)->first();
                            if ($localSource) {
                                $localReferensiId = $localSource->id;
                            } else {
                                // Jika tidak ditemukan, gunakan referensi_id asli (fallback)
                                \Log::warning("HSD Material kode {$externalSource->kode} tidak ditemukan di lokal saat sync AHSP {$externalId}");
                            }
                        }
                    } elseif ($detail->tipe === 'upah') {
                        // Ambil kode dari HSD Upah eksternal berdasarkan referensi_id
                        $externalSource = DB::connection('external')
                            ->table('hsd_upah')
                            ->where('id', $detail->referensi_id)
                            ->first(['kode']);

                        if ($externalSource) {
                            // Cari ID lokal yang punya kode yang sama
                            $localSource = HsdUpah::where('kode', $externalSource->kode)->first();
                            if ($localSource) {
                                $localReferensiId = $localSource->id;
                            } else {
                                // Jika tidak ditemukan, gunakan referensi_id asli (fallback)
                                \Log::warning("HSD Upah kode {$externalSource->kode} tidak ditemukan di lokal saat sync AHSP {$externalId}");
                            }
                        }
                    }

                    $diskonPersen = (float)($detail->diskon_persen ?? 0);
                    $ppnPersen = (float)($detail->ppn_persen ?? 0);
                    
                    // Normalize percentage: if input is 0-1 range (decimal), multiply by 100
                    if ($diskonPersen > 0 && $diskonPersen < 1) {
                        $diskonPersen = $diskonPersen * 100;
                    }
                    if ($ppnPersen > 0 && $ppnPersen < 1) {
                        $ppnPersen = $ppnPersen * 100;
                    }
                    
                    AhspDetail::create([
                        'ahsp_id' => $headerId,
                        'tipe' => $detail->tipe,
                        'referensi_id' => $localReferensiId,  // ← SUDAH DI-REMAP
                        'koefisien' => $detail->koefisien,
                        'harga_satuan' => $detail->harga_satuan,
                        'subtotal' => $detail->subtotal,
                        'diskon_persen' => $diskonPersen,
                        'ppn_persen' => $ppnPersen,
                    ]);
                    // Model boot() akan auto-calculate diskon_nominal, ppn_nominal, subtotal_final
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
     * Get AHSP details untuk preview sebelum sync
     */
    public function getAhspDetails(Request $request)
    {
        $id = $request->query('id');

        if (!$id) {
            return response()->json([
                'success' => false,
                'message' => 'ID tidak ditemukan'
            ], 400);
        }

        try {
            $this->setupExternalConnection();

            // Ambil header dari eksternal
            $externalHeader = DB::connection('external')
                ->table('ahsp_header')
                ->where('id', $id)
                ->first();

            if (!$externalHeader) {
                return response()->json([
                    'success' => false,
                    'message' => 'AHSP tidak ditemukan di database eksternal'
                ], 404);
            }

            // Cek existing di lokal
            $existingHeader = AhspHeader::where('kode_pekerjaan', $externalHeader->kode_pekerjaan)->first();

            // Ambil details dari eksternal
            $externalDetails = DB::connection('external')
                ->table('ahsp_detail')
                ->where('ahsp_id', $id)
                ->get();

            // Enrich external details dengan nama dari source (material/upah)
            $enrichedExternalDetails = [];
            $externalTotalMaterial = 0;
            $externalTotalUpah = 0;

            foreach ($externalDetails as $detail) {
                $sourceData = null;
                $sourceNama = 'N/A';

                if ($detail->tipe === 'material') {
                    $sourceData = DB::connection('external')
                        ->table('hsd_material')
                        ->where('id', $detail->referensi_id)
                        ->first();
                    if ($sourceData) {
                        $sourceNama = $sourceData->nama ?? 'N/A';
                    }
                } elseif ($detail->tipe === 'upah') {
                    $sourceData = DB::connection('external')
                        ->table('hsd_upah')
                        ->where('id', $detail->referensi_id)
                        ->first();
                    if ($sourceData) {
                        $sourceNama = $sourceData->jenis_pekerja ?? 'N/A';
                    }
                }

                $subtotalFinal = $detail->subtotal_final ?? $detail->subtotal;
                
                // Hitung total per tipe
                if ($detail->tipe === 'material') {
                    $externalTotalMaterial += $subtotalFinal;
                } elseif ($detail->tipe === 'upah') {
                    $externalTotalUpah += $subtotalFinal;
                }

                $enrichedExternalDetails[] = [
                    'tipe' => $detail->tipe,
                    'source_kode' => $sourceData ? ($sourceData->kode ?? 'N/A') : 'N/A',
                    'source_nama' => $sourceNama,
                    'satuan' => $sourceData ? ($sourceData->satuan ?? $detail->satuan) : $detail->satuan,
                    'koefisien' => $detail->koefisien,
                    'harga_satuan' => $detail->harga_satuan,
                    'subtotal' => $detail->subtotal,
                    'diskon_persen' => $detail->diskon_persen ?? 0,
                    'ppn_persen' => $detail->ppn_persen ?? 0,
                    'diskon_nominal' => $detail->diskon_nominal ?? 0,
                    'ppn_nominal' => $detail->ppn_nominal ?? 0,
                    'subtotal_final' => $subtotalFinal,
                ];
            }

            // Update external header dengan calculated total harga
            $externalHeaderArray = (array) $externalHeader;
            $externalHeaderArray['total_harga'] = $externalTotalMaterial + $externalTotalUpah;
            $externalHeader = (object) $externalHeaderArray;

            // Ambil existing details dari lokal jika ada
            $existingDetails = [];
            if ($existingHeader) {
                $localDetails = AhspDetail::where('ahsp_id', $existingHeader->id)->get();
                foreach ($localDetails as $detail) {
                    $sourceData = null;
                    $sourceNama = 'N/A';

                    if ($detail->tipe === 'material') {
                        $sourceData = HsdMaterial::find($detail->referensi_id);
                        if ($sourceData) {
                            $sourceNama = $sourceData->nama ?? 'N/A';
                        }
                    } elseif ($detail->tipe === 'upah') {
                        $sourceData = HsdUpah::find($detail->referensi_id);
                        if ($sourceData) {
                            $sourceNama = $sourceData->jenis_pekerja ?? 'N/A';
                        }
                    }

                    $existingDetails[] = [
                        'tipe' => $detail->tipe,
                        'source_kode' => $sourceData ? ($sourceData->kode ?? 'N/A') : 'N/A',
                        'source_nama' => $sourceNama,
                        'satuan' => $sourceData ? ($sourceData->satuan ?? $detail->satuan) : $detail->satuan,
                        'koefisien' => $detail->koefisien,
                        'harga_satuan' => $detail->harga_satuan,
                        'subtotal' => $detail->subtotal,
                        'diskon_persen' => $detail->diskon_persen ?? 0,
                        'ppn_persen' => $detail->ppn_persen ?? 0,
                        'diskon_nominal' => $detail->diskon_nominal ?? 0,
                        'ppn_nominal' => $detail->ppn_nominal ?? 0,
                        'subtotal_final' => $detail->subtotal_final ?? $detail->subtotal,
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'existing' => [
                    'header' => $existingHeader,
                    'details' => $existingDetails
                ],
                'external' => [
                    'header' => $externalHeader,
                    'details' => $enrichedExternalDetails
                ],
                'hasExisting' => $existingHeader ? true : false
            ]);
        } catch (\Exception $e) {
            Log::error('Get AHSP Details error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Re-sync satu item HSD Material (untuk data yang sudah sama)
     */
    public function resyncHsdMaterial($id)
    {
        try {
            $this->setupExternalConnection();

            $externalData = DB::connection('external')
                ->table('hsd_material')
                ->where('id', $id)
                ->first();

            if (!$externalData) {
                return response()->json([
                    'success' => false,
                    'message' => 'Item tidak ditemukan di database eksternal'
                ], 404);
            }

            DB::beginTransaction();

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

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Berhasil re-sync HSD Material'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Resync HSD Material error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Re-sync satu item HSD Upah (untuk data yang sudah sama)
     */
    public function resyncHsdUpah($id)
    {
        try {
            $this->setupExternalConnection();

            $externalData = DB::connection('external')
                ->table('hsd_upah')
                ->where('id', $id)
                ->first();

            if (!$externalData) {
                return response()->json([
                    'success' => false,
                    'message' => 'Item tidak ditemukan di database eksternal'
                ], 404);
            }

            DB::beginTransaction();

            // Cek apakah kode sudah ada
            $existing = HsdUpah::where('kode', $externalData->kode)->first();

            if ($existing) {
                // Update
                $existing->update([
                    'jenis_pekerja' => $externalData->jenis_pekerja,
                    'satuan' => $externalData->satuan,
                    'harga_satuan' => $externalData->harga_satuan,
                    'keterangan' => $externalData->keterangan,
                ]);
            } else {
                // Insert baru
                HsdUpah::create([
                    'kode' => $externalData->kode,
                    'jenis_pekerja' => $externalData->jenis_pekerja,
                    'satuan' => $externalData->satuan,
                    'harga_satuan' => $externalData->harga_satuan,
                    'keterangan' => $externalData->keterangan,
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Berhasil re-sync HSD Upah'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Resync HSD Upah error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Re-sync satu item AHSP (untuk data yang sudah sama)
     */
    public function resyncAhsp($id)
    {
        try {
            $this->setupExternalConnection();

            $externalHeader = DB::connection('external')
                ->table('ahsp_header')
                ->where('id', $id)
                ->first();

            if (!$externalHeader) {
                return response()->json([
                    'success' => false,
                    'message' => 'AHSP tidak ditemukan di database eksternal'
                ], 404);
            }

            DB::beginTransaction();

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

            // Copy details - dengan remapping referensi_id berdasarkan kode (bukan ID eksternal)
            $externalDetails = DB::connection('external')
                ->table('ahsp_detail')
                ->where('ahsp_id', $id)
                ->get();

            foreach ($externalDetails as $detail) {
                $localReferensiId = $detail->referensi_id; // default: ID eksternal

                // Jika tipe adalah material atau upah, lakukan remapping via kode
                if ($detail->tipe === 'material') {
                    // Ambil kode dari HSD Material eksternal berdasarkan referensi_id
                    $externalSource = DB::connection('external')
                        ->table('hsd_material')
                        ->where('id', $detail->referensi_id)
                        ->first(['kode']);

                    if ($externalSource) {
                        // Cari ID lokal yang punya kode yang sama
                        $localSource = HsdMaterial::where('kode', $externalSource->kode)->first();
                        if ($localSource) {
                            $localReferensiId = $localSource->id;
                        } else {
                            // Jika tidak ditemukan, gunakan referensi_id asli (fallback)
                            \Log::warning("HSD Material kode {$externalSource->kode} tidak ditemukan di lokal saat resync AHSP {$id}");
                        }
                    }
                } elseif ($detail->tipe === 'upah') {
                    // Ambil kode dari HSD Upah eksternal berdasarkan referensi_id
                    $externalSource = DB::connection('external')
                        ->table('hsd_upah')
                        ->where('id', $detail->referensi_id)
                        ->first(['kode']);

                    if ($externalSource) {
                        // Cari ID lokal yang punya kode yang sama
                        $localSource = HsdUpah::where('kode', $externalSource->kode)->first();
                        if ($localSource) {
                            $localReferensiId = $localSource->id;
                        } else {
                            // Jika tidak ditemukan, gunakan referensi_id asli (fallback)
                            \Log::warning("HSD Upah kode {$externalSource->kode} tidak ditemukan di lokal saat resync AHSP {$id}");
                        }
                    }
                }

                $diskonPersen = (float)($detail->diskon_persen ?? 0);
                $ppnPersen = (float)($detail->ppn_persen ?? 0);
                
                // Normalize percentage: if input is 0-1 range (decimal), multiply by 100
                if ($diskonPersen > 0 && $diskonPersen < 1) {
                    $diskonPersen = $diskonPersen * 100;
                }
                if ($ppnPersen > 0 && $ppnPersen < 1) {
                    $ppnPersen = $ppnPersen * 100;
                }
                
                AhspDetail::create([
                    'ahsp_id' => $headerId,
                    'tipe' => $detail->tipe,
                    'referensi_id' => $localReferensiId,  // ← SUDAH DI-REMAP
                    'koefisien' => $detail->koefisien,
                    'harga_satuan' => $detail->harga_satuan,
                    'subtotal' => $detail->subtotal,
                    'diskon_persen' => $diskonPersen,
                    'ppn_persen' => $ppnPersen,
                ]);
                // Model boot() akan auto-calculate diskon_nominal, ppn_nominal, subtotal_final
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Berhasil re-sync AHSP beserta detailnya'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Resync AHSP error: ' . $e->getMessage());

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
                    // For "same" items, return external data (untuk digunakan di resync)
                    // tapi keep local ID juga untuk keperluan lain
                    $sameItem = (array)$extItem;
                    $sameItem['local_id'] = $localItem->id;  // store local ID untuk reference
                    $result['same'][] = (object)$sameItem;
                }
            }
        }

        return $result;
    }
}
