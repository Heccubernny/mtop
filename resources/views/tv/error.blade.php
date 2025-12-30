@extends('user.layouts.master')

@push('css')

@endpush

@section('title', 'Cable TV Error')

@section('content')
<div class="container py-5">
    <div class="card shadow-sm border-0">
        <div class="card-body text-center">
            <h3 class="text-danger mb-3">⚠️ Unable to Load {{ strtoupper($service) }} Plans</h3>
            <p>{{ $message ?? 'Something went wrong while trying to fetch TV plans.' }}</p>

            <a href="{{ url()->previous() }}" class="btn btn-outline-primary mt-3">
                ← Go Back
            </a>
        </div>
    </div>
</div>
@endsection
