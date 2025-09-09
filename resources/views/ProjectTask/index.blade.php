<table>
    <thead>
        <tr>
            <th>Proyek</th>
            <th>Kode</th>
            <th>Deskripsi</th>
            <th>Bobot</th>
            <th>Durasi</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($tasks as $task)
        <tr>
            <td>{{ $task->proyek->nama_proyek }}</td>
            <td>{{ $task->kode }}</td>
            <td>{{ $task->deskripsi }}</td>
            <td>{{ $task->bobot }}%</td>
            <td>{{ $task->durasi }} minggu</td>
        </tr>
        @endforeach
    </tbody>
</table>
