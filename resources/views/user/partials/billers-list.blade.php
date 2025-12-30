@php
    $billers = collect(json_decode(json_encode($billers['content'] ?? [])));

@endphp
@if ($billers->isEmpty())
    <div class="alert alert-warning">No billers found for the selected options.</div>
@else
    <div class="alert alert-success">Biller fetched successfully</div>
@endif
