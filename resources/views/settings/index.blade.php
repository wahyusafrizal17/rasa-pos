@extends('layouts.app')
@section('title', 'Settings')
@section('breadcrumb', 'Pengaturan')
@section('content')
    <div class="mb-6">
        <p class="text-sm text-slate-500">Pengaturan global outlet dan loyalitas</p>
    </div>

    <form method="POST" action="{{ route('settings.update') }}" class="card max-w-2xl space-y-4 p-6">
        @csrf
        @method('PUT')
        <div>
            <label class="label">Tax rate (%)</label>
            <input class="input" type="number" step="0.01" name="tax_rate" value="{{ old('tax_rate', $settings['tax_rate'] ?? 11) }}" required>
        </div>
        <div>
            <label class="label">Service charge</label>
            <input class="input" type="number" step="0.01" name="service_charge" value="{{ old('service_charge', $settings['service_charge'] ?? 0) }}">
        </div>
        <div>
            <label class="label">Points earn per amount</label>
            <input class="input" type="number" name="points_earn_per_amount" value="{{ old('points_earn_per_amount', $settings['points_earn_per_amount'] ?? 10000) }}" required>
        </div>
        <div>
            <label class="label">Points redeem value</label>
            <input class="input" type="number" name="points_redeem_value" value="{{ old('points_redeem_value', $settings['points_redeem_value'] ?? 100) }}" required>
        </div>
        <div>
            <label class="label">Company name</label>
            <input class="input" type="text" name="company_name" value="{{ old('company_name', $settings['company_name'] ?? 'Rasa') }}">
        </div>
        <div>
            <label class="label">Receipt footer</label>
            <input class="input" type="text" name="receipt_footer" value="{{ old('receipt_footer', $settings['receipt_footer'] ?? 'Terima kasih') }}">
        </div>
        <button class="btn-primary" type="submit">Simpan</button>
    </form>
@endsection
