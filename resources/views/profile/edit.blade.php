@extends('layouts.main')

@section('title', 'Profil Saya')
@section('subtitle', 'Atur informasi akun dan pengaturan keamanan')

@section('content')
<div class="row g-4">
    <div class="col-12 col-xl-6">
        <div class="rp-card">
            <div class="rp-card-header">
                <div>
                    <div class="rp-card-title">Informasi Profil</div>
                    <div class="rp-card-sub" style="margin-top:2px;">Perbarui informasi profil akun dan alamat email Anda.</div>
                </div>
            </div>
            <div class="rp-card-body">
                @include('profile.partials.update-profile-information-form')
            </div>
        </div>
    </div>
    
    <div class="col-12 col-xl-6">
        <div class="rp-card">
            <div class="rp-card-header">
                <div>
                    <div class="rp-card-title">Ubah Password</div>
                    <div class="rp-card-sub" style="margin-top:2px;">Pastikan akun Anda menggunakan password panjang dan acak agar tetap aman.</div>
                </div>
            </div>
            <div class="rp-card-body">
                @include('profile.partials.update-password-form')
            </div>
        </div>
        
        <div class="rp-card mt-4" style="border-color:#fecaca;">
            <div class="rp-card-header" style="background:#fff1f2;border-bottom-color:#fecaca;">
                <div>
                    <div class="rp-card-title" style="color:#b91c1c;">Hapus Akun</div>
                    <div class="rp-card-sub" style="color:#ef4444;margin-top:2px;">Hapus akun secara permanen beserta semua datanya.</div>
                </div>
            </div>
            <div class="rp-card-body">
                @include('profile.partials.delete-user-form')
            </div>
        </div>
    </div>
</div>
@endsection
