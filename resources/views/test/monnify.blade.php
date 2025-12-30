@extends('user.layouts.user_auth')


@section('content')
<div class="container mt-5">
    <h2 class="mb-4 text-center">Monnify Identity Verification Test</h2>

    <form method="POST" action="{{ route('monnify.verify') }}">
        @csrf

        <div class="mb-3">
            <label for="type" class="form-label">Select Type</label>
            <select name="type" id="type" class="form-control" required>
                <option value="">-- Select --</option>
                <option value="bvn" {{ old('type') == 'bvn' ? 'selected' : '' }}>BVN</option>
                <option value="nin" {{ old('type') == 'nin' ? 'selected' : '' }}>NIN</option>
            </select>
        </div>

        <div id="bvnFields" class="type-fields" style="display: none;">
            <div class="mb-3">
                <label class="form-label">BVN</label>
                <input type="text" name="bvn" class="form-control" placeholder="Enter BVN (11 digits)">
            </div>
            <div class="mb-3">
                <label class="form-label">Full Name</label>
                <input type="text" name="name" class="form-control" placeholder="Enter Full Name">
            </div>
            <div class="mb-3">
                <label class="form-label">Date of Birth</label>
                <input type="date" name="dateOfBirth" class="form-control">
            </div>
            <div class="mb-3">
                <label class="form-label">Mobile No</label>
                <input type="text" name="mobileNo" class="form-control" placeholder="e.g. 08012345678">
            </div>
        </div>

        <div id="ninFields" class="type-fields" style="display: none;">
            <div class="mb-3">
                <label class="form-label">NIN</label>
                <input type="text" name="nin" class="form-control" placeholder="Enter NIN">
            </div>
        </div>

        <button type="submit" class="btn btn-primary w-100">Verify Identity</button>
    </form>

    @if(session('response'))
        <div class="mt-4 alert alert-{{ session('response.success') ? 'success' : 'danger' }}">
            <strong>{{ session('response.success') ? '✅ Success' : '❌ Failed' }}</strong><br>
            <pre class="mt-2">{{ json_encode(session('response'), JSON_PRETTY_PRINT) }}</pre>
        </div>
    @endif
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const typeSelect = document.getElementById('type');
        const bvnFields = document.getElementById('bvnFields');
        const ninFields = document.getElementById('ninFields');

        function toggleFields() {
            if (typeSelect.value === 'bvn') {
                bvnFields.style.display = 'block';
                ninFields.style.display = 'none';
            } else if (typeSelect.value === 'nin') {
                bvnFields.style.display = 'none';
                ninFields.style.display = 'block';
            } else {
                bvnFields.style.display = 'none';
                ninFields.style.display = 'none';
            }
        }

        typeSelect.addEventListener('change', toggleFields);
        toggleFields();
    });
</script>
@endsection
