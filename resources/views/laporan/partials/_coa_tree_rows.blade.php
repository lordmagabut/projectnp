@foreach($tree as $node)
<tr>
  <td style="padding-left: {{ $node['parent_id'] ? 20 : 0 }}px;">
    @if(count($node['children']) > 0)
      <a href="#" class="toggle-collapse" data-target="row-{{ $node['id'] }}">
        <span class="arrow">▶</span> {{ $node['no_akun'] }} - {{ $node['nama_akun'] }}
      </a>
    @else
      {{ $node['no_akun'] }} - {{ $node['nama_akun'] }}
    @endif
  </td>
  <td class="text-end">{{ number_format($node['saldo'], 0, ',', '.') }}</td>
</tr>

@if(count($node['children']) > 0)
<tr id="row-{{ $node['id'] }}" class="d-none">
  <td colspan="2" class="p-0">
    <table class="table table-sm mb-0">
      <tbody>
        @include('laporan.partials._coa_tree_rows', ['tree' => $node['children']])
      </tbody>
    </table>
  </td>
</tr>
@endif
@endforeach
