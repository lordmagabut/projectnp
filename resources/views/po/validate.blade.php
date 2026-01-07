@extends('layout.master')

@section('content')
<nav class="page-breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('po.index') }}">Purchase Order</a></li>
        <li class="breadcrumb-item active" aria-current="page">Validate PO (Disabled)</li>
    </ol>
</nav>

<div class="alert alert-warning">
    QR/validation feature has been disabled for security reasons.
    Please verify documents through internal workflows.
</div>
@endsection
@endsection
