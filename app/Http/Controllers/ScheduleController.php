<?php

namespace App\Http\Controllers;

use App\Models\Proyek;
use App\Models\RabHeader;
use App\Models\RabSchedule;
use App\Models\RabScheduleDetail;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ScheduleController extends Controller
{
    public function create($proyek_id)
    {
        $proyek = Proyek::findOrFail($proyek_id);

        $subHeaders = RabHeader::where('proyek_id', $proyek->id)
            ->whereRaw("LENGTH(kode) - LENGTH(REPLACE(kode, '.', '')) = 1") // WBS level 2 (contoh: 1.1, 2.1)
            ->orderBy('kode_sort')
            ->get();

        // Hitung minggu dari tanggal mulai proyek
        $tanggalMulai = Carbon::parse($proyek->tanggal_mulai);
        $tanggalSelesai = Carbon::parse($proyek->tanggal_selesai);
        $jumlahMinggu = $tanggalMulai->diffInWeeks($tanggalSelesai) + 1;

        $mingguOptions = [];
        for ($i = 1; $i <= $jumlahMinggu; $i++) {
            $tanggal = $tanggalMulai->copy()->addWeeks($i - 1)->format('d M Y');
            $mingguOptions[$i] = $tanggal;
        }

        $existingSchedules = RabSchedule::where('proyek_id', $proyek->id)->get()->keyBy('rab_header_id');
        return view('proyek.schedule-input', compact('proyek', 'subHeaders', 'mingguOptions', 'existingSchedules'));

    }
    
    public function store(Request $request, Proyek $proyek)
    {
        $request->validate([
            'jadwal' => 'required|array',
            'jadwal.*.minggu_ke' => 'required|integer|min:1',
            'jadwal.*.durasi' => 'required|integer|min:1',
        ]);

        DB::beginTransaction();

        try {
            foreach ($request->jadwal as $rabHeaderId => $data) {
                $rabHeader = RabHeader::findOrFail($rabHeaderId);

                // Simpan atau update rab_schedule
                $schedule = RabSchedule::updateOrCreate(
                    [
                        'proyek_id' => $proyek->id,
                        'rab_header_id' => $rabHeaderId,
                    ],
                    [
                        'minggu_ke' => $data['minggu_ke'],
                        'durasi' => $data['durasi'],
                    ]
                );

                // Hapus detail lama jika ada
                RabScheduleDetail::where('proyek_id', $proyek->id)
                    ->where('rab_header_id', $rabHeaderId)
                    ->delete();

                // Hitung bobot per minggu
                $bobotPerMinggu = $rabHeader->bobot / $data['durasi'];

                // Simpan ulang rab_schedule_detail
                for ($i = 0; $i < $data['durasi']; $i++) {
                    RabScheduleDetail::create([
                        'proyek_id' => $proyek->id,
                        'rab_header_id' => $rabHeaderId,
                        'minggu_ke' => $data['minggu_ke'] + $i,
                        'bobot_mingguan' => $bobotPerMinggu,
                    ]);
                }
            }

            DB::commit();
            return redirect()->route('proyek.show', $proyek->id)->with('success', 'Schedule berhasil disimpan dan diperbarui.');

        } catch (\Throwable $e) {
            DB::rollback();
            return back()->withErrors(['msg' => 'Gagal menyimpan jadwal: ' . $e->getMessage()]);
        }
    }

}
