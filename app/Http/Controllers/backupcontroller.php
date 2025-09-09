// proyek - show 20072025

public function show($id)
    {
        $proyek = Proyek::with('pemberiKerja')->findOrFail($id);

        // Ambil data RAB
        $details = RabDetail::where('proyek_id', $id)
            ->orderBy('kode_sort')->get();

        $headers = RabHeader::with('rabDetails')
            ->where('proyek_id', $proyek->id)
            ->orderBy('kode_sort')
            ->get();

        // Hitung nilai & bobot parent (sum dari anak-anaknya)
        foreach ($headers as $header) {
            $children = $headers->where('parent_id', $header->id);
            if ($children->count() > 0) {
                $header->nilai = $children->sum('nilai');
                $header->bobot = $children->sum('bobot');
            }
        }

        // Grand Total dari header induk
        $grandTotal = $headers->filter(function ($item) {
            return strpos($item->kode, '.') === false;
        })->sum('nilai');

        // Ambil data untuk Kurva-S
        $detailSchedule = \App\Models\RabScheduleDetail::where('proyek_id', $id)
            ->orderBy('minggu_ke')->get()
            ->groupBy('minggu_ke');

        $minggu = [];
        $akumulasi = [];
        $total = 0;

        foreach ($detailSchedule as $minggu_ke => $items) {
            $minggu[] = 'M- ' . $minggu_ke;
            $total += $items->sum('bobot_mingguan');
            $akumulasi[] = round($total, 2);
        }

        return view('proyek.show', compact(
            'proyek',
            'headers',
            'details',
            'grandTotal',
            'minggu',
            'akumulasi'
        ));
    }
